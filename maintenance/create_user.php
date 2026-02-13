<?php
require 'db_connect.php';

$email = 'sparsh.jindal@swanrose.co';
$password = 'password';
$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    // 1. Add to suppliers (Vendor access)
    $stmt = $pdo->prepare("INSERT INTO suppliers (email, password_hash, company_name, status) 
                           VALUES (?, ?, 'Swanrose IT Solutions', 'ACTIVE')
                           ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), status = 'ACTIVE'");
    $stmt->execute([$email, $hash]);
    echo "SUCCESS: User added to 'suppliers' table.\n";

    // 2. Add to users (Staff/Dashboard access) - Role: PURCHASER by default
    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role) 
                           VALUES (?, ?, 'PURCHASER')
                           ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), role = 'PURCHASER'");
    $stmt->execute([$email, $hash]);
    echo "SUCCESS: User added to 'users' table as PURCHASER.\n";

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>