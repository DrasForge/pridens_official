<?php
// migrate_agents.php
// Adds necessary columns to the 'agents' table for the Pridens Agent Portal

require_once 'config.php';

echo "<h2>Migrating Agents Table...</h2>";

try {
    $commands = [
        "ALTER TABLE agents ADD COLUMN IF NOT EXISTS transaction_number VARCHAR(50) UNIQUE",
        "ALTER TABLE agents ADD COLUMN IF NOT EXISTS phone VARCHAR(20)",
        "ALTER TABLE agents ADD COLUMN IF NOT EXISTS address TEXT",
        "ALTER TABLE agents ADD COLUMN IF NOT EXISTS birthdate DATE",
        "ALTER TABLE agents ADD COLUMN IF NOT EXISTS gender VARCHAR(10)",
        "ALTER TABLE agents ADD COLUMN IF NOT EXISTS marital_status VARCHAR(20)",
        "ALTER TABLE agents ADD COLUMN IF NOT EXISTS occupation VARCHAR(100)",
        "ALTER TABLE agents ADD COLUMN IF NOT EXISTS source_of_income VARCHAR(100)",
        "ALTER TABLE agents ADD COLUMN IF NOT EXISTS referral_code VARCHAR(50)",
        "ALTER TABLE agents ADD COLUMN IF NOT EXISTS account_id VARCHAR(50)",
        "ALTER TABLE agents ADD COLUMN IF NOT EXISTS agent_position VARCHAR(50)",
        "ALTER TABLE agents ADD COLUMN IF NOT EXISTS province VARCHAR(100)",
        "ALTER TABLE agents ADD COLUMN IF NOT EXISTS city VARCHAR(100)",
        "ALTER TABLE agents ADD COLUMN IF NOT EXISTS barangay VARCHAR(100)",
        "ALTER TABLE agents ADD COLUMN IF NOT EXISTS payment_id VARCHAR(100)",
        "ALTER TABLE agents ADD COLUMN IF NOT EXISTS payment_status VARCHAR(50) DEFAULT 'unpaid'"
    ];

    foreach ($commands as $sql) {
        try {
            $pdo->exec($sql);
            echo "<div style='color:green'>✔ Executed: $sql</div>";
        } catch (PDOException $e) {
            echo "<div style='color:orange'>⚠ Notice: " . $e->getMessage() . "</div>";
        }
    }

    echo "<h3>Migration Complete.</h3>";
    echo "<p>Please delete this file after use.</p>";

} catch (Exception $e) {
    echo "<div style='color:red'>❌ Fatal Error: " . $e->getMessage() . "</div>";
}
?>
