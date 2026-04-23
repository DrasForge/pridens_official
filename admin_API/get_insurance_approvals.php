<?php
// admin_API/get_insurance_approvals.php
header('Content-Type: application/json');
require_once 'db.php';
session_start();

if (empty($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

try {
    // 1. Get Stats
    $totalPending = $pdo->query("SELECT COUNT(*) FROM subscribers WHERE insurance_status = 'Pending'")->fetchColumn();
    $readyToApprove = $pdo->query("SELECT COUNT(*) FROM subscribers WHERE insurance_status = 'Pending' AND insurance_ready_for_approval = 1")->fetchColumn();
    $activePolicies = $pdo->query("SELECT COUNT(*) FROM subscribers WHERE insurance_status = 'Active'")->fetchColumn();

    // 2. Get Pending List
    $pendingList = $pdo->query("
        SELECT 
            s.account_id, s.first_name, s.last_name, s.email, s.sales_invoice_number,
            s.insurance_document_path, s.insurance_ready_for_approval,
            s.subscription_status,
            sp.plan_name
        FROM subscribers s
        JOIN subscription_plans sp ON s.plan_id = sp.id
        WHERE s.insurance_status = 'Pending'
        ORDER BY s.created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // 3. Get Approved List (last 50)
    $approvedList = $pdo->query("
        SELECT 
            s.account_id, s.first_name, s.last_name, s.email, s.sales_invoice_number,
            s.insurance_policy_number, s.insurance_document_path,
            sp.plan_name, s.joined_date,
            TRIM(CONCAT(adm.first_name, ' ', adm.last_name)) as insurance_approved_by_name,
            DATE_FORMAT(s.insurance_approved_at, '%m/%d/%Y %h:%i %p') as insurance_approved_at_formatted
        FROM subscribers s
        JOIN subscription_plans sp ON s.plan_id = sp.id
        LEFT JOIN admins adm ON s.insurance_approved_by = adm.id
        WHERE s.insurance_status = 'Active'
        ORDER BY s.created_at DESC
        LIMIT 50
    ")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'stats' => [
            'pending' => $totalPending,
            'ready' => $readyToApprove,
            'active' => $activePolicies
        ],
        'pending_list' => $pendingList,
        'approved_list' => $approvedList
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
