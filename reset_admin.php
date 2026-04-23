<?php
require_once 'admin_API/db.php';
$user = 'admin';
$pass = 'password123';
$hash = password_hash($pass, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("UPDATE admins SET password_hash = ? WHERE username = ?");
    $stmt->execute([$hash, $user]);
    
    // Also check if admin exists, if not insert
    $check = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
    $check->execute([$user]);
    if (!$check->fetch()) {
        $ins = $pdo->prepare("INSERT INTO admins (username, password_hash) VALUES (?, ?)");
        $ins->execute([$user, $hash]);
        echo "Admin 'admin' created with password 'password123'." . PHP_EOL;
    } else {
        echo "Admin 'admin' password reset to 'password123'." . PHP_EOL;
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
?>
