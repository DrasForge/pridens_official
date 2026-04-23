<?php
require_once 'db.php';

try {
    $pdo->beginTransaction();

    // 1. Add "Executive Director" to ranks at rank level 7
    $stmt = $pdo->prepare("SELECT id FROM ranks WHERE rank_name = 'Executive Director'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $pdo->exec("INSERT INTO ranks (rank_name, level, is_auto, is_entry, has_clusters, has_incentives, rules_count) VALUES ('Executive Director', 7, FALSE, FALSE, TRUE, TRUE, 0)");
        echo "Inserted Executive Director rank.\n";
    } else {
        echo "Executive Director rank already exists.\n";
    }

    // 2. Document Templates Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `document_templates` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `template_key` VARCHAR(50) NOT NULL UNIQUE,
            `template_html` LONGTEXT NOT NULL,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    echo "Created document_templates table.\n";

    // Insert Default Templates if they don't exist
    $defaultTnc = "
        <h2>Agent Terms and Conditions</h2>
        <p>This is the formal acknowledgement that {{AGENT_NAME}} is officially promoted to the rank of {{AGENT_RANK}}.</p>
        <p>By accepting this promotion, the agent agrees to adhere to the company's guidelines, maintain expected activity levels, and uphold professional standards.</p>
        <br><br><br>
        <div style='display:flex; justify-content: space-between; margin-top: 50px;'>
            <div style='text-align:center;'>
                <hr style='width:200px; border:1px solid #000;'>
                <strong>{{AGENT_NAME}}</strong><br>
                <span>Agent</span>
            </div>
            <div style='text-align:center;'>
                <hr style='width:200px; border:1px solid #000;'>
                <strong>CEO Name</strong><br>
                <span>Chief Executive Officer</span>
            </div>
        </div>
    ";
    
    $defaultCert = "
        <div style='text-align:center; padding: 50px; border: 10px double #gold; height: 100%; box-sizing: border-box;'>
            <h1 style='font-size: 48px; color: #b8860b;'>Certificate of Excellence</h1>
            <p style='font-size: 20px; margin-top: 40px;'>This certifies that</p>
            <h2 style='font-size: 36px; text-decoration: underline;'>{{AGENT_NAME}}</h2>
            <p style='font-size: 20px; margin-top: 20px;'>has successfully met all qualifications and is hereby promoted to</p>
            <h3 style='font-size: 30px; color: #2c3e50;'>{{AGENT_RANK}}</h3>
            <p style='font-size: 16px; margin-top: 50px;'>Awarded on {{DATE}}</p>
        </div>
    ";

    $stmtInsert = $pdo->prepare("INSERT IGNORE INTO document_templates (template_key, template_html) VALUES (?, ?)");
    $stmtInsert->execute(['terms_and_conditions', trim($defaultTnc)]);
    $stmtInsert->execute(['certificate', trim($defaultCert)]);

    echo "Default templates created.\n";

    $pdo->commit();
    echo "Migration complete!";
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Migration failed: " . $e->getMessage();
}
?>
