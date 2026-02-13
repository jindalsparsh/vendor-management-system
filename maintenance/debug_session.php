<?php
session_start();
require '../includes/db_connect.php';

echo "<h1>Session Debugger</h1>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

$username = 'admin.jil@swanrose.co';
echo "<h2>Database User Check ($username)</h2>";
$stmt = $pdo->prepare("SELECT id, username, role, password_hash FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<pre>";
print_r($user);
echo "</pre>";

echo "<h2>Cookie Params</h2>";
print_r(session_get_cookie_params());
?>