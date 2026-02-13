<?php
/**
 * Jagatjit Industries Limited - VMS Configuration
 */

// Portal Settings
define('PORTAL_LINK', 'http://192.168.1.14/sjvms/public/login.php');
define('SUPPORT_EMAIL', 'purchase@jagatjit.com');
define('APP_NAME', 'Jagatjit Industries Limited');

// Email Settings
define('MAIL_FROM_EMAIL', 'it.admin@jagatjit.com');
define('MAIL_FROM_NAME', 'Jagatjit Industries Purchase Team');

/**
 * SMTP Configuration
 * Office 365 requires Authentication and Port 587 (STARTTLS)
 */
define('SMTP_HOST', 'smtp.office365.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'it.admin@jagatjit.com'); // Update with correct username
define('SMTP_PASS', 'M@jee@1987'); // LEAVE EMPTY - User must provide this
define('SMTP_AUTH', true);
define('SMTP_SECURE', 'tls'); // 'tls' for STARTTLS (recommended for 587)

// Environment
define('DEV_MODE', true); // If true, logs emails instead of or along with sending
?>