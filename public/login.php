<?php
session_start();
require_once '../includes/db_connect.php';

// Check if already logged in
if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true) {
    if (in_array($_SESSION['role'] ?? '', ['PURCHASER', 'FINANCE', 'IT', 'ADMIN'])) {
        header("Location: dashboard.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username_input = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // First check VENDORS (suppliers table) - Priority for Registration flow
    $stmt = $pdo->prepare("SELECT id, email, password_hash, force_password_change, 'VENDOR' as role FROM suppliers WHERE email = ?");
    $stmt->execute([$username_input]);
    $user = $stmt->fetch();

    // If not found in vendors, check STAFF (users table)
    if (!$user) {
        $stmt = $pdo->prepare("SELECT id, username as email, password_hash, role, force_password_change FROM users WHERE username = ?");
        $stmt->execute([$username_input]);
        $user = $stmt->fetch();
    }

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['is_logged_in'] = true;

        // Force Password Change Check
        if ($user['force_password_change'] == 1) {
            $_SESSION['password_change_required'] = true;
            header("Location: change_password.php");
            exit;
        }

        // Redirect based on role
        if (in_array($user['role'], ['PURCHASER', 'FINANCE', 'IT', 'ADMIN'])) {
            header("Location: dashboard.php");
        } else {
            header("Location: index.php");
        }
        exit;
    } else {
        $error = "Invalid credentials. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Login | Vendor Management Portal</title>
    <link rel="icon" type="image/png" href="../assets/img/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="login-body">

    <div class="login-container">
        <div class="login-card">
            <div class="login-header" align="left">
                <img src="../assets/img/logo.jpg" alt="Jagatjit Industries Limited Logo"
                    style="width: 90px; height: auto; margin-bottom: 12px">
                <h1 align="center" style="font-size:14px">JAGATJIT INDUSTRIES LIMITED</h1>
                <p>Please log in to the <b>Vendor Management Portal</b></p>
            </div>

            <?php if ($error): ?>
                <div class="error-banner">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['reset_success'])): ?>
                <div
                    style="background-color: #d1fae5; color: #065f46; padding: 12px; border-radius: 8px; margin-bottom: 24px; text-align: center; font-size: 0.875rem; border: 1px solid #a7f3d0;">
                    Password reset successfully! Please login with your new password.
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">USERNAME</label><input type="text" id="username" name="username"
                        placeholder="Enter your username" required>
                </div>

                <div class="form-group">
                    <label for="password">PASSWORD</label> <input type="password" id="password" name="password"
                        placeholder="Enter your password" required>
                </div>

                <button type="submit" class="btn-primary">SIGN IN</button>
            </form>

            <div class="login-footer" style="text-align: center; margin-top: 24px;">
                <p style="font-size: 0.85rem; color: #1e293b; margin-bottom: 12px;">Forgot password? <a href="#"
                        style="color: #0047b3; text-decoration: none; font-weight: 500;">Contact Jagatjit Purchase
                        Team</a></p>
                <div style="padding-top: 12px; border-top: 1px solid #e2e8f0;">
                </div>
            </div>
        </div>
    </div>

</body>

</html>