<?php
// api/check_agent_for_promotion.php
header('Content-Type: application/json');
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$agentCode = $_POST['agent_code'] ?? '';

if (empty($agentCode)) {
    echo json_encode(['success' => false, 'message' => 'Agent Code is required']);
    exit;
}

try {
    // 1. Check if Agent Exists and is Active
    // We check agent_code column
    // Supabase REST: GET /agents?agent_code=eq.CODE&status=eq.active&order=created_at.desc&limit=1
    $data = $supabase->from('agents')
        ->select('full_name, status, agent_position')
        ->eq('agent_code', $agentCode)
        ->eq('status', 'active')
        ->order('created_at', 'desc')
        ->limit(1)
        ->get();

    $agent = isset($data[0]) ? $data[0] : null;

    if (!$agent) {
        echo json_encode(['success' => false, 'message' => 'Active Agent Code not found. Please ensure you are an approved agent.']);
        exit;
    }

    // 2. Check for Pending Promotion
    $pendingData = $supabase->from('agents')
        ->select('id')
        ->eq('account_id', $agentCode)
        ->eq('status', 'pending')
        ->limit(1)
        ->get();
        
    if (isset($pendingData[0])) {
        echo json_encode(['success' => false, 'message' => 'You already have a pending promotion application. Please wait for approval.']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'full_name' => $agent['full_name'],
            'current_position' => $agent['agent_position']
        ]
    ]);

} catch (Exception $e) {
    // Catch generic exceptions from db.php if any
    echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
}
?>
