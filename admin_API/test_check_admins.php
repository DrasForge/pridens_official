<?php
require 'c:/Pridens_trading_co/admin_API/db.php';
try {
    $stmt = $pdo->query('SHOW COLUMNS FROM admins');
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo $e->getMessage();
}
