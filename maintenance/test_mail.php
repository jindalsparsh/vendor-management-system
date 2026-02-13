<?php
require_once '../includes/db_connect.php';

echo "<h1>PHPMailer SMTP Test</h1>";
$to = "sparsh.jindal@swanrose.co";

echo "<p>Sending test email to: <b>$to</b>...</p>";

$result = sendVendorOnboardingEmail("Test User", $to, "temp_pass_123");

if ($result) {
    echo "<p style='color: green;'>SUCCESS: Email sent successfully via PHPMailer!</p>";
} else {
    echo "<p style='color: red;'>FAILURE: Failed to send email.</p>";
    echo "<p>Please check your <code>logs/mail_log_" . date('Y-m-d') . ".log</code> for detailed error messages.</p>";
    echo "<p><b>Common Causes:</b><br>";
    echo "- Incorrect SMTP password in <code>includes/config.php</code><br>";
    echo "- Firewall blocking port 587<br>";
    echo "- Office 365 account requires MFA or an App Password</p>";
}

echo "<h3>Config being used:</h3>";
echo "Host: " . SMTP_HOST . "<br>";
echo "Port: " . SMTP_PORT . "<br>";
echo "User: " . SMTP_USER . "<br>";
echo "Auth: " . (SMTP_AUTH ? "True" : "False") . "<br>";
echo "Secure: " . SMTP_SECURE . "<br>";
?>