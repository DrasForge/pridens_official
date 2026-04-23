<?php
// admin_API/migrate_agent_promotions.php
require_once 'db.php';

try {
    // 1. Add rank_promoted_at to agents
    try {
        $pdo->exec("ALTER TABLE agents ADD COLUMN rank_promoted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER rank_id");
        echo "Added: rank_promoted_at to agents\n";
    } catch (Exception $e) { 
        echo "Skipped: rank_promoted_at already exists\n"; 
    }

    // 2. Set existing agents' rank_promoted_at to their created_at if possible (to avoid breaking things)
    // Actually letting it default to CURRENT_TIMESTAMP from the alter table is safe enough for legacy data.
    $pdo->exec("UPDATE agents SET rank_promoted_at = created_at WHERE rank_promoted_at IS NULL AND created_at IS NOT NULL");

    // 3. Create Agent Promotions Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `agent_promotions` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `agent_id` VARCHAR(20) NOT NULL,
            `current_rank_id` INT NOT NULL,
            `target_rank_id` INT NOT NULL,
            `status` ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
            `reviewed_by` INT DEFAULT NULL,
            `review_notes` TEXT DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`agent_id`) REFERENCES `agents`(`agent_id`) ON DELETE CASCADE,
            FOREIGN KEY (`current_rank_id`) REFERENCES `ranks`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`target_rank_id`) REFERENCES `ranks`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`reviewed_by`) REFERENCES `admins`(`id`) ON DELETE SET NULL
        )
    ");
    echo "agent_promotions table created.\n";

    echo "Migration complete!";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage();
}
?>
