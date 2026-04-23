<?php
// admin_API/verify_si.php
// Validates a Sales Invoice for subscriber registration.
// Returns: plan_id, plan_name, agent_code (from transaction), and agent full name.
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$si_number = trim($_GET['si'] ?? '');

if (empty($si_number)) {
    echo json_encode(['success' => false, 'error' => 'SI number is required']);
    exit;
}

try {
    // 1. Check if SI is already used by another subscriber
    $stmt = $pdo->prepare("SELECT account_id FROM subscribers WHERE sales_invoice_number = ? LIMIT 1");
    $stmt->execute([$si_number]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'This Sales Invoice has already been used for registration.']);
        exit;
    }

    // 2. Fetch the transaction (with its referral agent code and status)
    $stmt = $pdo->prepare("
        SELECT t.id AS trx_id, t.status, t.agent_referral_code
        FROM pos_transactions t
        WHERE t.receipt_number = ?
        LIMIT 1
    ");
    $stmt->execute([$si_number]);
    $txn = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$txn) {
        echo json_encode(['success' => false, 'error' => 'Sales Invoice number not found in POS records.']);
        exit;
    }

    if ($txn['status'] === 'Voided') {
        echo json_encode(['success' => false, 'error' => 'This transaction has been voided and cannot be used.']);
        exit;
    }

    if ($txn['status'] !== 'Completed') {
        echo json_encode(['success' => false, 'error' => 'Transaction is not completed. Status: ' . $txn['status']]);
        exit;
    }

    // 3. Identify the subscription plan from items by SKU via subscription_plans.onboarding_product_id
    $stmt = $pdo->prepare("
        SELECT sp.id AS plan_id, sp.plan_name, pp.sku AS onboarding_sku
        FROM pos_transaction_items ti
        JOIN pos_products pp ON ti.product_id = pp.id
        JOIN subscription_plans sp ON sp.onboarding_product_id = pp.id
        WHERE ti.transaction_id = ?
        AND sp.status = 'Active'
        LIMIT 1
    ");
    $stmt->execute([$txn['trx_id']]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$plan) {
        echo json_encode(['success' => false, 'error' => 'No active subscription plan product found in this transaction. Please confirm the correct SI was used.']);
        exit;
    }

    // 4. Resolve agent name from referral code (if present in transaction)
    $agent_name = null;
    $agent_code = $txn['agent_referral_code'] ?? '';

    if (!empty($agent_code)) {
        $stmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) AS full_name FROM agents WHERE agent_id = ? LIMIT 1");
        $stmt->execute([$agent_code]);
        $agent = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($agent) $agent_name = $agent['full_name'];
    }

    echo json_encode([
        'success'    => true,
        'data'       => [
            'plan_id'     => $plan['plan_id'],
            'plan'        => $plan['plan_name'],
            'plan_sku'    => $plan['onboarding_sku'],
            'agent_code'  => $agent_code,
            'agent_name'  => $agent_name,
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
