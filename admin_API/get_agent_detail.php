<?php
// admin_API/get_agent_detail.php
header('Content-Type: application/json');
session_start();

if (empty($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

require_once 'db.php';

$agent_id = $_GET['agent_id'] ?? '';
if (empty($agent_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Agent ID required.']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT a.*, r.rank_name, r.level AS rank_level,
            DATE_FORMAT(a.created_at, '%M %d, %Y') AS registered_date,
            DATE_FORMAT(a.birthdate, '%M %d, %Y') AS birthdate_formatted,
            s.subscription_status AS subscriber_status,
            s.plan_id
        FROM agents a
        LEFT JOIN ranks r ON a.rank_id = r.id
        LEFT JOIN subscribers s ON a.account_id = s.account_id
        WHERE a.agent_id = ?
    ");
    $stmt->execute([$agent_id]);
    $agent = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$agent) {
        echo json_encode(['status' => 'error', 'message' => 'Agent not found.']);
        exit();
    }

    echo json_encode(['status' => 'success', 'data' => $agent]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
