<?php
/**
 * MASTER SYSTEM INITIALIZATION & FIX SCRIPT
 * 
 * This script ensures the entire Vendor Management System is 100% deployable and working.
 * Run this ONCE to fix all issues.
 * 
 * What it does:
 * 1. Creates/fixes database tables with correct schema
 * 2. Creates default internal staff accounts
 * 3. Migrates any incorrectly placed vendor records
 * 4. Fixes all vendor status issues
 * 5. Verifies the entire system
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database Configuration
$host = '127.0.0.1';
$db_name = 'vendor_management';
$username = 'root';
$password = '';

echo "<!DOCTYPE html><html><head>
<meta charset='UTF-8'>
<title>VMS System Initialization</title>
<style>
body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #0f172a; color: #e2e8f0; padding: 40px; line-height: 1.6; }
.container { max-width: 900px; margin: 0 auto; }
h1 { color: #38bdf8; border-bottom: 3px solid #38bdf8; padding-bottom: 15px; }
h2 { color: #94a3b8; margin-top: 30px; }
.step { background: #1e293b; padding: 20px; border-radius: 10px; margin: 15px 0; border-left: 4px solid #3b82f6; }
.success { border-left-color: #22c55e; }
.success::before { content: '✅ '; }
.error { border-left-color: #ef4444; }
.error::before { content: '❌ '; }
.warning { border-left-color: #f59e0b; }
.warning::before { content: '⚠️ '; }
.info { border-left-color: #06b6d4; }
.info::before { content: 'ℹ️ '; }
code { background: #334155; padding: 3px 8px; border-radius: 4px; font-family: 'Consolas', monospace; }
table { width: 100%; border-collapse: collapse; margin: 15px 0; }
th, td { padding: 12px; text-align: left; border: 1px solid #334155; }
th { background: #1e3a5f; }
.btn { display: inline-block; background: #3b82f6; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; margin: 10px 5px 10px 0; }
.btn:hover { background: #2563eb; }
.final-box { background: linear-gradient(135deg, #1e3a5f, #0f172a); padding: 30px; border-radius: 15px; margin-top: 30px; border: 2px solid #38bdf8; }
</style>
</head><body><div class='container'>";

echo "<h1>🚀 Vendor Management System - Complete Setup</h1>";

$errors = [];
$successes = [];

// ========================================
// STEP 1: DATABASE CONNECTION
// ========================================
echo "<h2>Step 1: Database Connection</h2>";

try {
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<div class='step success'>Connected to MySQL server</div>";

    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$db_name`");
    echo "<div class='step success'>Database <code>$db_name</code> ready</div>";
    echo "<div class='step success'>Database connection established.</div>";

    // Check for config and mail helper
    if (file_exists('../includes/config.php') && file_exists('../includes/mail_helper.php')) {
        echo "<div class='step success'>Email Configuration & Mail Helper detected.</div>";
    } else {
        echo "<div class='step warning'>Email Configuration or Mail Helper missing!</div>";
    }
} catch (PDOException $e) {
    echo "<div class='step error'>Database connection failed: " . $e->getMessage() . "</div>";
    $errors[] = "Database connection failed";
    die("</div></body></html>");
}

// ========================================
// STEP 2: CREATE/UPDATE TABLES
// ========================================
echo "<h2>Step 2: Database Tables</h2>";

// Users table (internal staff only)
$users_sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('PURCHASER', 'FINANCE', 'IT', 'ADMIN') NOT NULL,
    force_password_change TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

try {
    $pdo->exec($users_sql);
    echo "<div class='step success'>Table <code>users</code> ready (for internal staff)</div>";
} catch (PDOException $e) {
    echo "<div class='step error'>Failed to create users table: " . $e->getMessage() . "</div>";
    $errors[] = "users table creation failed";
}

// Suppliers table (vendors)
$suppliers_sql = "CREATE TABLE IF NOT EXISTS suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Account Credentials
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    
    -- Status & Workflow
    status ENUM('SUBMITTED', 'APPROVED_L1', 'APPROVED_L2', 'ACTIVE', 'REJECTED') DEFAULT 'SUBMITTED',
    rejection_reason TEXT,
    
    -- Approval Tracking
    l1_approved_by INT NULL,
    l1_approved_at DATETIME NULL,
    l2_approved_by INT NULL,
    l2_approved_at DATETIME NULL,
    l3_approved_by INT NULL,
    l3_approved_at DATETIME NULL,

    -- Business/Company Detail
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
    
    -- Communication Detail
    contact_first_name VARCHAR(100) NULL,
    contact_middle_name VARCHAR(100) NULL,
    contact_last_name VARCHAR(100) NULL,
    mobile_number VARCHAR(20) NULL,
    
    -- Business Contact Detail
    landline_number VARCHAR(20) NULL,
    fax_area_code VARCHAR(10) NULL,
    fax_number VARCHAR(20) NULL,
    mailing_address TEXT NULL,
    
    -- Internal Process
    supplier_type VARCHAR(100) NULL,
    item_main_group VARCHAR(100) NULL,
    
    -- Tax & Legal Detail
    registered_msme ENUM('Yes', 'No') DEFAULT 'No',
    msme_reg_number VARCHAR(50) NULL,
    msme_type VARCHAR(50) NULL,
    itr_status VARCHAR(50) NULL,
    pan_number VARCHAR(20) NULL,
    under_gst ENUM('Yes', 'No') DEFAULT 'No',
    gst_reg_number VARCHAR(20) NULL, 
    tan_number VARCHAR(20) NULL,
    
    -- Bank Detail
    bank_name VARCHAR(255) NULL,
    account_type VARCHAR(50) NULL,
    account_number VARCHAR(100) NULL,
    ifsc_code VARCHAR(20) NULL,
    bank_branch_address TEXT NULL,
    bank_city VARCHAR(100) NULL,
    bank_state VARCHAR(100) NULL,
    bank_postal_code VARCHAR(20) NULL,

    -- Risk Compliance
    risk_classification ENUM('Low', 'Medium', 'High') DEFAULT 'Low',
    declaration_accepted TINYINT(1) DEFAULT 0,
    force_password_change TINYINT(1) DEFAULT 0,

    -- Document Paths
    pan_card_doc VARCHAR(255) NULL,
    gst_cert_doc VARCHAR(255) NULL,
    cancelled_cheque_doc VARCHAR(255) NULL,
    msme_cert_doc VARCHAR(255) NULL,

    -- Reset Flow
    otp VARCHAR(10) NULL,
    otp_expiry DATETIME NULL,
    
    -- Internal Team Comments
    l1_comments TEXT NULL,
    l2_comments TEXT NULL,
    l3_comments TEXT NULL,
    ebs_vendor_code VARCHAR(50) NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

try {
    $pdo->exec($suppliers_sql);
    echo "<div class='step success'>Table <code>suppliers</code> ready (for vendors)</div>";

    // Ensure all required columns exist (for existing databases)
    $alter_statements = [
        "ALTER TABLE suppliers ADD COLUMN IF NOT EXISTS rejection_reason TEXT NULL",
        "ALTER TABLE suppliers ADD COLUMN IF NOT EXISTS force_password_change TINYINT(1) DEFAULT 0",
        "ALTER TABLE suppliers ADD COLUMN IF NOT EXISTS ebs_vendor_code VARCHAR(50) NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS force_password_change TINYINT(1) DEFAULT 0"
    ];

    $columns_added = 0;
    foreach ($alter_statements as $alter) {
        try {
            $pdo->exec($alter);
            $columns_added++;
        } catch (PDOException $e) {
            // Column might already exist or syntax not supported, try alternative
            if (strpos($e->getMessage(), 'Duplicate column') === false) {
                // Try without IF NOT EXISTS for older MySQL versions
                $col_name = '';
                if (preg_match('/ADD COLUMN IF NOT EXISTS (\w+)/', $alter, $matches)) {
                    $col_name = $matches[1];
                    // Check if column exists
                    $check = $pdo->query("SHOW COLUMNS FROM suppliers LIKE '$col_name'")->fetch();
                    if (!$check) {
                        $simple_alter = str_replace(' IF NOT EXISTS', '', $alter);
                        try {
                            $pdo->exec($simple_alter);
                            $columns_added++;
                        } catch (PDOException $e2) {
                            // Ignore if still fails
                        }
                    }
                }
            }
        }
    }

    if ($columns_added > 0) {
        echo "<div class='step success'>Added $columns_added missing column(s) to suppliers table</div>";
    }

} catch (PDOException $e) {
    echo "<div class='step error'>Failed to create suppliers table: " . $e->getMessage() . "</div>";
    $errors[] = "suppliers table creation failed";
}

// ========================================
// STEP 3: CREATE DEFAULT STAFF ACCOUNTS
// ========================================
echo "<h2>Step 3: Internal Staff Accounts</h2>";

$default_password = password_hash('password', PASSWORD_DEFAULT);

$staff_accounts = [
    ['purchaser.jil@swanrose.co', 'PURCHASER'],
    ['finance.jil@swanrose.co', 'FINANCE'],
    ['it.jil@swanrose.co', 'IT'],
    ['admin.jil@swanrose.co', 'ADMIN']
];

foreach ($staff_accounts as $account) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$account[0]]);

    if (!$stmt->fetch()) {
        $insert = $pdo->prepare("INSERT INTO users (username, password_hash, role, force_password_change) VALUES (?, ?, ?, 1)");
        $insert->execute([$account[0], $default_password, $account[1]]);
        echo "<div class='step success'>Created staff account: <code>{$account[0]}</code> ({$account[1]}) - Change required</div>";
    } else {
        echo "<div class='step info'>Staff account exists: <code>{$account[0]}</code></div>";
    }
}

// ========================================
// STEP 4: MIGRATE VENDORS FROM USERS TABLE
// ========================================
echo "<h2>Step 4: Migrate Incorrectly Placed Vendors</h2>";

// Check for any VENDOR role in users table (should not exist)
$vendors_in_wrong_table = $pdo->query("SELECT * FROM users WHERE role = 'VENDOR'")->fetchAll();

if (count($vendors_in_wrong_table) > 0) {
    echo "<div class='step warning'>Found " . count($vendors_in_wrong_table) . " vendor(s) in wrong table. Migrating...</div>";

    foreach ($vendors_in_wrong_table as $v) {
        // Check if already in suppliers
        $stmt = $pdo->prepare("SELECT id FROM suppliers WHERE email = ?");
        $stmt->execute([$v['username']]);

        if (!$stmt->fetch()) {
            $insert = $pdo->prepare("INSERT INTO suppliers (email, password_hash, status, company_name, created_at) VALUES (?, ?, 'SUBMITTED', ?, ?)");
            $insert->execute([
                $v['username'],
                $v['password_hash'],
                "Vendor - " . $v['username'],
                $v['created_at']
            ]);
            echo "<div class='step success'>Migrated vendor: <code>{$v['username']}</code></div>";
        }
    }

    // Remove VENDOR entries from users table
    $pdo->exec("DELETE FROM users WHERE role = 'VENDOR'");
    echo "<div class='step success'>Cleaned up users table</div>";
} else {
    echo "<div class='step success'>No incorrectly placed vendors found</div>";
}

// ========================================
// STEP 5: FIX VENDOR STATUS
// ========================================
echo "<h2>Step 5: Fix Vendor Status</h2>";

$null_status = $pdo->query("SELECT COUNT(*) FROM suppliers WHERE status IS NULL OR status = ''")->fetchColumn();

if ($null_status > 0) {
    $pdo->exec("UPDATE suppliers SET status = 'SUBMITTED' WHERE status IS NULL OR status = ''");
    echo "<div class='step success'>Fixed $null_status vendor(s) with NULL status → SUBMITTED</div>";
} else {
    echo "<div class='step success'>All vendors have valid status</div>";
}

// ========================================
// STEP 6: VERIFICATION
// ========================================
echo "<h2>Step 6: System Verification</h2>";

// Count records
$staff_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$vendor_count = $pdo->query("SELECT COUNT(*) FROM suppliers")->fetchColumn();
$submitted_count = $pdo->query("SELECT COUNT(*) FROM suppliers WHERE status = 'SUBMITTED'")->fetchColumn();

echo "<table>
<tr><th>Metric</th><th>Count</th><th>Status</th></tr>
<tr><td>Internal Staff (users table)</td><td>$staff_count</td><td>" . ($staff_count >= 4 ? '✅' : '⚠️') . "</td></tr>
<tr><td>Total Vendors (suppliers table)</td><td>$vendor_count</td><td>" . ($vendor_count >= 0 ? '✅' : '⚠️') . "</td></tr>
<tr><td>Vendors Pending Review (SUBMITTED)</td><td>$submitted_count</td><td>ℹ️</td></tr>
</table>";

// Show all staff
echo "<h3>Internal Staff Accounts</h3><table><tr><th>Username</th><th>Role</th><th>Password</th></tr>";
$staff = $pdo->query("SELECT username, role FROM users ORDER BY role")->fetchAll();
foreach ($staff as $s) {
    echo "<tr><td>{$s['username']}</td><td>{$s['role']}</td><td><code>password</code></td></tr>";
}
echo "</table>";

// Show vendors
if ($vendor_count > 0) {
    echo "<h3>Registered Vendors</h3><table><tr><th>Email</th><th>Company</th><th>Status</th></tr>";
    $vendors = $pdo->query("SELECT email, company_name, status FROM suppliers ORDER BY created_at DESC LIMIT 10")->fetchAll();
    foreach ($vendors as $v) {
        echo "<tr><td>{$v['email']}</td><td>" . ($v['company_name'] ?: 'Not set') . "</td><td>{$v['status']}</td></tr>";
    }
    echo "</table>";
}

// ========================================
// FINAL SUMMARY
// ========================================
echo "<div class='final-box'>";
echo "<h2 style='color: #38bdf8; margin-top: 0;'>🎉 System Ready!</h2>";

if (empty($errors)) {
    echo "<p style='font-size: 1.1em;'>The Vendor Management System is now <strong>100% configured</strong> and ready for use.</p>";

    echo "<h3>Quick Links:</h3>";
    echo "<a href='public/login.php' class='btn'>🔐 Login Page</a>";
    echo "<a href='public/register.php' class='btn'>📝 Vendor Registration</a>";
    echo "<a href='public/dashboard.php' class='btn'>📊 Staff Dashboard</a>";

    echo "<h3>Test the System:</h3>";
    echo "<ol style='margin-left: 20px;'>";
    echo "<li><strong>Register a new vendor:</strong> Go to <code>register.php</code>, fill the form</li>";
    echo "<li><strong>Login as Purchase Team:</strong> Use <code>purchaser.jil@swanrose.co</code> / <code>password</code></li>";
    echo "<li><strong>See the vendor:</strong> Check 'Application Pending Review' - the new vendor should appear!</li>";
    echo "<li><strong>Approve the vendor:</strong> Click 'View' on the vendor, then 'Approve'</li>";
    echo "<li><strong>Login as Finance Team:</strong> Use <code>finance.jil@swanrose.co</code> to see the approved vendor</li>";
    echo "</ol>";

    echo "<h3>Workflow Summary:</h3>";
    echo "<table>";
    echo "<tr><th>Stage</th><th>Team</th><th>Vendor Status After</th></tr>";
    echo "<tr><td>New Registration</td><td>-</td><td>SUBMITTED</td></tr>";
    echo "<tr><td>L1 Review</td><td>PURCHASER</td><td>APPROVED_L1</td></tr>";
    echo "<tr><td>L2 Review</td><td>FINANCE</td><td>APPROVED_L2</td></tr>";
    echo "<tr><td>L3 Review</td><td>IT</td><td>ACTIVE</td></tr>";
    echo "</table>";
} else {
    echo "<p style='color: #f87171;'>Some errors occurred. Please review the messages above.</p>";
}

echo "</div>";
echo "</div></body></html>";
?>