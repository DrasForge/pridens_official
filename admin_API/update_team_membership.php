<?php
// admin_API/update_team_membership.php
header('Content-Type: application/json');
require_once 'db.php';
session_start();

if (empty($_SESSION['admin_logged_in'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['agent_ids']) || !isset($input['team_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input.']);
    exit();
}

try {
    $teamId = $input['team_id'] === 0 ? null : $input['team_id']; // 0 means remove from team
    $agentIds = $input['agent_ids'];

    $placeholders = implode(',', array_fill(0, count($agentIds), '?'));
    $stmt = $pdo->prepare("UPDATE agents SET team_id = ? WHERE agent_id IN ($placeholders)");
    
    $params = array_merge([$teamId], $agentIds);
    $stmt->execute($params);

    echo json_encode(['status' => 'success', 'message' => 'Membership updated.']);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
