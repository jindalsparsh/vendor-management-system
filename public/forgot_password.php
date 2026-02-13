<?php
session_start();
require '../includes/db_connect.php';

// Auto-migration for OTP columns
try {
    $pdo->query("SELECT otp FROM suppliers LIMIT 1");
} catch (PDOException $e) {
    // Columns likely missing
    $pdo->exec("ALTER TABLE suppliers ADD COLUMN otp VARCHAR(10) NULL, ADD COLUMN otp_expiry DATETIME NULL;");
}

$error = '';
$success = '';
$otp_sent = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');

    $stmt = $pdo->prepare("SELECT id FROM suppliers WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $otp = sprintf("%06d", mt_rand(1, 999999));
        $expiry = date("Y-m-d H:i:s", strtotime("+15 minutes"));

        $update = $pdo->prepare("UPDATE suppliers SET otp = ?, otp_expiry = ? WHERE id = ?");
        $update->execute([$otp, $expiry, $user['id']]);

        $_SESSION['reset_email'] = $email;

        // Display OTP for dev purposes as per plan
        $msg = "Your OTP for password reset is: $otp (Expires in 15 mins)";
        $success = "OTP has been sent to your email address.";
        $dev_otp = $otp; // To show in a banner for testing

        // In a real scenario, use mail() here
        // mail($email, "Password Reset OTP", $msg);

        header("Location: verify_otp.php?sent=1&otp_hint=" . urlencode($otp));
        exit;
    } else {
        $error = "If an account exists for this email, an OTP has been sent."; // Security: don't reveal if email exists
        // But for this task, let's be more helpful if requested
        $error = "Email address not found in our records.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | Vendor Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="login-body">

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>Forgot Password</h1>
                <p>Enter your email to receive a reset OTP</p>
            </div>

            <?php if ($error): ?>
                <div class="error-banner">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Enter your registered email" required>
                </div>

                <button type="submit" class="btn-primary">Send OTP</button>
            </form>

            <div class="login-footer">
                <p><a href="login.php">Back to Login</a></p>
            </div>
        </div>
    </div>

</body>

</html>