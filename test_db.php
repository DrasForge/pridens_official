<?php
require_once 'admin_API/db.php';
try {
    $s = $pdo->query('SELECT COUNT(*) FROM admins');
    echo 'Admins table exists. Count: ' . $s->fetchColumn() . PHP_EOL;
    
    $p = $pdo->query('SELECT COUNT(*) FROM pos_products');
    echo 'POS Products table exists. Count: ' . $p->fetchColumn() . PHP_EOL;
    
    $u = $pdo->query("SELECT username FROM admins WHERE username = 'admin'");
    if ($u->fetch()) {
        echo 'User "admin" exists.' . PHP_EOL;
    } else {
        echo 'User "admin" NOT found.' . PHP_EOL;
    }
} catch(Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>
