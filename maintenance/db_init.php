<?php
// JIL-VMS Database Initializer
require '../includes/db_connect.php';

echo "<h1>VMS Database Initializer</h1>";

try {
    $sql = file_get_contents('config/init.sql');
    if (!$sql) {
        die("Error: Could not read config/init.sql");
    }

    // Split SQL into individual statements
    // This is a simple split, works for basic init.sql
    $queries = explode(';', $sql);

    foreach ($queries as $query) {
        $query = trim($query);
        if ($query) {
            $pdo->exec($query);
            echo "<p style='color: green;'>Successfully executed: " . substr($query, 0, 50) . "...</p>";
        }
    }

    echo "<h2 style='color: blue;'>Database Schema Initialized Successfully!</h2>";
    echo "<p><a href='public/login.php'>Go to Login Page</a></p>";

} catch (PDOException $e) {
    echo "<h2 style='color: red;'>Critical Error: " . $e->getMessage() . "</h2>";
    echo "<p>Please ensure your database 'vendor_management' exists or the user has CREATE permissions.</p>";
}
?>