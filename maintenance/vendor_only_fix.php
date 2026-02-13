<?php
require 'db_connect.php';

$email = 'sparsh.jindal@swanrose.co';
$password = 'password';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "<h2>Restricting $email to VENDOR ONLY</h2>";

try {
    // 1. Remove from 'users' table (Staff/Dashboard)
    $stmt = $pdo->prepare("DELETE FROM users WHERE username = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        echo "SUCCESS: Account removed from internal staff (users) table.<br>";
    } else {
        echo "INFO: Account was not in the internal staff table.<br>";
    }

    // 2. Ensure exists in 'suppliers' table (Vendor Portal)
    $stmt = $pdo->prepare("INSERT INTO suppliers (email, password_hash, company_name, status) 
                           VALUES (?, ?, 'Swanrose Global', 'ACTIVE')
                           ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), status = 'ACTIVE'");
    $stmt->execute([$email, $hash]);
    echo "SUCCESS: Account verified in Supplier Portal table.<br>";

    echo "<br><b>Access Finalized!</b><br>";
    echo "Email: $email<br>";
    echo "Password: password<br>";
    echo "This account now has <b>Vendor-only</b> access.<br>";

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage();
}
?>