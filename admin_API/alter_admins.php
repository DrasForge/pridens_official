<?php
require 'db.php';
try {
    $pdo->exec("ALTER TABLE admins ADD COLUMN first_name VARCHAR(100) DEFAULT 'Admin'");
    $pdo->exec("ALTER TABLE admins ADD COLUMN last_name VARCHAR(100) DEFAULT 'User'");
    echo "Added columns to admins table.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
