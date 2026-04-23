<?php
// admin_API/check_merchant_email.php
require_once 'db.php';
header('Content-Type: application/json');
$email = trim($_GET['email'] ?? '');
if (empty($email)) { echo json_encode(['available'=>false]); exit; }
$stmt = $pdo->prepare("SELECT COUNT(*) FROM merchant_users WHERE email = ?");
$stmt->execute([$email]);
echo json_encode(['available' => ($stmt->fetchColumn() == 0)]);
?>
