<?php
require_once 'admin_API/db.php';
$stmt = $pdo->query("SELECT id, store_name FROM merchants LIMIT 1");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row) {
    echo "ID: {$row['id']} | Name: {$row['store_name']}\n";
} else {
    echo "No merchants found.\n";
}
?>
