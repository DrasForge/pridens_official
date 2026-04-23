<?php
require_once 'db.php';

try {
    $sqls = [
        "ALTER TABLE ranks 
         ADD COLUMN IF NOT EXISTS promotion_type ENUM('auto', 'paid', 'appointed') DEFAULT 'auto' AFTER rank_name,
         ADD COLUMN IF NOT EXISTS is_team_leader BOOLEAN DEFAULT FALSE AFTER promotion_type,
         ADD COLUMN IF NOT EXISTS rank_order INT DEFAULT 0 AFTER is_team_leader,
         ADD COLUMN IF NOT EXISTS is_default BOOLEAN DEFAULT FALSE AFTER rank_order,
         ADD COLUMN IF NOT EXISTS card_theme TEXT DEFAULT NULL AFTER is_default",

        "SET @row_number = 0",
        // Multi-line update in one go is tricky with PDO sometimes, lets do it separately if needed
        "UPDATE ranks SET rank_order = (@row_number:=@row_number + 1) ORDER BY level ASC",

        "CREATE TABLE IF NOT EXISTS rank_qualifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            rank_id INT NOT NULL,
            qualification_type VARCHAR(100) NOT NULL,
            value INT NOT NULL,
            plan_id INT DEFAULT NULL,
            target_rank_id INT DEFAULT NULL,
            is_direct BOOLEAN DEFAULT TRUE,
            max_levels INT DEFAULT NULL,
            FOREIGN KEY (rank_id) REFERENCES ranks(id) ON DELETE CASCADE
        )",

        "CREATE TABLE IF NOT EXISTS rank_clusters (
            id INT AUTO_INCREMENT PRIMARY KEY,
            rank_id INT NOT NULL,
            subordinate_rank_id INT NOT NULL,
            plan_id INT DEFAULT NULL,
            min_groups INT DEFAULT 1,
            min_members_per_group INT DEFAULT 1,
            group_quota INT DEFAULT 0,
            group_incentive DECIMAL(15, 2) DEFAULT 0.00,
            start_day INT DEFAULT 1,
            end_day INT DEFAULT 31,
            allow_multipayout BOOLEAN DEFAULT FALSE,
            is_group_count BOOLEAN DEFAULT TRUE,
            individual_peer_boost_incentive DECIMAL(15, 2) DEFAULT 0.00,
            leader_leg_bonus_incentive DECIMAL(15, 2) DEFAULT 0.00,
            boosted_subsub_incentive DECIMAL(15, 2) DEFAULT 0.00,
            FOREIGN KEY (rank_id) REFERENCES ranks(id) ON DELETE CASCADE
        )",

        "CREATE TABLE IF NOT EXISTS rank_incentive_config (
            id INT AUTO_INCREMENT PRIMARY KEY,
            rank_id INT NOT NULL,
            plan_id INT NOT NULL,
            monthly_quota INT DEFAULT 0,
            base_incentive DECIMAL(15, 2) DEFAULT 0.00,
            quota_50 INT DEFAULT 0,
            incentive_50 DECIMAL(15, 2) DEFAULT 0.00,
            quota_10 INT DEFAULT 0,
            incentive_10 DECIMAL(15, 2) DEFAULT 0.00,
            allow_multipayout BOOLEAN DEFAULT FALSE,
            count_downline BOOLEAN DEFAULT TRUE,
            start_day INT DEFAULT 1,
            end_day INT DEFAULT 31,
            FOREIGN KEY (rank_id) REFERENCES ranks(id) ON DELETE CASCADE
        )",

        "CREATE TABLE IF NOT EXISTS rank_incentive_tiers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            rank_name VARCHAR(100) NOT NULL,
            quota INT DEFAULT 0,
            incentive DECIMAL(15, 2) DEFAULT 0.00,
            is_shared BOOLEAN DEFAULT FALSE
        )"
    ];

    foreach ($sqls as $sql) {
        $pdo->exec($sql);
        echo "Executed: " . substr($sql, 0, 50) . "...\n";
    }

    echo "Migration completed successfully.";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
