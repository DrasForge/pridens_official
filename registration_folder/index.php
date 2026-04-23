<?php
// index.php - The landing Page / Transaction Gate
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php'; // DB Connection ($pdo)

$error = '';

/**
 * Real Transaction Validation Logic (MySQL version)
 */
function validateTransaction($trxId, $pdo) {
    try {
        // 1. Get Transaction - Must NOT be void and must NOT be used
        // In the current schema, APP-REG-01 is the target SKU
        $stmt = $pdo->prepare("
            SELECT t.id AS trx_id, t.is_used, t.status
            FROM pos_transactions t
            JOIN pos_transaction_items i ON t.id = i.transaction_id
            JOIN pos_products p ON i.product_id = p.id
            WHERE t.receipt_number = ? 
            AND p.sku IN ('APP-REG-01', '11001', '1102') 
            AND t.status = 'Completed'
            LIMIT 1
        ");
        $stmt->execute([$trxId]);
        $trx = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$trx) {
            return ['valid' => false, 'message' => 'Transaction ID not found, voided, or invalid for registration.'];
        }

        if ($trx['is_used']) {
            return ['valid' => false, 'message' => 'This receipt has already been used for registration.'];
        }

        // 2. Identify Type based on SKU
        $stmt = $pdo->prepare("
            SELECT p.sku 
            FROM pos_transaction_items i 
            JOIN pos_products p ON i.product_id = p.id 
            WHERE i.transaction_id = ?
        ");
        $stmt->execute([$trx['trx_id']]);
        $skus = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $type = '';
        if (in_array('APP-REG-01', $skus) || in_array('11001', $skus)) {
            $type = 'new_agent';
        } elseif (in_array('APP-PROM-01', $skus) || in_array('1102', $skus)) {
            $type = 'promotion';
        } else {
            return ['valid' => false, 'message' => 'No registration or promotion fee found in this transaction.'];
        }

        // 3. Check Reuse in Agents table
        $stmt = $pdo->prepare("SELECT agent_id FROM agents WHERE transaction_number = ? LIMIT 1");
        $stmt->execute([$trxId]);
        if ($stmt->fetch()) {
            return ['valid' => false, 'message' => 'This Transaction ID has already been recorded in an agent profile.'];
        }

        return ['valid' => true, 'type' => $type];

    } catch (Exception $e) {
        return ['valid' => false, 'message' => 'System Error: ' . $e->getMessage()];
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $trxId = trim($_POST['trx_id'] ?? ''); 
    
    if (empty($trxId)) {
        $error = "Please enter a Transaction ID.";
    } else {
        // Pass $pdo instead of $supabase
        $result = validateTransaction($trxId, $pdo);
        
        if ($result['valid']) {
            $_SESSION['registration_access'] = true;
            $_SESSION['trx_id'] = $trxId;
            $_SESSION['app_type'] = $result['type'];
            header("Location: form.php");
            exit();
        } else {
            $error = $result['message'] ?? "Invalid Transaction ID.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Portal - Pridens</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <div class="landing-wrapper">
        
        <!-- Left Side: Hero / Branding -->
        <div class="landing-hero">
            <div class="hero-content">
                <!-- Branding Image -->
                <div style="margin-bottom: 2rem;">
                    <img src="assets/img/pridens.png" alt="Pridens" style="height: 60px;">
                </div>

                <div class="brand-badge">OFFICIAL AGENT PORTAL</div>
                <h1 class="hero-title">Join the Future,<br>Empower Your Growth.</h1>
                <p class="hero-subtitle">
                    Welcome to the Pridens Agent Portal. Register your account or apply for promotion seamlessly.
                </p>

                <div class="feature-list">
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-bolt"></i></div>
                        <div>
                            <h4 style="margin-bottom: 2px;">Instant Processing</h4>
                            <span style="color: var(--text-muted-dark); font-size: 0.9rem;">Real-time validation of your transaction details.</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SEC Footer -->
            <div style="margin-top: auto; padding-top: 3rem; display: flex; align-items: center; gap: 15px; opacity: 0.8;">
                <img src="assets/img/sec_logo_official.png" alt="SEC Logo" style="height: 50px;">
                <div>
                     <div style="font-size: 0.75rem; color: var(--text-muted-dark); text-transform: uppercase; letter-spacing: 0.5px;">Registered with</div>
                     <div style="font-weight: 700; color: var(--text-on-dark); font-size: 0.9rem;">Securities and Exchange Commission</div>
                     <div style="font-size: 0.8rem; color: var(--text-on-dark);">Company Reg. No.: 2026010231492-15</div>
                </div>
            </div>
        </div>

        <!-- Right Side: Interaction -->
        <div class="landing-form-section">
            <div class="auth-card">
                <div style="text-align: center; margin-bottom: 1rem;">
                     <img src="assets/img/pridens.png" alt="Pridens" style="height: 40px; filter: invert(0) brightness(0.2);">
                </div>
                <h3 style="text-align: center; margin-bottom: 1.5rem; font-size: 1.1rem; color: var(--text-on-light);">Verifying Transaction</h3>
                
                <?php if ($error): ?>
                    <div class="alert"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="trx_id">Transaction ID</label>
                        <div style="position: relative;">
                            <i class="fas fa-receipt" style="position: absolute; top: 50%; left: 15px; transform: translateY(-50%); color: var(--text-muted);"></i>
                            <input type="text" name="trx_id" id="trx_id" class="form-control" placeholder="SI-00000000" style="padding-left: 45px;" required autocomplete="off">
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 10px; margin-top: 1rem;">
                        <button type="submit" class="btn btn-primary" style="flex: 1;">
                            Verify ID <i class="fas fa-arrow-right" style="margin-left: 8px;"></i>
                        </button>
                    </div>

                    <div style="text-align: center; margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #eee;">
                        <span style="font-size: 0.85rem; color: var(--text-muted);">Don't have a transaction ID?</span><br>
                        <a href="initiate_online_pay.php" class="btn btn-outline" style="margin-top: 10px; display: inline-block; width: 100%; text-align: center;">
                            <i class="fas fa-credit-card" style="margin-right: 8px;"></i> Pay Online Registration
                        </a>
                    </div>
                </form>
            </div>
            
            <div style="position: absolute; bottom: 20px; left: 0; right: 0; text-align: center; font-size: 0.8rem; color: var(--text-muted); opacity: 0.6;">
                © 2026 Pridens Trading Corporation
            </div>
        </div>

    </div>

</body>
</html>
