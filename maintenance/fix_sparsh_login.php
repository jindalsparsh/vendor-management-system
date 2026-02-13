<?php
require 'db_connect.php';

$email = 'sparsh.jindal@swanrose.co';

echo "<h3>Cleaning up users table:</h3>";
$stmt = $pdo->prepare("DELETE FROM users WHERE username = ?");
$stmt->execute([$email]);

if ($stmt->rowCount() > 0) {
    echo "Removed $email from users table. Now they will log in as a Vendor.<br>";
} else {
    echo "$email was not in users table or already removed.<br>";
}

echo "<h3>Ensuring supplier record exists:</h3>";
$stmt = $pdo->prepare("SELECT id FROM suppliers WHERE email = ?");
$stmt->execute([$email]);
$supplier = $stmt->fetch();

if (!$supplier) {
    echo "Creating supplier record for $email...<br>";
    $password_hash = password_hash('password', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO suppliers (company_name, email, password_hash, status) VALUES (?, ?, ?, 'SUBMITTED')");
    $stmt->execute(['Swanrose Global', $email, $password_hash]);
    echo "Supplier record created.<br>";
} else {
    echo "Supplier record already exists.<br>";
}

echo "<h3>Done! Please try logging in as sparsh.jindal@swanrose.co again.</h3>";
?>