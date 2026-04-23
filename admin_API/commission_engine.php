<?php
// admin_API/commission_engine.php
require_once 'db.php';

class CommissionEngine {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Traverses the upline using agents.referral_code to return up to $maxLevels of sponsors.
     * Returns array where index 0 is Level 1, index 1 is Level 2, etc. array mapping level => agent_id.
     */
    private function getUplineChain($directAgentId, $maxLevels = 5) {
        $upline = [];
        $currentAgent = $directAgentId;
        
        for ($i = 0; $i < $maxLevels; $i++) {
            if (!$currentAgent) break;
            
            $upline[$i + 1] = $currentAgent; // 1-indexed for levels
            
            // Find next upline
            $stmt = $this->pdo->prepare("SELECT referral_code FROM agents WHERE agent_id = ? LIMIT 1");
            $stmt->execute([$currentAgent]);
            $currentAgent = $stmt->fetchColumn(); 
        }
        
        return $upline;
    }

    /**
     * Triggers when subscriber status = Active
     */
    public function distributeOneTime($subscriberAccountId, $planId, $directAgentId) {
        // Fetch OneTime Comm values
        $stmtPlan = $this->pdo->prepare("SELECT otc_l1, otc_l2, otc_l3, otc_l4, otc_l5 FROM subscription_plans WHERE id = ?");
        $stmtPlan->execute([$planId]);
        $plan = $stmtPlan->fetch(PDO::FETCH_ASSOC);
        
        if (!$plan) return false;

        $uplines = $this->getUplineChain($directAgentId, 5);

        for ($level = 1; $level <= 5; $level++) {
            if (isset($uplines[$level])) {
                $amount = floatval($plan["otc_l$level"] ?? 0);
                if ($amount > 0) {
                    $status = ($level === 1) ? 'Outright' : 'Unpaid';
                    $this->insertCommission($uplines[$level], $subscriberAccountId, null, 'One-Time', $level, $amount, $status);
                }
            }
        }
        return true;
    }

    /**
     * Triggers when monthly payment is posted
     */
    public function distributeMonthly($subscriberAccountId, $transactionId, $planId, $isOnTime, $directAgentId) {
        // Fetch Monthly values
        $stmtPlan = $this->pdo->prepare("SELECT residual_l1, residual_l2, residual_l3, residual_l4, monthly_collection_fee FROM subscription_plans WHERE id = ?");
        $stmtPlan->execute([$planId]);
        $plan = $stmtPlan->fetch(PDO::FETCH_ASSOC);
        
        if (!$plan) return false;

        $uplines = $this->getUplineChain($directAgentId, 4);

        // 1. Level-Based Residuals
        for ($level = 1; $level <= 4; $level++) {
            if (isset($uplines[$level])) {
                $amount = floatval($plan["residual_l$level"] ?? 0);
                if ($amount > 0) {
                    $this->insertCommission($uplines[$level], $subscriberAccountId, $transactionId, 'Residual', $level, $amount, 'Unpaid');
                }
            }
        }

        // 2. Collection Fee (1st Level only, if on time)
        if ($isOnTime && isset($uplines[1])) {
            $colFee = floatval($plan['monthly_collection_fee']);
            if ($colFee > 0) {
                // Outright or unpaid? Prompt says same as 1st level residual monthly, so we assume Unpaid like above.
                $this->insertCommission($uplines[1], $subscriberAccountId, $transactionId, 'Collection Fee', null, $colFee, 'Unpaid');
            }
        }

        // 3. Position-Based Residuals
        // Fetch the config mapping Rank -> Amount
        $stmtPos = $this->pdo->prepare("SELECT rank_name, amount FROM plan_position_residuals WHERE plan_id = ?");
        $stmtPos->execute([$planId]);
        $posResiduals = $stmtPos->fetchAll(PDO::FETCH_KEY_PAIR); // ['Rank Name' => amount]

        if (!empty($posResiduals)) {
            // Traverse full upline indefinitely until root
            $currentAgent = $directAgentId;
            $safetyNet = 0;
            // Ensure we don't grant position residual to someone matching a rank if a lower upline already consumed that rank
            $awardedRanks = []; 
            
            while ($currentAgent && $safetyNet < 100) {
                $stmtRank = $this->pdo->prepare("
                    SELECT a.referral_code, r.rank_name, r.level
                    FROM agents a
                    LEFT JOIN ranks r ON a.rank_id = r.id
                    WHERE a.agent_id = ?
                ");
                $stmtRank->execute([$currentAgent]);
                $agentData = $stmtRank->fetch(PDO::FETCH_ASSOC);
                
                if (!$agentData) break;
                
                $rName = $agentData['rank_name'];
                
                // If this agent's rank is in the config and we haven't paid this rank tier yet to a closer upline
                if (isset($posResiduals[$rName]) && !isset($awardedRanks[$rName])) {
                    $amt = floatval($posResiduals[$rName]);
                    if ($amt > 0) {
                        $this->insertCommission($currentAgent, $subscriberAccountId, $transactionId, 'Position-Based', null, $amt, 'Unpaid');
                    }
                    $awardedRanks[$rName] = true;
                }
                
                $currentAgent = $agentData['referral_code'];
                $safetyNet++;
            }
        }

        return true;
    }

    private function insertCommission($agentId, $sourceAccountId, $txId, $type, $level, $amount, $status) {
        $stmt = $this->pdo->prepare("
            INSERT INTO agent_commissions (agent_id, source_account_id, transaction_id, commission_type, level, amount, status)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$agentId, $sourceAccountId, $txId, $type, $level, $amount, $status]);
    }
}
?>
