<?php
require_once 'db.php';

try {
    // 1. Get the first subscriber
    $stmt = $pdo->query("SELECT * FROM subscribers ORDER BY created_at ASC LIMIT 1");
    $sub = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sub) {
        die("No subscribers found in the database.");
    }

    echo "Found subscriber: " . $sub['first_name'] . " " . $sub['last_name'] . " (" . $sub['account_id'] . ")\n";

    // 2. Check if already an agent
    $stmt = $pdo->prepare("SELECT agent_id FROM agents WHERE account_id = ? OR email = ?");
    $stmt->execute([$sub['account_id'], $sub['email']]);
    if ($stmt->fetch()) {
        die("This subscriber is already registered as an agent.");
    }

    // 3. Generate Agent ID
    $year = date('Y');
    $agent_id = '';
    $exists = true;
    while ($exists) {
        $rand = str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        $agent_id = "AG-{$year}-{$rand}";
        $check = $pdo->prepare("SELECT agent_id FROM agents WHERE agent_id = ?");
        $check->execute([$agent_id]);
        if (!$check->fetch()) $exists = false;
    }

    // 4. Generate temp password
    $temp_password = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 8);
    $password_hash = password_hash($temp_password, PASSWORD_DEFAULT);

    // 5. Get Sales Agent rank_id
    $stmt = $pdo->query("SELECT id FROM ranks WHERE rank_name = 'Sales Agent' LIMIT 1");
    $rank_id = $stmt->fetchColumn();

    // 6. Insert Agent
    $stmt = $pdo->prepare("
        INSERT INTO agents (
            agent_id, first_name, last_name, email, password_hash, status,
            account_id, middle_name, suffix, birthdate, gender, marital_status,
            occupation, source_of_income, phone, address, province, city,
            barangay, agent_position, rank_id
        ) VALUES (
            ?, ?, ?, ?, ?, 'Active',
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, 'Sales Agent', ?
        )
    ");

    $address_full = $sub['address_house_street'];
    
    $stmt->execute([
        $agent_id, $sub['first_name'], $sub['last_name'], $sub['email'], $password_hash,
        $sub['account_id'], $sub['middle_name'] ?? '', $sub['suffix'] ?? '', $sub['date_of_birth'],
        $sub['gender'], $sub['civil_status'],
        $sub['occupation'], $sub['source_of_income'], $sub['contact_number'],
        $address_full, $sub['address_region'], $sub['address_city'],
        $sub['address_barangay'], $rank_id
    ]);

    echo "===========================================\n";
    echo "Agent Registered Successfully!\n";
    echo "===========================================\n";
    echo "Agent ID:      " . $agent_id . "\n";
    echo "Name:          " . $sub['first_name'] . " " . $sub['last_name'] . "\n";
    echo "Email:         " . $sub['email'] . "\n";
    echo "Temp Password: " . $temp_password . "\n";
    echo "Position:      Sales Agent\n";
    echo "===========================================\n";
    echo "IMPORTANT: Save this password now. It won't be shown again.\n";

} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
}
?>
