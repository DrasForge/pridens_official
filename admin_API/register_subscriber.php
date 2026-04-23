<?php
// admin_API/register_subscriber.php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid input data']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Generate unique Account ID: PR-XXXXXXX
    // We get the max current one or just use a random/sequential approach
    $stmtId = $pdo->query("SELECT account_id FROM subscribers ORDER BY account_id DESC LIMIT 1");
    $lastId = $stmtId->fetchColumn();
    $nextNum = 1;
    if ($lastId && preg_match('/PR-(\d+)/', $lastId, $matches)) {
        $nextNum = intval($matches[1]) + 1;
    }
    $newAccountId = 'PR-' . str_pad($nextNum, 7, '0', STR_PAD_LEFT);
    $si_number = $input['sales_invoice_number'] ?: 'PRE-REG-' . time();

    // 2. Find Plan ID and Billing Bracket
    // Prefer the pre-verified plan_id from the form; fall back to SI-based detection.
    $plan_id = !empty($input['plan_id']) ? (int)$input['plan_id'] : null;
    $billing_day = null;
    $insurance_status = 'N/A';

    // If plan_id was NOT supplied from the form, try to detect from SI
    if (!$plan_id) {
        $stmtPlan = $pdo->prepare("
            SELECT sp.id, sp.has_insurance
            FROM pos_transactions pt
            JOIN pos_transaction_items pti ON pt.id = pti.transaction_id
            JOIN subscription_plans sp ON pti.product_id = sp.onboarding_product_id
            WHERE pt.receipt_number = ?
            AND sp.status = 'Active'
            LIMIT 1
        ");
        $stmtPlan->execute([$si_number]);
        $planData = $stmtPlan->fetch(PDO::FETCH_ASSOC);
        if ($planData) {
            $plan_id = $planData['id'];
            $insurance_status = ($planData['has_insurance'] == 1) ? 'Pending' : 'N/A';
        }
    } else {
        // We have the plan_id from form — just fetch has_insurance
        $stmtIns = $pdo->prepare("SELECT has_insurance FROM subscription_plans WHERE id = ?");
        $stmtIns->execute([$plan_id]);
        $planMeta = $stmtIns->fetch(PDO::FETCH_ASSOC);
        if ($planMeta) {
            $insurance_status = ($planMeta['has_insurance'] == 1) ? 'Pending' : 'N/A';
        }
    }

    // Find matching billing bracket based on today's date
    if ($plan_id) {
        $currentDay = (int)date('j');
        $stmtBrackets = $pdo->prepare("SELECT * FROM plan_billing_brackets WHERE plan_id = ?");
        $stmtBrackets->execute([$plan_id]);
        $brackets = $stmtBrackets->fetchAll(PDO::FETCH_ASSOC);
        foreach ($brackets as $bracket) {
            if ($currentDay >= $bracket['approval_from_day'] && $currentDay <= $bracket['approval_to_day']) {
                $billing_day = $bracket['bill_on_day'];
                break;
            }
        }
    }

    // 3. Insert into Subscribers table
    $sqlSub = "INSERT INTO subscribers (
        account_id, referral_code, last_name, first_name, middle_name, suffix,
        date_of_birth, place_of_birth, gender, civil_status, nationality,
        occupation, source_of_income, monthly_income, email, contact_number,
        address_house_street, address_region, address_city, address_barangay, address_zip_code,
        privacy_policy_ack, sales_invoice_number, plan_id, billing_day, subscription_status, insurance_status, joined_date, avatar_initials
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, 'Pending', ?, CURDATE(), ?)";

    $initials = strtoupper(substr($input['first_name'], 0, 1) . substr($input['last_name'], 0, 1));

    $stmtSub = $pdo->prepare($sqlSub);
    $stmtSub->execute([
        $newAccountId,
        $input['referral_code'] ?: null,
        $input['last_name'],
        $input['first_name'],
        $input['middle_name'] ?: null,
        $input['suffix'] ?: null,
        $input['date_of_birth'],
        $input['place_of_birth'],
        $input['gender'],
        $input['civil_status'],
        $input['nationality'],
        $input['occupation'],
        $input['source_of_income'],
        $input['monthly_income'],
        $input['email'],
        $input['contact_number'],
        $input['address_house_street'],
        $input['address_region'],
        $input['address_city'],
        $input['address_barangay'],
        $input['address_zip_code'],
        $si_number,
        $plan_id,
        $billing_day,
        $insurance_status,
        $initials
    ]);

    // 3. Create Authentication Account
    $default_password = password_hash('password123', PASSWORD_DEFAULT);
    $stmtUser = $pdo->prepare("INSERT INTO subscriber_user (account_id, email, password_hash) VALUES (?, ?, ?)");
    $stmtUser->execute([$newAccountId, $input['email'], $default_password]);

    // 4. Insert Beneficiaries (1-3)
    if (isset($input['beneficiaries']) && is_array($input['beneficiaries'])) {
        foreach ($input['beneficiaries'] as $ben) {
            if (empty($ben['full_name'])) continue;
            $stmtBen = $pdo->prepare("INSERT INTO subscriber_beneficiary (account_id, full_name, date_of_birth, relation, contact_number) VALUES (?, ?, ?, ?, ?)");
            $stmtBen->execute([
                $newAccountId,
                $ben['full_name'],
                $ben['date_of_birth'],
                $ben['relation'],
                $ben['contact_number'] ?: ''
            ]);
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'account_id' => $newAccountId]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
