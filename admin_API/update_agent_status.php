<?php
// admin_API/update_agent_status.php
header('Content-Type: application/json');
session_start();

if (empty($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

require_once 'db.php';

$data = json_decode(file_get_contents('php://input'), true);
$agent_id = $data['agent_id'] ?? '';
$new_status = $data['status'] ?? '';

if (empty($agent_id) || !in_array($new_status, ['Active', 'Inactive'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input.']);
    exit();
}

try {
    $stmt = $pdo->prepare("UPDATE agents SET status = ? WHERE agent_id = ?");
    $stmt->execute([$new_status, $agent_id]);
    echo json_encode(['status' => 'success', 'message' => "Agent status updated to $new_status."]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
