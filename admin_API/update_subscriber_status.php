<?php
// admin_API/update_subscriber_status.php
header('Content-Type: application/json');
require_once 'db.php';
session_start();

if (empty($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['account_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Account ID missing']);
    exit();
}

try {
    $pdo->beginTransaction();

    $accountId = $input['account_id'];
    $updates = [];
    $params = [];
    $isApproval = false;

    if (isset($input['subscription_status'])) {
        $updates[] = "subscription_status = ?, approved_by = ?, approved_at = NOW()";
        $params[] = $input['subscription_status'];
        $params[] = $_SESSION['admin_id'];
        
        if ($input['subscription_status'] === 'Active') {
            $isApproval = true;
        }
    }

    if (isset($input['insurance_status'])) {
        $updates[] = "insurance_status = ?, insurance_approved_by = ?, insurance_approved_at = NOW()";
        $params[] = $input['insurance_status'];
        $params[] = $_SESSION['admin_id'];
    }

    if (empty($updates)) {
        echo json_encode(['status' => 'error', 'message' => 'No fields to update']);
        $pdo->rollBack();
        exit();
    }

    $params[] = $accountId; // Final WHERE parameter
    $sql = "UPDATE subscribers SET " . implode(', ', $updates) . " WHERE account_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // AWARD P-VOUCHER ON INITIAL APPROVAL
    if ($isApproval) {
        // 1. Check if already rewarded to prevent duplicates
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM subscriber_pvoucher_ledger WHERE account_id = ? AND description = 'Initial Approval Reward'");
        $stmtCheck->execute([$accountId]);
        if ($stmtCheck->fetchColumn() == 0) {
            // 2. Fetch plan P-voucher amount
            $stmtPlan = $pdo->prepare("
                SELECT sp.monthly_pvoucher 
                FROM subscription_plans sp
                JOIN subscribers s ON s.plan_id = sp.id
                WHERE s.account_id = ?
            ");
            $stmtPlan->execute([$accountId]);
            $pvoucherAmount = $stmtPlan->fetchColumn();

            if ($pvoucherAmount > 0) {
                // 3. Insert into ledger
                $stmtLedger = $pdo->prepare("
                    INSERT INTO subscriber_pvoucher_ledger (account_id, amount, type, description)
                    VALUES (?, ?, 'Credit', 'Initial Approval Reward')
                ");
                $stmtLedger->execute([$accountId, $pvoucherAmount]);
            }
        }

        // ==========================================
        // AUTO-AFFILIATOR: If not in agents, insert as Affiliator (Rank 1)
        // ==========================================
        $stmtCheckAgent = $pdo->prepare("SELECT COUNT(*) FROM agents WHERE account_id = ?");
        $stmtCheckAgent->execute([$accountId]);
        if ($stmtCheckAgent->fetchColumn() == 0) {
            // Fetch subscriber details to map cleanly
            $stmtSubData = $pdo->prepare("SELECT * FROM subscribers WHERE account_id = ?");
            $stmtSubData->execute([$accountId]);
            $subData = $stmtSubData->fetch(PDO::FETCH_ASSOC);

            if ($subData) {
                // We use account_id as agent_code temporarily until they register as Pre-Agent
                $tempAgentCode = $subData['account_id'];
                
                // Fetch the password from subscriber_user
                $stmtSubUser = $pdo->prepare("SELECT password_hash FROM subscriber_user WHERE account_id = ? LIMIT 1");
                $stmtSubUser->execute([$accountId]);
                $pwdHash = $stmtSubUser->fetchColumn();

                if (!$pwdHash) {
                    $pwdHash = password_hash('password123', PASSWORD_DEFAULT);
                }

                $fullName = trim($subData['first_name'] . ' ' . $subData['middle_name'] . ' ' . $subData['last_name']);

                // Insert into agents as Affiliator
                $stmtInsertAgent = $pdo->prepare("
                    INSERT INTO agents (
                        agent_id,
                        account_id,
                        first_name, 
                        last_name,
                        middle_name,
                        email, 
                        phone, 
                        agent_position, 
                        rank_id, 
                        status, 
                        password_hash, 
                        address, 
                        city, 
                        province, 
                        birthdate, 
                        gender, 
                        marital_status, 
                        occupation
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, 'Affiliator', 1, 'Active', ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmtInsertAgent->execute([
                    $tempAgentCode, 
                    $accountId,
                    $subData['first_name'],
                    $subData['last_name'],
                    $subData['middle_name'] ?? null,
                    $subData['email'],
                    $subData['contact_number'],
                    $pwdHash,
                    $subData['address_house_street'],
                    $subData['address_city'],
                    $subData['address_region'],
                    $subData['date_of_birth'],
                    $subData['gender'],
                    $subData['civil_status'],
                    $subData['occupation']
                ]);
            }
        }
        // ==========================================
        
        // ==========================================
        // 3. ONE-TIME COMMISSION DISTRIBUTION
        // ==========================================
        require_once 'commission_engine.php';
        $engine = new CommissionEngine($pdo);
        if (!empty($subData['referral_code'])) {
            $engine->distributeOneTime($accountId, $subData['plan_id'], $subData['referral_code']);
        }
        // ==========================================
        
    }

    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'Subscriber updated and P-voucher awarded']);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
