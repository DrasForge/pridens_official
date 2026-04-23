<?php
$d = json_decode(file_get_contents('c:/Pridens_trading_co/admin_API/psgc_cache/provinces.json'), true);
foreach ($d as $p) {
    if (stripos($p['name'], 'Davao') !== false) {
        $c = preg_replace('/[^0-9]/', '', $p['code']);
        $cities = @json_decode(file_get_contents('c:/Pridens_trading_co/admin_API/psgc_cache/prov_' . $c . '_cities.json'), true);
        $names = $cities ? array_column($cities, 'name') : [];
        if (in_array('City of Davao', $names) || in_array('Davao City', $names)) {
             echo $p['name'] . " HAS Davao City! (" . count($names) . " total cities/munis)\n";
        } else {
             echo $p['name'] . " (code: ".$p['code'].")\n";
        }
    }
}
$hucs = json_decode(file_get_contents('c:/Pridens_trading_co/admin_API/psgc_cache/hucs.json'), true);
foreach ($hucs as $h) {
    if (stripos($h['name'], 'Davao') !== false) {
        echo "HUC: " . $h['name'] . " (code: ".$h['code'].")\n";
    }
}
