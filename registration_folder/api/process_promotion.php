<?php
// api/process_promotion.php
header('Content-Type: application/json');
session_start();
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Security Check
if (!isset($_SESSION['registration_access']) || !isset($_SESSION['trx_id']) || $_SESSION['app_type'] !== 'promotion') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized or invalid session']);
    exit;
}

$agentCode = $_POST['agent_code'] ?? '';
$newPosition = $_POST['position'] ?? '';
$trxId = $_SESSION['trx_id'];

if (empty($agentCode) || empty($newPosition)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

try {
    // 1. Double check reuse
    $checkData = $supabase->from('agents')
        ->select('id')
        ->eq('transaction_number', $trxId)
        ->limit(1)
        ->get();
        
    if (isset($checkData[0])) {
        throw new Exception("This Transaction ID has already been used.");
    }

    // 2. Double check current status
    $agentData = $supabase->from('agents')
        ->select('id, full_name, email, phone, rank_id, agent_position')
        ->eq('agent_code', $agentCode)
        ->eq('status', 'active')
        ->limit(1)
        ->get();

    $agent = isset($agentData[0]) ? $agentData[0] : null;
    
    if (!$agent) {
        throw new Exception("Active Agent not found for code: $agentCode");
    }

    // 3. Insert Promotion Application (to agent_applications table)
    $desiredRankId = getRankIdByName($supabase, $newPosition);
    $currentRankId = $agent['rank_id'] ?? getRankIdByName($supabase, $agent['agent_position']);

    $insertData = [
        'type' => 'promotion',
        'agent_id' => $agent['id'],
        'transaction_number' => $trxId,
        'current_rank_id' => $currentRankId,
        'desired_rank_id' => $desiredRankId,
        'status' => 'pending',
        'application_data' => json_encode([
            'agent_code' => $agentCode,
            'full_name' => $agent['full_name'],
            'requested_position' => $newPosition
        ]),
        'submitted_at' => date('Y-m-d H:i:s')
    ];
    
    $insertResult = $supabase->from('agent_applications')->insert($insertData);
    
    if (isset($insertResult['error'])) {
        throw new Exception("Failed to submit application: " . print_r($insertResult['error'], true));
    }

    // 4. Mark Transaction as USED
    $updateResult = $supabase->from('pos_transactions')
        ->update(['is_used' => true])
        ->eq('transaction_number', $trxId)
        ->execute();

    // No commit/rollback available in REST
    
    // Clear session for this transaction
    unset($_SESSION['registration_access']);
    unset($_SESSION['trx_id']);
    unset($_SESSION['app_type']);

    echo json_encode([
        'success' => true,
        'message' => 'Promotion application submitted!',
        'data' => [
            'agent_code' => $agentCode, // Showing their existing code
            'full_name' => $agent['full_name'],
            'position' => $newPosition,
            'status' => 'Pending Approval',
            'email' => $agent['email'],
            'phone' => $agent['phone'],
            'date' => date('Y-m-d H:i:s')
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function getRankIdByName($supabase, $name) {
    $ranks = $supabase->from('agent_ranks')->select('id')->eq('name', $name)->limit(1)->get();
    return isset($ranks[0]) ? (int)$ranks[0]['id'] : null;
}
?>
