<?php
require_once "../../config/database.php";
require_once "../../includes/session.php";

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$query = trim($_GET['q'] ?? '');

if (strlen($query) < 2) {
    echo json_encode(["users" => []]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, full_name, email, profile_image, is_online
    FROM users
    WHERE id != ? AND (full_name LIKE ? OR email LIKE ?)
    LIMIT 10
");
$stmt->execute([$_SESSION['user_id'], "%$query%", "%$query%"]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode(["users" => $users]);
