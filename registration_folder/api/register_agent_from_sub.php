<?php
// api/register_agent_from_sub.php
header('Content-Type: application/json');
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

function generateAgentCode($supabase) {
    // Format: AG-YYYY-XXXXXX (e.g., AG-2025-123456)
    $prefix = 'AG-' . date('Y'); 
    do {
        $rand = str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
        $code = $prefix . '-' . $rand;
        
        $data = $supabase->from('agents')
            ->select('id')
            ->eq('agent_code', $code)
            ->limit(1)
            ->get();
            
        $exists = isset($data[0]);
    } while ($exists);
    return $code;
}

function generateTempPassword() {
    return substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 8); // Alphanumeric, no ambiguous chars
}

try {
    // 1. Inputs
    $subscriberId = $_POST['subscriber_id'] ?? '';
    $firstName = $_POST['firstname'] ?? '';
    $lastName = $_POST['lastname'] ?? '';
    $middleName = $_POST['middlename'] ?? '';
    // Full Name: First Middle Last
    $fullName = trim("$firstName $middleName $lastName");
    
    $email = $_POST['email'] ?? '';
    $phone = $_POST['contact'] ?? '';
    
    // Address
    $province = $_POST['province'] ?? '';
    $city = $_POST['city'] ?? '';
    $barangay = $_POST['barangay'] ?? '';
    $addressLine = $_POST['address_line'] ?? '';
    
    $birthdate = $_POST['birthdate'] ?? null;
    $gender = $_POST['gender'] ?? '';
    $civilStatus = $_POST['marital_status'] ?? '';
    
    $occupation = $_POST['occupation'] ?? 'N/A';
    $sourceOfIncome = $_POST['source_of_income'] ?? 'N/A';
    $referralCode = $_POST['referral_code'] ?? '';

    if (empty($subscriberId)) throw new Exception("Subscriber ID is required.");

    // 2. Generate Credentials
    $agentCode = generateAgentCode($supabase);
    
    $tempPassword = generateTempPassword();
    $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);
    
    $position = 'Pre-Agent';
    
    // Transaction ID generation (for record keeping)
    $trxId = 'REG-' . date('ymdHis');

    // 3. Check for duplicates/reuse of transaction
    $check1 = $supabase->from('agents')->select('id')->eq('transaction_number', $trxId)->limit(1)->get();
    
    if (isset($check1[0])) {
        throw new Exception("This Transaction ID has already been used for application.");
    }

    // 4. Check if they are already an agent (Affiliator)
    $check2 = $supabase->from('agents')->select('id, rank_id')->eq('account_id', $subscriberId)->limit(1)->get();
    $isUpgrade = isset($check2[0]);

    if ($isUpgrade && $check2[0]['rank_id'] >= getRankIdByName($supabase, 'Pre-Agent')) {
        throw new Exception("This subscriber is already a Pre-Agent or higher.");
    }

    $agentData = [
        'full_name' => $fullName,
        'email' => $email,
        'phone' => $phone,
        'account_id' => $subscriberId,
        'transaction_number' => $trxId,
        'referral_code' => $referralCode,
        'birthdate' => $birthdate,
        'gender' => $gender,
        'marital_status' => $civilStatus,
        'occupation' => $occupation,
        'source_of_income' => $sourceOfIncome,
        'province' => $province,
        'city' => $city,
        'barangay' => $barangay,
        'address' => $addressLine,
        'agent_position' => $position,
        'rank_id' => getRankIdByName($supabase, $position),
        'status' => 'active',
        'agent_code' => $agentCode,
        'password' => $hashedPassword
    ];
    
    if ($isUpgrade) {
        $agentData['updated_at'] = date('Y-m-d H:i:s');
        // We also want to reset rank_promoted_at
        $agentData['rank_promoted_at'] = date('Y-m-d H:i:s');
        $result = $supabase->from('agents')->update($agentData)->eq('account_id', $subscriberId)->execute();
    } else {
        $agentData['id'] = $subscriberId;
        $agentData['created_at'] = date('Y-m-d H:i:s');
        $agentData['rank_promoted_at'] = date('Y-m-d H:i:s');
        $result = $supabase->from('agents')->insert($agentData);
    }
    
    if (isset($result['error'])) {
        throw new Exception("Failed to register agent: " . print_r($result['error'], true));
    }

    // Mark Transaction as USED
    $updateResult = $supabase->from('pos_transactions')
        ->update(['is_used' => true])
        ->eq('transaction_number', $trxId)
        ->execute();

    echo json_encode([
        'success' => true, 
        'message' => 'Agent registered successfully!',
        'data' => [
            'agent_code' => $agentCode,
            'full_name' => $fullName,
            'position' => $position,
            'username' => $email,
            'password' => $tempPassword,
            'email' => $email,
            'phone' => $phone,
            'birthdate' => $birthdate,
            'gender' => $gender,
            'marital_status' => $civilStatus,
            'address' => "$addressLine, $barangay, $city, $province",
            'occupation' => $occupation,
            'referral_code' => $referralCode
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
}

function getRankIdByName($supabase, $name) {
    $ranks = $supabase->from('agent_ranks')->select('id')->eq('name', $name)->limit(1)->get();
    return isset($ranks[0]) ? (int)$ranks[0]['id'] : null;
}
?>
