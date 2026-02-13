<?php
require 'db_connect.php';

try {
    $pdo->exec("ALTER TABLE suppliers ADD COLUMN registration_id VARCHAR(50) UNIQUE AFTER id");
    echo "Column 'registration_id' added successfully.\n";

    // Backfill existing records
    $stmt = $pdo->query("SELECT id FROM suppliers WHERE registration_id IS NULL");
    $suppliers = $stmt->fetchAll();

    $updateStmt = $pdo->prepare("UPDATE suppliers SET registration_id = ? WHERE id = ?");
    foreach ($suppliers as $s) {
        $regId = "JIL/" . date('Y') . "/" . str_pad($s['id'], 4, '0', STR_PAD_LEFT);
        $updateStmt->execute([$regId, $s['id']]);
        echo "Updated Supplier ID {$s['id']} with Ref: $regId\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>