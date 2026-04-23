<?php
// admin_API/approve_insurance_policy.php
header('Content-Type: application/json');
require_once 'db.php';
session_start();

if (empty($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['account_id']) || empty($input['policy_number'])) {
    echo json_encode(['status' => 'error', 'message' => 'Account ID and Policy Number are required']);
    exit();
}

try {
    $accountId = $input['account_id'];
    $policyNumber = $input['policy_number'];

    // Check if document exists and subscriber is active
    $stmtCheck = $pdo->prepare("SELECT insurance_document_path, insurance_ready_for_approval, subscription_status FROM subscribers WHERE account_id = ?");
    $stmtCheck->execute([$accountId]);
    $subscriber = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$subscriber || $subscriber['subscription_status'] !== 'Active') {
        echo json_encode(['status' => 'error', 'message' => 'Subscriber account must be officially approved (Active) first.']);
        exit();
    }

    if (!$subscriber['insurance_ready_for_approval']) {
        echo json_encode(['status' => 'error', 'message' => 'Insurance document must be uploaded before approval.']);
        exit();
    }

    $pdo->prepare("UPDATE subscribers SET insurance_status = 'Active', insurance_policy_number = ?, insurance_ready_for_approval = 0, insurance_approved_by = ?, insurance_approved_at = NOW() WHERE account_id = ?")
        ->execute([$policyNumber, $_SESSION['admin_id'], $accountId]);

    echo json_encode(['status' => 'success', 'message' => 'Insurance policy approved successfully']);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
