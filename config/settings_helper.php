<?php
// Simple settings storage (JSON file based for prototype flexibility)
$settingsFile = 'config/app_settings.json';

// Default Settings
$defaultSettings = [
    'store_name' => 'Toko Kopi',
    'store_address' => 'Jl. Kopi No. 1, Jakarta',
    'store_phone' => '0812-3456-7890',
    'tax_rate' => 10,
    'footer_note' => 'Terima Kasih atas Kunjungan Anda!',
    'currency_symbol' => 'Rp',
    'theme_color' => '#D4A373',
    'ga_measurement_id' => 'G-HQM17DJ1JW' 
];

// Load Settings
$appSettings = $defaultSettings;
if (file_exists($settingsFile)) {
    $loaded = json_decode(file_get_contents($settingsFile), true);
    $appSettings = array_merge($defaultSettings, $loaded);
}

// Function to get a setting
function getSetting($key) {
    global $appSettings;
    return isset($appSettings[$key]) ? $appSettings[$key] : '';
}
?>

