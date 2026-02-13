<?php
session_start();
require '../includes/db_connect.php';

// Strict Access Control
if (!isset($_SESSION['is_logged_in']) || $_SESSION['role'] !== 'ADMIN') {
    die("Unauthorized Access");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. ADD USER
    if ($action === 'add_user') {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $role = $_POST['role'];

        if (empty($username) || empty($password) || empty($role)) {
            header("Location: admin_users.php?error=" . urlencode("All fields are required."));
            exit();
        }

        // SPECIAL HANDLING FOR VENDOR ROLE
        // Vendors are stored in the 'suppliers' table, not 'users' table
        if ($role === 'VENDOR') {
            // Check if email already exists in suppliers table
            $stmt = $pdo->prepare("SELECT id FROM suppliers WHERE email = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                header("Location: admin_users.php?error=" . urlencode("A vendor with this email already exists."));
                exit();
            }

            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            // Insert vendor into suppliers table with DRAFT status
            $stmt = $pdo->prepare("INSERT INTO suppliers (email, password_hash, status, company_name, force_password_change) VALUES (?, ?, 'DRAFT', NULL, 1)");

            try {
                $stmt->execute([$username, $password_hash]);

                // Send Onboarding Email
                sendVendorOnboardingEmail(null, $username, $password);

                header("Location: admin_users.php?success=" . urlencode("Vendor created successfully and onboarding email sent."));
            } catch (PDOException $e) {
                header("Location: admin_users.php?error=" . urlencode("Database Error: " . $e->getMessage()));
            }
        } else {
            // INTERNAL STAFF (PURCHASER, FINANCE, IT, ADMIN) - use users table
            // Check if username exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                header("Location: admin_users.php?error=" . urlencode("Username already exists."));
                exit();
            }

            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role, force_password_change) VALUES (?, ?, ?, 1)");

            try {
                $stmt->execute([$username, $password_hash, $role]);
                header("Location: admin_users.php?success=" . urlencode("User created successfully."));
            } catch (PDOException $e) {
                header("Location: admin_users.php?error=" . urlencode("Database Error: " . $e->getMessage()));
            }
        }
    }
    // 2. EDIT USER
    elseif ($action === 'edit_user') {
        $id = $_POST['user_id'];
        $role = $_POST['role'];
        $password = trim($_POST['password']);

        // Determine table based on role
        if ($role === 'VENDOR') {
            $table = 'suppliers';
            $password_col = 'password_hash';
            // Vendors don't have a 'role' column in suppliers table, so we ignore role update
            if (!empty($password)) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE suppliers SET password_hash = ? WHERE id = ?");
                $params = [$password_hash, $id];
            } else {
                // If no password change, nothing to update for a vendor in this context
                header("Location: admin_users.php?success=" . urlencode("Vendor profile remains unchanged."));
                exit();
            }
        } else {
            // Internal staff
            if (!empty($password)) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET role = ?, password_hash = ? WHERE id = ?");
                $params = [$role, $password_hash, $id];
            } else {
                $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
                $params = [$role, $id];
            }
        }

        try {
            $stmt->execute($params);
            header("Location: admin_users.php?success=" . urlencode("User updated successfully."));
        } catch (PDOException $e) {
            header("Location: admin_users.php?error=" . urlencode("Database Error: " . $e->getMessage()));
        }
    }
    // 3. DELETE USER
    elseif ($action === 'delete_user') {
        $id = $_POST['user_id'];
        $role = $_POST['role'] ?? 'STAFF';

        // Prevent deleting yourself (only for staff, as admin is a staff role)
        if ($role !== 'VENDOR' && $id == $_SESSION['user_id']) {
            header("Location: admin_users.php?error=" . urlencode("You cannot delete your own account."));
            exit();
        }

        if ($role === 'VENDOR') {
            $stmt = $pdo->prepare("DELETE FROM suppliers WHERE id = ?");
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        }

        try {
            $stmt->execute([$id]);
            header("Location: admin_users.php?success=" . urlencode(ucfirst(strtolower($role)) . " deleted successfully."));
        } catch (PDOException $e) {
            header("Location: admin_users.php?error=" . urlencode("Database Error: " . $e->getMessage()));
        }
    }
} else {
    header("Location: admin_users.php");
}
