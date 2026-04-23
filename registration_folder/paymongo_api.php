<?php
// paymongo_api.php
require_once 'paymongo_config.php';

function createPayMongoCheckout($amount, $description, $remarks = '') {
    $url = PAYMONGO_BASE_URL . '/checkout_sessions';
    
    // Amount is in centavos (e.g., 100.00 PHP = 10000)
    // Ensure amount is integer
    $amountInCentavos = (int) ($amount * 100);

    $data = [
        'data' => [
            'attributes' => [
                'line_items' => [
                    [
                        'currency' => 'PHP',
                        'amount' => $amountInCentavos,
                        'description' => $description,
                        'name' => 'Pridens Registration Fee',
                        'quantity' => 1
                    ]
                ],
                'payment_method_types' => ['card', 'gcash', 'paymaya', 'grab_pay'],
                'success_url' => PAYMONGO_SUCCESS_URL,
                'cancel_url' => PAYMONGO_FAILED_URL,
                'description' => $description,
                'reference_number' => $remarks // Use transaction ID as reference if possible
            ]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode(PAYMONGO_SECRET_KEY)
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 300) {
        return json_decode($response, true);
    } else {
        // Log error
        error_log("PayMongo Error: " . $response);
        return null; // Or throw exception
    }
}

function retrievePayMongoCheckout($checkoutSessionId) {
    if (empty($checkoutSessionId)) return null;

    $url = PAYMONGO_BASE_URL . '/checkout_sessions/' . $checkoutSessionId;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode(PAYMONGO_SECRET_KEY)
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 300) {
        return json_decode($response, true);
    } else {
        error_log("PayMongo Retrieve Error: " . $response);
        return null;
    }
}
?>
