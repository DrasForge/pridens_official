<?php
require_once 'admin_API/db.php';

try {
    // 1. Add suffix column to subscribers if not exists
    $pdo->exec("ALTER TABLE subscribers ADD COLUMN IF NOT EXISTS suffix VARCHAR(20) DEFAULT NULL AFTER middle_name");
    echo "Suffix column ensured.\n";

    // 2. Ensure test agent exists for verification
    $stmt = $pdo->prepare("SELECT agent_id FROM agents WHERE agent_id = 'AG-0000001'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        // We omit 'contact' or 'contact_number' since it doesn't exist in the actual schema
        $pdo->exec("INSERT INTO agents (agent_id, first_name, last_name, email, password_hash, status) 
                   VALUES ('AG-0000001', 'Test', 'Agent', 'agent1@example.com', 'hashed_pass', 'Active')");
        echo "Test agent AG-0000001 created.\n";
    } else {
        echo "Test agent already exists.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
