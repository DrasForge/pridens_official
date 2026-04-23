<?php
require_once 'db.php';
$mId = 1;
$res = [];
$res['products'] = $pdo->query("SELECT COUNT(*) FROM merchant_products WHERE merchant_id = $mId")->fetchColumn();
$res['foods'] = $pdo->query("SELECT COUNT(*) FROM merchant_foods WHERE merchant_id = $mId")->fetchColumn();
$res['services'] = $pdo->query("SELECT COUNT(*) FROM merchant_services WHERE merchant_id = $mId")->fetchColumn();
$res['spots'] = $pdo->query("SELECT COUNT(*) FROM merchant_spots WHERE merchant_id = $mId")->fetchColumn();

$res['active_products'] = $pdo->query("SELECT COUNT(*) FROM merchant_products WHERE merchant_id = $mId AND status = 'active'")->fetchColumn();
$res['active_foods'] = $pdo->query("SELECT COUNT(*) FROM merchant_foods WHERE merchant_id = $mId AND status = 'active'")->fetchColumn();
$res['active_services'] = $pdo->query("SELECT COUNT(*) FROM merchant_services WHERE merchant_id = $mId AND status = 'active'")->fetchColumn();

print_r($res);
?>
