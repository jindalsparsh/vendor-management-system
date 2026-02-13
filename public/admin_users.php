<?php
session_start();
require_once '../includes/db_connect.php';

// Strict Access Control
if (!isset($_SESSION['is_logged_in']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: dashboard.php"); // Redirect non-admins back to dashboard
    exit();
}

// Fetch all users (Staff + Vendors)
$stmt = $pdo->query("
    (SELECT id, username, role, created_at FROM users)
    UNION ALL
    (SELECT id, email as username, 'VENDOR' as role, created_at FROM suppliers)
    ORDER BY created_at DESC
");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" type="image/png" href="../assets/img/favicon.ico">
    <style>
        .admin-layout {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            padding: 30px;
            background: #f8fafc;
        }

        .header-action {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .user-table {
            width: 100%;
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border-collapse: collapse;
        }

        .user-table th,
        .user-table td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .user-table th {
            background: #f1f5f9;
            font-weight: 600;
            color: #475569;
        }

        .role-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }

        .role-admin {
            background: #fee2e2;
            color: #991b1b;
        }

        .role-purchaser {
            background: #dbf4ff;
            color: #075985;
        }

        .role-finance {
            background: #dcfce7;
            color: #166534;
        }

        .role-it {
            background: #f3e8ff;
            color: #6b21a8;
        }

        .role-vendor {
            background: #fffbeb;
            color: #b45309;
        }

        .action-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
            color: #64748b;
            transition: color 0.2s;
        }

        .action-btn:hover {
            color: #0f172a;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin: 0;
        }

        .btn-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <button class="mobile-nav-toggle" onClick="toggleSidebar()">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
    </button>

    <div class="dashboard-layout">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">JIL- VMS</div>
            <nav class="sidebar-nav">
                <a href="admin_dashboard.php" class="nav-item">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path
                            d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z">
                        </path>
                        <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                        <line x1="12" y1="22.08" x2="12" y2="12"></line>
                    </svg>
                    <span>Analytics</span>
                </a>
                <a href="dashboard.php" class="nav-item">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                    <span>Applications</span>
                </a>
                <a href="admin_users.php" class="nav-item active">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    <span>User Management</span>
                </a>
                <a href="logout.php" class="nav-item logout-item">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    <span>Logout</span>
                </a>
            </nav>
        </aside>

        <main class="main-content">
            <?php $page_title = "User Management"; ?>
            <header class="top-bar">
                <div>
                    <h1 class="page-title"><?php echo $page_title; ?></h1>
                    <p class="subtitle">System User Access Management</p>
                </div>
                <div class="top-bar-actions" style="display: flex; gap: 10px; align-items: center;">
                    <a href="admin_import.php" class="btn-primary"
                        style="background: #3b82f6; text-decoration: none; display: flex; align-items: center; gap: 8px; padding: 12px 18px; font-size: 0.75rem; border-radius: 8px; font-weight: 700; white-space: nowrap; width: 165px; justify-content: center; box-sizing: border-box; text-transform: uppercase; letter-spacing: 0.05em; transition: all 0.2s; box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.2);">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.5" style="flex-shrink: 0;">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                        Bulk Import
                    </a>
                    <button class="btn-primary" onClick="openModal('addModal')"
                        style="background: #3b82f6; display: flex; align-items: center; gap: 8px; padding: 12px 18px; font-size: 0.75rem; border-radius: 8px; font-weight: 700; border: none; cursor: pointer; white-space: nowrap; width: 165px; justify-content: center; box-sizing: border-box; text-transform: uppercase; letter-spacing: 0.05em; transition: all 0.2s; box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.2);">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.5" style="flex-shrink: 0;">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        Add New User
                    </button>
                </div>
            </header>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert success"
                    style="background: #dcfce7; color: #166534; padding: 12px; border-radius: 6px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['error'])): ?>
                <div class="alert error"
                    style="background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 6px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>Username / Email</th>
                            <th>Role</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 500; color: #0f172a;">
                                        <?php echo htmlspecialchars($user['username']); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="role-badge role-<?php echo strtolower($user['role']); ?>">
                                        <?php echo htmlspecialchars($user['role']); ?>
                                    </span>
                                </td>
                                <td style="color: #64748b; font-size: 0.9rem;">
                                    <?php echo date('M j, Y h:i A', strtotime($user['created_at'])); ?>
                                </td>
                                <td>
                                    <button onclick='editUser(<?php echo json_encode($user); ?>)' class="action-btn"
                                        title="Edit">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg>
                                    </button>
                                    <?php if ($user['id'] != $_SESSION['user_id'] || $user['role'] === 'VENDOR'): ?>
                                        <button onClick="deleteUser(<?php echo $user['id']; ?>, '<?php echo $user['role']; ?>')"
                                            class="action-btn" style="color: #ef4444;" title="Delete">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                <path
                                                    d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2">
                                                </path>
                                            </svg>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- ADD USER MODAL -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Add New User</h2>
                <button onClick="closeModal('addModal')" class="btn-close">&times;</button>
            </div>
            <form action="admin_user_action.php" method="POST">
                <input type="hidden" name="action" value="add_user">
                <div class="form-group" style="margin-bottom: 16px;">
                    <label>Username (Email)</label>
                    <input type="email" name="username" required placeholder="user@swanrose.co"
                        style="width: 100%; padding: 10px; margin-top: 6px; border: 1px solid #e2e8f0; border-radius: 6px;">
                </div>
                <div class="form-group" style="margin-bottom: 16px;">
                    <label>Role</label>
                    <select name="role" required
                        style="width: 100%; padding: 10px; margin-top: 6px; border: 1px solid #e2e8f0; border-radius: 6px;">
                        <option value="VENDOR">Vendor</option>
                        <option value="PURCHASER">Purchaser</option>
                        <option value="FINANCE">Finance</option>
                        <option value="IT">IT</option>
                        <option value="ADMIN">Admin</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 24px;">
                    <label>Password</label>
                    <input type="password" name="password" required placeholder="*******"
                        style="width: 100%; padding: 10px; margin-top: 6px; border: 1px solid #e2e8f0; border-radius: 6px;">
                </div>
                <button type="submit" class="btn-primary" style="width: 100%;">Create User</button>
            </form>
        </div>
    </div>

    <!-- EDIT USER MODAL -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Edit User</h2>
                <button onClick="closeModal('editModal')" class="btn-close">&times;</button>
            </div>
            <form action="admin_user_action.php" method="POST">
                <input type="hidden" name="action" value="edit_user">
                <input type="hidden" name="user_id" id="edit_user_id">
                <input type="hidden" name="role" id="edit_user_role">

                <div class="form-group" style="margin-bottom: 16px;">
                    <label>Username</label>
                    <input type="text" id="edit_username" disabled
                        style="width: 100%; padding: 10px; margin-top: 6px; border: 1px solid #e2e8f0; border-radius: 6px; background: #f1f5f9;">
                </div>
                <div class="form-group" style="margin-bottom: 16px;">
                    <label>Role</label>
                    <select name="role" id="edit_role" required
                        style="width: 100%; padding: 10px; margin-top: 6px; border: 1px solid #e2e8f0; border-radius: 6px;">
                        <option value="VENDOR">Vendor</option>
                        <option value="PURCHASER">Purchaser</option>
                        <option value="FINANCE">Finance</option>
                        <option value="IT">IT</option>
                        <option value="ADMIN">Admin</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 24px;">
                    <label>New Password (Optional)</label>
                    <input type="password" name="password" placeholder="Leave blank to keep current"
                        style="width: 100%; padding: 10px; margin-top: 6px; border: 1px solid #e2e8f0; border-radius: 6px;">
                </div>
                <button type="submit" class="btn-primary" style="width: 100%;">Update User</button>
            </form>
        </div>
    </div>

    <!-- DELETE FORM (Hidden) -->
    <form id="deleteForm" action="admin_user_action.php" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete_user">
        <input type="hidden" name="user_id" id="delete_user_id">
        <input type="hidden" name="role" id="delete_user_role">
    </form>

    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function (event) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.querySelector('.mobile-nav-toggle');
            if (window.innerWidth <= 1024 &&
                sidebar &&
                !sidebar.contains(event.target) &&
                !toggle.contains(event.target) &&
                sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
            }
        });

        function openModal(id) {
            document.getElementById(id).classList.add('active');
        }
        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
        }
        function editUser(user) {
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_user_role').value = user.role;
            document.getElementById('edit_username').value = user.username;
            document.getElementById('edit_role').value = user.role;
            openModal('editModal');
        }
        function deleteUser(id, role) {
            if (confirm('Are you sure you want to delete this ' + role.toLowerCase() + '? This action cannot be undone.')) {
                document.getElementById('delete_user_id').value = id;
                document.getElementById('delete_user_role').value = role;
                document.getElementById('deleteForm').submit();
            }
        }
        // Close modal on outside click
        window.onclick = function (event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }
        }
    </script>
</body>

</html>