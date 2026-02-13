<?php
session_start();
require_once '../includes/db_connect.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$role = $_SESSION['role'];
// Fetch Supplier Data
$stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
$stmt->execute([$id]);
$s = $stmt->fetch();
if (!$s)
    die("Supplier not found.");
// Determine Workflow Step
$workflow_step = "Level 1: Purchase Team Verification";
if ($s['status'] == 'APPROVED_L1')
    $workflow_step = "Level 2: Finance Team Verification";
if ($s['status'] == 'APPROVED_L2')
    $workflow_step = "Level 3: IT Team Activation";
if ($s['status'] == 'ACTIVE')
    $workflow_step = "Supplier Active / Onboarded";
if ($s['status'] == 'REJECTED')
    $workflow_step = "Application Rejected";

// Determine if User Can Approve
$can_approve = false;
if ($role == 'ADMIN') {
    // Admin can approve/reject at any stage that is not already completed or rejected
    if (in_array($s['status'], ['SUBMITTED', 'APPROVED_L1', 'APPROVED_L2'])) {
        $can_approve = true;
    }
} else {
    if ($role == 'PURCHASER' && $s['status'] == 'SUBMITTED')
        $can_approve = true;
    if ($role == 'FINANCE' && $s['status'] == 'APPROVED_L1')
        $can_approve = true;
    if ($role == 'IT' && $s['status'] == 'APPROVED_L2')
        $can_approve = true;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Management System | <?php echo htmlspecialchars($s['company_name']); ?></title>
    <link rel="icon" type="image/png" href="../assets/img/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* INTERNAL FORCE HIGHLIGHT */
        .view-data-box {
            background-color: #fdf9c3 !important;
            border: 2px solid #eab308 !important;
            padding: 10px 16px !important;
            font-weight: 700 !important;
            color: #0f172a !important;
            border-radius: 4px !important;
            display: flex !important;
            align-items: center !important;
            min-height: 42px !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
        }
    </style>
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
        <nav class="sidebar-nav">
            <a href="dashboard.php?view=pending" class="nav-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10" />
                    <polyline points="12 6 12 12 16 14" />
                </svg>
                <span>Pending Review</span>
            </a>
            <a href="dashboard.php?view=submitted" class="nav-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                    <polyline points="14 2 14 8 20 8" />
                    <line x1="16" y1="13" x2="8" y2="13" />
                    <line x1="16" y1="17" x2="8" y2="17" />
                </svg>
                <span>Workflow All</span>
            </a>
            <a href="dashboard.php?view=history" class="nav-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                    <polyline points="22 4 12 14.01 9 11.01" />
                </svg>
                <span>History</span>
            </a>
        </nav>
        <div class="sidebar-footer">
            <a href="logout.php" class="nav-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                    <polyline points="16 17 21 12 16 7" />
                    <line x1="21" y1="12" x2="9" y2="12" />
                </svg>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <main class="main-content">
        <!-- HEADER -->
        <div style="margin-bottom: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                <div>
                    <a href="dashboard.php"
                        style="color: var(--accent); text-decoration: none; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; display: flex; align-items: center; gap: 6px; margin-bottom: 8px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="3">
                            <polyline points="15 18 9 12 15 6" />
                        </svg>
                        Back to Dashboard
                    </a>
                    <h1
                        style="font-size: 1.5rem; font-weight: 700; color: #1e293b; margin: 0; text-transform: none; text-align: left;">
                        <?php echo htmlspecialchars($s['company_name']); ?>
                    </h1>
                    <p style="margin: 4px 0 0 0; color: #3b82f6; font-weight: 600; font-size: 0.9rem;">
                        <?php echo $workflow_step; ?>
                    </p>
                </div>
                <div style="text-align: right; display: flex; flex-direction: column; align-items: flex-end; gap: 8px;">
                    <span class="badge status-<?php echo strtolower($s['status']); ?>"
                        style="padding: 10px 24px; font-size: 0.85rem; box-shadow: var(--shadow-sm);">
                        <?php echo str_replace('_', ' ', $s['status']); ?>
                    </span>
                    <?php if ($s['ebs_vendor_code']): ?>
                        <div
                            style="background: #eff6ff; border: 1px solid #3b82f6; color: #1e40af; padding: 4px 12px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; display: flex; align-items: center; gap: 6px;">
                            <span style="text-transform: uppercase; color: #3b82f6; font-size: 0.65rem;">EBS CODE:</span>
                            <span
                                style="font-family: monospace; letter-spacing: 0.05em;"><?php echo htmlspecialchars($s['ebs_vendor_code']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="glass-card animate-slide-up">
            <!-- 1. BUSINESS CORE DATA (SCREENSHOT STYLE) -->
            <section class="view-form-section">
                <h2 class="view-section-title">Business/Company Detail</h2>

                <div class="view-form-row">
                    <div class="view-form-group full-width">
                        <span class="view-label required">Name of Company</span>
                        <div class="view-data-box"><?php echo htmlspecialchars($s['company_name']); ?></div>
                    </div>
                </div>

                <div class="view-form-row">
                    <div class="view-form-group full-width">
                        <span class="view-label required">Company Address</span>
                        <div class="view-data-box textarea">
                            <?php echo nl2br(htmlspecialchars($s['company_address'])); ?>
                        </div>
                    </div>
                </div>

                <div class="view-form-row">
                    <div class="view-form-group">
                        <span class="view-label required">City</span>
                        <div class="view-data-box"><?php echo htmlspecialchars($s['city']); ?></div>
                    </div>
                    <div class="view-form-group">
                        <span class="view-label required">State</span>
                        <div class="view-data-box"><?php echo htmlspecialchars($s['state']); ?></div>
                    </div>
                    <div class="view-form-group">
                        <span class="view-label required">Postal Code</span>
                        <div class="view-data-box"><?php echo htmlspecialchars($s['postal_code']); ?></div>
                    </div>
                    <div class="view-form-group">
                        <span class="view-label required">Country</span>
                        <div class="view-data-box"><?php echo htmlspecialchars($s['country']); ?></div>
                    </div>
                </div>

                <div class="view-form-row">
                    <div class="view-form-group full-width">
                        <span class="view-label">Supplier Website (If Any)</span>
                        <div class="view-data-box"><?php echo htmlspecialchars($s['supplier_website'] ?: 'N/A'); ?>
                        </div>
                    </div>
                </div>

                <div class="view-form-row">
                    <div class="view-form-group full-width">
                        <span class="view-label">Nature of Business:</span>
                        <div class="view-data-box"><?php echo htmlspecialchars($s['nature_of_business']); ?></div>
                    </div>
                </div>

                <div class="view-form-row">
                    <div class="view-form-group full-width">
                        <span class="view-label">Type of Product/Services</span>
                        <div class="view-data-box"><?php echo htmlspecialchars($s['product_services_type'] ?: 'N/A'); ?>
                        </div>
                    </div>
                </div>

                <div class="view-form-row">
                    <div class="view-form-group full-width">
                        <span class="view-label">Domestic/International</span>
                        <div class="view-data-box dropdown">
                            <?php echo htmlspecialchars($s['market_type'] ?: 'Domestic'); ?>
                        </div>
                    </div>
                </div>
            </section>

            <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 40px 0;">

            <!-- 2. POINT OF CONTACT -->
            <section class="view-form-section">
                <h2 class="view-section-title">Point of Contact Detail</h2>
                <div class="view-form-row">
                    <div class="view-form-group">
                        <span class="view-label required">First Name</span>
                        <div class="view-data-box"><?php echo htmlspecialchars($s['contact_first_name']); ?></div>
                    </div>
                    <div class="view-form-group">
                        <span class="view-label">Middle Name</span>
                        <div class="view-data-box"><?php echo htmlspecialchars($s['contact_middle_name'] ?: 'N/A'); ?>
                        </div>
                    </div>
                    <div class="view-form-group">
                        <span class="view-label required">Last Name</span>
                        <div class="view-data-box"><?php echo htmlspecialchars($s['contact_last_name']); ?></div>
                    </div>
                </div>
                <div class="view-form-row">
                    <div class="view-form-group">
                        <span class="view-label required">Mobile Number</span>
                        <div class="view-data-box"><?php echo htmlspecialchars($s['mobile_number']); ?></div>
                    </div>
                    <div class="view-form-group">
                        <span class="view-label">Alternate Mobile Number</span>
                        <div class="view-data-box"><?php echo htmlspecialchars($s['alt_mobile_number'] ?: 'N/A'); ?>
                        </div>
                    </div>
                    <div class="view-form-group">
                        <span class="view-label">Landline Number</span>
                        <div class="view-data-box"><?php echo htmlspecialchars($s['landline_number'] ?: 'N/A'); ?></div>
                    </div>
                    <div class="view-form-group">
                        <span class="view-label required">Email ID</span>
                        <div class="view-data-box"><?php echo htmlspecialchars($s['email']); ?></div>
                    </div>
                </div>
            </section>

            <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 40px 0;">

            <!-- 3. TAX & COMPLIANCE -->
            <section class="view-form-section">
                <h2 class="view-section-title">Tax & Compliance Detail</h2>
                <div class="view-form-row">
                    <div class="view-form-group">
                        <span class="view-label required">PAN Number</span>
                        <div class="view-data-box" style="font-family: monospace; font-weight: 700;">
                            <?php echo htmlspecialchars($s['pan_number']); ?>
                        </div>
                    </div>
                    <div class="view-form-group">
                        <span class="view-label">GST Registration Number</span>
                        <div class="view-data-box">
                            <?php echo htmlspecialchars($s['gst_reg_number'] ?: 'Not Registered'); ?>
                        </div>
                    </div>
                </div>
                <div class="view-form-row">
                    <div class="view-form-group">
                        <span class="view-label required">Registered with MSME?</span>
                        <div class="view-data-box"><?php echo htmlspecialchars($s['registered_msme']); ?></div>
                    </div>
                    <div class="view-form-group">
                        <span class="view-label">Type of MSME (If Yes)</span>
                        <div class="view-data-box"><?php echo htmlspecialchars($s['msme_type'] ?: 'N/A'); ?></div>
                    </div>
                </div>
                <div class="view-form-row">
                    <div class="view-form-group">
                        <span class="view-label required">Risk Classification</span>
                        <div class="view-data-box"><?php echo htmlspecialchars($s['risk_classification']); ?></div>
                    </div>
                </div>
            </section>

            <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 40px 0;">

            <!-- 4. BANKING DATA -->
            <section class="view-form-section">
                <h2 class="view-section-title">Banking Detail</h2>
                <div class="view-form-row">
                    <div class="view-form-group full-width">
                        <span class="view-label required">Name of the Bank</span>
                        <div class="view-data-box"><?php echo htmlspecialchars($s['bank_name']); ?></div>
                    </div>
                </div>
                <div class="view-form-row">
                    <div class="view-form-group">
                        <span class="view-label required">Bank Account Number</span>
                        <div class="view-data-box" style="font-family: monospace;">
                            <?php echo htmlspecialchars($s['account_number']); ?>
                        </div>
                    </div>
                    <div class="view-form-group">
                        <span class="view-label required">IFSC Code</span>
                        <div class="view-data-box" style="font-weight: 700; color: #3b82f6;">
                            <?php echo htmlspecialchars($s['ifsc_code']); ?>
                        </div>
                    </div>
                </div>
            </section>

            <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 40px 0;">

            <!-- INTERNAL COMMENTS HISTORY -->
            <?php
            $c1 = $s['l1_comments'] ?? '';
            $c2 = $s['l2_comments'] ?? '';
            $c3 = $s['l3_comments'] ?? '';
            $ebs_code = $s['ebs_vendor_code'] ?? '';
            if ($c1 || $c2 || $c3 || $ebs_code): ?>
                <section class="view-form-section">
                    <h2 class="view-section-title">Internal Team Remarks</h2>
                    <div style="background: #f1f5f9; border-radius: 8px; padding: 20px;">
                        <?php if ($c1): ?>
                            <div style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px dashed #cbd5e1;">
                                <strong style="color: #475569; font-size: 0.75rem; text-transform: uppercase;">Purchase Team
                                    (L1)</strong>
                                <p style="margin: 5px 0 0 0; color: #1e293b;">
                                    <?php echo nl2br(htmlspecialchars($c1)); ?>
                                </p>
                            </div>
                        <?php endif; ?>

                        <?php if ($c2): ?>
                            <div style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px dashed #cbd5e1;">
                                <strong style="color: #475569; font-size: 0.75rem; text-transform: uppercase;">Finance Team
                                    (L2)</strong>
                                <p style="margin: 5px 0 0 0; color: #1e293b;">
                                    <?php echo nl2br(htmlspecialchars($c2)); ?>
                                </p>
                            </div>
                        <?php endif; ?>

                        <?php if ($c3): ?>
                            <div style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px dashed #cbd5e1;">
                                <strong style="color: #475569; font-size: 0.75rem; text-transform: uppercase;">IT Team
                                    (L3)</strong>
                                <p style="margin: 5px 0 0 0; color: #1e293b;">
                                    <?php echo nl2br(htmlspecialchars($c3)); ?>
                                </p>
                            </div>
                        <?php endif; ?>

                        <?php if ($ebs_code): ?>
                            <div style="margin-top: 15px; padding-top: 15px; border-top: 2px solid #3b82f6;">
                                <strong style="color: #3b82f6; font-size: 0.75rem; text-transform: uppercase;">EBS Vendor
                                    Code</strong>
                                <p
                                    style="margin: 5px 0 0 0; color: #1e293b; font-family: monospace; font-size: 1.1rem; font-weight: 700;">
                                    <?php echo htmlspecialchars($ebs_code); ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 40px 0;">
            <?php endif; ?>

            <!-- 5. UPLOADED DOCUMENTS -->
            <section class="view-form-section">
                <h2 class="view-section-title">Submitted Documents (Availability Check)</h2>
                <div class="view-form-row">
                    <?php
                    $docs = [
                        ['label' => 'PAN Card Image', 'file' => $s['pan_card_doc'], 'has' => $s['has_pan'] ?? 'Yes'],
                        ['label' => 'GST Registration Certificate', 'file' => $s['gst_cert_doc'], 'has' => $s['has_gst'] ?? 'Yes'],
                        ['label' => 'Cancelled Cheque/Bank Mandate', 'file' => $s['cancelled_cheque_doc'], 'has' => $s['has_cheque'] ?? 'Yes'],
                        ['label' => 'MSME Registration Certificate', 'file' => $s['msme_cert_doc'], 'has' => $s['has_msme'] ?? 'No']
                    ];
                    foreach ($docs as $doc):
                        ?>
                        <div class="view-form-group" style="min-width: 45%;">
                            <span class="view-label"><?php echo $doc['label']; ?></span>
                            <div class="view-data-box" style="justify-content: space-between;">
                                <div style="display: flex; flex-direction: column;">
                                    <span style="font-size: 0.75rem; color: #64748b; font-weight: 500;">AVAILABLE:
                                        <b><?php echo $doc['has']; ?></b></span>
                                    <span
                                        style="font-size: 0.85rem; color: #1e293b;"><?php echo $doc['file'] ? 'File Attached' : 'No Digital Copy'; ?></span>
                                </div>
                                <?php if ($doc['file']): ?>
                                    <a href="<?php echo htmlspecialchars($doc['file']); ?>" target="_blank" class="btn-view"
                                        style="padding: 6px 12px; font-size: 0.75rem;">
                                        View Digital Copy
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- ACTIONS / APPROVAL PANEL -->
            <?php if ($can_approve): ?>
                <div style="margin-top: 4rem; background: #0f172a; color: white; border-radius: 12px; padding: 32px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                        <div>
                            <h3 style="font-size: 1.1rem; font-weight: 700; margin: 0 0 4px 0;">Officer Verification Action
                            </h3>
                            <p style="margin: 0; color: #94a3b8; font-size: 0.85rem;">Currently reviewing as
                                <strong><?php echo $role; ?></strong>.
                            </p>
                        </div>
                    </div>

                    <form action="workflow_action.php" method="POST" id="approvalForm">
                        <input type="hidden" name="supplier_id" value="<?php echo $s['id']; ?>">
                        <input type="hidden" name="action" id="workflowAction" value="approve">

                        <div style="margin-bottom: 24px;">
                            <label for="comments"
                                style="display: block; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #94a3b8; margin-bottom: 8px;">Comments
                                / Internal Notes</label>
                            <textarea name="comments" id="comments" rows="3"
                                style="width: 100%; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; padding: 12px; color: white; font-family: inherit; resize: vertical;"
                                placeholder="Add any notes here that should be seen by the next level team..."></textarea>
                        </div>

                        <?php
                        // EBS Vendor Code field - only visible for IT team (or ADMIN at L3 stage)
                        $show_ebs = false;
                        if ($role == 'IT' && $s['status'] == 'APPROVED_L2')
                            $show_ebs = true;
                        if ($role == 'ADMIN' && $s['status'] == 'APPROVED_L2')
                            $show_ebs = true;
                        if ($show_ebs): ?>
                            <div
                                style="margin-bottom: 24px; background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 8px; padding: 20px;">
                                <label for="ebs_vendor_code"
                                    style="display: block; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #60a5fa; margin-bottom: 8px;">EBS
                                    Vendor Code <span style="color: #ef4444;">*</span> (Required for Approval)</label>
                                <input type="text" name="ebs_vendor_code" id="ebs_vendor_code"
                                    style="width: 100%; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 6px; padding: 12px; color: white; font-family: monospace; font-size: 1rem; font-weight: 700;"
                                    placeholder="Enter the EBS Vendor Code before approving...">
                                <p style="margin: 6px 0 0; color: #94a3b8; font-size: 0.75rem;">This code will be assigned to
                                    the vendor upon successful activation.</p>
                            </div>
                        <?php endif; ?>

                        <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                            <button type="submit"
                                onclick="document.getElementById('workflowAction').value='reject'; return confirm('REJECT this application?');"
                                style="background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); padding: 12px 24px; border-radius: 6px; font-weight: 700; cursor: pointer; font-size: 0.85rem;">
                                Reject Application
                            </button>
                            <button type="submit" id="approveBtn"
                                style="background: #3b82f6; color: white; border: none; padding: 12px 32px; border-radius: 6px; font-weight: 700; cursor: pointer; font-size: 0.85rem; box-shadow: 0 4px 14px rgba(59, 130, 246, 0.4);">
                                Approve & Proceed
                            </button>
                        </div>
                    </form>
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
            sidebar &&
            !sidebar.contains(event.target) &&
            !toggle.contains(event.target) &&
            sidebar.classList.contains('active')) {
            sidebar.classList.remove('active');
        }
    });

    // EBS Vendor Code validation & Approve button handler
    var approveBtn = document.getElementById('approveBtn');
    if (approveBtn) {
        approveBtn.addEventListener('click', function (e) {
            document.getElementById('workflowAction').value = 'approve';
            var ebsField = document.getElementById('ebs_vendor_code');
            if (ebsField && ebsField.value.trim() === '') {
                e.preventDefault();
                alert('EBS Vendor Code is mandatory for final approval. Please enter the EBS Vendor Code.');
                ebsField.focus();
                return false;
            }
        });
    }
</script>
</body>

</html>

</html>