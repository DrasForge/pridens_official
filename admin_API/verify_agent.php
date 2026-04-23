<?php
// admin_API/verify_agent.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$agent_id = trim($_GET['id'] ?? '');

if (empty($agent_id)) {
    echo json_encode(['success' => false, 'error' => 'Agent ID is required']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT first_name, last_name, status FROM agents WHERE agent_id = ? LIMIT 1");
    $stmt->execute([$agent_id]);
    $agent = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$agent) {
        echo json_encode(['success' => false, 'error' => 'Agent code not found.']);
        exit;
    }

    if ($agent['status'] !== 'Active') {
        echo json_encode(['success' => false, 'error' => 'This agent is currently inactive.']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'full_name' => $agent['first_name'] . ' ' . $agent['last_name']
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>
