<?php
/**
 * Vendor Migration Fix Script
 * 
 * This script:
 * 1. Finds vendors incorrectly created in the 'users' table
 * 2. Migrates them to the 'suppliers' table
 * 3. Sets their status to 'SUBMITTED' so they appear in the dashboard
 */

require '../includes/db_connect.php';

echo "<html><head><style>
body { font-family: 'Segoe UI', sans-serif; padding: 20px; background: #1e293b; color: #f8fafc; }
h1 { color: #38bdf8; }
h2 { color: #94a3b8; border-bottom: 1px solid #334155; padding-bottom: 10px; }
.success { color: #4ade80; }
.warning { color: #facc15; }
.error { color: #f87171; }
pre { background: #0f172a; padding: 15px; border-radius: 8px; overflow-x: auto; }
table { border-collapse: collapse; width: 100%; margin: 15px 0; }
th, td { border: 1px solid #334155; padding: 12px; text-align: left; }
th { background: #1e3a5f; }
.btn { background: #3b82f6; color: white; padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; margin: 5px; text-decoration: none; display: inline-block; }
.btn:hover { background: #2563eb; }
.btn-danger { background: #ef4444; }
.btn-danger:hover { background: #dc2626; }
</style></head><body>";

echo "<h1>🔧 Vendor System Fix & Migration Tool</h1>";

// Check for action
$action = $_GET['action'] ?? '';

if ($action === 'migrate') {
    echo "<h2>Migrating Vendors...</h2>";

    // Find vendors in users table
    $vendors_in_users = $pdo->query("SELECT * FROM users WHERE role = 'VENDOR'")->fetchAll();

    $migrated = 0;
    $errors = [];

    foreach ($vendors_in_users as $vendor) {
        // Check if already exists in suppliers
        $stmt = $pdo->prepare("SELECT id FROM suppliers WHERE email = ?");
        $stmt->execute([$vendor['username']]);

        if (!$stmt->fetch()) {
            // Insert into suppliers
            try {
                $insert = $pdo->prepare("INSERT INTO suppliers (email, password_hash, status, company_name, created_at) VALUES (?, ?, 'SUBMITTED', ?, ?)");
                $insert->execute([
                    $vendor['username'],
                    $vendor['password_hash'],
                    "Vendor - " . $vendor['username'],
                    $vendor['created_at']
                ]);
                $migrated++;
                echo "<p class='success'>✓ Migrated: {$vendor['username']}</p>";
            } catch (Exception $e) {
                $errors[] = $vendor['username'] . ": " . $e->getMessage();
            }
        } else {
            echo "<p class='warning'>⚠ Already exists in suppliers: {$vendor['username']}</p>";
        }
    }

    // Delete from users table
    if ($migrated > 0 && empty($errors)) {
        $pdo->exec("DELETE FROM users WHERE role = 'VENDOR'");
        echo "<p class='success'>✓ Cleaned up users table</p>";
    }

    echo "<p><strong>Migrated: $migrated vendor(s)</strong></p>";

    if (!empty($errors)) {
        echo "<p class='error'>Errors:</p><pre>" . implode("\n", $errors) . "</pre>";
    }

    echo "<a href='fix_vendor_migration.php' class='btn'>← Back to Diagnostic</a>";
} elseif ($action === 'fix_status') {
    echo "<h2>Fixing Vendor Status...</h2>";

    $stmt = $pdo->prepare("UPDATE suppliers SET status = 'SUBMITTED' WHERE status IS NULL OR status = ''");
    $stmt->execute();
    $affected = $stmt->rowCount();

    echo "<p class='success'>✓ Updated $affected vendor(s) to SUBMITTED status</p>";
    echo "<a href='fix_vendor_migration.php' class='btn'>← Back to Diagnostic</a>";
} else {
    // DIAGNOSTIC VIEW

    // 1. Check users table for vendors
    echo "<h2>1. Vendors in USERS Table (Incorrect Location)</h2>";
    $vendors_in_users = $pdo->query("SELECT id, username, role, created_at FROM users WHERE role = 'VENDOR'")->fetchAll();

    if (count($vendors_in_users) > 0) {
        echo "<p class='error'>⚠ Found " . count($vendors_in_users) . " vendor(s) in the WRONG table!</p>";
        echo "<table><tr><th>ID</th><th>Username/Email</th><th>Created</th></tr>";
        foreach ($vendors_in_users as $v) {
            echo "<tr><td>{$v['id']}</td><td>{$v['username']}</td><td>{$v['created_at']}</td></tr>";
        }
        echo "</table>";
        echo "<a href='fix_vendor_migration.php?action=migrate' class='btn btn-danger'>Migrate These Vendors to Suppliers Table</a>";
    } else {
        echo "<p class='success'>✓ No vendors found in users table (correct!)</p>";
    }

    // 2. Check suppliers table
    echo "<h2>2. Vendors in SUPPLIERS Table (Correct Location)</h2>";
    $suppliers = $pdo->query("SELECT id, email, company_name, status, created_at FROM suppliers ORDER BY created_at DESC")->fetchAll();

    if (count($suppliers) > 0) {
        echo "<table><tr><th>ID</th><th>Email</th><th>Company</th><th>Status</th><th>Created</th></tr>";
        foreach ($suppliers as $s) {
            $statusClass = ($s['status'] === 'SUBMITTED') ? 'success' : (empty($s['status']) ? 'error' : 'warning');
            echo "<tr>";
            echo "<td>{$s['id']}</td>";
            echo "<td>{$s['email']}</td>";
            echo "<td>{$s['company_name']}</td>";
            echo "<td class='$statusClass'>" . ($s['status'] ?: 'NULL') . "</td>";
            echo "<td>{$s['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>⚠ No vendors in suppliers table!</p>";
    }

    // Check for NULL status
    $null_status = $pdo->query("SELECT COUNT(*) FROM suppliers WHERE status IS NULL OR status = ''")->fetchColumn();
    if ($null_status > 0) {
        echo "<p class='warning'>⚠ $null_status vendor(s) have NULL/empty status!</p>";
        echo "<a href='fix_vendor_migration.php?action=fix_status' class='btn'>Fix Status to SUBMITTED</a>";
    }

    // 3. What each team would see
    echo "<h2>3. Dashboard Visibility</h2>";

    $submitted = $pdo->query("SELECT COUNT(*) FROM suppliers WHERE status = 'SUBMITTED'")->fetchColumn();
    $approved_l1 = $pdo->query("SELECT COUNT(*) FROM suppliers WHERE status = 'APPROVED_L1'")->fetchColumn();
    $approved_l2 = $pdo->query("SELECT COUNT(*) FROM suppliers WHERE status = 'APPROVED_L2'")->fetchColumn();

    echo "<table>";
    echo "<tr><th>Team</th><th>Status Query</th><th>Visible Vendors</th></tr>";
    echo "<tr><td>Purchase (PURCHASER)</td><td>status = 'SUBMITTED'</td><td class='" . ($submitted > 0 ? "success" : "error") . "'>$submitted</td></tr>";
    echo "<tr><td>Finance (FINANCE)</td><td>status = 'APPROVED_L1'</td><td>$approved_l1</td></tr>";
    echo "<tr><td>IT</td><td>status = 'APPROVED_L2'</td><td>$approved_l2</td></tr>";
    echo "</table>";

    // 4. Internal Staff
    echo "<h2>4. Internal Staff (users table)</h2>";
    $staff = $pdo->query("SELECT id, username, role FROM users WHERE role != 'VENDOR' ORDER BY role")->fetchAll();
    echo "<table><tr><th>ID</th><th>Username</th><th>Role</th></tr>";
    foreach ($staff as $s) {
        echo "<tr><td>{$s['id']}</td><td>{$s['username']}</td><td>{$s['role']}</td></tr>";
    }
    echo "</table>";

    echo "<h2>5. Quick Actions</h2>";
    echo "<a href='fix_vendor_migration.php?action=migrate' class='btn'>Migrate Any Vendors from users→suppliers</a> ";
    echo "<a href='fix_vendor_migration.php?action=fix_status' class='btn'>Fix NULL Status</a>";
}

echo "</body></html>";
?>