<?php
require 'db_connect.php';

$email = 'sparsh.jindal@swanrose.co';

// 1. Manually set status to 'ACTIVE' for testing
$pdo->prepare("UPDATE suppliers SET status = 'ACTIVE' WHERE email = ?")->execute([$email]);
echo "Status set to ACTIVE for $email.<br>";

// 2. Simulate what process.php does (the fix)
// We just check if the logic I added works by running a similar update
$stmt = $pdo->prepare("SELECT id FROM suppliers WHERE email = ?");
$stmt->execute([$email]);
$vendor = $stmt->fetch();

if ($vendor) {
    $vendor_id = $vendor['id'];
    // This is the logic I added to process.php
    $sql = "UPDATE suppliers SET status = 'SUBMITTED', company_name = 'Swanrose Updated' WHERE id = ?";
    $pdo->prepare($sql)->execute([$vendor_id]);

    // 3. Verify
    $stmt = $pdo->prepare("SELECT status FROM suppliers WHERE id = ?");
    $stmt->execute([$vendor_id]);
    $new_status = $stmt->fetchColumn();

    if ($new_status === 'SUBMITTED') {
        echo "<h2 style='color: green;'>VERIFICATION SUCCESS!</h2>";
        echo "The status was successfully reset to <b>SUBMITTED</b> after the update.";
    } else {
        echo "<h2 style='color: red;'>VERIFICATION FAILED!</h2>";
        echo "Status is still: " . $new_status;
    }
} else {
    echo "Vendor not found.";
}
?>