<?php
$ch = curl_init('https://psgc.gitlab.io/api/provinces.json');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$body = curl_exec($ch);
$err  = curl_error($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($err) { echo "cURL Error: $err\n"; exit(1); }
$data = json_decode($body, true);
echo "HTTP: $http\n";
echo "Total provinces: " . count($data) . "\n";
echo "Sample: " . $data[0]['name'] . "\n";
