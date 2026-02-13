<?php
require '../includes/db_connect.php';

try {
    $pdo->exec("ALTER TABLE suppliers MODIFY COLUMN status ENUM('DRAFT', 'SUBMITTED', 'REJECTED', 'APPROVED_L1', 'APPROVED_L2', 'ACTIVE') DEFAULT 'DRAFT'");
    echo "Migration successful: DRAFT status added to suppliers table.";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage();
}
?>