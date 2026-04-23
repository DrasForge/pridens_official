<?php
$jsonFile = __DIR__ . '/ph_locations.json';
$data = json_decode(file_get_contents($jsonFile), true);

$provinces = [];
foreach ($data as $rName => $region) {
    if (isset($region['province_list'])) {
        foreach ($region['province_list'] as $pName => $pV) {
            $provinces[] = $pName;
        }
    }
}
echo "Provinces count: " . count($provinces) . "\n";
if (in_array("NCR", $provinces) || in_array("METRO MANILA", $provinces)) {
    echo "NCR found.\n";
} else {
    echo "NCR NOT found!\n";
}

if (in_array("DAVAO DEL SUR", $provinces)) {
    echo "DDS found.\n";
    $munis = array_keys($data['11']['province_list']['DAVAO DEL SUR']['municipality_list']);
    echo "DDS Cities: " . count($munis) . "\n";
    if (in_array("CITY OF DAVAO", $munis) || in_array("DAVAO CITY", $munis)) {
       echo "Davao City found in DDS.\n";
    }
}
