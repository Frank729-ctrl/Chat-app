<?php
require_once "../../config/database.php";
require_once "../../includes/session.php";

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$data           = json_decode(file_get_contents("php://input"), true);
$conversationId = (int)($data['conversation_id'] ?? 0);
$content        = trim($data['content'] ?? '');

if (!$conversationId || $content === '') {
    http_response_code(400);
    echo json_encode(["error" => "Missing fields"]);
    exit;
}

// Check user is a participant
$check = $pdo->prepare("SELECT 1 FROM participants WHERE conversation_id = ? AND user_id = ?");
$check->execute([$conversationId, $_SESSION['user_id']]);
if (!$check->fetch()) {
    http_response_code(403);
    echo json_encode(["error" => "Forbidden"]);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO messages (conversation_id, sender_id, content) VALUES (?, ?, ?)");
$stmt->execute([$conversationId, $_SESSION['user_id'], $content]);
$messageId = $pdo->lastInsertId();

// Update online status
$pdo->prepare("UPDATE users SET is_online = 1, last_seen = NOW() WHERE id = ?")
    ->execute([$_SESSION['user_id']]);

header('Content-Type: application/json');
echo json_encode([
    "success"    => true,
    "message_id" => $messageId,
    "created_at" => date('Y-m-d H:i:s')
]);
