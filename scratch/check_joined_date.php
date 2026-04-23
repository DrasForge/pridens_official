<?php
require_once 'admin_API/db.php';
$s = $pdo->query('SELECT account_id, joined_date FROM subscribers');
while($r = $s->fetch()) {
    echo "ID: {$r['account_id']} | Joined: {$r['joined_date']}\n";
}
?>
