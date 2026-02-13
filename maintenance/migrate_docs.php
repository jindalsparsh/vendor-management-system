<?php
require '../includes/db_connect.php';

try {
    $pdo->exec("ALTER TABLE suppliers 
        ADD COLUMN has_pan ENUM('Yes', 'No') DEFAULT 'Yes' AFTER pan_number,
        ADD COLUMN has_gst ENUM('Yes', 'No') DEFAULT 'Yes' AFTER gst_reg_number,
        ADD COLUMN has_cheque ENUM('Yes', 'No') DEFAULT 'Yes' AFTER ifsc_code,
        ADD COLUMN has_msme ENUM('Yes', 'No') DEFAULT 'No' AFTER msme_reg_number");
    echo "Migration successful: Columns added to suppliers table.";
} catch (PDOException $e) {
    echo "Migration failed or already applied: " . $e->getMessage();
}
?>