<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/config.php';
require_once '../includes/mail_helper.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $supplier_id = intval($_POST['supplier_id']);
    $action = $_POST['action'];
    $role = $_SESSION['role'];
    $user_id = $_SESSION['user_id'];
    $comments = trim($_POST['comments'] ?? '');
    $ebs_vendor_code = trim($_POST['ebs_vendor_code'] ?? '');

    // Fetch current supplier data
    $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
    $stmt->execute([$supplier_id]);
    $supplier = $stmt->fetch();
    if (!$supplier)
        die("Supplier not found.");
    $current_status = $supplier['status'];
    $new_status = $current_status;
    $column_to_update = '';

    if ($action == 'approve') {
        if (($role == 'PURCHASER' || $role == 'ADMIN') && $current_status == 'SUBMITTED') {
            $new_status = 'APPROVED_L1';
            $column_to_update = 'l1_approved_by = ?, l1_approved_at = NOW(), l1_comments = ?';
        } elseif (($role == 'FINANCE' || $role == 'ADMIN') && $current_status == 'APPROVED_L1') {
            $new_status = 'APPROVED_L2';
            $column_to_update = 'l2_approved_by = ?, l2_approved_at = NOW(), l2_comments = ?';
        } elseif (($role == 'IT' || $role == 'ADMIN') && $current_status == 'APPROVED_L2') {
            $new_status = 'ACTIVE';
            // Include ebs_vendor_code in the L3 approval
            $column_to_update = 'l3_approved_by = ?, l3_approved_at = NOW(), l3_comments = ?, ebs_vendor_code = ?';
        } else {
            die("Unauthorized action or invalid status flow.");
        }
    } elseif ($action == 'reject') {
        if (
            (($role == 'PURCHASER' || $role == 'ADMIN') && $current_status == 'SUBMITTED') ||
            (($role == 'FINANCE' || $role == 'ADMIN') && $current_status == 'APPROVED_L1') ||
            (($role == 'IT' || $role == 'ADMIN') && $current_status == 'APPROVED_L2')
        ) {
            $new_status = 'REJECTED';
            $comment_col = '';
            if ($current_status == 'SUBMITTED')
                $comment_col = 'l1_comments';
            elseif ($current_status == 'APPROVED_L1')
                $comment_col = 'l2_comments';
            elseif ($current_status == 'APPROVED_L2')
                $comment_col = 'l3_comments';

            $column_to_update = 'rejection_reason = ?, ' . $comment_col . ' = ?';
        } else {
            die("Unauthorized rejection.");
        }
    }

    if ($column_to_update) {
        $sql = "UPDATE suppliers SET status = ?, $column_to_update WHERE id = ?";
        $params = [$new_status];
        if ($action == 'approve') {
            $params[] = $user_id;
            $params[] = $comments;
            // Add EBS vendor code param for L3 approval
            if ($new_status == 'ACTIVE') {
                $params[] = $ebs_vendor_code;
            }
        } else {
            $params[] = "Rejected by " . $role;
            $params[] = $comments;
        }
        $params[] = $supplier_id;
        $update_stmt = $pdo->prepare($sql);
        $update_stmt->execute($params);

        // ===== EMAIL NOTIFICATIONS =====
        // Send notification emails after successful status update
        try {
            $company_name = $supplier['company_name'] ?? '';

            if ($action == 'approve') {
                if ($new_status == 'APPROVED_L1') {
                    // Notify Finance team - application is ready for L2 review
                    $finance_stmt = $pdo->query("SELECT username FROM users WHERE role = 'FINANCE'");
                    $finance_users = $finance_stmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($finance_users as $fu) {
                        sendWorkflowNotificationEmail(
                            $fu['username'],
                            'Finance Team',
                            $company_name,
                            $supplier_id,
                            'Level 2 - Finance Verification'
                        );
                    }
                } elseif ($new_status == 'APPROVED_L2') {
                    // Notify IT team - application is ready for L3 review
                    $it_stmt = $pdo->query("SELECT username FROM users WHERE role = 'IT'");
                    $it_users = $it_stmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($it_users as $iu) {
                        sendWorkflowNotificationEmail(
                            $iu['username'],
                            'IT Team',
                            $company_name,
                            $supplier_id,
                            'Level 3 - IT Final Activation'
                        );
                    }
                } elseif ($new_status == 'ACTIVE') {
                    // Notify vendor - successfully onboarded
                    sendVendorApprovalEmail(
                        $supplier['email'],
                        $company_name,
                        $ebs_vendor_code
                    );
                }
            }
        } catch (Exception $e) {
            // Email failures should not block workflow
            // Errors are logged inside the mail helper functions
        }
    }
    header("Location: dashboard.php");
    exit();
}
?>