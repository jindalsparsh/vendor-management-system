<?php
require 'db_connect.php';

echo "<h2>Latest Supplier</h2>";
$stmt = $pdo->query("SELECT id, company_name, email, status, created_at FROM suppliers ORDER BY id DESC LIMIT 5");
$suppliers = $stmt->fetchAll();
echo "<pre>";
print_r($suppliers);
echo "</pre>";

echo "<h2>Staff Accounts</h2>";
$stmt = $pdo->query("SELECT id, username, role FROM users");
$users = $stmt->fetchAll();
echo "<pre>";
print_r($users);
echo "</pre>";
?>