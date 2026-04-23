<?php
require_once 'db.php';
$stmt = $pdo->query("SELECT id, username, first_name, last_name FROM admins");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT);
?>
