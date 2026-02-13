<?php
$host = 'localhost';
$db_name = 'vendor_management';
$username = 'root';
$password = '';

echo "Testing connection to $host...\n";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    echo "Connection successful!\n";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
    
    echo "Trying 127.0.0.1...\n";
    try {
        $pdo = new PDO("mysql:host=127.0.0.1;dbname=$db_name", $username, $password);
        echo "Connection to 127.0.0.1 successful!\n";
    } catch (PDOException $e2) {
        echo "Connection to 127.0.0.1 failed: " . $e2->getMessage() . "\n";
    }
}
?>
