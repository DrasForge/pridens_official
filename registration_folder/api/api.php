<?php
// registration_folder/api/api.php
header('Content-Type: application/json');
require_once '../../admin_API/db.php';

$action = $_GET['action'] ?? '';
$data = $_POST;

try {
    switch ($action) {
        case 'check_subscriber':
            checkSubscriber($pdo, $data);
            break;
        case 'register_agent_from_sub':
            registerAgentFromSub($pdo, $data);
            break;
        case 'check_agent_for_promotion':
            echo json_encode(['success' => false, 'message' => 'Promotion feature coming soon.']);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

function checkSubscriber($pdo, $data) {
    $subscriberId = $data['subscriber_id'] ?? '';
    if (empty($subscriberId)) {
        echo json_encode(['success' => false, 'message' => 'Subscriber ID is required']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM subscribers WHERE account_id = ?");
    $stmt->execute([$subscriberId]);
    $sub = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sub) {
        echo json_encode(['success' => false, 'message' => 'Subscriber ID not found']);
        exit;
    }

    // Map to expected JS fields
    echo json_encode([
        'success' => true,
        'data' => [
            'firstname' => $sub['first_name'],
            'middlename' => $sub['middle_name'],
            'lastname' => $sub['last_name'],
            'birthdate' => $sub['date_of_birth'],
            'gender' => $sub['gender'],
            'marital_status' => $sub['civil_status'],
            'province' => $sub['address_region'],
            'city' => $sub['address_city'],
            'barangay' => $sub['address_barangay'],
            'address_line' => $sub['address_house_street'],
            'email' => $sub['email'],
            'contact' => $sub['contact_number'],
            'referral_code' => $sub['referred_by'] ?? ''
        ]
    ]);
}

function registerAgentFromSub($pdo, $data) {
    // Logic similar to finalize_agent_registration but with POST data from form
    $subscriberId = $data['subscriber_id'] ?? '';
    $receipt = $data['trx_id'] ?? ''; // form-wizard passes trx_id

    if (empty($subscriberId) || empty($receipt)) {
        throw new Exception('Missing identification data (Subscriber ID or Receipt).');
    }

    $pdo->beginTransaction();

    // 1. Verify Transaction
    $stmt = $pdo->prepare("
        SELECT t.id AS trx_id, t.is_used
        FROM pos_transactions t
        JOIN pos_transaction_items i ON t.id = i.transaction_id
        JOIN pos_products p ON i.product_id = p.id
        WHERE t.receipt_number = ? AND p.sku IN ('APP-REG-01', '11001') AND t.status = 'Completed'
        FOR UPDATE
    ");
    $stmt->execute([$receipt]);
    $trx = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$trx) throw new Exception('Invalid registration fee receipt.');
    if ($trx['is_used']) throw new Exception('This receipt has already been used.');

    // 2. Map personal data (use what's in POST or re-fetch from Sub)
    // For safety, re-fetch from DB based on ID
    $stmt = $pdo->prepare("SELECT * FROM subscribers WHERE account_id = ?");
    $stmt->execute([$subscriberId]);
    $sub = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$sub) throw new Exception('Subscriber not found.');

    // 3. Credentials
    $year = date('Y');
    $agent_id = ''; $exists = true;
    while ($exists) {
        $rand = str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        $agent_id = "AG-{$year}-{$rand}";
        $stmt = $pdo->prepare("SELECT agent_id FROM agents WHERE agent_id = ?");
        $stmt->execute([$agent_id]);
        if (!$stmt->fetch()) $exists = false;
    }

    $temp_password = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 8);
    $password_hash = password_hash($temp_password, PASSWORD_DEFAULT);

    // 4. Get default rank_id
    $stmt = $pdo->query("SELECT id FROM ranks WHERE rank_name = 'Sales Agent' LIMIT 1");
    $rank_id = $stmt->fetchColumn();

    // 5. Insert
    $stmt = $pdo->prepare("
        INSERT INTO agents (
            agent_id, first_name, last_name, email, password_hash, status, 
            account_id, transaction_number, middle_name, suffix, birthdate, 
            gender, marital_status, occupation, source_of_income, phone, 
            address, province, city, barangay, agent_position, rank_id
        ) VALUES (
            ?, ?, ?, ?, ?, 'Active', 
            ?, ?, ?, ?, ?, 
            ?, ?, ?, ?, ?, 
            ?, ?, ?, ?, 'Sales Agent', ?
        )
    ");

    $address_full = $sub['address_house_street'] . ', ' . $sub['address_zip_code'];
    
    $stmt->execute([
        $agent_id, $sub['first_name'], $sub['last_name'], $sub['email'], $password_hash,
        $sub['account_id'], $receipt, $sub['middle_name'], $sub['suffix'], $sub['date_of_birth'],
        $sub['gender'], $sub['civil_status'], $data['occupation'] ?? $sub['occupation'], $data['source_of_income'] ?? $sub['source_of_income'], $sub['contact_number'],
        $address_full, $sub['address_region'], $sub['address_city'], $sub['address_barangay'], $rank_id
    ]);

    // 6. Mark Transaction
    $pdo->prepare("UPDATE pos_transactions SET is_used = TRUE WHERE id = ?")->execute([$trx['trx_id']]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'data' => [
            'agent_code' => $agent_id,
            'full_name' => $sub['first_name'] . ' ' . $sub['last_name'],
            'username' => $sub['email'],
            'password' => $temp_password,
            'position' => 'Sales Agent',
            'phone' => $sub['contact_number'],
            'birthdate' => $sub['date_of_birth'],
            'address' => $address_full,
            'email' => $sub['email'],
            'referral_code' => $sub['referred_by'] ?? ''
        ]
    ]);
}
?>
