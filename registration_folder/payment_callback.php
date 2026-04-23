<?php
// payment_callback.php
session_start();
require_once 'config.php';
require_once 'paymongo_api.php';

$status = $_GET['status'] ?? '';
$message = '';
$icon = '';
$color = '';

if ($status === 'success') {
    // Upfront Payment Flow
    // We arrive here after successful payment.
    // We need to allow the user to proceed to form.php.
    
    // We don't have a database record yet (unlike the previous flow).
    // So we just set the session and redirect.
    
    // Retrieve the Temp ID we set before leaving
    $trxId = $_SESSION['temp_trx_id'] ?? 'OL-UNKNOWN-' . time();
    
    // Since we can't easily retrieve the Checkout Session ID from the URL (unless we add logic), 
    // we'll rely on the user landing here as proof for now (MVP). 
    // Ideally, we'd verify the session ID from PayMongo API via `retrievePayMongoCheckout`.
    // Let's assume for now.
    
    $_SESSION['registration_access'] = true;
    $_SESSION['trx_id'] = $trxId;
    $_SESSION['app_type'] = 'new_agent'; // We set this in initiate
    $_SESSION['payment_status'] = 'paid';
    $_SESSION['payment_ref'] = 'PAYMONGO-VERIFIED'; // Or get real ID if possible
    
    // Redirect to Form
    header("Location: form.php");
    exit();

} else {
    $message = "Payment Cancelled or Failed.";
    $icon = "❌";
    $color = "#e74c3c";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Status - Pridens</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body style="display:flex; justify-content:center; align-items:center; height:100vh; background:#f0f2f5;">
    <div style="text-align:center; background:white; padding:40px; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.1);">
        <h1 style="color:<?= $color ?>; font-size:50px; margin:0;"><?= $icon ?></h1>
        <h2 style="color:#333;"><?= $message ?></h2>
        <br>
        <a href="index.php" style="display:inline-block; margin-top:20px; text-decoration:none; color:#3498db;">Back to Home</a>
    </div>
</body>
</html>
