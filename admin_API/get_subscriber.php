<?php
// admin_API/get_subscriber.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-TOKEN');

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Check Authentication
if (empty($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

require_once 'db.php';

try {
    $id = $_GET['id'] ?? '';
    if (!$id) {
        echo json_encode(['status' => 'error', 'message' => 'Account ID is required']);
        exit;
    }

    // 1. Basic Details
    $stmt = $pdo->prepare("
        SELECT s.*, 
               DATE_FORMAT(s.joined_date, '%m/%d/%Y') as joined_date_formatted,
               a.first_name as agent_first_name, 
               a.last_name as agent_last_name,
               adm1.username as approved_by_name,
               adm2.username as insurance_approved_by_name
        FROM subscribers s
        LEFT JOIN agents a ON s.referral_code = a.agent_id
        LEFT JOIN admins adm1 ON s.approved_by = adm1.id
        LEFT JOIN admins adm2 ON s.insurance_approved_by = adm2.id
        WHERE s.account_id = ?
    ");
    $stmt->execute([$id]);
    $subscriber = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$subscriber) {
        echo json_encode(['status' => 'error', 'message' => 'Subscriber not found']);
        exit;
    }

    // 2. Beneficiaries
    $stmtBen = $pdo->prepare("SELECT * FROM subscriber_beneficiary WHERE account_id = ?");
    $stmtBen->execute([$id]);
    $subscriber['beneficiaries'] = $stmtBen->fetchAll(PDO::FETCH_ASSOC);

    // 3. Subscription Plan
    $plan = null;
    if (!empty($subscriber['plan_id'])) {
        // Optimized path: Use plan_id directly with product prices
        $stmtPlan = $pdo->prepare("
            SELECT sp.*, 
                   p1.price as base_onboarding_fee, p1.name as onboarding_product_name,
                   p2.price as base_monthly_fee, p2.name as monthly_product_name
            FROM subscription_plans sp
            LEFT JOIN pos_products p1 ON sp.onboarding_product_id = p1.id
            LEFT JOIN pos_products p2 ON sp.monthly_product_id = p2.id
            WHERE sp.id = ?
        ");
        $stmtPlan->execute([$subscriber['plan_id']]);
        $plan = $stmtPlan->fetch(PDO::FETCH_ASSOC);
        
        // Find the POS transaction for dates/financials
        $stmtTxn = $pdo->prepare("
            SELECT id as pos_txn_id, created_at as purchase_date 
            FROM pos_transactions 
            WHERE receipt_number = ? 
            LIMIT 1
        ");
        $stmtTxn->execute([$subscriber['sales_invoice_number']]);
        $txn = $stmtTxn->fetch(PDO::FETCH_ASSOC);
        if ($txn && $plan) {
            $plan['pos_txn_id'] = $txn['pos_txn_id'];
            $plan['purchase_date'] = $txn['purchase_date'];
        }
    } else {
        // Legacy path: Join via POS Invoice
        $stmtPlanLegacy = $pdo->prepare("
            SELECT sp.*, pt.created_at as purchase_date, pt.id as pos_txn_id
            FROM pos_transactions pt
            JOIN pos_transaction_items pti ON pt.id = pti.transaction_id
            JOIN subscription_plans sp ON (pti.product_id = sp.onboarding_product_id OR pti.product_id = sp.monthly_product_id)
            WHERE pt.receipt_number = ?
            LIMIT 1
        ");
        $stmtPlanLegacy->execute([$subscriber['sales_invoice_number']]);
        $plan = $stmtPlanLegacy->fetch(PDO::FETCH_ASSOC);
    }

    if ($plan) {
        $txn_id = $plan['pos_txn_id'];
        
        // 3a. Get POS details for onboarding & monthly to show what was PAID by THIS subscriber
        $stmtItems = $pdo->prepare("SELECT product_id, price_snapshot FROM pos_transaction_items WHERE transaction_id = ?");
        $stmtItems->execute([$txn_id]);
        $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        $plan['actual_onboarding_paid'] = 0;
        $plan['actual_monthly_paid'] = 0;

        foreach ($items as $item) {
            if ($item['product_id'] == $plan['onboarding_product_id']) {
                $plan['actual_onboarding_paid'] = $item['price_snapshot'];
            }
            if ($item['product_id'] == $plan['monthly_product_id']) {
                $plan['actual_monthly_paid'] = $item['price_snapshot'];
            }
        }

        // 3b. Fetch Insurance Benefits
        $stmtBen = $pdo->prepare("SELECT benefit_name, amount, requires_contestability FROM plan_insurance_benefits WHERE plan_id = ?");
        $stmtBen->execute([$plan['id']]);
        $plan['insurance_benefits'] = $stmtBen->fetchAll(PDO::FETCH_ASSOC);

        $subscriber['plan'] = $plan;

        // 3c. Fetch all relevant POS transactions for this subscriber
        // We'll look for the initial invoice AND any subsequent monthly payments
        // We categorize them to help the frontend sum up the totals correctly
        $stmtAllTxns = $pdo->prepare("
            SELECT DISTINCT pt.id, pt.receipt_number, pt.grand_total, pt.created_at, pt.status,
                   (CASE WHEN pt.receipt_number = ? THEN 'Registration' ELSE 'Monthly' END) as txn_type
            FROM pos_transactions pt
            JOIN pos_transaction_items pti ON pt.id = pti.transaction_id
            WHERE pt.receipt_number = ? 
               OR (
                   pt.customer_first_name = ? 
                   AND pt.customer_last_name = ? 
                   AND pti.product_id = ?
                   AND pt.status = 'Completed'
               )
            ORDER BY pt.created_at ASC
        ");
        $stmtAllTxns->execute([
            $subscriber['sales_invoice_number'], // For txn_type check
            $subscriber['sales_invoice_number'], // For WHERE clause
            $subscriber['first_name'],
            $subscriber['last_name'],
            $plan['monthly_product_id']
        ]);
        $subscriber['payment_history'] = $stmtAllTxns->fetchAll(PDO::FETCH_ASSOC);

    } else {
        $subscriber['plan'] = null;
        $subscriber['payment_history'] = [];
    }

    echo json_encode([
        'status' => 'success',
        'data' => $subscriber
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
