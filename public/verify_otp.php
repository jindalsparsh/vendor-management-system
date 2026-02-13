<?php
session_start();
require '../includes/db_connect.php';

if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit;
}

$error = '';
$email = $_SESSION['reset_email'];
$otp_hint = $_GET['otp_hint'] ?? ''; // Dev hint

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $otp = trim($_POST['otp'] ?? '');

    $stmt = $pdo->prepare("SELECT id, otp_expiry FROM suppliers WHERE email = ? AND otp = ?");
    $stmt->execute([$email, $otp]);
    $user = $stmt->fetch();

    if ($user) {
        $expiry_ts = strtotime($user['otp_expiry']);
        $now_ts = time();

        if ($expiry_ts > $now_ts) {
            $_SESSION['otp_verified'] = true;
            header("Location: reset_password.php");
            exit;
        } else {
            $error = "This OTP has expired. Please request a new one.";
        }
    } else {
        $error = "Invalid OTP. Please check the code and try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP | Vendor Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="login-body">

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>Verify OTP</h1>
                <p>Enter the 6-digit code sent to your email</p>
            </div>

            <?php if ($otp_hint): ?>
                <div
                    style="background-color: #fef3c7; color: #92400e; padding: 12px; border-radius: 8px; margin-bottom: 24px; text-align: center; font-size: 0.875rem; border: 1px solid #fde68a;">
                    <strong>[Dev Mode]</strong> Your OTP is: <code><?php echo htmlspecialchars($otp_hint); ?></code>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="error-banner">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="otp">OTP Code</label>
                    <input type="text" id="otp" name="otp" placeholder="000000" maxlength="6" required
                        style="letter-spacing: 0.5rem; text-align: center; font-weight: 700; font-size: 1.25rem;">
                </div>

                <button type="submit" class="btn-primary">Verify OTP</button>
            </form>

            <div class="login-footer">
                <p><a href="forgot_password.php">Resend Code</a></p>
            </div>
        </div>
    </div>

</body>

</html>