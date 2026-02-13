<?php
require 'db_connect.php';

$email = 'sparsh.jindal@swanrose.co';
$password = 'password';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "<h2>Force-Fixing Login for $email</h2>";

try {
    // 1. Remove any conflicting staff account
    $stmt = $pdo->prepare("DELETE FROM users WHERE username = ?");
    $stmt->execute([$email]);

    // 2. Clear and recreate vendor record with CORRECT column mapping
    // Note: Previous attempts might have swapped fields.
    $stmt = $pdo->prepare("DELETE FROM suppliers WHERE email = ?");
    $stmt->execute([$email]);

    $stmt = $pdo->prepare("INSERT INTO suppliers (company_name, email, password_hash, status) VALUES (?, ?, ?, 'ACTIVE')");
    $stmt->execute(['Swanrose Global', $email, $hash]);

    echo "SUCCESS: Account established as VENDOR-ONLY.<br>";
    echo "Email: $email<br>";
    echo "Password: password<br>";
    echo "<br><b>Please log in at login.php now.</b>";

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage();
}
?>