<?php
require_once 'admin_API/db.php';
$pw = password_hash('admin123', PASSWORD_BCRYPT);
$stmt = $pdo->prepare("UPDATE admins SET password_hash = ? WHERE username = 'admin'");
$stmt->execute([$pw]);
echo "Password updated to admin123";
?>
