<?php
require_once 'admin_API/db.php';

try {
    $stmt = $pdo->query("SELECT account_id, approved_by, insurance_approved_by FROM subscribers LIMIT 5");
    $rows = $stmt->fetchAll();
    echo "Subscribers Content:\n";
    foreach ($rows as $row) {
        print_r($row);
    }
    
    $stmt = $pdo->query("SELECT id, username FROM admins LIMIT 5");
    $rows = $stmt->fetchAll();
    echo "\nAdmins Content:\n";
    foreach ($rows as $row) {
        print_r($row);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
