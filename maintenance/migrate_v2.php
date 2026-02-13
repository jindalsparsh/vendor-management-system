<?php
require 'db_connect.php';

try {
    // Add new columns to suppliers table
    $sql = "ALTER TABLE suppliers 
            ADD COLUMN IF NOT EXISTS risk_classification ENUM('Low', 'Medium', 'High') DEFAULT 'Low',
            ADD COLUMN IF NOT EXISTS pan_card_doc VARCHAR(255) NULL,
            ADD COLUMN IF NOT EXISTS gst_cert_doc VARCHAR(255) NULL,
            ADD COLUMN IF NOT EXISTS cancelled_cheque_doc VARCHAR(255) NULL,
            ADD COLUMN IF NOT EXISTS msme_cert_doc VARCHAR(255) NULL,
            ADD COLUMN IF NOT EXISTS declaration_accepted TINYINT(1) DEFAULT 0";

    $pdo->exec($sql);
    echo "Successfully updated suppliers table schema.<br>";

    // Create uploads directory if it doesn't exist
    $upload_dir = 'uploads';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
        echo "Created 'uploads' directory.<br>";
    }

} catch (PDOException $e) {
    echo "Error updating database: " . $e->getMessage() . "<br>";
}
?>