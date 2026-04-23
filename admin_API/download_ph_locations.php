<?php
// admin_API/download_ph_locations.php
// Download a well-formatted hierarchical PH locations JSON
// which correctly puts HUCs under their geographic provinces.

$url = 'https://raw.githubusercontent.com/flores-jacob/philippine-regions-provinces-cities-municipalities-barangays/master/philippine_provinces_cities_municipalities_and_barangays_2019v2.json';
// Or we can use another reliable one. The isaaccambron one is very good:
$url = 'https://raw.githubusercontent.com/isaaccambron/philippine-address-selector/master/src/philippine_provinces_cities_municipalities_and_barangays_2019v2.json';

echo "Downloading from: $url\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Local dev bypass
$json = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if ($err) {
    echo "cURL Error: $err\n"; exit;
}

if ($json) {
    file_put_contents(__DIR__ . '/ph_locations.json', $json);
    echo "Successfully downloaded and saved ph_locations.json (" . strlen($json) . " bytes)\n";
} else {
    echo "Failed to get contents.\n";
}
