<?php
session_start();
require_once '../includes/db_connect.php';

// Strict Access Control - Admin Only
if (!isset($_SESSION['is_logged_in']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: dashboard.php");
    exit();
}

// Get selected filter from query string
$filter = $_GET['filter'] ?? 'all';

// Build query based on filter
$vendors_query = "SELECT id, company_name, email, contact_first_name, status, ebs_vendor_code, created_at FROM suppliers";
$staff_query = "SELECT id, username, role, created_at FROM users";
$is_staff_view = false;

switch ($filter) {
    case 'active':
        $vendors_query .= " WHERE status = 'ACTIVE'";
        $title = "Active Vendors";
        break;
    case 'pending':
        $vendors_query .= " WHERE status = 'SUBMITTED'";
        $title = "Pending Submissions";
        break;
    case 'in_progress':
        $vendors_query .= " WHERE status IN ('APPROVED_L1', 'APPROVED_L2')";
        $title = "In-Progress Approvals";
        break;
    case 'rejected':
        $vendors_query .= " WHERE status = 'REJECTED'";
        $title = "Rejected Vendors";
        break;
    case 'draft':
        $vendors_query .= " WHERE status = 'DRAFT'";
        $title = "Draft Vendors";
        break;
    case 'staff':
        $is_staff_view = true;
        $title = "Internal Staff";
        break;
    default:
        $title = "All Vendors";
        break;
}

$vendors_query .= " ORDER BY created_at DESC";
$staff_query .= " ORDER BY created_at DESC";

// Fetch data based on filter
if ($is_staff_view) {
    $stmt = $pdo->query($staff_query);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->query($vendors_query);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Aggregate Metrics for display
$stmt = $pdo->query("SELECT COUNT(*) AS total FROM suppliers");
$total_vendors = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) AS total FROM suppliers WHERE status = 'ACTIVE'");
$active_vendors = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) AS total FROM suppliers WHERE status = 'SUBMITTED'");
$pending_vendors = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) AS total FROM suppliers WHERE status IN ('APPROVED_L1', 'APPROVED_L2')");
$in_progress_vendors = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) AS total FROM suppliers WHERE status = 'REJECTED'");
$rejected_vendors = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) AS total FROM suppliers WHERE status = 'DRAFT'");
$draft_vendors = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) AS total FROM users");
$total_staff = $stmt->fetch()['total'];

// Status Data for Chart
$status_counts = [
    'DRAFT' => $draft_vendors,
    'SUBMITTED' => $pending_vendors,
    'APPROVED_L1' => 0,
    'APPROVED_L2' => 0,
    'ACTIVE' => $active_vendors,
    'REJECTED' => $rejected_vendors
];
$stmt = $pdo->query("SELECT status, COUNT(*) AS count FROM suppliers GROUP BY status");
while ($row = $stmt->fetch()) {
    $status_counts[$row['status']] = $row['count'];
}

