<?php
require_once 'db.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    // 1. Expand agents table
    $columns = [
        "account_id VARCHAR(20) DEFAULT NULL",
        "transaction_number VARCHAR(50) DEFAULT NULL UNIQUE",
        "middle_name VARCHAR(100) DEFAULT NULL",
        "suffix VARCHAR(20) DEFAULT NULL",
        "birthdate DATE DEFAULT NULL",
        "gender VARCHAR(10) DEFAULT NULL",
        "marital_status VARCHAR(20) DEFAULT NULL",
        "occupation VARCHAR(100) DEFAULT NULL",
        "source_of_income VARCHAR(100) DEFAULT NULL",
        "phone VARCHAR(20) DEFAULT NULL",
        "address TEXT DEFAULT NULL",
        "province VARCHAR(100) DEFAULT NULL",
        "city VARCHAR(100) DEFAULT NULL",
        "barangay VARCHAR(100) DEFAULT NULL",
        "agent_position VARCHAR(50) DEFAULT 'Sales Agent'",
        "rank_id INT DEFAULT NULL",
        "payment_status ENUM('unpaid', 'paid') DEFAULT 'paid'"
    ];

    foreach ($columns as $col) {
        list($colName) = explode(" ", $col);
        try {
            $pdo->exec("ALTER TABLE agents ADD COLUMN $col");
            echo "Added: $colName\n";
        } catch (Exception $e) {
            echo "Error/Exists ($colName): " . $e->getMessage() . "\n";
        }
    }

    // 2. Set default rank_id for existing agents if possible
    $stmt = $pdo->query("SELECT id FROM ranks WHERE rank_name = 'Sales Agent' LIMIT 1");
    $rankId = $stmt->fetchColumn();
    if ($rankId) {
        $pdo->exec("UPDATE agents SET rank_id = $rankId WHERE rank_id IS NULL");
        echo "Ranks initialized.\n";
    }

    echo "Migration complete!";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage();
}
?>
