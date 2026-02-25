<?php
require "../../config/database.php";
require "../../includes/session.php";

$data = json_decode(file_get_contents("php://input"), true);

$name     = trim($data['name'] ?? '');
$email    = trim($data['email'] ?? '');
$password = $data['password'] ?? '';
$confirm  = $data['confirm'] ?? '';

if (!$name || !$email || !$password || $password !== $confirm) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid input"]);
    exit;
}

$hashed = password_hash($password, PASSWORD_BCRYPT);

$stmt = $pdo->prepare("INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)");
$stmt->execute([$name, $email, $hashed]);

echo json_encode(["success" => true]);