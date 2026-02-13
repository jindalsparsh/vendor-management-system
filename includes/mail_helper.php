<?php
/**
 * Vendor Email Helper using PHPMailer
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

function sendVendorOnboardingEmail($vendorName, $vendorEmail, $temporaryPassword)
{
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = SMTP_AUTH;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;

        // Recipients
        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($vendorEmail, $vendorName);
        $mail->addReplyTo(SUPPORT_EMAIL, 'Support');

        // Content
        $mail->isHTML(false); // Plain text as per original request
        $mail->Subject = "Action Required: Complete Vendor Onboarding Form";

        $portalLink = PORTAL_LINK;
        $supportEmail = SUPPORT_EMAIL;

        $message = "Dear " . ($vendorName ? $vendorName : "Vendor") . ",\r\n\r\n";
        $message .= "Thank you for your request to onboard with Jagatjit Industries Limited as a vendor.\r\n\r\n";
        $message .= "To proceed with your onboarding request, you are required to complete the vendor onboarding form. For this purpose, a vendor portal account has been created for you.\r\n\r\n";
        $message .= "Please log in to the vendor portal using the credentials below and complete the onboarding form with the required business, banking, and statutory details.\r\n\r\n";
        $message .= "Login Details\r\n";
        $message .= "Email ID: $vendorEmail\r\n";
        $message .= "Temporary Password: $temporaryPassword\r\n\r\n";
        $message .= "Vendor Portal Link\r\n";
        $message .= "$portalLink\r\n\r\n";
        $message .= "Once you submit the onboarding form, your application will be taken up for internal verification and approval.\r\n";
        $message .= "Please note that submission of the form does not imply approval or successful onboarding. You will be notified once the review process is completed.\r\n\r\n";
        $message .= "Important\r\n";
        $message .= "- We recommend changing your password after your first login.\r\n";
        $message .= "- Ensure all details are accurate to avoid delays during verification.\r\n";
        $message .= "- You can track your application status by logging into the vendor portal.\r\n\r\n";
        $message .= "If you face any issues while accessing the portal or completing the form, please contact us at $supportEmail.\r\n\r\n";
        $message .= "Regards,\r\n";
        $message .= "Purchase Team\r\n";
        $message .= "Jagatjit Industries Limited\r\n";
        $message .= "www.jagatjit.com";

        $mail->Body = $message;

        $mail->send();
        $status = "SUCCESS";
        $error_msg = "";
    } catch (Exception $e) {
        $status = "FAILURE";
        $error_msg = $mail->ErrorInfo;
    }

    // Log the result
    if (defined('DEV_MODE') && DEV_MODE) {
        $logDir = __DIR__ . '/../logs/';
        if (!is_dir($logDir))
            mkdir($logDir, 0777, true);
        $logFile = $logDir . 'mail_log_' . date('Y-m-d') . '.log';
        $logEntry = "[" . date('Y-m-d H:i:s') . "] [$status] To: $vendorEmail | Subject: " . $mail->Subject . "\r\n";
        if ($status === "FAILURE") {
            $logEntry .= "Error: $error_msg\r\n";
        }
        $logEntry .= str_repeat("-", 50) . "\r\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }

    return $status === "SUCCESS";
}

/**
 * Send workflow notification email to internal team members
 * Called when an application advances to their review queue
 */
