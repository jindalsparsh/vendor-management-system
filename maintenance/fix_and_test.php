<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>JIL- VMS | Master Repair & Diagnostic</title>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            padding: 40px;
            background: #f8fafc;
            color: #1e293b;
        }

        .container {
            max-width: 800px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        h1 {
            color: #0f172a;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .step {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 8px;
            border-left: 5px solid #cbd5e1;
            background: #f1f5f9;
        }

        .success {
            border-left-color: #22c55e;
            background: #f0fdf4;
        }

        .error {
            border-left-color: #ef4444;
            background: #fef2f2;
        }

        .info {
            border-left-color: #3b82f6;
            background: #eff6ff;
        }

        pre {
            background: #1e293b;
            color: #f8fafc;
            padding: 10px;
            border-radius: 6px;
            overflow-x: auto;
            font-size: 13px;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #3b82f6;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>JIL- VMS Diagnostic Tool</h1>

        <?php
        require 'db_connect.php';

        function report($msg, $type = 'info')
        {
            echo "<div class='step $type'>$msg</div>";
        }

        // --- STEP 1: Check Connection ---
        try {
            $pdo->query("SELECT 1");
            report("<b>Step 1:</b> Database connection successful.", "success");
        } catch (Exception $e) {
            report("<b>Step 1 failed:</b> Connection error: " . $e->getMessage(), "error");
            exit;
        }

        // --- STEP 2: Verify Tables ---
        $tables = ['users', 'suppliers'];
        foreach ($tables as $table) {
            try {
                $pdo->query("SELECT 1 FROM $table LIMIT 1");
                report("Table <b>'$table'</b> exists.", "success");
            } catch (Exception $e) {
                report("Table <b>'$table'</b> is missing! Attempting to create...", "error");
                // Basic creation if missing (based on database.txt)
                if ($table == 'users') {
                    $pdo->exec("CREATE TABLE IF NOT EXISTS users (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(50) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL, role ENUM('PURCHASER', 'FINANCE', 'IT') NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
                    report("Created 'users' table.", "success");
                }
            }
        }

        // --- STEP 3: Upsert Team Accounts ---
        $team_users = [
            ['email' => 'purchase@swanrose.co', 'role' => 'PURCHASER'],
            ['email' => 'finance@swanrose.co', 'role' => 'FINANCE'],
            ['email' => 'it@swanrose.co', 'role' => 'IT']
        ];
        $password = 'password';
        $hash = password_hash($password, PASSWORD_DEFAULT);

        foreach ($team_users as $u) {
            try {
                $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?) 
                                   ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), role = VALUES(role)");
                $stmt->execute([$u['email'], $hash, $u['role']]);
                report("Configured account: <b>" . $u['email'] . "</b> as " . $u['role'] . " (Password: password)", "success");
            } catch (Exception $e) {
                report("Failed to configure " . $u['email'] . ": " . $e->getMessage(), "error");
            }
        }

        // --- STEP 4: Login Simulation Test ---
        report("<b>Step 4:</b> Performing Login Simulation for 'purchase@swanrose.co'...", "info");
        $test_user = 'purchase@swanrose.co';
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$test_user]);
        $user_row = $stmt->fetch();

        if ($user_row) {
            $match = password_verify('password', $user_row['password_hash']);
            if ($match) {
                report("Login Simulation SUCCESS! Password verify passed for $test_user.", "success");
            } else {
                report("Login Simulation FAILED! Password hash in DB does not match 'password'. This is very strange.", "error");
                report("Hash in DB: <pre>" . $user_row['password_hash'] . "</pre>");
            }
        } else {
            report("Login Simulation FAILED! User not found in DB after attempted insertion.", "error");
        }

        // --- STEP 5: Check for pending suppliers ---
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM suppliers WHERE status = 'SUBMITTED'");
            $count = $stmt->fetchColumn();
            report("<b>Step 5:</b> There are <b>$count</b> suppliers pending approval (Status: SUBMITTED).", "info");
            if ($count == 0) {
                report("<b>Note:</b> Purchase team will see an empty dashboard because no one has submitted a registration yet.", "info");
            }
        } catch (Exception $e) {
        }

        // --- STEP 6: Ensure Test Supplier exists ---
        try {
            $stmt = $pdo->query("SELECT id FROM suppliers LIMIT 1");
            if (!$stmt->fetch()) {
                report("<b>Step 6:</b> No suppliers found. Creating a test supplier for you to approve...", "info");
                $pdo->exec("INSERT INTO suppliers (company_name, contact_first_name, mobile_number, email, status, pan_number, bank_name, account_number, ifsc_code) 
                        VALUES ('Test Vendor Ltd', 'John', '9876543210', 'vendor@example.com', 'SUBMITTED', 'ABCDE1234F', 'Test Bank', '1234567890', 'ABCD0123456')");
                report("Created test supplier 'Test Vendor Ltd'.", "success");
            }
        } catch (Exception $e) {
        }

        echo "<hr><p><b>Diagnostic Complete!</b> If every step above shows 'SUCCESS', your system is now correctly configured.</p>";
        echo "<h3>Current Internal Accounts:</h3>";
        try {
            $stmt = $pdo->query("SELECT username, role FROM users");
            echo "<ul>";
            while ($u = $stmt->fetch()) {
                echo "<li><b>Email:</b> " . htmlspecialchars($u['username']) . " | <b>Role:</b> " . $u['role'] . "</li>";
            }
            echo "</ul>";
        } catch (Exception $e) {
        }
        echo "<p><b>Next Step:</b> Try logging in with: <br>Email: <code>purchase@swanrose.co</code><br>Password: <code>password</code></p>";
        echo "<a href='login.php' class='btn'>Proceed to Login Page</a>";
        ?>
    </div>
</body>

</html>