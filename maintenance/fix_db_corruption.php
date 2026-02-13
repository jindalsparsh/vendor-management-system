<?php
/**
 * DATABASE REPAIR SCRIPT
 * Resolves error 1932 (Table doesn't exist in engine) by recreating the suppliers table.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = '127.0.0.1';
$db_name = 'vendor_management';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Attempting to fix 'suppliers' table corruption...\n";

    // 1. Force drop the table (might fail if engine is very confused, but we try)
    try {
        $pdo->exec("DROP TABLE IF EXISTS suppliers");
        echo "Old table dropped (if existed).\n";
    } catch (Exception $e) {
        echo "Warning during drop: " . $e->getMessage() . "\n";
    }

    // 2. Recreate from schema
    $sql = "CREATE TABLE suppliers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        status ENUM('DRAFT', 'SUBMITTED', 'APPROVED_L1', 'APPROVED_L2', 'ACTIVE', 'REJECTED') DEFAULT 'DRAFT',
        rejection_reason TEXT,
        l1_approved_by INT NULL,
        l1_approved_at DATETIME NULL,
        l2_approved_by INT NULL,
        l2_approved_at DATETIME NULL,
        l3_approved_by INT NULL,
        l3_approved_at DATETIME NULL,
        company_name VARCHAR(255) NULL,
        company_address TEXT NULL,
        city VARCHAR(100) NULL,
        state VARCHAR(100) NULL,
        postal_code VARCHAR(20) NULL,
        country VARCHAR(100) NULL,
        supplier_website VARCHAR(255) NULL,
        nature_of_business TEXT NULL, 
        product_services_type VARCHAR(255) NULL,
        market_type ENUM('Domestic', 'International', 'Both') DEFAULT 'Domestic',
        contact_first_name VARCHAR(100) NULL,
        contact_middle_name VARCHAR(100) NULL,
        contact_last_name VARCHAR(100) NULL,
        mobile_number VARCHAR(20) NULL,
        alt_mobile_number VARCHAR(20) NULL,
        landline_number VARCHAR(20) NULL,
        supplier_type VARCHAR(100) NULL,
        item_main_group VARCHAR(100) NULL,
        registered_msme ENUM('Yes', 'No') DEFAULT 'No',
        msme_reg_number VARCHAR(50) NULL,
        msme_type VARCHAR(50) NULL,
        itr_status VARCHAR(50) NULL,
        pan_number VARCHAR(20) NULL,
        under_gst ENUM('Yes', 'No') DEFAULT 'No',
        gst_reg_number VARCHAR(20) NULL, 
        tan_number VARCHAR(20) NULL,
        bank_name VARCHAR(255) NULL,
        account_type VARCHAR(50) NULL,
        account_number VARCHAR(100) NULL,
        ifsc_code VARCHAR(20) NULL,
        bank_branch_address TEXT NULL,
        bank_city VARCHAR(100) NULL,
        bank_state VARCHAR(100) NULL,
        bank_postal_code VARCHAR(20) NULL,
        risk_classification ENUM('Low', 'Medium', 'High') DEFAULT 'Low',
        declaration_accepted TINYINT(1) DEFAULT 0,
        pan_card_doc VARCHAR(255) NULL,
        gst_cert_doc VARCHAR(255) NULL,
        cancelled_cheque_doc VARCHAR(255) NULL,
        msme_cert_doc VARCHAR(255) NULL,
        otp VARCHAR(10) NULL,
        otp_expiry DATETIME NULL,
        l1_comments TEXT NULL,
        l2_comments TEXT NULL,
        l3_comments TEXT NULL,
        has_pan ENUM('Yes', 'No') DEFAULT 'Yes',
        has_gst ENUM('Yes', 'No') DEFAULT 'Yes',
        has_cheque ENUM('Yes', 'No') DEFAULT 'Yes',
        has_msme ENUM('Yes', 'No') DEFAULT 'No',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    $pdo->exec($sql);
    echo "Table 'suppliers' recreated successfully.\n";

    echo "Repair complete. Please try logging in now.\n";

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>