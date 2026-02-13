<?php
require 'db_connect.php';
try {
    $pdo->exec("ALTER TABLE suppliers 
        ADD COLUMN l1_comments TEXT NULL,
        ADD COLUMN l2_comments TEXT NULL,
        ADD COLUMN l3_comments TEXT NULL");
    echo "Migration successful: Comments columns added.";
} catch (PDOException $e) {
    echo "Migration error (might already exist): " . $e->getMessage();
}
?>