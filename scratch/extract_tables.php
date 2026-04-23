<?php
$dir = 'admin_API';
$files = glob("$dir/*.php");

$tables_cols = [];

foreach ($files as $file) {
    if (basename($file) === 'schema.sql' || basename($file) === 'db.php') continue;
    
    $content = file_get_contents($file);
    
    // Simple regex to find SELECT from tables
    if (preg_match_all('/SELECT\s+.*?\s+FROM\s+([a-zA-Z0-9_]+)/si', $content, $matches)) {
        foreach ($matches[1] as $table) {
            $tables_cols[$table] = $tables_cols[$table] ?? [];
        }
    }
    
    // Find aliases and columns
    // e.g. SELECT s.column, a.column FROM subscribers s LEFT JOIN admins a
    if (preg_match_all('/SELECT\s+(.*?)\s+FROM/si', $content, $matches)) {
        // This is complex to parse with regex, but we can look for specific patterns
    }
}

print_r(array_keys($tables_cols));
?>
