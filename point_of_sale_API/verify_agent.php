<?php
// point_of_sale_API/verify_agent.php
session_start();
require_once '../admin_API/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$agent_id = trim($_GET['agent_id'] ?? '');

if (empty($agent_id)) {
    echo json_encode(['success' => true, 'found' => false, 'message' => 'No agent code provided.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT agent_id, first_name, last_name, status FROM `agents` WHERE `agent_id` = ? LIMIT 1");
    $stmt->execute([$agent_id]);
    $agent = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($agent) {
        echo json_encode([
            'success' => true,
            'found' => true,
            'agent' => [
                'agent_id' => $agent['agent_id'],
                'name' => $agent['first_name'] . ' ' . $agent['last_name'],
                'status' => $agent['status']
            ]
        ]);
    } else {
        echo json_encode(['success' => true, 'found' => false, 'message' => 'Agent not found.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error.']);
}
?>
