<?php
require 'db_connect.php';

echo "<h3>Checking users table:</h3>";
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute(['sparsh.jindal@swanrose.co']);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if ($user) {
    print_r($user);
} else {
    echo "Not found in users table.<br>";
}

echo "<h3>Checking suppliers table:</h3>";
$stmt = $pdo->prepare("SELECT id, company_name, email, status FROM suppliers WHERE email = ?");
$stmt->execute(['sparsh.jindal@swanrose.co']);
$supplier = $stmt->fetch(PDO::FETCH_ASSOC);
if ($supplier) {
    print_r($supplier);
} else {
    echo "Not found in suppliers table.<br>";
}
?>