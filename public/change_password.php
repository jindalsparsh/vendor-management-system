<?php
session_start();
require '../includes/db_connect.php';

// Access Control: Must be logged in and REQUIRE a password change
if (!isset($_SESSION['is_logged_in']) || !isset($_SESSION['password_change_required'])) {
    header("Location: login.php");
    exit();
}

$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($new_password) || strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $user_id = $_SESSION['user_id'];
        $role = $_SESSION['role'];
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);

        try {
            if ($role === 'VENDOR') {
                $stmt = $pdo->prepare("UPDATE suppliers SET password_hash = ?, force_password_change = 0 WHERE id = ?");
            } else {
                $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, force_password_change = 0 WHERE id = ?");
            }
            $stmt->execute([$password_hash, $user_id]);

            // Success: Clear flag and redirect
            unset($_SESSION['password_change_required']);

            if (in_array($role, ['PURCHASER', 'FINANCE', 'IT', 'ADMIN'])) {
                header("Location: dashboard.php?password_updated=1");
            } else {
                header("Location: index.php?password_updated=1");
            }
            exit();
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password | Vendor Management System</title>
    <link rel="icon" type="image/png" href="../assets/img/favicon.ico">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .change-password-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f1f5f9;
            padding: 20px;
        }

        .cp-card {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            max-width: 450px;
            width: 100%;
        }

        .cp-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .cp-header h1 {
            font-size: 1.5rem;
            color: #0f172a;
            margin-bottom: 8px;
        }

        .cp-header p {
            color: #64748b;
            font-size: 0.95rem;
        }

        .alert-info {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 12px;
            margin-bottom: 24px;
            font-size: 0.875rem;
            color: #1e40af;
        }

        .error-banner {
            background: #fef2f2;
            color: #991b1b;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.875rem;
            border: 1px solid #fecaca;
        }
    </style>
</head>

<body>
    <div class="change-password-container">
        <div class="cp-card">
            <div class="cp-header">
                <h1>Update Your Password</h1>
                <p>To ensure your account security, please change your temporary password before proceeding.</p>
            </div>

            <div class="alert-info">
                <strong>First-time Login:</strong> You are required to set a new password for your account <b>
                    <?php echo htmlspecialchars($_SESSION['username']); ?>
                </b>.
            </div>

            <?php if ($error): ?>
                <div class="error-banner">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="new_password">NEW PASSWORD</label>
                    <input type="password" id="new_password" name="new_password" placeholder="Minimum 6 characters"
                        required autofocus>
                </div>

                <div class="form-group">
                    <label for="confirm_password">CONFIRM NEW PASSWORD</label>
                    <input type="password" id="confirm_password" name="confirm_password"
                        placeholder="Re-enter your new password" required>
                </div>

                <button type="submit" class="btn-primary" style="margin-top: 10px;">UPDATE & LOGIN</button>
            </form>
        </div>
    </div>
</body>

</html>