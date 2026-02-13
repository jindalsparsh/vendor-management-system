<?php
require 'db_connect.php';

$email = 'sparsh.jindal@swanrose.co';
$password = 'password123';
$company = 'Swanrose Demo';

$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    // Check if exists first
    $check = $pdo->prepare("SELECT id FROM suppliers WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        $stmt = $pdo->prepare("UPDATE suppliers SET password_hash = ?, company_name = ? WHERE email = ?");
        $stmt->execute([$hash, $company, $email]);
        echo "SUCCESS: Test supplier UPDATED.\n";
    } else {
        $stmt = $pdo->prepare("INSERT INTO suppliers (email, password_hash, company_name, status) VALUES (?, ?, ?, 'ACTIVE')");
        $stmt->execute([$email, $hash, $company]);
        echo "SUCCESS: Test supplier CREATED.\n";
    }
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>