<?php
// Simulate approve request
$_SESSION['admin_id'] = 1;
$_SESSION['admin_full_name'] = 'Admin';
require 'c:/Pridens_trading_co/admin_API/db.php';

$input = [
    'account_id' => 'PR-0000001', // Change if needed
    'subscription_status' => 'Active'
];

// Check if subscriber exists
$stmt = $pdo->prepare("SELECT account_id FROM subscribers LIMIT 1");
$stmt->execute();
$sub = $stmt->fetch();
if ($sub) {
    $input['account_id'] = $sub['account_id'];
    echo "Testing with account: " . $sub['account_id'] . "\n";
} else {
    echo "No subscribers found.\n";
    exit;
}

ob_start();
include 'c:/Pridens_trading_co/admin_API/update_subscriber_status.php';
$output = ob_get_clean();

echo "Result: " . $output . "\n";
