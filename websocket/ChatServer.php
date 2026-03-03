<?php
/**
 * ChatConnect – WebSocket Chat Server
 * ====================================
 * Handles real-time messaging, online presence, and typing indicators
 * using Ratchet (cboden/ratchet).
 *
 * Runs as: php websocket/ChatServer.php
 */

namespace ChatConnect;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use PDO;

class ChatServer implements MessageComponentInterface
{
    /** @var array<int, ConnectionInterface>  userId => connection */
    protected array $userConnections = [];

    /** @var array<string, int>  resourceId => userId  */
    protected array $resourceToUser = [];

    protected PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        echo "[ChatConnect WS] Server started\n";
    }

    // ─────────────────────────────────────────────────────────
    // Connection lifecycle
    // ─────────────────────────────────────────────────────────

    public function onOpen(ConnectionInterface $conn): void
    {
        echo "[WS] New connection #{$conn->resourceId}\n";
        // Auth happens on first "auth" message — don't mark online yet
    }

    public function onClose(ConnectionInterface $conn): void
    {
        $userId = $this->resourceToUser[$conn->resourceId] ?? null;

        if ($userId) {
            unset($this->userConnections[$userId]);
            unset($this->resourceToUser[$conn->resourceId]);
            $this->markOffline($userId);
            $this->broadcastPresence($userId, false);
            echo "[WS] User #{$userId} disconnected\n";
        }

        echo "[WS] Connection #{$conn->resourceId} closed\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e): void
    {
        echo "[WS] Error: {$e->getMessage()}\n";
        $conn->close();
    }

    // ─────────────────────────────────────────────────────────
    // Message routing
    // ─────────────────────────────────────────────────────────

    public function onMessage(ConnectionInterface $from, $rawMsg): void
    {
        $payload = json_decode($rawMsg, true);
        if (!$payload || !isset($payload['type'])) return;

        switch ($payload['type']) {
            case 'auth':       $this->handleAuth($from, $payload);    break;
            case 'message':    $this->handleMessage($from, $payload); break;
            case 'typing':     $this->handleTyping($from, $payload);  break;
            case 'read':       $this->handleRead($from, $payload);    break;
            case 'ping':       $from->send(json_encode(['type' => 'pong'])); break;
        }
    }

    // ─────────────────────────────────────────────────────────
    // Auth: client sends { type:"auth", token: SESSION_USER_ID }
    // In production replace with a short-lived JWT or signed token.
    // ─────────────────────────────────────────────────────────

    private function handleAuth(ConnectionInterface $conn, array $payload): void
    {
        $userId = (int)($payload['user_id'] ?? 0);
        if (!$userId) { $conn->close(); return; }

        // Verify user exists
        $stmt = $this->pdo->prepare("SELECT id, full_name FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) { $conn->send(json_encode(['type' => 'error', 'msg' => 'Invalid user'])); $conn->close(); return; }

        // Register connection
        $this->userConnections[$userId]              = $conn;
        $this->resourceToUser[$conn->resourceId]     = $userId;

        $this->markOnline($userId);
        $this->broadcastPresence($userId, true);

        $conn->send(json_encode(['type' => 'auth_ok', 'user_id' => $userId]));
        echo "[WS] User #{$userId} ({$user['full_name']}) authenticated\n";
    }

    // ─────────────────────────────────────────────────────────
    // Send message: { type:"message", conversation_id, content }
    // ─────────────────────────────────────────────────────────

    private function handleMessage(ConnectionInterface $from, array $payload): void
    {
        $senderId       = $this->resourceToUser[$from->resourceId] ?? null;
        $conversationId = (int)($payload['conversation_id'] ?? 0);
        $content        = trim($payload['content'] ?? '');

        if (!$senderId || !$conversationId || $content === '') return;

        // Verify sender is a participant
        $check = $this->pdo->prepare("SELECT 1 FROM participants WHERE conversation_id = ? AND user_id = ?");
        $check->execute([$conversationId, $senderId]);
        if (!$check->fetch()) return;

        // Persist to DB
        $insert = $this->pdo->prepare("INSERT INTO messages (conversation_id, sender_id, content) VALUES (?, ?, ?)");
        $insert->execute([$conversationId, $senderId, $content]);
        $messageId = $this->pdo->lastInsertId();
        $createdAt = date('Y-m-d H:i:s');

        // Fetch sender info for the broadcast
        $senderStmt = $this->pdo->prepare("SELECT full_name, profile_image FROM users WHERE id = ?");
        $senderStmt->execute([$senderId]);
        $sender = $senderStmt->fetch(PDO::FETCH_ASSOC);

        $outbound = json_encode([
            'type'            => 'message',
            'id'              => $messageId,
            'conversation_id' => $conversationId,
            'sender_id'       => $senderId,
            'sender_name'     => $sender['full_name'],
            'sender_image'    => $sender['profile_image'],
            'content'         => $content,
            'created_at'      => $createdAt,
            'is_mine'         => false,   // overridden client-side per recipient
        ]);

        // Deliver to all online participants in the conversation
        $participants = $this->getParticipants($conversationId);
        foreach ($participants as $participantId) {
            if (isset($this->userConnections[$participantId])) {
                $msg = json_decode($outbound, true);
                $msg['is_mine'] = ($participantId === $senderId);
                $this->userConnections[$participantId]->send(json_encode($msg));
            }
        }

        // Update last_seen for sender
        $this->pdo->prepare("UPDATE users SET last_seen = NOW() WHERE id = ?")->execute([$senderId]);

        echo "[WS] Message #{$messageId} in conv #{$conversationId} from user #{$senderId}\n";
    }

    // ─────────────────────────────────────────────────────────
    // Typing indicator: { type:"typing", conversation_id, is_typing }
    // ─────────────────────────────────────────────────────────

    private function handleTyping(ConnectionInterface $from, array $payload): void
    {
        $senderId       = $this->resourceToUser[$from->resourceId] ?? null;
        $conversationId = (int)($payload['conversation_id'] ?? 0);
        if (!$senderId || !$conversationId) return;

        $participants = $this->getParticipants($conversationId);
        $event = json_encode([
            'type'            => 'typing',
            'conversation_id' => $conversationId,
            'user_id'         => $senderId,
            'is_typing'       => (bool)($payload['is_typing'] ?? false),
        ]);

        foreach ($participants as $participantId) {
            if ($participantId !== $senderId && isset($this->userConnections[$participantId])) {
                $this->userConnections[$participantId]->send($event);
            }
        }
    }

    // ─────────────────────────────────────────────────────────
    // Read receipt: { type:"read", conversation_id }
    // ─────────────────────────────────────────────────────────

    private function handleRead(ConnectionInterface $from, array $payload): void
    {
        $readerId       = $this->resourceToUser[$from->resourceId] ?? null;
        $conversationId = (int)($payload['conversation_id'] ?? 0);
        if (!$readerId || !$conversationId) return;

        $this->pdo->prepare("
            UPDATE messages SET is_read = 1
            WHERE conversation_id = ? AND sender_id != ? AND is_read = 0
        ")->execute([$conversationId, $readerId]);

        // Notify the other participant(s) that messages were read
        $participants = $this->getParticipants($conversationId);
        $event = json_encode(['type' => 'read', 'conversation_id' => $conversationId, 'reader_id' => $readerId]);
        foreach ($participants as $participantId) {
            if ($participantId !== $readerId && isset($this->userConnections[$participantId])) {
                $this->userConnections[$participantId]->send($event);
            }
        }
    }

    // ─────────────────────────────────────────────────────────
    // Presence broadcast
    // ─────────────────────────────────────────────────────────

    private function broadcastPresence(int $userId, bool $isOnline): void
    {
        $event = json_encode(['type' => 'presence', 'user_id' => $userId, 'is_online' => $isOnline]);

        // Broadcast to all connected users who share a conversation with this user
        $convIds = $this->getUserConversationIds($userId);
        $notified = [];

        foreach ($convIds as $convId) {
            foreach ($this->getParticipants($convId) as $participantId) {
                if ($participantId !== $userId && !isset($notified[$participantId]) && isset($this->userConnections[$participantId])) {
                    $this->userConnections[$participantId]->send($event);
                    $notified[$participantId] = true;
                }
            }
        }
    }

    // ─────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────

    private function getParticipants(int $conversationId): array
    {
        $stmt = $this->pdo->prepare("SELECT user_id FROM participants WHERE conversation_id = ?");
        $stmt->execute([$conversationId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function getUserConversationIds(int $userId): array
    {
        $stmt = $this->pdo->prepare("SELECT conversation_id FROM participants WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function markOnline(int $userId): void
    {
        $this->pdo->prepare("UPDATE users SET is_online = 1, last_seen = NOW() WHERE id = ?")->execute([$userId]);
    }

    private function markOffline(int $userId): void
    {
        $this->pdo->prepare("UPDATE users SET is_online = 0, last_seen = NOW() WHERE id = ?")->execute([$userId]);
    }
}
