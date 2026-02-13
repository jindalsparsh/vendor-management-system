<?php
session_start();
require '../includes/db_connect.php';

// Auth Check: Only ADMIN
if (!isset($_SESSION['is_logged_in']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['csv_file'])) {
    header("Location: admin_import.php");
    exit();
}

$file = $_FILES['csv_file'];

// Check for errors
if ($file['error'] !== UPLOAD_ERR_OK) {
    header("Location: admin_import.php?error=File upload failed.");
    exit();
}

// Check extension
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
if (strtolower($ext) !== 'csv') {
    header("Location: admin_import.php?error=Invalid file type. Please upload a CSV.");
    exit();
}

$handle = fopen($file['tmp_name'], 'r');
if (!$handle) {
    header("Location: admin_import.php?error=Could not open file.");
    exit();
}

// Skip header row
$header = fgetcsv($handle);

$success_count = 0;
$skip_count = 0;
$errors = [];

// Prepare statements
$insert_sql = "INSERT INTO suppliers (
    email, password_hash, company_name, company_address, city, state, postal_code, 
    contact_first_name, mobile_number, pan_number, bank_name, account_number, ifsc_code,
    status, force_password_change, created_at
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'SUBMITTED', 1, NOW())";
$insert_stmt = $pdo->prepare($insert_sql);

while (($data = fgetcsv($handle)) !== FALSE) {
    // Expected order: email, password, company_name, company_address, city, state, postal_code, contact_name, mobile, pan, bank, account, ifsc
    if (count($data) < 13)
        continue;

    $email = trim($data[0]);
    $password = trim($data[1]);
    $company_name = trim($data[2]);
    $company_address = trim($data[3]);
    $city = trim($data[4]);
    $state = trim($data[5]);
    $postal_code = trim($data[6]);
    $contact_name = trim($data[7]);
    $mobile = trim($data[8]);
    $pan = trim($data[9]);
    $bank = trim($data[10]);
    $account = trim($data[11]);
    $ifsc = trim($data[12]);

    if (empty($email) || empty($password))
        continue;

    // Check if duplicate
    $check_stmt->execute([$email]);
    if ($check_stmt->fetchColumn() > 0) {
        $skip_count++;
        continue;
    }

    // Hash password and insert
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    try {
        $insert_stmt->execute([
            $email,
            $hashed_password,
            $company_name,
            $company_address,
            $city,
            $state,
            $postal_code,
            $contact_name,
            $mobile,
            $pan,
            $bank,
            $account,
            $ifsc
        ]);

        // Send Onboarding Email
        sendVendorOnboardingEmail($company_name, $email, $password);

        $success_count++;
    } catch (Exception $e) {
        $errors[] = "Error inserting $email: " . $e->getMessage();
    }
}

fclose($handle);

if ($success_count > 0 || $skip_count > 0) {
    $msg = $success_count . " imported successfully.";
    if ($skip_count > 0)
        $msg .= " " . $skip_count . " skipped (duplicates).";
    header("Location: admin_import.php?success=" . urlencode($msg));
} else {
    header("Location: admin_import.php?error=No valid records found in CSV.");
}
exit();
?>