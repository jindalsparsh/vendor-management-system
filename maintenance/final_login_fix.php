<?php
require 'db_connect.php';

$email = 'sparsh.jindal@swanrose.co';
$password = 'password';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "<h2>Fixing login for $email</h2>";

try {
    // 1. Ensure supplier record exists (Vendor View)
    $stmt = $pdo->prepare("INSERT INTO suppliers (email, password_hash, company_name, status) 
                           VALUES (?, ?, 'Swanrose Global', 'ACTIVE')
                           ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), status = 'ACTIVE'");
    $stmt->execute([$email, $hash]);
    echo "SUCCESS: Updated 'suppliers' record.<br>";

    // 2. Ensure user record exists (Staff Dashboard View)
    // Using PURCHASER role by default
    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role) 
                           VALUES (?, ?, 'PURCHASER')
                           ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), role = 'PURCHASER'");
    $stmt->execute([$email, $hash]);
    echo "SUCCESS: Updated 'users' record as PURCHASER.<br>";

    echo "<br><b>Login established!</b><br>";
    echo "Email: $email<br>";
    echo "Password: password<br>";
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage();
}
?>