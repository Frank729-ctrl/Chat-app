<?php
require_once "../../config/database.php";
require_once "../../includes/session.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$data     = json_decode(file_get_contents('php://input'), true);
$action   = $data['action'] ?? '';

if ($action === 'update_name') {
    $name = trim($data['full_name'] ?? '');
    if (strlen($name) < 2) {
        http_response_code(400);
        echo json_encode(['error' => 'Name must be at least 2 characters']);
        exit;
    }
    $pdo->prepare("UPDATE users SET full_name = ? WHERE id = ?")
        ->execute([$name, $_SESSION['user_id']]);
    echo json_encode(['success' => true, 'full_name' => $name]);

} elseif ($action === 'change_password') {
    $current = $data['current_password'] ?? '';
    $new     = $data['new_password'] ?? '';
    $confirm = $data['confirm_password'] ?? '';

    if (strlen($new) < 6) {
        http_response_code(400);
        echo json_encode(['error' => 'New password must be at least 6 characters']);
        exit;
    }
    if ($new !== $confirm) {
        http_response_code(400);
        echo json_encode(['error' => 'Passwords do not match']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!password_verify($current, $user['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Current password is incorrect']);
        exit;
    }

    $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")
        ->execute([password_hash($new, PASSWORD_BCRYPT), $_SESSION['user_id']]);

    echo json_encode(['success' => true]);

} else {
    http_response_code(400);
    echo json_encode(['error' => 'Unknown action']);
}