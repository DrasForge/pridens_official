<?php
// admin_API/migrate_approval_audit.php
require_once 'db.php';

try {
    // 1. Add name columns to admins
    $pdo->exec("ALTER TABLE admins ADD COLUMN IF NOT EXISTS first_name VARCHAR(100) DEFAULT ''");
    $pdo->exec("ALTER TABLE admins ADD COLUMN IF NOT EXISTS last_name VARCHAR(100) DEFAULT ''");

    // 2. Add audit columns to subscribers
    $pdo->exec("ALTER TABLE subscribers ADD COLUMN IF NOT EXISTS approved_by INT DEFAULT NULL");
    $pdo->exec("ALTER TABLE subscribers ADD COLUMN IF NOT EXISTS insurance_approved_by INT DEFAULT NULL");

    // 3. Add foreign keys (wrapped in try-catch in case they already exist)
    try {
        $pdo->exec("ALTER TABLE subscribers ADD CONSTRAINT fk_approved_by FOREIGN KEY (approved_by) REFERENCES admins(id) ON DELETE SET NULL");
    } catch (Exception $e) {}

    try {
        $pdo->exec("ALTER TABLE subscribers ADD CONSTRAINT fk_ins_approved_by FOREIGN KEY (insurance_approved_by) REFERENCES admins(id) ON DELETE SET NULL");
    } catch (Exception $e) {}

    // 4. Update default admin
    $pdo->exec("UPDATE admins SET first_name = 'System', last_name = 'Admin' WHERE username = 'admin' AND (first_name = '' OR first_name IS NULL)");

    echo "Migration successful!";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage();
}
?>
