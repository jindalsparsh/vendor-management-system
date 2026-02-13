<?php
/**
 * Deep Diagnostic Script
 * 
 * This script checks the entire vendor management system
 */

require '../includes/db_connect.php';

echo "<html><head><style>
body { font-family: 'Courier New', monospace; padding: 20px; background: #1e1e1e; color: #fff; }
h2 { color: #4fc3f7; }
.success { color: #00e676; }
.warning { color: #ffab00; }
.error { color: #ff5252; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #444; padding: 8px; text-align: left; }
th { background: #333; }
</style></head><body>";

echo "<h1>🔍 Vendor Management System - Deep Diagnostic</h1>";

// 1. CHECK DATABASE TABLES
echo "<h2>1. Database Tables</h2>";
try {
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<p class='success'>✓ Connected to database: vendor_management</p>";
    echo "<p>Tables found: " . implode(", ", $tables) . "</p>";

    // Check if required tables exist
    $required = ['users', 'suppliers'];
    foreach ($required as $table) {
        if (in_array($table, $tables)) {
            echo "<p class='success'>✓ Table '$table' exists</p>";
        } else {
            echo "<p class='error'>✗ Table '$table' is MISSING!</p>";
        }
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Database Error: " . $e->getMessage() . "</p>";
}

// 2. CHECK USERS TABLE (Internal Teams)
echo "<h2>2. Internal Users (users table)</h2>";
try {
    $users = $pdo->query("SELECT id, username, role FROM users ORDER BY role")->fetchAll();
    echo "<p>Found " . count($users) . " internal user(s):</p>";
    echo "<table><tr><th>ID</th><th>Username</th><th>Role</th></tr>";
    foreach ($users as $u) {
        echo "<tr><td>{$u['id']}</td><td>{$u['username']}</td><td>{$u['role']}</td></tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
}

// 3. CHECK SUPPLIERS TABLE STRUCTURE
echo "<h2>3. Suppliers Table Structure</h2>";
try {
    $columns = $pdo->query("DESCRIBE suppliers")->fetchAll();
    echo "<table><tr><th>Column</th><th>Type</th><th>Null</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Default']}</td></tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
}

// 4. CHECK ALL SUPPLIERS
echo "<h2>4. All Suppliers in Database</h2>";
try {
    $suppliers = $pdo->query("SELECT id, company_name, email, status, created_at FROM suppliers ORDER BY created_at DESC")->fetchAll();
    if (count($suppliers) == 0) {
        echo "<p class='warning'>⚠ No suppliers found in database!</p>";
    } else {
        echo "<p>Found " . count($suppliers) . " supplier(s):</p>";
        echo "<table><tr><th>ID</th><th>Company</th><th>Email</th><th>Status</th><th>Created</th></tr>";
        foreach ($suppliers as $s) {
            $statusClass = empty($s['status']) ? 'error' : 'success';
            echo "<tr>";
            echo "<td>{$s['id']}</td>";
            echo "<td>" . ($s['company_name'] ?: '<em>empty</em>') . "</td>";
            echo "<td>" . ($s['email'] ?: '<span class="error">MISSING</span>') . "</td>";
            echo "<td class='$statusClass'>" . ($s['status'] ?: 'NULL/EMPTY') . "</td>";
            echo "<td>{$s['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
}

// 5. CHECK FOR THE SPECIFIC TEST EMAIL
echo "<h2>5. Looking for test3@jagatjit.com</h2>";
try {
    $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE email LIKE ?");
    $stmt->execute(['%test3%']);
    $result = $stmt->fetchAll();
    if (count($result) > 0) {
        echo "<p class='success'>✓ Found " . count($result) . " record(s) matching 'test3':</p>";
        foreach ($result as $r) {
            echo "<pre>" . print_r($r, true) . "</pre>";
        }
    } else {
        echo "<p class='warning'>⚠ No supplier with 'test3' in email found!</p>";
        echo "<p>This means the vendor registration was never saved to the database.</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
}

// 6. What Purchase Team Would See
echo "<h2>6. What Purchase Team (PURCHASER) Would See</h2>";
try {
    $submitted = $pdo->query("SELECT id, company_name, email, status FROM suppliers WHERE status = 'SUBMITTED'")->fetchAll();
    if (count($submitted) == 0) {
        echo "<p class='warning'>⚠ No vendors with status='SUBMITTED'!</p>";
        echo "<p>The Purchase team will see an EMPTY inbox.</p>";
    } else {
        echo "<p class='success'>✓ Found " . count($submitted) . " vendor(s) pending review:</p>";
        foreach ($submitted as $s) {
            echo "<p>• {$s['company_name']} ({$s['email']})</p>";
        }
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
}

// 7. RECOMMENDATIONS
echo "<h2>7. Recommendations</h2>";
echo "<p>Based on the above diagnostics:</p><ul>";
echo "<li>If no suppliers exist: Register a new vendor at /public/register.php</li>";
echo "<li>If suppliers have NULL status: Run the SQL - <code>UPDATE suppliers SET status='SUBMITTED' WHERE status IS NULL</code></li>";
echo "<li>If test3@jagatjit.com doesn't exist: The registration wasn't submitted or failed silently</li>";
echo "</ul>";

echo "</body></html>";
?>