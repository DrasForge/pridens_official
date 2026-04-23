<?php
// api/check_subscriber.php
header('Content-Type: application/json');
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$subscriberId = $_POST['subscriber_id'] ?? '';

if (empty($subscriberId)) {
    echo json_encode(['success' => false, 'message' => 'Subscriber ID is required']);
    exit;
}

try {
    // 1. Check if Subscriber Exists using the correct column 'account_id'
    $data = $supabase->from('subscribers')
        ->select('*')
        ->eq('account_id', $subscriberId)
        ->limit(1)
        ->get();
        
    $subscriber = isset($data[0]) ? $data[0] : null;

    if (!$subscriber) {
        echo json_encode(['success' => false, 'message' => 'Subscriber ID not found']);
        exit;
    }

    // 2. Check if Already an Agent or has Pending Application
    $agentData = $supabase->from('agents')
        ->select('status')
        ->eq('account_id', $subscriberId)
        ->limit(1)
        ->get();
        
    $existing = isset($agentData[0]) ? $agentData[0] : null;

    if ($existing) {
        if ($existing['status'] === 'active') {
            echo json_encode(['success' => false, 'message' => 'This subscriber is already a registered active agent.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'This subscriber has a pending agent application. Please wait for approval.']);
        }
        exit;
    }

    // 3. Return Data for Auto-Fill
    echo json_encode([
        'success' => true,
        'data' => [
            'firstname' => $subscriber['first_name'],
            'middlename' => $subscriber['middle_name'],
            'lastname' => $subscriber['last_name'],
            'birthdate' => $subscriber['dob'], // Column is 'dob'
            'gender' => $subscriber['gender'],
            'marital_status' => $subscriber['civil_status'],
            'province' => $subscriber['province'],
            'city' => $subscriber['municipality'], // Column is 'municipality'
            'barangay' => $subscriber['barangay'],
            'address_line' => $subscriber['address_line'], 
            'contact' => $subscriber['phone'], // Column is 'phone'
            'email' => $subscriber['email'] ?? '',
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
