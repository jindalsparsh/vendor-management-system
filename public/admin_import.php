<?php
session_start();
require '../includes/db_connect.php';

// Auth Check: Only ADMIN can access
if (!isset($_SESSION['is_logged_in']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: login.php");
    exit();
}

$page_title = "Bulk Import Vendors";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo $page_title; ?> | JIL-VMS
    </title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .import-box {
            max-width: 600px;
            margin: 40px auto;
            padding: 32px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }

        .csv-format-info {
            background: #f8fafc;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            border-left: 4px solid #3b82f6;
        }

        .csv-format-info h3 {
            margin-top: 0;
            font-size: 1rem;
            color: #1e293b;
        }

        .csv-format-info ul {
            margin: 8px 0 0 20px;
            font-size: 0.875rem;
            color: #64748b;
            line-height: 1.6;
        }

        .file-upload-area {
            border: 2px dashed #cbd5e1;
            padding: 30px;
            text-align: center;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .file-upload-area:hover {
            border-color: #3b82f6;
            background: #f0f7ff;
        }

        .file-upload-area svg {
            color: #94a3b8;
            margin-bottom: 12px;
        }

        .btn-import {
            width: 100%;
            background: #3b82f6;
            color: white;
            padding: 12px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            margin-top: 24px;
            cursor: pointer;
        }

        .btn-import:hover {
            background: #2563eb;
        }
    </style>
</head>

<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">JIL- VMS</div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                    <span>Dashboard</span>
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
            <header class="top-bar">
                <div>
                    <h1 class="page-title"><?php echo $page_title; ?></h1>
                    <p class="subtitle">Import vendors directly into the system</p>
                </div>
                <div class="top-bar-actions">
                    <a href="admin_users.php" class="btn-view"
                        style="text-decoration: none; display: flex; align-items: center; gap: 8px; color: #64748b; border: 1px solid #e2e8f0; padding: 10px 16px; border-radius: 8px; font-weight: 600; font-size: 0.85rem;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <line x1="19" y1="12" x2="5" y2="12"></line>
                            <polyline points="12 19 5 12 12 5"></polyline>
                        </svg>
                        Back to Users
                    </a>
                </div>
            </header>

            <?php if (isset($_GET['success'])): ?>
                <div
                    style="background: #2ecc71; color: white; padding: 16px; border-radius: 8px; margin-bottom: 24px; text-align: center;">
                    <b>Import Complete!</b>
                    <?php echo htmlspecialchars($_GET['success']); ?> vendors processed.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div
                    style="background: #e74c3c; color: white; padding: 16px; border-radius: 8px; margin-bottom: 24px; text-align: center;">
                    <b>Import Error:</b>
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <div class="import-box"
                style="max-width: 800px; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); border: 1px solid #e2e8f0; border-radius: 16px; box-shadow: 0 20px 40px rgba(0,0,0,0.08); padding: 40px;">
                <div class="csv-format-info"
                    style="background: #eff6ff; border-left: 5px solid #3b82f6; padding: 24px; border-radius: 12px; margin-bottom: 32px;">
                    <h3
                        style="color: #1e3a8a; display: flex; align-items: center; gap: 10px; font-size: 1.2rem; margin-bottom: 12px;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="16" x2="12" y2="12"></line>
                            <line x1="12" y1="8" x2="12.01" y2="8"></line>
                        </svg>
                        Professional CSV Schema Selection
                    </h3>
                    <p style="font-size: 0.95rem; color: #1e40af; margin-bottom: 20px;">To ensure a 100% deployable
                        import, your CSV must contain these <b>13 columns</b> in exact order:</p>
                    <div
                        style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; font-size: 0.85rem; color: #334155; background: white; padding: 20px; border-radius: 8px; border: 1px solid #dbeafe;">
                        <div style="display:flex; gap:8px;"><span>1.</span> <b>email</b></div>
                        <div style="display:flex; gap:8px;"><span>2.</span> <b>password</b></div>
                        <div style="display:flex; gap:8px;"><span>3.</span> <b>company_name</b></div>
                        <div style="display:flex; gap:8px;"><span>4.</span> <b>company_address</b></div>
                        <div style="display:flex; gap:8px;"><span>5.</span> <b>city</b></div>
                        <div style="display:flex; gap:8px;"><span>6.</span> <b>state</b></div>
                        <div style="display:flex; gap:8px;"><span>7.</span> <b>postal_code</b></div>
                        <div style="display:flex; gap:8px;"><span>8.</span> <b>contact_name</b></div>
                        <div style="display:flex; gap:8px;"><span>9.</span> <b>mobile_number</b></div>
                        <div style="display:flex; gap:8px;"><span>10.</span> <b>pan_number</b></div>
                        <div style="display:flex; gap:8px;"><span>11.</span> <b>bank_name</b></div>
                        <div style="display:flex; gap:8px;"><span>12.</span> <b>account_number</b></div>
                        <div style="display:flex; gap:8px;"><span>13.</span> <b>ifsc_code</b></div>
                    </div>
                    <div style="margin-top: 24px;">
                        <a href="sample_vendors.csv"
                            style="display: inline-flex; align-items: center; gap: 8px; background: #3b82f6; color: white; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; font-size: 0.9rem; transition: all 0.2s; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);"
                            onmouseover="this.style.background='#2563eb'" onmouseout="this.style.background='#3b82f6'">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7 10 12 15 17 10"></polyline>
                                <line x1="12" y1="15" x2="12" y2="3"></line>
                            </svg>
                            Download Master CSV Template
                        </a>
                    </div>
                </div>

                <form action="admin_process_import.php" method="POST" enctype="multipart/form-data">
                    <div class="file-upload-area" id="drop-zone"
                        style="background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 12px; padding: 40px; text-align: center; transition: all 0.3s; cursor: pointer;"
                        onclick="document.getElementById('csv_file').click()">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="1.5"
                            stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 16px;">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="12" y1="18" x2="12" y2="12"></line>
                            <line x1="9" y1="15" x2="15" y2="15"></line>
                        </svg>
                        <h4 style="margin: 0; color: #1e293b; font-size: 1.1rem;">Select Database Import File</h4>
                        <p id="file-label" style="margin: 8px 0 0 0; color: #64748b; font-size: 0.95rem;">Drag & drop
                            your CSV file here or click to browse</p>
                        <input type="file" id="csv_file" name="csv_file" accept=".csv" style="display:none;"
                            onchange="updateFileName(this)">
                    </div>

                    <button type="submit" class="btn-import"
                        style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none; height: 56px; font-size: 1.1rem; font-weight: 700; border-radius: 10px; margin-top: 24px; cursor: pointer; transition: all 0.3s; box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.3);"
                        onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 15px 25px -5px rgba(37, 99, 235, 0.4)'"
                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 10px 15px -3px rgba(37, 99, 235, 0.3)'">
                        EXECUTE BULK IMPORT
                    </button>
                </form>
            </div>
        </main>
    </div>

    <script>
        function updateFileName(input) {
            const label = document.getElementById('file-label');
            if (input.files.length > 0) {
                label.innerText = 'Selected: ' + input.files[0].name;
                label.style.color = '#3b82f6';
                label.style.fontWeight = '700';
            }
        }
    </script>
</body>

</html>