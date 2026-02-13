<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || !in_array($_SESSION['role'], ['PURCHASER', 'FINANCE', 'IT', 'ADMIN'])) {
    header("Location: login.php");
    exit();
}

// Security: Force Password Change if required
if (isset($_SESSION['password_change_required']) && $_SESSION['password_change_required'] === true) {
    header("Location: change_password.php");
    exit();
}

$role = $_SESSION['role'] ?? 'STAFF';
$team_name = "Staff Dashboard";
if ($role === 'PURCHASER')
    $team_name = "Purchase Team (Level 1)";
if ($role === 'FINANCE')
    $team_name = "Finance Team (Level 2)";
if ($role === 'IT')
    $team_name = "IT Team (Level 3)";
if ($role === 'ADMIN')
    $team_name = "System Administrator";

// Determine view type and search query
$view = $_GET['view'] ?? 'pending';
$search = trim($_GET['search'] ?? '');
$search_param = "%$search%";

// Section 1: Application Pending Review (Inbox)
$pending_query = "";
if ($role === 'PURCHASER') {
    $pending_query = "WHERE status = 'SUBMITTED'";
} elseif ($role === 'FINANCE') {
    $pending_query = "WHERE status = 'APPROVED_L1'";
} elseif ($role === 'IT') {
    $pending_query = "WHERE status = 'APPROVED_L2'";
} elseif ($role === 'ADMIN') {
    // Admin pending view shows everything currently in the verification pipeline
    $pending_query = "WHERE status IN ('SUBMITTED', 'APPROVED_L1', 'APPROVED_L2')";
}

// Section 2: All Submitted Applications (Master List)
$submitted_query = "WHERE status != 'DRAFT'";

// Section 3: Applications Submitted Previously (History)
$history_query = "WHERE status = 'ACTIVE'"; // History should primarily be successful activations

// Section 4: Rejected Applications
$rejected_query = "WHERE status = 'REJECTED'";

// Base Query Logic for Filtering
$status_filter = "";
if ($view === 'submitted') {
    $status_filter = $submitted_query;
    $view_title = "Applications Submitted";
} elseif ($view === 'history') {
    $status_filter = $history_query;
    $view_title = "Applications Submitted Previously";
} elseif ($view === 'rejected') {
    $status_filter = $rejected_query;
    $view_title = "Rejected Applications";
} else {
    $status_filter = $pending_query;
    $view_title = "Application Pending Review";
}

// Add Search Filter
if ($search !== '') {
    $status_filter .= " AND (company_name LIKE :search OR email LIKE :search OR contact_first_name LIKE :search OR city LIKE :search OR id LIKE :search)";
}

$page_title = $team_name;

// Pagination Configuration
$records_per_page = 5; // Set to 5 for easy testing
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $records_per_page;

// Count total records for this filter
$count_sql = "SELECT COUNT(*) FROM suppliers $status_filter";
$count_stmt = $pdo->prepare($count_sql);
if ($search !== '') {
    $count_stmt->bindValue(':search', $search_param);
}
$count_stmt->execute();
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $records_per_page);

// Fetch Suppliers with Pagination
$sql = "SELECT id, company_name, city, state, contact_first_name, mobile_number, status, ebs_vendor_code, created_at FROM suppliers $status_filter ORDER BY created_at DESC LIMIT $records_per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
if ($search !== '') {
    $stmt->bindValue(':search', $search_param);
}
$stmt->execute();
$suppliers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Vendor Management System | Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" type="image/png" href="../assets/img/favicon.ico">
</head>

<button class="mobile-nav-toggle" onClick="toggleSidebar()">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="3" y1="12" x2="21" y2="12"></line>
        <line x1="3" y1="6" x2="21" y2="6"></line>
        <line x1="3" y1="18" x2="21" y2="18"></line>
    </svg>
</button>

