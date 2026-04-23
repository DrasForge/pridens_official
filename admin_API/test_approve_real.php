<?php
// admin_API/test_approve_real.php
require 'c:/Pridens_trading_co/admin_API/db.php';
session_start();
$_SESSION['admin_id'] = 1;

// Get a pending subscriber
$stmt = $pdo->query("SELECT account_id FROM subscribers WHERE subscription_status = 'Pending' LIMIT 1");
$sub = $stmt->fetch();

if (!$sub) {
    echo "No pending subscribers found to test with.\n";
    exit;
}

$accountId = $sub['account_id'];
echo "Testing approval for: $accountId\n";

$url = 'http://localhost:8000/admin_API/update_subscriber_status.php';
$data = ['account_id' => $accountId, 'subscription_status' => 'Active'];

$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\nCookie: " . session_name() . '=' . session_id() . "\r\n",
        'method'  => 'POST',
        'content' => json_encode($data),
    ],
];

$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);

echo "Response: $result\n";
