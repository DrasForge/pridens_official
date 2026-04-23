<?php
// paymongo_config.php

// PayMongo API Keys
// REPLACE THESE WITH YOUR ACTUAL KEYS
define('PAYMONGO_PUBLIC_KEY', '');
define('PAYMONGO_SECRET_KEY', '');

// API Base URL
define('PAYMONGO_BASE_URL', 'https://api.paymongo.com/v1');

// Return URLs (Adjust domain as needed)
// Assuming localhost for now, change to production domain when deploying.
define('PAYMONGO_SUCCESS_URL', 'http://localhost/pridens_registration/payment_callback.php?status=success');
define('PAYMONGO_FAILED_URL', 'http://localhost/pridens_registration/payment_callback.php?status=failed');
?>
