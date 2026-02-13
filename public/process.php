<?php
require_once '../includes/db_connect.php';
require_once '../includes/config.php';
require_once '../includes/mail_helper.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nature_of_business = isset($_POST['nature_of_business']) ? implode(", ", $_POST['nature_of_business']) : '';
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $mobile = trim($_POST['mobile_number'] ?? '');
    $pan = strtoupper(trim($_POST['pan_number'] ?? ''));
    $msme = strtoupper(trim($_POST['msme_reg_number'] ?? ''));
    $is_msme = ($_POST['registered_msme'] ?? 'No') === 'Yes';

    // SERVER-SIDE VALIDATION
    $errors = [];

    if (isset($_POST['email']) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    if (!empty($mobile) && !preg_match('/^\d{10}$/', $mobile)) {
        $errors[] = "Mobile number must be exactly 10 digits.";
    }

    $alt_mobile = trim($_POST['alt_mobile_number'] ?? '');
    if (!empty($alt_mobile) && !preg_match('/^\d{10}$/', $alt_mobile)) {
        $errors[] = "Alternate mobile number must be exactly 10 digits.";
    }

    $landline = trim($_POST['landline_number'] ?? '');
    if (!empty($landline) && !preg_match('/^\d{10,12}$/', $landline)) {
        $errors[] = "Landline number must be between 10 to 12 digits.";
    }

    if (!empty($pan) && !preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/', $pan)) {
        $errors[] = "Invalid PAN number format.";
    }

    if ($is_msme) {
        if (empty($msme)) {
            $errors[] = "MSME Registration number is required.";
        } elseif (!preg_match('/^UDYAM-[A-Z]{2}-[0-9]{2}-[0-9]{7}$/', $msme)) {
            $errors[] = "Invalid MSME format (Expected: UDYAM-XX-00-0000000).";
        }
    }

    if (!empty($errors)) {
        $err_str = implode(" | ", $errors);
        $redirect = isset($_SESSION['user_id']) ? "index.php?error=" . urlencode($err_str) . "&action=edit" : "register.php?error=" . urlencode($err_str);
        header("Location: " . $redirect);
        exit();
    }

    // File Upload Handler
    $uploaded_files = [
        'pan_card_doc' => null,
        'gst_cert_doc' => null,
        'cancelled_cheque_doc' => null,
        'msme_cert_doc' => null
    ];

    $upload_dir = 'uploads/';
    if (!is_dir($upload_dir))
        mkdir($upload_dir, 0777, true);

    foreach ($uploaded_files as $field => &$path) {
        if (isset($_FILES[$field]) && $_FILES[$field]['error'] == 0) {
            $filename = time() . '_' . basename($_FILES[$field]['name']);
            $target_path = $upload_dir . $filename;
            if (move_uploaded_file($_FILES[$field]['tmp_name'], $target_path)) {
                $path = $target_path;
            }
        }
    }

    // Fetch existing data for vendor if logged in to preserve files
    $existing_data = null;
    if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'VENDOR') {
        $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $existing_data = $stmt->fetch();
    }

    $data = [
        'company_name' => $_POST['company_name'],
        'company_address' => $_POST['company_address'],
        'city' => $_POST['city'],
        'state' => $_POST['state'],
        'postal_code' => $_POST['postal_code'],
        'country' => $_POST['country'],
        'supplier_website' => $_POST['supplier_website'],
        'nature_of_business' => $nature_of_business,
        'product_services_type' => $_POST['product_services_type'],
        'market_type' => $_POST['market_type'],
        'contact_first_name' => $_POST['contact_first_name'],
        'contact_middle_name' => $_POST['contact_middle_name'],
        'contact_last_name' => $_POST['contact_last_name'],
        'mobile_number' => $_POST['mobile_number'],
        'alt_mobile_number' => $_POST['alt_mobile_number'] ?? null,
        'landline_number' => $_POST['landline_number'] ?? null,
        'supplier_type' => $_POST['supplier_type'] ?? null,
        'item_main_group' => $_POST['item_main_group'] ?? null,
        'registered_msme' => $_POST['registered_msme'] ?? 'No',
        'msme_reg_number' => $_POST['msme_reg_number'] ?? null,
        'msme_type' => $_POST['msme_type'] ?? null,
        'itr_status' => $_POST['itr_status'] ?? null,
        'pan_number' => $_POST['pan_number'],
        'under_gst' => $_POST['under_gst'] ?? 'No',
        'gst_reg_number' => $_POST['gst_reg_number'] ?? null,
        'tan_number' => $_POST['tan_number'] ?? null,
        'bank_name' => $_POST['bank_name'],
        'account_type' => $_POST['account_type'] ?? 'Current',
        'account_number' => $_POST['account_number'],
        'ifsc_code' => $_POST['ifsc_code'],
        'bank_branch_address' => $_POST['bank_branch_address'] ?? null,
        'bank_city' => $_POST['bank_city'] ?? null,
        'bank_state' => $_POST['bank_state'] ?? null,
        'bank_postal_code' => $_POST['bank_postal_code'] ?? null,
        'risk_classification' => $_POST['risk_classification'] ?? 'Low',

        // Document Availability Flags
        'has_pan' => $_POST['has_pan'] ?? 'Yes',
        'has_gst' => $_POST['has_gst'] ?? 'Yes',
        'has_cheque' => $_POST['has_cheque'] ?? 'Yes',
        'has_msme' => $_POST['has_msme'] ?? 'No',

        // Preserve existing files if new ones weren't uploaded
        'pan_card_doc' => $uploaded_files['pan_card_doc'] ?? ($existing_data['pan_card_doc'] ?? null),
        'gst_cert_doc' => $uploaded_files['gst_cert_doc'] ?? ($existing_data['gst_cert_doc'] ?? null),
        'cancelled_cheque_doc' => $uploaded_files['cancelled_cheque_doc'] ?? ($existing_data['cancelled_cheque_doc'] ?? null),
        'msme_cert_doc' => $uploaded_files['msme_cert_doc'] ?? ($existing_data['msme_cert_doc'] ?? null),
        'declaration_accepted' => isset($_POST['declaration']) ? 1 : 0
    ];

    // SERVER-SIDE VALIDATION FOR CONDITIONAL DOCUMENTS
    $has_pan = $data['has_pan'] === 'Yes';
    $has_gst = $data['has_gst'] === 'Yes';
    $has_cheque = $data['has_cheque'] === 'Yes';
    $has_msme = $data['has_msme'] === 'Yes';

    if ($has_pan && !$data['pan_card_doc'])
        $errors[] = "PAN Card document is mandatory when selected 'Yes'.";
    if ($has_gst && !$data['gst_cert_doc'])
        $errors[] = "GST Registration document is mandatory when selected 'Yes'.";
    if ($has_cheque && !$data['cancelled_cheque_doc'])
        $errors[] = "Cancelled Cheque document is mandatory when selected 'Yes'.";
    if ($has_msme && !$data['msme_cert_doc'])
        $errors[] = "MSME Certificate is mandatory when selected 'Yes'.";

    // Handle Email and Password
    // For NEW registrations: email and password are REQUIRED
    // For UPDATES (logged in vendors): email/password are optional
    if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'VENDOR') {
        // UPDATE: Only update email/password if provided
        if (!empty($email)) {
            $data['email'] = $email;
        }
        if (!empty($password)) {
            $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }
    } else {
        // NEW REGISTRATION: Email and password are REQUIRED
        if (empty($email) || empty($password)) {
            header("Location: register.php?error=" . urlencode("Email and password are required."));
            exit();
        }
        $data['email'] = $email;
        $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
    }

    // If user is logged in, use UPDATE, otherwise INSERT
    if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'VENDOR') {
        $set_parts = [];
        $params = [];
        foreach ($data as $col => $val) {
            $set_parts[] = "$col = ?";
            $params[] = $val;
        }
        $params[] = $_SESSION['user_id'];
        // RESET STATUS to pending (SUBMITTED) on edit, EXCEPT if it's already at a higher level (keep existing logic)
        // BUT if it's a DRAFT, it MUST become SUBMITTED
        $target_status = 'SUBMITTED';
        $sql = "UPDATE suppliers SET status = ?, created_at = NOW(), " . implode(", ", $set_parts) . " WHERE id = ?";
        array_unshift($params, $target_status);
    } else {
        // NEW REGISTRATION: Set status to SUBMITTED so it appears in Purchase team's inbox
        $data['status'] = 'SUBMITTED';
        $cols = array_keys($data);
        $placeholders = array_fill(0, count($cols), "?");
        $params = array_values($data);
        $sql = "INSERT INTO suppliers (" . implode(", ", $cols) . ") VALUES (" . implode(", ", $placeholders) . ")";
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $id = (isset($_SESSION['user_id']) && $_SESSION['role'] === 'VENDOR') ? $_SESSION['user_id'] : $pdo->lastInsertId();

        // Notify Purchase team about new/resubmitted application
        try {
            $company_name = $data['company_name'] ?? '';
            $purchase_stmt = $pdo->query("SELECT username FROM users WHERE role = 'PURCHASER'");
            $purchase_users = $purchase_stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($purchase_users as $pu) {
                sendWorkflowNotificationEmail(
                    $pu['username'],
                    'Purchase Team',
                    $company_name,
                    $id,
                    'Level 1 - Purchase Team Verification'
                );
            }
        } catch (Exception $e) {
            // Email failures should not block registration
        }

        header("Location: index.php?success=1&id=" . $id);
        exit;
    } catch (PDOException $e) {
        die("Registration failed: " . $e->getMessage());
    }
}
?>