function sendWorkflowNotificationEmail($toEmail, $toName, $companyName, $supplierId, $teamLevel)
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = SMTP_AUTH;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;

        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName);
        $mail->addReplyTo(SUPPORT_EMAIL, 'Support');

        $mail->isHTML(false);
        $mail->Subject = "Action Required: Vendor Application #$supplierId Awaiting Your Review";

        $portalLink = PORTAL_LINK;
        $supportEmail = SUPPORT_EMAIL;

        $message = "Dear $toName,\r\n\r\n";
        $message .= "A vendor application requires your review and action.\r\n\r\n";
        $message .= "Application Details\r\n";
        $message .= "Application #: $supplierId\r\n";
        $message .= "Company Name: " . ($companyName ?: 'Not Provided') . "\r\n";
        $message .= "Review Level: $teamLevel\r\n\r\n";
        $message .= "Please log in to the Vendor Management Portal to review and take action on this application.\r\n\r\n";
        $message .= "Portal Link\r\n";
        $message .= "$portalLink\r\n\r\n";
        $message .= "If you have any questions, please contact us at $supportEmail.\r\n\r\n";
        $message .= "Regards,\r\n";
        $message .= "VMS Automated Notification\r\n";
        $message .= "Jagatjit Industries Limited\r\n";
        $message .= "www.jagatjit.com";

        $mail->Body = $message;
        $mail->send();
        $status = "SUCCESS";
        $error_msg = "";
    } catch (Exception $e) {
        $status = "FAILURE";
        $error_msg = $mail->ErrorInfo;
    }

    // Log the result
    if (defined('DEV_MODE') && DEV_MODE) {
        $logDir = __DIR__ . '/../logs/';
        if (!is_dir($logDir))
            mkdir($logDir, 0777, true);
        $logFile = $logDir . 'mail_log_' . date('Y-m-d') . '.log';
        $logEntry = "[" . date('Y-m-d H:i:s') . "] [$status] To: $toEmail | Subject: " . $mail->Subject . "\r\n";
        if ($status === "FAILURE") {
            $logEntry .= "Error: $error_msg\r\n";
        }
        $logEntry .= str_repeat("-", 50) . "\r\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }

    return $status === "SUCCESS";
}

/**
 * Send approval/activation email to vendor
 * Called when vendor application reaches ACTIVE status
 */
function sendVendorApprovalEmail($vendorEmail, $vendorName, $ebsVendorCode)
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = SMTP_AUTH;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;

        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($vendorEmail, $vendorName);
        $mail->addReplyTo(SUPPORT_EMAIL, 'Support');

        $mail->isHTML(false);
        $mail->Subject = "Congratulations! Your Vendor Onboarding is Complete";

        $portalLink = PORTAL_LINK;
        $supportEmail = SUPPORT_EMAIL;

        $message = "Dear " . ($vendorName ? $vendorName : "Vendor") . ",\r\n\r\n";
        $message .= "We are pleased to inform you that your vendor onboarding application with Jagatjit Industries Limited has been reviewed and approved.\r\n\r\n";
        $message .= "You have been successfully onboarded as a vendor. Your details are now active in our system.\r\n\r\n";
        if ($ebsVendorCode) {
            $message .= "Your EBS Vendor Code\r\n";
            $message .= "$ebsVendorCode\r\n\r\n";
            $message .= "Please keep this code for your reference. It will be used for all future transactions and communications.\r\n\r\n";
        }
        $message .= "You can log in to the vendor portal at any time to view your application status and details.\r\n\r\n";
        $message .= "Vendor Portal Link\r\n";
        $message .= "$portalLink\r\n\r\n";
        $message .= "If you have any questions or need further assistance, please contact us at $supportEmail.\r\n\r\n";
        $message .= "We look forward to a fruitful business relationship.\r\n\r\n";
        $message .= "Regards,\r\n";
        $message .= "Purchase Team\r\n";
        $message .= "Jagatjit Industries Limited\r\n";
        $message .= "www.jagatjit.com";

        $mail->Body = $message;
        $mail->send();
        $status = "SUCCESS";
        $error_msg = "";
    } catch (Exception $e) {
        $status = "FAILURE";
        $error_msg = $mail->ErrorInfo;
    }

    // Log the result
    if (defined('DEV_MODE') && DEV_MODE) {
        $logDir = __DIR__ . '/../logs/';
        if (!is_dir($logDir))
            mkdir($logDir, 0777, true);
        $logFile = $logDir . 'mail_log_' . date('Y-m-d') . '.log';
        $logEntry = "[" . date('Y-m-d H:i:s') . "] [$status] To: $vendorEmail | Subject: " . $mail->Subject . "\r\n";
        if ($status === "FAILURE") {
            $logEntry .= "Error: $error_msg\r\n";
        }
        $logEntry .= str_repeat("-", 50) . "\r\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }

    return $status === "SUCCESS";
}
?>