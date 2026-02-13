<?php
require '../includes/db_connect.php';

echo "--- CHECKING USER ---\n";
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute(['admin.jil@swanrose.co']);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
print_r($user);

echo "\n--- CHECKING SCHEMA ---\n";
$stmt = $pdo->query("DESCRIBE users");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($columns as $col) {
    if ($col['Field'] === 'role') {
        echo "Role Column Type: " . $col['Type'] . "\n";
    }
}
?>