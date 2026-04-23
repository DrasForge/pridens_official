<?php
require_once 'admin_API/db.php';
$stmt = $pdo->query("SELECT id, username, password_hash FROM admins");
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($admins);
?>
