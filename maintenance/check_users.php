<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>JIL- VMS Account Status</title>
    <style>
        body {
            font-family: sans-serif;
            padding: 20px;
            background: #f4f7f6;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #eee;
        }

        .success {
            color: #27ae60;
            font-weight: bold;
        }

        .warning {
            color: #f39c12;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="card">
        <h2>JIL- VMS Internal Accounts</h2>
        <?php
        require 'db_connect.php';
        try {
            $stmt = $pdo->query("SELECT username, role, created_at FROM users");
            $users = $stmt->fetchAll();

            if (count($users) > 0) {
                echo "<table><tr><th>Email / Username</th><th>Role</th><th>Created</th></tr>";
                foreach ($users as $u) {
                    echo "<tr><td>" . htmlspecialchars($u['username']) . "</td><td>" . $u['role'] . "</td><td>" . $u['created_at'] . "</td></tr>";
                }
                echo "</table>";
            } else {
                echo "<p class='warning'>No internal accounts found in the 'users' table. Please run the setup script.</p>";
            }
        } catch (PDOException $e) {
            echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
        }
        ?>
        <hr>
        <p><a href="setup_approval_teams.php">Run Setup Script</a> | <a href="login.php">Back to Login</a></p>
    </div>
</body>

</html>