<?php
// admin_API/get_agents.php
header('Content-Type: application/json');
session_start();

if (empty($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

require_once 'db.php';

try {
    $status_filter = $_GET['status'] ?? 'All';
    $search        = trim($_GET['search'] ?? '');

    $query = "
        SELECT 
            a.agent_id, a.first_name, a.last_name, a.email, a.phone,
            a.agent_position, a.status, a.gender,
            r.rank_name,
            DATE_FORMAT(a.created_at, '%m/%d/%Y') AS registered_date
        FROM agents a
        LEFT JOIN ranks r ON a.rank_id = r.id
        WHERE 1=1
    ";
    $params = [];

    if ($status_filter !== 'All' && in_array($status_filter, ['Active', 'Inactive'])) {
        $query .= " AND a.status = :status";
        $params['status'] = $status_filter;
    }

    if (!empty($search)) {
        $query .= " AND (a.first_name LIKE :search OR a.last_name LIKE :search OR a.agent_id LIKE :search OR a.email LIKE :search)";
        $params['search'] = "%$search%";
    }

    $query .= " ORDER BY a.created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $agents]);

} catch (PDOException $e) {
    if ($e->getCode() === '42S02') {
        echo json_encode(['status' => 'success', 'data' => []]);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
?>
