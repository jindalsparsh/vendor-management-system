<?php
/**
 * Comprehensive Vendor Fix Script
 * 
 * This script fixes multiple issues with vendor visibility:
 * 1. Sets status to 'SUBMITTED' for vendors with NULL/empty status
 * 2. Identifies vendors missing email or password (cannot login)
 * 3. Shows all vendors and their current state
 */

require '../includes/db_connect.php';

echo "=== VENDOR MANAGEMENT SYSTEM - FIX SCRIPT ===\n\n";

try {
    // 1. Fix NULL/empty status
    echo "1. Fixing vendor status...\n";
    $sql = "UPDATE suppliers SET status = 'SUBMITTED' WHERE status IS NULL OR status = ''";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $affected_rows = $stmt->rowCount();
    echo "   ✅ Updated $affected_rows vendor(s) to SUBMITTED status\n\n";

    // 2. Check for vendors missing credentials
    echo "2. Checking for vendors with missing credentials...\n";
    $check_sql = "SELECT id, company_name, email, status FROM suppliers WHERE email IS NULL OR email = '' OR password_hash IS NULL OR password_hash = ''";
    $check_stmt = $pdo->query($check_sql);
    $missing_creds = $check_stmt->fetchAll();

    if (count($missing_creds) > 0) {
        echo "   ⚠️  WARNING: " . count($missing_creds) . " vendor(s) are missing email or password:\n";
        foreach ($missing_creds as $vendor) {
            echo sprintf(
                "      - ID: %d | Company: %s | Email: %s | Status: %s\n",
                $vendor['id'],
                $vendor['company_name'] ?: 'N/A',
                $vendor['email'] ?: 'MISSING',
                $vendor['status'] ?: 'NULL'
            );
        }
        echo "   These vendors CANNOT login until credentials are added!\n\n";
    } else {
        echo "   ✅ All vendors have email and password set\n\n";
    }

    // 3. Show all vendors with SUBMITTED status
    echo "3. Vendors visible to Purchase Team (status = SUBMITTED):\n";
    $submitted_sql = "SELECT id, company_name, email, status, created_at FROM suppliers WHERE status = 'SUBMITTED' ORDER BY created_at DESC";
    $submitted_stmt = $pdo->query($submitted_sql);
    $submitted_vendors = $submitted_stmt->fetchAll();

    if (count($submitted_vendors) > 0) {
        echo "   Found " . count($submitted_vendors) . " vendor(s):\n";
        foreach ($submitted_vendors as $vendor) {
            echo sprintf(
                "   ✓ ID: %d | %s | %s | Created: %s\n",
                $vendor['id'],
                $vendor['company_name'] ?: 'No Company Name',
                $vendor['email'] ?: 'No Email',
                $vendor['created_at']
            );
        }
    } else {
        echo "   ⚠️  No vendors with SUBMITTED status found!\n";
    }

    echo "\n=== FIX COMPLETE ===\n";
    echo "\nNext steps:\n";
    echo "1. Login as purchaser.jil@swanrose.co (password: password)\n";
    echo "2. Check the 'Application Pending Review' section\n";
    echo "3. You should see all vendors listed above\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>