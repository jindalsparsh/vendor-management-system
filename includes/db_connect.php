<?php
require_once 'config.php';
require_once 'mail_helper.php';
date_default_timezone_set('Asia/Kolkata');
$host = '127.0.0.1';
$db_name = 'vendor_management';
$username = 'root'; // Update as per local config
$password = ''; // Update as per local config
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Self-Healing Migration: Add DRAFT status if missing
    $stmt = $pdo->query("SHOW COLUMNS FROM suppliers LIKE 'status'");
    $col = $stmt->fetch();
    if ($col && strpos($col['Type'], 'DRAFT') === false) {
        $pdo->exec("ALTER TABLE suppliers MODIFY COLUMN status ENUM('DRAFT', 'SUBMITTED', 'REJECTED', 'APPROVED_L1',
'APPROVED_L2', 'ACTIVE') DEFAULT 'DRAFT'");
    }

    // Add alt_mobile_number if missing
    $stmt = $pdo->query("SHOW COLUMNS FROM suppliers LIKE 'alt_mobile_number'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE suppliers ADD COLUMN alt_mobile_number VARCHAR(20) NULL AFTER mobile_number");
    }

    // Add force_password_change if missing
    $stmt = $pdo->query("SHOW COLUMNS FROM suppliers LIKE 'force_password_change'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE suppliers ADD COLUMN force_password_change TINYINT(1) DEFAULT 0 AFTER declaration_accepted");
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'force_password_change'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN force_password_change TINYINT(1) DEFAULT 0 AFTER role");
    }

    // Add ebs_vendor_code if missing
    $stmt = $pdo->query("SHOW COLUMNS FROM suppliers LIKE 'ebs_vendor_code'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE suppliers ADD COLUMN ebs_vendor_code VARCHAR(50) NULL AFTER l3_comments");
    }
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>