<div class="dashboard-layout">
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">JIL- VMS</div>
        <div
            style="padding: 0 24px; color: rgba(255,255,255,0.6); font-size: 0.75rem; text-transform: uppercase; margin-bottom: 20px;">
            <?php echo $team_name; ?>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php?view=pending" class="nav-item <?php echo $view == 'pending' ? 'active' : ''; ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                <span>Application Pending Review</span>
            </a>
            <a href="dashboard.php?view=submitted" class="nav-item <?php echo $view == 'submitted' ? 'active' : ''; ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                </svg>
                <span>Applications Submitted</span>
            </a>
            <a href="dashboard.php?view=history" class="nav-item <?php echo $view == 'history' ? 'active' : ''; ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <span>Application Submitted Previously</span>
            </a>
            <a href="dashboard.php?view=rejected" class="nav-item <?php echo $view == 'rejected' ? 'active' : ''; ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="15" y1="9" x2="9" y2="15"></line>
                    <line x1="9" y1="9" x2="15" y2="15"></line>
                </svg>
                <span>Rejected Applications</span>
            </a>
            <?php if ($role === 'ADMIN'): ?>
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
                <a href="admin_users.php" class="nav-item">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    <span>User Management</span>
                </a>
            <?php endif; ?>
        </nav>
        <div class="sidebar-footer">
            <a href="logout.php" class="nav-item logout-item">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <main class="main-content">
        <header class="top-bar">
            <div style="flex: 1;">
                <h1 class="page-title"><?php echo $view_title; ?></h1>
                <p class="subtitle" style="text-align: left; margin-bottom: 0;">Managing the vendor onboarding journey
                </p>
            </div>

            <!-- Search Box -->
            <div style="margin-right: 20px;">
                <form action="dashboard.php" method="GET"
                    style="display: flex; align-items: center; position: relative;">
                    <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
                    <div style="position: relative; display: flex; align-items: center;">
                        <span style="position: absolute; left: 12px; color: #94a3b8;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                        </span>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                            placeholder="Search App #, company, email..."
                            style="padding: 10px 16px 10px 40px; border: 1px solid #e2e8f0; border-radius: 8px; width: 300px; font-size: 0.9rem; transition: all 0.2s; background: #f8fafc; outline: none;"
                            onfocus="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 3px rgba(59, 130, 246, 0.1)'; this.style.background='white';"
                            onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'; this.style.background='#f8fafc';">
                    </div>
                    <?php if ($search !== ''): ?>
                        <a href="dashboard.php?view=<?php echo $view; ?>"
                            style="margin-left: 8px; color: #64748b; font-size: 0.85rem; text-decoration: none; font-weight: 500; display: flex; align-items: center; gap: 4px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                            Clear
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <div style="display: flex; align-items: center; gap: 1rem;">
                <a href="export_applications.php?view=<?php echo $view; ?>" class="btn-view"
                    style="background: #10b981; color: white; border: none; display: flex; align-items: center; gap: 6px; text-decoration: none;"
                    title="Download CSV">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    Export CSV
                </a>
                <span style="font-size: 0.875rem; color: #64748b;">Logged in as:
                    <b><?php echo $_SESSION['username']; ?></b></span>
                <a href="logout.php" class="btn-view" style="color: #ef4444; border: 1px solid #fee2e2;">Logout</a>
            </div>
        </header>

        <div class="glass-card animate-fade-in" style="margin-top: 20px;">
            <?php if (count($suppliers) > 0): ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>App #</th>
                                <th>Company</th>
                                <th>Location</th>
                                <th>Contact</th>
                                <th>Current Status</th>
                                <th>EBS Code</th>
                                <th>Submission Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($suppliers as $s): ?>
                                <tr>
                                    <td style="font-weight: 700; color: #3b82f6;"><?php echo $s['id']; ?></td>
                                    <td>
                                        <div style="font-weight: 600; color: #0f172a;">
                                            <?php echo htmlspecialchars($s['company_name']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-size: 0.85rem; color: #1e293b; font-weight: 500;">
                                            <?php echo htmlspecialchars($s['city'] ?? ''); ?>,
                                            <?php echo htmlspecialchars($s['state'] ?? ''); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div><?php echo htmlspecialchars($s['contact_first_name']); ?></div>
                                        <div style="font-size: 0.75rem; color: #64748b;">
                                            <?php echo htmlspecialchars($s['mobile_number']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge status-<?php echo strtolower($s['status']); ?>">
                                            <?php echo str_replace('_', ' ', $s['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div
                                            style="font-family: monospace; font-weight: 700; color: #1e293b; background: #f1f5f9; padding: 4px 8px; border-radius: 4px; display: inline-block;">
                                            <?php echo $s['ebs_vendor_code'] ?: '—'; ?>
                                        </div>
                                    </td>
                                    <td><?php echo date('M d, Y h:i A', strtotime($s['created_at'])); ?></td>
                                    <td>
                                        <a href="view_supplier.php?id=<?php echo $s['id']; ?>" class="btn-view">
                                            View Details
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_pages > 1): ?>
                    <div class="pagination-container">
                        <div class="pagination-info">
                            Showing <b><?php echo $offset + 1; ?></b> to
                            <b><?php echo min($offset + $records_per_page, $total_records); ?></b> of
                            <b><?php echo $total_records; ?></b> entries
                        </div>
                        <div class="pagination-controls">
                            <!-- Previous Page -->
                            <a href="dashboard.php?view=<?php echo $view; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $current_page - 1; ?>"
                                class="page-link <?php echo $current_page <= 1 ? 'disabled' : ''; ?>">
                                &laquo; Prev
                            </a>

                            <!-- Page Numbers -->
                            <?php
                            $start_page = max(1, $current_page - 2);
                            $end_page = min($total_pages, $current_page + 2);

                            for ($i = $start_page; $i <= $end_page; $i++):
                                ?>
                                <a href="dashboard.php?view=<?php echo $view; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>"
                                    class="page-link <?php echo $i == $current_page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <!-- Next Page -->
                            <a href="dashboard.php?view=<?php echo $view; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $current_page + 1; ?>"
                                class="page-link <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>">
                                Next &raquo;
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 60px 20px;">
                    <div style="margin-bottom: 20px;">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#e2e8f0" stroke-width="1.5">
                            <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                        </svg>
                    </div>
                    <h3 style="color: #64748b; font-weight: 500;">No applications found</h3>
                    <p style="color: #94a3b8; font-size: 0.875rem;">There are no records matching the current criteria.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

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
            !sidebar.contains(event.target) &&
            !toggle.contains(event.target) &&
            sidebar.classList.contains('active')) {
            sidebar.classList.remove('active');
        }
    });
</script>
</body>

</html>