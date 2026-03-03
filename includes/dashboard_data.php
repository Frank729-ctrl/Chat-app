<?php
require_once "../config/database.php";
require_once "../includes/session.php";

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /public/index.html");
    exit;
}

// Fetch current user
$stmt = $pdo->prepare("SELECT id, full_name, email, created_at, profile_image FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: /public/index.html");
    exit;
}

// Mark user as online
$pdo->prepare("UPDATE users SET is_online = 1, last_seen = NOW() WHERE id = ?")->execute([$_SESSION['user_id']]);

$currentUserId = $_SESSION['user_id'];

// Fetch recent conversations with last message, partner info, and unread count
$stmt = $pdo->prepare("
    SELECT
        c.id AS conversation_id,
        c.is_group,
        c.name,
        u_other.full_name   AS other_user_name,
        u_other.profile_image AS other_user_image,
        u_other.is_online   AS other_user_online,
        m.content           AS last_message,
        m.created_at        AS last_message_time,
        (
            SELECT COUNT(*) FROM messages mu
            WHERE mu.conversation_id = c.id
              AND mu.sender_id != :uid1
              AND mu.is_read = 0
        ) AS unread_count
    FROM conversations c

    -- The other participant
    INNER JOIN participants p_other
        ON p_other.conversation_id = c.id
        AND p_other.user_id != :uid2
    LEFT JOIN users u_other ON u_other.id = p_other.user_id

    -- Latest message
    LEFT JOIN (
        SELECT conversation_id, content, created_at,
               ROW_NUMBER() OVER (PARTITION BY conversation_id ORDER BY created_at DESC) AS rn
        FROM messages
    ) m ON m.conversation_id = c.id AND m.rn = 1

    -- Only conversations current user is in
    WHERE EXISTS (
        SELECT 1 FROM participants p_self
        WHERE p_self.conversation_id = c.id AND p_self.user_id = :uid3
    )

    ORDER BY COALESCE(m.created_at, c.created_at) DESC
    LIMIT 30
");

$stmt->execute(['uid1' => $currentUserId, 'uid2' => $currentUserId, 'uid3' => $currentUserId]);
$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
