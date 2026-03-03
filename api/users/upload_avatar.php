<?php
/**
 * Profile Picture Upload
 * POST /api/users/upload_avatar.php
 * Accepts: multipart/form-data  with field "avatar"
 */
require_once "../../config/database.php";
require_once "../../includes/session.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (empty($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded or upload error']);
    exit;
}

$file     = $_FILES['avatar'];
$maxSize  = 5 * 1024 * 1024; // 5 MB
$allowed  = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

// Validate size
if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['error' => 'File too large (max 5 MB)']);
    exit;
}

// Validate MIME type (use finfo for security, not just $_FILES['type'])
$finfo    = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($file['tmp_name']);

if (!in_array($mimeType, $allowed)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid file type. Allowed: JPEG, PNG, GIF, WebP']);
    exit;
}

// Build destination path
$uploadDir = __DIR__ . '/../../storage/avatars/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

$ext      = match($mimeType) {
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
    default      => 'jpg',
};

$filename = 'avatar_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
$destPath = $uploadDir . $filename;
$publicUrl = '/storage/avatars/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save file']);
    exit;
}

// Delete old avatar file (optional cleanup)
$oldStmt = $pdo->prepare("SELECT profile_image FROM users WHERE id = ?");
$oldStmt->execute([$_SESSION['user_id']]);
$old = $oldStmt->fetchColumn();
if ($old && strpos($old, '/storage/avatars/') !== false) {
    $oldPath = __DIR__ . '/../../' . ltrim($old, '/');
    if (file_exists($oldPath)) @unlink($oldPath);
}

// Persist to DB
$pdo->prepare("UPDATE users SET profile_image = ? WHERE id = ?")
    ->execute([$publicUrl, $_SESSION['user_id']]);

echo json_encode(['success' => true, 'url' => $publicUrl]);
