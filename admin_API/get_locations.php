<?php
// admin_API/get_locations.php
// Provides PH location data for cascading dropdowns
// Uses a comprehensive static JSON mapping Provinces -> Cities -> Barangays.

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$type     = $_GET['type']     ?? 'provinces';
$province = trim($_GET['province'] ?? '');
$city     = trim($_GET['city']     ?? '');

$jsonFile = __DIR__ . '/ph_locations.json';
if (!file_exists($jsonFile)) {
    echo json_encode(['error' => 'Location data not found']);
    exit;
}

$data = json_decode(file_get_contents($jsonFile), true);
if (!is_array($data)) {
    echo json_encode([]);
    exit;
}

// Helper to format names (Title Case, mostly)
function formatName($name) {
    // Keep some acronyms uppercase if needed, but simple ucwords is generally fine
    if ($name === 'NCR' || str_starts_with($name, 'NCR,')) {
        return $name; // Leave NCR keys as is
    }
    return mb_convert_case(strtolower($name), MB_CASE_TITLE, 'UTF-8');
}

// ─── GET PROVINCES ──────────────────────────────────────────────
if ($type === 'provinces') {
    $provinces = [];
    foreach ($data as $regionCode => $region) {
        if (!isset($region['province_list'])) continue;
        foreach ($region['province_list'] as $provName => $provData) {
            $provinces[] = formatName($provName);
        }
    }
    $provinces = array_unique($provinces);
    sort($provinces);
    // Put NCR at the top for convenience
    $ncr = array_filter($provinces, fn($p) => str_starts_with(strtoupper($p), 'NCR'));
    $others = array_filter($provinces, fn($p) => !str_starts_with(strtoupper($p), 'NCR'));
    
    echo json_encode(array_values(array_merge($ncr, $others)));
    exit;
}

// ─── GET CITIES/MUNICIPALITIES ─────────────────────────────────
if ($type === 'cities' && $province !== '') {
    $targetProv = strtoupper($province);
    $cities = [];
    foreach ($data as $region) {
        if (isset($region['province_list'][$targetProv]['municipality_list'])) {
            foreach ($region['province_list'][$targetProv]['municipality_list'] as $cityName => $cityData) {
                $cities[] = formatName($cityName);
            }
        }
    }
    $cities = array_unique($cities);
    sort($cities);
    echo json_encode(array_values($cities));
    exit;
}

// ─── GET BARANGAYS ─────────────────────────────────────────────
if ($type === 'barangays' && $province !== '' && $city !== '') {
    $targetProv = strtoupper($province);
    $targetCity = strtoupper($city);
    $barangays = [];
    foreach ($data as $region) {
        if (isset($region['province_list'][$targetProv]['municipality_list'][$targetCity]['barangay_list'])) {
            foreach ($region['province_list'][$targetProv]['municipality_list'][$targetCity]['barangay_list'] as $brgy) {
                $barangays[] = formatName($brgy);
            }
        }
    }
    $barangays = array_unique($barangays);
    sort($barangays);
    echo json_encode(array_values($barangays));
    exit;
}

echo json_encode([]);
?>
