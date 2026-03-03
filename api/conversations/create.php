<?php
require_once "../../config/database.php";
require_once "../../includes/session.php";

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$data       = json_decode(file_get_contents("php://input"), true);
$targetUser = (int)($data['user_id'] ?? 0);

if (!$targetUser || $targetUser === (int)$_SESSION['user_id']) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid user"]);
    exit;
}

// Check if a direct (non-group) conversation already exists between these two users
$stmt = $pdo->prepare("
    SELECT c.id
    FROM conversations c
    JOIN participants p1 ON p1.conversation_id = c.id AND p1.user_id = :me
    JOIN participants p2 ON p2.conversation_id = c.id AND p2.user_id = :them
    WHERE c.is_group = 0
    LIMIT 1
");
$stmt->execute(['me' => $_SESSION['user_id'], 'them' => $targetUser]);
$existing = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existing) {
    echo json_encode(["success" => true, "conversation_id" => $existing['id'], "new" => false]);
    exit;
}

// Create new conversation
$pdo->beginTransaction();
$pdo->prepare("INSERT INTO conversations (is_group, created_by) VALUES (0, ?)")
    ->execute([$_SESSION['user_id']]);
$convId = $pdo->lastInsertId();

$pdo->prepare("INSERT INTO participants (conversation_id, user_id) VALUES (?, ?), (?, ?)")
    ->execute([$convId, $_SESSION['user_id'], $convId, $targetUser]);

$pdo->commit();

header('Content-Type: application/json');
echo json_encode(["success" => true, "conversation_id" => $convId, "new" => true]);
