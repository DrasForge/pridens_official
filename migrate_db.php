<?php
require_once 'admin_API/db.php';
$sql = file_get_contents('admin_API/schema.sql');

try {
    // Split by semicolons, but being careful with some statements.
    // For a simple script, we can just execute the whole thing if the driver supports it.
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, 1);
    $pdo->exec($sql);
    echo "Database schema imported successfully." . PHP_EOL;
} catch (Exception $e) {
    echo "Error importing schema: " . $e->getMessage() . PHP_EOL;
}
?>
