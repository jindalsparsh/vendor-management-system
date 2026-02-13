<?php
require '../includes/db_connect.php';

echo "<h1>JIL-VMS Admin Repair Tool</h1>";

try {
    // 1. Update Schema
    echo "<p>Step 1: Updating Database Schema... ";
    $sql = "ALTER TABLE users MODIFY COLUMN role ENUM('PURCHASER', 'FINANCE', 'IT', 'ADMIN') NOT NULL";
    $pdo->exec($sql);
    echo "<b style='color:green'>Done.</b></p>";

    // 2. Update Admin User
    echo "<p>Step 2: Assigning ADMIN role to user... ";
    $username = 'admin.jil@swanrose.co';
    $role = 'ADMIN';
    $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE username = ?");
    $stmt->execute([$role, $username]);
    echo "<b style='color:green'>Done.</b></p>";

    // 3. Verify
    echo "<p>Step 3: Verification... ";
    $stmt = $pdo->prepare("SELECT username, role FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $user['role'] === 'ADMIN') {
        echo "<b style='color:green'>SUCCESS! User '{$user['username']}' is now an ADMIN.</b></p>";
        echo "<div style='padding: 20px; background: #dcfce7; border: 1px solid #166534; color: #166534; margin-top: 20px;'>";
        echo "<strong>FIX COMPLETE.</strong><br>Please <a href='logout.php'>Logout</a> and Sign In again.";
        echo "</div>";
    } else {
        echo "<b style='color:red'>FAILED. Role is '{$user['role']}'</b></p>";
    }

} catch (PDOException $e) {
    echo "<b style='color:red'>Error: " . $e->getMessage() . "</b>";
}
?>