// Staff by Role for Bar Chart
$stmt = $pdo->query("SELECT role, COUNT(*) AS count FROM users GROUP BY role");
$staff_roles = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard | Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" type="image/png" href="../assets/img/favicon.ico">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
        .analytics-layout {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 24px;
            margin-top: 24px;
        }

        @media (max-width: 1200px) {
            .analytics-layout {
                grid-template-columns: 1fr;
            }
        }

        .filter-section {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 24px;
        }

        .filter-label {
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 12px;
            display: block;
        }

        .filter-select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 500;
            color: #0f172a;
            background: #f8fafc;
            cursor: pointer;
            transition: all 0.2s;
        }

        .filter-select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .metric-highlight {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            border-radius: 16px;
            padding: 32px;
            color: white;
            text-align: center;
            margin-bottom: 24px;
        }

        .metric-highlight .value {
            font-size: 4rem;
            font-weight: 800;
            line-height: 1;
        }

        .metric-highlight .label {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-top: 8px;
        }

        .chart-container {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .chart-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 16px;
        }

        .data-table-container {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            max-height: 600px;
            overflow-y: auto;
        }

        .data-table-container h3 {
            font-size: 1.1rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .records-table {
            width: 100%;
            border-collapse: collapse;
        }

        .records-table th,
        .records-table td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid #f1f5f9;
        }

        .records-table th {
            background: #f8fafc;
            font-weight: 600;
            color: #475569;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            position: sticky;
            top: 0;
        }

        .records-table tr:hover {
            background: #f8fafc;
        }

        .records-table td {
            font-size: 0.9rem;
            color: #0f172a;
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-active {
            background: #dcfce7;
            color: #166534;
        }

        .status-submitted {
            background: #ffedd5;
            color: #c2410c;
        }

        .status-approved_l1,
        .status-approved_l2 {
            background: #e0e7ff;
            color: #4338ca;
        }

        .status-rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-draft {
            background: #f1f5f9;
            color: #475569;
        }

        .role-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .role-admin {
            background: #fee2e2;
            color: #991b1b;
        }

        .role-purchaser {
            background: #dbeafe;
            color: #1e40af;
        }

        .role-finance {
            background: #dcfce7;
            color: #166534;
        }

        .role-it {
            background: #f3e8ff;
            color: #7c3aed;
        }

        .empty-state {
            text-align: center;
            padding: 48px 24px;
            color: #94a3b8;
        }

        .empty-state svg {
            width: 64px;
            height: 64px;
            margin-bottom: 16px;
            opacity: 0.5;
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
                <a href="admin_dashboard.php" class="nav-item active">
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
            <header class="top-bar">
                <div>
                    <h1 class="page-title">Analytics Dashboard</h1>
                    <p class="subtitle">Real-time overview of the Vendor Management System.</p>
                </div>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <span style="font-size: 0.875rem; color: #64748b;">Logged in as:
                        <b><?php echo $_SESSION['username']; ?></b></span>
                    <a href="logout.php" class="btn-view" style="color: #ef4444; border: 1px solid #fee2e2;">Logout</a>
                </div>
            </header>

            <!-- Filter Section -->
            <div class="filter-section">
                <label class="filter-label">Select Metric to View</label>
                <select class="filter-select" id="metricFilter"
                    onchange="window.location.href='admin_dashboard.php?filter=' + this.value">
                    <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Vendors
                        (<?php echo $total_vendors; ?>)</option>
                    <option value="active" <?php echo $filter === 'active' ? 'selected' : ''; ?>>Active Vendors
                        (<?php echo $active_vendors; ?>)</option>
                    <option value="pending" <?php echo $filter === 'pending' ? 'selected' : ''; ?>>Pending Submissions
                        (<?php echo $pending_vendors; ?>)</option>
                    <option value="in_progress" <?php echo $filter === 'in_progress' ? 'selected' : ''; ?>>In-Progress
                        Approvals (<?php echo $in_progress_vendors; ?>)</option>
                    <option value="rejected" <?php echo $filter === 'rejected' ? 'selected' : ''; ?>>Rejected Vendors
                        (<?php echo $rejected_vendors; ?>)</option>
                    <option value="draft" <?php echo $filter === 'draft' ? 'selected' : ''; ?>>Draft Vendors
                        (<?php echo $draft_vendors; ?>)</option>
                    <option value="staff" <?php echo $filter === 'staff' ? 'selected' : ''; ?>>Internal Staff
                        (<?php echo $total_staff; ?>)</option>
                </select>
            </div>

            <!-- Main Analytics Layout -->
            <div class="analytics-layout">
                <!-- Left Column: Metrics & Chart -->
                <div>
                    <div class="metric-highlight">
                        <div class="value"><?php echo count($records); ?></div>
                        <div class="label"><?php echo $title; ?></div>
                    </div>

                    <div class="chart-container">
                        <div class="chart-title">
                            <?php echo $is_staff_view ? 'Staff by Role' : 'Vendor Status Distribution'; ?>
                        </div>
                        <canvas id="mainChart"></canvas>
                    </div>
                </div>

                <!-- Right Column: Data Table -->
                <div class="data-table-container">
                    <h3>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                        </svg>
                        <?php echo $title; ?> - Details
                    </h3>

                    <?php if (count($records) > 0): ?>
                        <table class="records-table">
                            <thead>
                                <tr>
                                    <?php if ($is_staff_view): ?>
                                        <th>Username / Email</th>
                                        <th>Role</th>
                                        <th>Created At</th>
                                    <?php else: ?>
                                        <th>Company</th>
                                        <th>Contact Name</th>
                                        <th>Email</th>
                                        <th>EBS Code</th>
                                        <th>Status</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($records as $record): ?>
                                    <tr>
                                        <?php if ($is_staff_view): ?>
                                            <td style="font-weight: 500;"><?php echo htmlspecialchars($record['username']); ?></td>
                                            <td>
                                                <span class="role-badge role-<?php echo strtolower($record['role']); ?>">
                                                    <?php echo htmlspecialchars($record['role']); ?>
                                                </span>
                                            </td>
                                            <td style="color: #64748b; font-size: 0.85rem;">
                                                <?php echo date('M j, Y h:i A', strtotime($record['created_at'])); ?>
                                            </td>
                                        <?php else: ?>
                                            <td style="font-weight: 500;"><?php echo htmlspecialchars($record['company_name']); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($record['contact_first_name']); ?></td>
                                            <td style="color: #3b82f6;"><?php echo htmlspecialchars($record['email']); ?></td>
                                            <td style="font-family: monospace; font-weight: 700; color: #1e293b;">
                                                <?php echo $record['ebs_vendor_code'] ?: '—'; ?>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower($record['status']); ?>">
                                                    <?php echo str_replace('_', ' ', $record['status']); ?>
                                                </span>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path
                                    d="M9 12h6m-3-3v6m-7 4h14a2 2 0 002-2V7a2 2 0 00-2-2h-4l-2-2H8L6 5H4a2 2 0 00-2 2v10a2 2 0 002 2z">
                                </path>
                            </svg>
                            <p>No records found for this category.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }
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

        // Dynamic Chart based on filter
        const ctx = document.getElementById('mainChart').getContext('2d');
        <?php if ($is_staff_view): ?>
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: [<?php echo implode(',', array_map(function ($k) {
                        return "'$k'";
                    }, array_keys($staff_roles))); ?>],
                    datasets: [{
                        label: 'Count',
                        data: [<?php echo implode(',', array_values($staff_roles)); ?>],
                        backgroundColor: ['#3b82f6', '#22c55e', '#f97316', '#a855f7', '#ef4444'],
                        borderRadius: 8,
                        borderSkipped: false
                    }]
                },
                options: {
                    responsive: true,
                    indexAxis: 'y',
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { beginAtZero: true, grid: { display: false } },
                        y: { grid: { display: false } }
                    }
                }
            });
        <?php else: ?>
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Draft', 'Submitted', 'Approved L1', 'Approved L2', 'Active', 'Rejected'],
                    datasets: [{
                        data: [
                            <?php echo $status_counts['DRAFT']; ?>,
                            <?php echo $status_counts['SUBMITTED']; ?>,
                            <?php echo $status_counts['APPROVED_L1']; ?>,
                            <?php echo $status_counts['APPROVED_L2']; ?>,
                            <?php echo $status_counts['ACTIVE']; ?>,
                            <?php echo $status_counts['REJECTED']; ?>
                        ],
                        backgroundColor: ['#94a3b8', '#f97316', '#3b82f6', '#8b5cf6', '#22c55e', '#ef4444'],
                        borderWidth: 0,
                        hoverOffset: 10
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { padding: 20, usePointStyle: true, font: { size: 12 } }
                        }
                    },
                    cutout: '65%'
                }
            });
        <?php endif; ?>
    </script>
</body>

</html>