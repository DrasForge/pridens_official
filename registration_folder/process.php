<?php
// process.php - Handle Form Submission
session_start();
require_once 'config.php';

// Security Check
if (!isset($_SESSION['registration_access']) || !isset($_SESSION['trx_id'])) {
    header("Location: index.php");
    exit();
}

$appType = $_SESSION['app_type'];
$trxId = $_SESSION['trx_id'];
$formType = $_POST['form_type'] ?? '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid Request Method");
}

try {
    // 1. Double check reuse (Safety net)
    $checkData = $supabase->from('agents')
        ->select('id')
        ->eq('transaction_number', $trxId)
        ->get();
        
    // Also check account_id if provided
    $accountId = $_POST['account_id'] ?? ($_POST['agent_code'] ?? '');
    if (!empty($accountId)) {
        $accCheck = $supabase->from('agents')
            ->select('id')
            ->eq('account_id', $accountId)
            ->get();
        if (isset($accCheck[0])) {
            throw new Exception("This Account ID has already been used.");
        }
    }

    if (isset($checkData[0])) {
        throw new Exception("This Transaction ID has already been used.");
    }

    $insertResult = null;

    if ($formType === 'new_agent') {
        // Collect Data
        $first = trim($_POST['firstname']);
        $last = trim($_POST['lastname']);
        $middle = trim($_POST['middlename'] ?? '');
        $fullName = "$first " . ($middle ? "$middle " : "") . "$last";
        
        $insertData = [
            'type' => 'new_registration',
            'subscriber_id' => $_POST['subscriber_id'] ?? null,
            'transaction_number' => $trxId,
            'desired_rank_id' => getRankIdByName($supabase, 'Sales Agent'),
            'status' => 'pending',
            'application_data' => json_encode([
                'full_name' => $fullName,
                'email' => $_POST['email'],
                'phone' => $_POST['contact'],
                'birthdate' => $_POST['birthdate'] ?? '',
                'gender' => $_POST['gender'] ?? '',
                'marital_status' => $_POST['marital_status'] ?? '',
                'occupation' => $_POST['occupation'] ?? '',
                'source_of_income' => $_POST['source_of_income'] ?? '',
                'address' => $_POST['address_line'] ?? '',
                'referral_code' => $_POST['referral_code'] ?? ''
            ]),
            'submitted_at' => date('Y-m-d H:i:s')
        ];

        $insertResult = $supabase->from('agent_applications')->insert($insertData);

    } elseif ($formType === 'promotion') {
        $agentCode = $_POST['agent_code'];
        $position = $_POST['position'];
        
        // Find existing agent to get ID
        $agentRaw = $supabase->from('agents')->select('id, agent_position, full_name')->eq('agent_code', $agentCode)->get();
        if (!isset($agentRaw[0])) throw new Exception("Agent not found.");
        
        $agentId = $agentRaw[0]['id'];
        $currentPos = $agentRaw[0]['agent_position'] ?? 'Affiliator';
        $agentName = $agentRaw[0]['full_name'] ?? '';

        $insertData = [
            'type' => 'promotion',
            'agent_id' => $agentId,
            'transaction_number' => $trxId,
            'current_rank_id' => getRankIdByName($supabase, $currentPos),
            'desired_rank_id' => getRankIdByName($supabase, $position),
            'status' => 'pending',
            'application_data' => json_encode([
                'agent_code' => $agentCode,
                'full_name' => $agentName ?? '', // We should get full_name from agentRaw
                'new_position' => $position
            ]),
            'submitted_at' => date('Y-m-d H:i:s')
        ];

        $insertResult = $supabase->from('agent_applications')->insert($insertData);
    }

    if (isset($insertResult['error'])) {
        throw new Exception("Insert Failed: " . $insertResult['error']['message']);
    }

    // 2. Handle Payment Details
    $paymentId = $_SESSION['payment_ref'] ?? null;
    $paymentStatus = $_SESSION['payment_status'] ?? 'pending';

    if ($paymentId) {
        $paymentStatus = 'paid';
    }

    // Update Agent with Payment Info
    $supabase->from('agents')
        ->eq('transaction_number', $trxId)
        ->update([
            'payment_id' => $paymentId,
            'payment_status' => $paymentStatus
        ]);

    // 3. Mark Transaction as USED
    $supabase->from('pos_transactions')
        ->eq('transaction_number', $trxId)
        ->update(['is_used' => true]);

    // Clear Session
    session_destroy();
    
    // Success UI
    echo '<!DOCTYPE html>
    <html lang="en">
    <head><title>Success</title><link rel="stylesheet" href="assets/css/style.css"></head>
    <body style="display:flex; justify-content:center; align-items:center; height:100vh; background:#f0f2f5;">
        <div style="text-align:center; background:white; padding:40px; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.1);">
            <h1 style="color:#2ecc71; font-size:50px; margin:0;">✔</h1>
            <h2 style="color:#333;">Application Submitted!</h2>
            <p style="color:#666;">Your application has been successfully recorded.<br>Transaction ID: <strong>'.htmlspecialchars($trxId).'</strong> used.</p>
            <a href="index.php" style="display:inline-block; margin-top:20px; text-decoration:none; color:#3498db;">Back to Home</a>
        </div>
    </body>
    </html>';

} catch (Exception $e) {
    die("Error Processing Application: " . $e->getMessage());
}

function getRankIdByName($supabase, $name) {
    $ranks = $supabase->from('agent_ranks')->select('id')->eq('name', $name)->limit(1)->get();
    return $ranks[0]['id'] ?? null;
}
?>
