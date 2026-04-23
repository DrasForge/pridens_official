<?php
// admin_API/get_accounts.php
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
    $status_filter = $_GET['status'] ?? 'All';

    $query = "SELECT s.account_id, s.email, s.first_name, s.last_name, s.subscription_status as status, s.insurance_status, s.avatar_initials, 
                     DATE_FORMAT(s.joined_date, '%m/%d/%Y') as joined_date,
                     TRIM(CONCAT(adm1.first_name, ' ', adm1.last_name)) as approved_by_name,
                     DATE_FORMAT(s.approved_at, '%m/%d/%Y %h:%i %p') as approved_at_formatted,
                     TRIM(CONCAT(adm2.first_name, ' ', adm2.last_name)) as insurance_approved_by_name,
                     DATE_FORMAT(s.insurance_approved_at, '%m/%d/%Y %h:%i %p') as insurance_approved_at_formatted
              FROM subscribers s
              LEFT JOIN admins adm1 ON s.approved_by = adm1.id
              LEFT JOIN admins adm2 ON s.insurance_approved_by = adm2.id";
    $params = [];

    if ($status_filter !== 'All' && in_array($status_filter, ['Pending', 'Active', 'Rejected'])) {
        $query .= " WHERE subscription_status = :status";
        $params['status'] = $status_filter;
    }

    $query .= " ORDER BY s.created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    
    $accounts = $stmt->fetchAll();

    echo json_encode([
        'status' => 'success',
        'data' => $accounts
    ]);

} catch (PDOException $e) {
    if ($e->getCode() === '42S02') { 
        echo json_encode(['status' => 'success', 'data' => []]);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database query failed: ' . $e->getMessage()]);
    }
}
?>
