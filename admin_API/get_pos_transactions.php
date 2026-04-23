<?php
// admin_API/get_pos_transactions.php
require_once 'db.php';
header('Content-Type: application/json');

try {
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
    $to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';

    $query = "SELECT t.*, s.opening_time as shift_start 
              FROM `pos_transactions` t
              JOIN `pos_shifts` s ON t.shift_id = s.id ";
    
    $where = [];
    $params = [];

    if (!empty($search)) {
        $where[] = "(t.receipt_number LIKE ? OR t.customer_last_name LIKE ? OR t.customer_first_name LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if (!empty($status)) {
        $where[] = "t.status = ?";
        $params[] = $status;
    }

    if (!empty($from_date)) {
        $where[] = "DATE(t.created_at) >= ?";
        $params[] = $from_date;
    }

    if (!empty($to_date)) {
        $where[] = "DATE(t.created_at) <= ?";
        $params[] = $to_date;
    }

    if (count($where) > 0) {
        $query .= " WHERE " . implode(" AND ", $where);
    }

    $query .= " ORDER BY t.created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $transactions]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
