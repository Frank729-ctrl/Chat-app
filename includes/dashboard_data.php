<?php
require_once "../config/database.php"; // Your PDO connection

session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /public/index.html");
    exit;
}

// Fetch current user info
$stmt = $pdo->prepare("
    SELECT id, full_name, email, created_at, profile_image 
    FROM users 
    WHERE id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: /public/index.html");
    exit;
}

// ────────────────────────────────────────────────
// Fetch recent conversations with last message & other participant info
// ────────────────────────────────────────────────

$currentUserId = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT 
        c.id AS conversation_id,
        c.is_group,
        COALESCE(c.name, CONCAT('Chat with ', u_other.full_name)) AS name,
        u_other.full_name AS other_user_name,
        u_other.profile_image AS other_user_image,
        m.content AS last_message,
        m.created_at AS last_message_time
    FROM conversations c
    
    -- Join participants to find the OTHER user (not current user)
    INNER JOIN participants p_other 
        ON p_other.conversation_id = c.id 
        AND p_other.user_id != :current_user_id
    
    LEFT JOIN users u_other 
        ON u_other.id = p_other.user_id
    
    -- Get the most recent message per conversation
    LEFT JOIN (
        SELECT conversation_id, content, created_at,
               ROW_NUMBER() OVER (PARTITION BY conversation_id ORDER BY created_at DESC) AS rn
        FROM messages
    ) m ON m.conversation_id = c.id AND m.rn = 1
    
    -- Only conversations where current user is a participant
    WHERE EXISTS (
        SELECT 1 
        FROM participants p_self 
        WHERE p_self.conversation_id = c.id 
          AND p_self.user_id = :current_user_id
    )
    
    ORDER BY COALESCE(m.created_at, c.created_at) DESC
    LIMIT 30
");

$stmt->execute(['current_user_id' => $currentUserId]);

$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];

// If no conversations exist yet, you can optionally add fallback dummy ones (for onboarding/testing)
// But ideally remove this once real data flows
if (empty($conversations)) {
    $conversations = [
        // Optional: keep 1–2 dummies during early development
        [
            'name'              => 'Welcome to ChatConnect',
            'other_user_name'   => 'Team',
            'other_user_image'  => null,
            'last_message'      => 'Start your first conversation!',
            'last_message_time' => date('Y-m-d H:i:s'),
            'is_group'          => true,
        ]
    ];
}