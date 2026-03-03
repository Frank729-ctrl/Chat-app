<?php
require_once "../../config/database.php";
require_once "../../includes/session.php";

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$conversationId = (int)($_GET['conversation_id'] ?? 0);
$since          = $_GET['since'] ?? null; // ISO timestamp for polling

if (!$conversationId) {
    http_response_code(400);
    echo json_encode(["error" => "Missing conversation_id"]);
    exit;
}

// Ensure the current user is a participant
$check = $pdo->prepare("SELECT 1 FROM participants WHERE conversation_id = ? AND user_id = ?");
$check->execute([$conversationId, $_SESSION['user_id']]);
if (!$check->fetch()) {
    http_response_code(403);
    echo json_encode(["error" => "Forbidden"]);
    exit;
}

// Fetch messages (optionally only new ones since a timestamp)
$sql = "
    SELECT m.id, m.content, m.created_at, m.is_read,
           m.sender_id,
           u.full_name AS sender_name,
           u.profile_image AS sender_image,
           (m.sender_id = :me) AS is_mine
    FROM messages m
    JOIN users u ON u.id = m.sender_id
    WHERE m.conversation_id = :cid
";

$params = ['cid' => $conversationId, 'me' => $_SESSION['user_id']];

if ($since) {
    $sql .= " AND m.created_at > :since";
    $params['since'] = $since;
}

$sql .= " ORDER BY m.created_at ASC LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mark messages as read
$pdo->prepare("
    UPDATE messages SET is_read = 1
    WHERE conversation_id = ? AND sender_id != ? AND is_read = 0
")->execute([$conversationId, $_SESSION['user_id']]);

// Return conversation info too (partner name, online status)
$infoStmt = $pdo->prepare("
    SELECT u.full_name, u.profile_image, u.is_online, u.last_seen
    FROM participants p
    JOIN users u ON u.id = p.user_id
    WHERE p.conversation_id = ? AND p.user_id != ?
    LIMIT 1
");
$infoStmt->execute([$conversationId, $_SESSION['user_id']]);
$partner = $infoStmt->fetch(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode([
    "messages" => $messages,
    "partner"  => $partner,
    "server_time" => date('Y-m-d H:i:s')
]);
