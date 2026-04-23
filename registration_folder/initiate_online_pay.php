<?php
// initiate_online_pay.php
session_start();
require_once 'paymongo_api.php';

// Generate a Temporary Transaction ID for tracking
$tempTrxId = 'OL-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

// Store in Session
$_SESSION['temp_trx_id'] = $tempTrxId;
$_SESSION['app_type'] = 'new_agent'; // Default or from POST

// Amount
$amount = 1500.00; // Agent Fee
$description = "Online Registration Fee - $tempTrxId";

// Create Checkout
$checkout = createPayMongoCheckout($amount, $description, $tempTrxId);

if ($checkout && isset($checkout['data']['attributes']['checkout_url'])) {
    header("Location: " . $checkout['data']['attributes']['checkout_url']);
    exit();
} else {
    die("Error creating payment session. Please try again.");
}
?>
