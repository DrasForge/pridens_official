<?php
require_once 'db.php';

try {
    $pdo->exec("
        UPDATE ranks SET level = CASE rank_name
            WHEN 'Executive Director' THEN 1
            WHEN 'Executive Sales Director' THEN 2
            WHEN 'Executive Sales Manager' THEN 3
            WHEN 'Sales Manager' THEN 4
            WHEN 'Sales Agent' THEN 5
            WHEN 'Pre-Agent' THEN 6
            WHEN 'Affiliator' THEN 7
            ELSE level
        END
    ");
    echo "Ranks successfully reordered!";
} catch (Exception $e) {
    echo "Error reordering ranks: " . $e->getMessage();
}
?>
