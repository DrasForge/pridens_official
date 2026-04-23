<?php
$j = file_get_contents('c:/Pridens_trading_co/admin_API/ph_locations.json');
$d = json_decode($j, true);
echo "Root keys: " . implode(', ', array_keys($d)) . "\n";
foreach ($d as $k => $v) {
   if (is_array($v)) {
       echo "Structure of $k:\n";
       echo json_encode(array_slice($v, 0, 1), JSON_PRETTY_PRINT);
       break;
   }
}
