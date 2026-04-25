<?php
// Mocking session for CLI
$_SESSION['admin_id'] = 1;
$_GET['merchant_id'] = 1;

require_once __DIR__ . '/../get_merchant_points_monitoring.php';
