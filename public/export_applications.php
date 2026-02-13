<?php
session_start();
require_once '../includes/db_connect.php';

// Access Control - Internal Teams and Admin Only
if (!isset($_SESSION['is_logged_in']) || $_SESSION['role'] === 'VENDOR') {
    header("Location: dashboard.php");
    exit();
}

$view = $_GET['view'] ?? 'all';
$role = $_SESSION['role'];

// Determine which statuses to include based on view
switch ($view) {
    case 'pending':
        // Applications pending for this team's review
        if ($role === 'PURCHASER') {
            $status_filter = "status = 'SUBMITTED'";
        } elseif ($role === 'FINANCE') {
            $status_filter = "status = 'APPROVED_L1'";
        } elseif ($role === 'IT') {
            $status_filter = "status = 'APPROVED_L2'";
        } else { // ADMIN sees all pending
            $status_filter = "status IN ('SUBMITTED', 'APPROVED_L1', 'APPROVED_L2')";
        }
        $filename = "pending_applications";
        break;

    case 'submitted':
        // Applications submitted (waiting for review)
        $status_filter = "status = 'SUBMITTED'";
        $filename = "submitted_applications";
        break;

    case 'history':
        // Previously approved applications
        $status_filter = "status = 'ACTIVE'";
        $filename = "approved_applications";
        break;

    case 'rejected':
        $status_filter = "status = 'REJECTED'";
        $filename = "rejected_applications";
        break;

    case 'all':
    default:
        $status_filter = "1=1"; // All records
        $filename = "all_applications";
        break;
}

// Fetch all relevant supplier data using actual column names from schema
$query = "SELECT 
    id AS 'Application #',
    company_name AS 'Company Name',
    nature_of_business AS 'Nature of Business',
    product_services_type AS 'Product/Service Type',
    pan_number AS 'PAN Number',
    gst_reg_number AS 'GST Number',
    contact_first_name AS 'Contact First Name',
    contact_last_name AS 'Contact Last Name',
    email AS 'Email',
    mobile_number AS 'Mobile Number',
    alt_mobile_number AS 'Alt Mobile Number',
    landline_number AS 'Landline Number',
    company_address AS 'Address',
    city AS 'City',
    state AS 'State',
    postal_code AS 'PIN Code',
    country AS 'Country',
    supplier_website AS 'Website',
    supplier_type AS 'Supplier Type',
    item_main_group AS 'Item Main Group',
    registered_msme AS 'Registered MSME',
    msme_reg_number AS 'MSME Reg Number',
    bank_name AS 'Bank Name',
    account_number AS 'Account Number',
    ifsc_code AS 'IFSC Code',
    status AS 'Status',
    ebs_vendor_code AS 'EBS Vendor Code',
    created_at AS 'Submitted At'
FROM suppliers
WHERE $status_filter
ORDER BY created_at DESC";

$stmt = $pdo->query($query);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Generate CSV
$filename = $filename . '_' . date('Y-m-d_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// Add BOM for Excel UTF-8 compatibility
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Write headers
if (count($results) > 0) {
    fputcsv($output, array_keys($results[0]));

    // Write data rows
    foreach ($results as $row) {
        fputcsv($output, $row);
    }
} else {
    fputcsv($output, ['No records found for this category']);
}

fclose($output);
exit();
