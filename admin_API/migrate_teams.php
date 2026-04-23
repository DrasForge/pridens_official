<?php
// admin_API/migrate_teams.php
require_once 'db.php';

try {
    // 1. Add is_team_leader to ranks
    try {
        $pdo->exec("ALTER TABLE ranks ADD COLUMN is_team_leader BOOLEAN DEFAULT FALSE");
        echo "Added: is_team_leader to ranks\n";
    } catch (Exception $e) { echo "Skipped: is_team_leader already exists\n"; }

    // 2. Set default team leaders (Level 5 and above)
    $pdo->exec("UPDATE ranks SET is_team_leader = 1 WHERE level >= 5");
    echo "Ranks updated for team leadership.\n";

    // 3. Create Teams table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `teams` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `team_name` VARCHAR(255) NOT NULL,
            `team_goal` TEXT DEFAULT NULL,
            `team_logo` VARCHAR(255) DEFAULT NULL,
            `leader_id` VARCHAR(20) NOT NULL UNIQUE,
            `parent_team_id` INT DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`leader_id`) REFERENCES `agents`(`agent_id`) ON DELETE CASCADE,
            FOREIGN KEY (`parent_team_id`) REFERENCES `teams`(`id`) ON DELETE SET NULL
        )
    ");
    echo "Teams table created.\n";

    // 4. Add team_id to agents
    try {
        $pdo->exec("ALTER TABLE agents ADD COLUMN team_id INT DEFAULT NULL");
        $pdo->exec("ALTER TABLE agents ADD FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE SET NULL");
        echo "Added: team_id to agents\n";
    } catch (Exception $e) { echo "Skipped: team_id already exists or FK error\n"; }

    echo "Migration complete!";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage();
}
?>
