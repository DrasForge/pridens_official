<?php
require_once 'db.php';
try {
    $pdo->exec("ALTER TABLE pos_transactions ADD COLUMN IF NOT EXISTS is_used BOOLEAN DEFAULT FALSE");
    echo "pos_transactions updated with is_used column.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
