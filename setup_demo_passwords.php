#!/usr/bin/env php
<?php
/**
 * Run ONCE after importing database.sql:
 *   php setup_demo_passwords.php
 * Delete this file before going live.
 */

require_once __DIR__ . '/config/database.php';

echo "\n========================================\n";
echo "  ChatConnect Setup\n";
echo "========================================\n\n";

$demoPassword = 'password123';
$hash         = password_hash($demoPassword, PASSWORD_BCRYPT);

$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email IN (
    'demo@chatconnect.app',
    'jane@chatconnect.app',
    'bob@chatconnect.app'
)");
$stmt->execute([$hash]);

echo "✓ Passwords set for " . $stmt->rowCount() . " demo user(s)\n";
echo "  Login: demo@chatconnect.app / password123\n";
echo "  Login: jane@chatconnect.app / password123\n\n";

echo "✓ Hash check: " . (password_verify($demoPassword, $hash) ? "PASSED ✅" : "FAILED ❌") . "\n\n";

echo "Create your own account? (y/n): ";
$answer = trim(fgets(STDIN));

if (strtolower($answer) === 'y') {
    echo "Full name: ";    $name  = trim(fgets(STDIN));
    echo "Email: ";        $email = trim(fgets(STDIN));
    echo "Password: ";
    if (PHP_OS_FAMILY !== 'Windows') { system('stty -echo'); }
    $password = trim(fgets(STDIN));
    if (PHP_OS_FAMILY !== 'Windows') { system('stty echo'); echo "\n"; }

    try {
        $pdo->prepare("INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)")
            ->execute([$name, $email, password_hash($password, PASSWORD_BCRYPT)]);
        echo "✓ Account created: {$name} ({$email})\n";
    } catch (\PDOException $e) {
        echo "✗ " . (str_contains($e->getMessage(), 'Duplicate') ? "Email already exists." : $e->getMessage()) . "\n";
    }
}

echo "\n✅ Done! You can now log in.\n";
echo "   Delete this file before going live.\n\n";