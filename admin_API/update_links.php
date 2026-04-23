<?php
$dir = 'c:/Pridens_trading_co/admin_folder/';
$files = glob($dir . '*.html');

foreach ($files as $file) {
    if (basename($file) === 'pvoucher_ledger.html') continue;
    
    $content = file_get_contents($file);
    $old = '<li><a href="#" class="sub-item">P-Voucher Ledger</a></li>';
    $new = '<li><a href="pvoucher_ledger.html" class="sub-item">P-Voucher Ledger</a></li>';
    
    if (strpos($content, $old) !== false) {
        $newContent = str_replace($old, $new, $content);
        file_put_contents($file, $newContent);
        echo "Updated $file\n";
    } else {
        echo "Skipped $file (not found)\n";
    }
}
