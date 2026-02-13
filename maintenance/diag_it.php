<?php
require '../includes/db_connect.php';

echo "<h2>Supplier Status Count</h2>";
$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM suppliers GROUP BY status");
while ($row = $stmt->fetch()) {
    echo "Status: " . $row['status'] . " - Count: " . $row['count'] . "<br>";
}

echo "<h2>Users with IT Role</h2>";
$stmt = $pdo->query("SELECT id, username, role FROM users WHERE role = 'IT'");
while ($row = $stmt->fetch()) {
    echo "ID: " . $row['id'] . " - Username: " . $row['username'] . " - Role: " . $row['role'] . "<br>";
}

echo "<h2>Suppliers at APPROVED_L2 stage</h2>";
$stmt = $pdo->query("SELECT id, company_name, status FROM suppliers WHERE status = 'APPROVED_L2'");
while ($row = $stmt->fetch()) {
    echo "ID: " . $row['id'] . " - Company: " . $row['company_name'] . " - Status: " . $row['status'] . "<br>";
}
?>