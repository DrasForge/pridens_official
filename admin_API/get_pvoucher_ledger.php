<?php
header('Content-Type: application/json');
require_once 'db.php';
session_start();

if (empty($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

try {
    $search = $_GET['search'] ?? '';
    
    $query = "
        SELECT 
            l.id,
            l.account_id,
            s.first_name,
            s.last_name,
            l.amount,
            l.type,
            l.description,
            DATE_FORMAT(l.created_at, '%m/%d/%Y %h:%i %p') as date_formatted
        FROM subscriber_pvoucher_ledger l
        JOIN subscribers s ON l.account_id = s.account_id
    ";
    
    $params = [];
    if (!empty($search)) {
        $query .= " WHERE l.account_id LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? ";
        $searchTerm = "%$search%";
        $params = [$searchTerm, $searchTerm, $searchTerm];
    }
    
    $query .= " ORDER BY l.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['status' => 'success', 'data' => $data]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
