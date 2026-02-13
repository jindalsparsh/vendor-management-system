<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>JIL- VMS Setup</title>
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

        .success {
            color: #27ae60;
            font-weight: bold;
            margin: 10px 0;
        }

        .error {
            color: #e74c3c;
            font-weight: bold;
            margin: 10px 0;
        }
    </style>
</head>

<body>
    <div class="card">
        <h2>JIL- VMS Team Setup</h2>
        <?php
        require 'db_connect.php';

        $users = [
            ['email' => 'purchase@swanrose.co', 'role' => 'PURCHASER'],
            ['email' => 'finance@swanrose.co', 'role' => 'FINANCE'],
            ['email' => 'it@swanrose.co', 'role' => 'IT']
        ];

        $password = 'password';
        $hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            foreach ($users as $u) {
                $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role) 
                                   VALUES (?, ?, ?)
                                   ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), role = VALUES(role)");
                $stmt->execute([$u['email'], $hash, $u['role']]);
                echo "<div class='success'>SUCCESS: Account configured for <b>" . $u['email'] . "</b> (" . $u['role'] . ")</div>";
            }
            echo "<hr><p>Setup complete! You can now <a href='login.php'>Login</a> with these accounts.</p>";
        } catch (PDOException $e) {
            echo "<div class='error'>ERROR: " . $e->getMessage() . "</div>";
        }
        ?>
    </div>
</body>

</html>