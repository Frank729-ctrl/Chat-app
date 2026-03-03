<?php
require_once "../../config/database.php";
require_once "../../includes/session.php";

// Mark user offline
if (isset($_SESSION['user_id'])) {
    $pdo->prepare("UPDATE users SET is_online = 0, last_seen = NOW() WHERE id = ?")
        ->execute([$_SESSION['user_id']]);
}

session_unset();
session_destroy();

header("Location: /public/index.html");
exit;
