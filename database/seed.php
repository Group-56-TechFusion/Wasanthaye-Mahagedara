<?php
// Creates the test accounts. Safe to run more than once.
try {
    $db = require __DIR__ . '/../config/database.php';

    $exists = $db->prepare('SELECT COUNT(*) FROM users WHERE username = ?');
    $insert = $db->prepare(
        'INSERT INTO users (username, password_hash, role, branch_id) VALUES (?, ?, ?, ?)'
    );

    $users = [
        // username,  role,             branch_id (1 = Nittambuwa, 2 = Kadawatha)
        ['admin1',   'admin',          null],
        ['manager1', 'branch_manager', 1],
        ['manager2', 'branch_manager', 2],
    ];

    foreach ($users as [$username, $role, $branchId]) {
        $exists->execute([$username]);
        if ((int) $exists->fetchColumn() > 0) {
            echo "Skipped $username (already exists)\n";
            continue;
        }
        $insert->execute([$username, password_hash('Test@1234', PASSWORD_DEFAULT), $role, $branchId]);
        echo "Created $username\n";
    }

    echo "Seeded.\n";
} catch (Throwable $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
}