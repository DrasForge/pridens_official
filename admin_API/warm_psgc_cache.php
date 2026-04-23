<?php
// Pre-warm the PSGC cache: downloads provinces + HUCs + all their cities
// Run once via: php admin_API/warm_psgc_cache.php
// Takes ~1-3 minutes depending on connection speed.

$base     = 'https://psgc.gitlab.io/api';
$cacheDir = __DIR__ . '/psgc_cache/';

function psgcGet(string $url, string $file): ?array {
    if (file_exists($file) && filesize($file) > 10) {
        echo "  [CACHED] $file\n";
        return json_decode(file_get_contents($file), true);
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $body = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($err || $http !== 200 || !$body) {
        echo "  [FAIL] $url — HTTP $http $err\n";
        return null;
    }
    $data = json_decode($body, true);
    if (!is_array($data)) { echo "  [INVALID JSON] $url\n"; return null; }
    file_put_contents($file, $body);
    echo "  [OK] $url → " . count($data) . " items\n";
    return $data;
}

$start = microtime(true);

echo "\n=== Fetching Provinces ===\n";
$provinces = psgcGet("$base/provinces.json", $cacheDir . 'provinces.json');

echo "\n=== Fetching Highly Urbanized Cities ===\n";
$hucs = psgcGet("$base/highly-urbanized-cities.json", $cacheDir . 'hucs.json');

echo "\n=== Fetching Cities/Municipalities per Province ===\n";
if ($provinces) {
    foreach ($provinces as $p) {
        $code = preg_replace('/[^0-9]/', '', $p['code']);
        psgcGet(
            "$base/provinces/$code/cities-municipalities.json",
            $cacheDir . "prov_{$code}_cities.json"
        );
    }
}

echo "\n=== Done! ===\n";
$elapsed = round(microtime(true) - $start, 1);
echo "Total time: {$elapsed}s\n";
echo "Cache directory: $cacheDir\n";
echo "Files cached: " . count(glob($cacheDir . '*.json')) . "\n";
