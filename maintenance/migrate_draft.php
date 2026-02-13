<?php
require '../includes/db_connect.php';

try {
    $pdo->exec("ALTER TABLE suppliers MODIFY COLUMN status ENUM('DRAFT', 'SUBMITTED', 'REJECTED', 'APPROVED_L1', 'APPROVED_L2', 'ACTIVE') DEFAULT 'DRAFT'");
    echo "<h1>Migration successful!</h1><p>DRAFT status has been added to the suppliers table.</p>";
} catch (PDOException $e) {
    echo "<h1>Migration failed</h1><p>Error: " . $e->getMessage() . "</p>";
}
?>