<?php
$d = json_decode(file_get_contents('c:/Pridens_trading_co/admin_API/ph_locations.json'), true);
echo "Cities in DAVAO DEL SUR:\n";
print_r(array_keys($d['11']['province_list']['DAVAO DEL SUR']['municipality_list']));

echo "\nProvinces in NCR:\n";
print_r(array_keys($d['NCR']['province_list']));
