<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Security: Force Password Change if required
if (isset($_SESSION['password_change_required']) && $_SESSION['password_change_required'] === true) {
    header("Location: change_password.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Strict Role Check: Only VENDORs can access this page
if ($role !== 'VENDOR') {
    header("Location: dashboard.php");
    exit();
}

// Fetch registration data if vendor is logged in
$supplier_data = null;
if ($role === 'VENDOR') {
    $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
    $stmt->execute([$user_id]);
    $supplier_data = $stmt->fetch();
}

// Determine Mode
$mode = 'new';
$is_draft = false;
if ($supplier_data) {
    // Robust Draft Detection: Status is DRAFT OR Company Name is missing (for admin-created vendors)
    $is_draft = ($supplier_data['status'] === 'DRAFT') || empty($supplier_data['company_name']);
    
    // If DRAFT, force edit mode. Otherwise default to view or requested action.
    if ($is_draft) {
        $mode = 'edit';
    } else {
        $mode = (isset($_GET['action']) && $_GET['action'] === 'edit') ? 'edit' : 'view';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Management System | Jagatjit Industries</title>
    <link rel="stylesheet" href="../assets/css/style.css">
	<link rel="icon" type="image/png" href="../assets/img/favicon.ico">	
    <style>
        /* REUSE PREMIUM VIEW STYLES */
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
            margin-top: 8px;
        }

        .view-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 4px;
            display: block;
        }

        .btn-edit-toggle {
            background: #3b82f6;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .btn-edit-toggle:hover {
            background: #2563eb;
            transform: translateY(-1px);
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
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">JIL- VMS</div>
            <nav class="sidebar-nav">
                <a href="#documents-compliance" class="nav-item">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <path d="M12 18h.01"></path>
                        <path d="M12 15a3 3 0 1 0-3-3"></path>
                    </svg>
                    <span>Step 1: Documents</span>
                </a>
                <a href="#business-detail" class="nav-item">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="9" y1="3" x2="9" y2="21"></line>
                    </svg>
                    <span>Business Detail</span>
                </a>
                <a href="#communication-detail" class="nav-item">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path
                            d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z">
                        </path>
                    </svg>
                    <span>Communication</span>
                </a>
                <a href="#tax-legal-detail" class="nav-item">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                    <span>Tax & Legal</span>
                </a>
                <a href="#bank-detail" class="nav-item">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="1" x2="12" y2="23"></line>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg>
                    <span>Bank Details</span>
                </a>
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
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="top-bar" style="align-items: flex-end;">
                <div>
                    <h1 class="page-title">Supplier Portal</h1>
                    <p style="color: #64748b; margin-top: 4px; font-size: 0.9rem;">
                        <?php echo $is_draft ? 'Welcome! Please complete your business profile to submit for verification.' : (($mode === 'view') ? 'Your submitted business profile' : 'Update your business profile for Jagatjit Industries'); ?>
                    </p>
                </div>
                <div class="top-bar-actions" style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                    <?php if ($supplier_data && !$is_draft): ?>
                        <?php if ($mode === 'view'): ?>
                            <a href="index.php?action=edit" class="btn-edit-toggle">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                                Edit Profile
                            </a>
                        <?php else: ?>
                            <a href="index.php" class="btn-edit-toggle" style="background: #64748b;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                View Profile
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                    <div style="font-size: 0.8rem; color: #64748b; background: white; padding: 6px 12px; border-radius: 6px; border: 1px solid #e2e8f0;">
                        Logged as: <b><?php echo $_SESSION['username']; ?></b>
                    </div>
                </div>
            </header>

            <?php if ($supplier_data && $supplier_data['status'] === 'REJECTED'): ?>
                <div class="glass-card animate-fade-in" style="margin-bottom: 32px; border-left: 6px solid #ef4444; background: #fef2f2;">
                    <div style="display: flex; align-items: flex-start; gap: 20px;">
                        <div style="background: #fee2e2; padding: 12px; border-radius: 50%; color: #ef4444;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="15" y1="9" x2="9" y2="15"></line>
                                <line x1="9" y1="9" x2="15" y2="15"></line>
                            </svg>
                        </div>
                        <div style="flex: 1;">
                            <h3 style="margin: 0; color: #991b1b; font-size: 1.1rem; font-weight: 700;">Your Application was Rejected</h3>
                            <p style="margin: 8px 0 16px 0; color: #b91c1c; font-size: 0.95rem; line-height: 1.5;">
                                Reason: <b><?php echo htmlspecialchars($supplier_data['rejection_reason'] ?: 'No specific reason provided.'); ?></b>
                            </p>
                            <div style="display: flex; gap: 12px;">
                                <a href="index.php?action=edit" class="btn-primary" style="width: auto; padding: 10px 24px; font-size: 0.9rem; background: #ef4444; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);">Edit Profile & Resubmit</a>
                                <a href="#communication-detail" style="color: #475569; font-size: 0.9rem; padding: 10px 0; text-decoration: underline; font-weight: 500;">Contact Support</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['success'])): ?>
                <div
                    style="background: #2ecc71; color: white; padding: 15px; border-radius: 8px; margin-bottom: 24px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <b>Profile Updated Successfully!</b> Your details have been sent for re-verification.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div
                    style="background: #e74c3c; color: white; padding: 15px; border-radius: 8px; margin-bottom: 24px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <b>Validation Failed:</b> <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <form action="process.php" method="POST" enctype="multipart/form-data">

                <!-- DOCUMENTS (Moved to Top) -->
                <div id="documents-compliance" class="glass-card" style="margin-bottom: 32px; border-left: 4px solid #10b981;">
                    <div class="section-title" style="margin-top: 0; padding-top: 0; color: #059669;">Step 1: Upload Documents (Auto-fill Enabled)</div>
                    <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 20px;">Upload your GST Certificate to automatically fill your business details.</p>

                    <div class="form-row">
                        <div class="form-group full-width">
                            <label class="required">Vendor Risk Classification</label>
                            <?php if ($mode === 'view'): ?>
                                    <div class="view-data-box"><?php echo htmlspecialchars($supplier_data['risk_classification'] ?? 'Low'); ?></div>
                            <?php else: ?>
                                    <div style="display: flex; gap: 20px; margin-top: 10px;">
                                        <label style="display: flex; align-items: center; gap: 8px; font-weight: normal; text-transform: none;">
                                            <input type="radio" name="risk_classification" value="Low" <?php echo ($supplier_data['risk_classification'] ?? 'Low') === 'Low' ? 'checked' : ''; ?>> Low Risk (Supplies)
                                        </label>
                                        <label style="display: flex; align-items: center; gap: 8px; font-weight: normal; text-transform: none;">
                                            <input type="radio" name="risk_classification" value="Medium" <?php echo ($supplier_data['risk_classification'] ?? '') === 'Medium' ? 'checked' : ''; ?>> Medium Risk (Services)
                                        </label>
                                        <label style="display: flex; align-items: center; gap: 8px; font-weight: normal; text-transform: none;">
                                            <input type="radio" name="risk_classification" value="High" <?php echo ($supplier_data['risk_classification'] ?? '') === 'High' ? 'checked' : ''; ?>> High Risk (IT/SaaS)
                                        </label>
                                    </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- DOCUMENT SELECTION & UPLOAD -->
                    <div class="doc-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px; margin-top: 20px;">
                        
                        <!-- PAN Card -->
                        <div class="doc-item">
                            <label class="required">Do you have a PAN Card? *</label>
                            <?php if ($mode === 'view'): ?>
                                <div class="view-data-box"><?php echo $supplier_data['has_pan'] ?? 'Yes'; ?></div>
                            <?php else: ?>
                                <div style="display: flex; gap: 15px; margin: 8px 0;">
                                    <label style="font-weight: normal;"><input type="radio" name="has_pan" value="Yes" <?php echo ($supplier_data['has_pan'] ?? 'Yes') === 'Yes' ? 'checked' : ''; ?> onClick="toggleDocRow('pan_row', true, 'pan_card_doc')"> Yes</label>
                                    <label style="font-weight: normal;"><input type="radio" name="has_pan" value="No" <?php echo ($supplier_data['has_pan'] ?? '') === 'No' ? 'checked' : ''; ?> onClick="toggleDocRow('pan_row', false, 'pan_card_doc')"> No</label>
                                </div>
                            <?php endif; ?>

                            <div id="pan_row" style="<?php echo ($supplier_data['has_pan'] ?? 'Yes') === 'Yes' ? '' : 'display:none;'; ?> margin-top: 10px;">
                                <label class="required" for="pan_card_doc">Upload PAN Card (PDF/Image)</label>
                                <?php if ($mode === 'view'): ?>
                                    <div class="view-data-box"><?php echo ($supplier_data['pan_card_doc'] ?? null) ? '<a href="' . htmlspecialchars($supplier_data['pan_card_doc']) . '" target="_blank" style="color: #0047b3;">View Digital Copy</a>' : 'No file uploaded'; ?></div>
                                <?php else: ?>
                                    <input type="file" id="pan_card_doc" name="pan_card_doc" accept=".pdf,.jpg,.jpeg,.png" <?php echo ($supplier_data['has_pan'] ?? 'Yes') === 'Yes' && !($supplier_data['pan_card_doc'] ?? null) ? 'required' : ''; ?>>
                                    <div style="font-size: 0.75rem; color: #64748b; margin-top: 5px;">
                                        <?php echo ($supplier_data['pan_card_doc'] ?? null) ? 'Already uploaded: <a href="' . htmlspecialchars($supplier_data['pan_card_doc']) . '" target="_blank">View Existing</a>. Re-upload to replace.' : 'Mandatory for all vendors.'; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- GST Registration -->
                        <div class="doc-item">
                            <label class="required">Do you have a GST Registration? *</label>
                            <?php if ($mode === 'view'): ?>
                                <div class="view-data-box"><?php echo $supplier_data['has_gst'] ?? 'Yes'; ?></div>
                            <?php else: ?>
                                <div style="display: flex; gap: 15px; margin: 8px 0;">
                                    <label style="font-weight: normal;"><input type="radio" name="has_gst" value="Yes" <?php echo ($supplier_data['has_gst'] ?? 'Yes') === 'Yes' ? 'checked' : ''; ?> onClick="toggleDocRow('gst_row', true, 'gst_cert_doc')"> Yes</label>
                                    <label style="font-weight: normal;"><input type="radio" name="has_gst" value="No" <?php echo ($supplier_data['has_gst'] ?? '') === 'No' ? 'checked' : ''; ?> onClick="toggleDocRow('gst_row', false, 'gst_cert_doc')"> No</label>
                                </div>
                            <?php endif; ?>

                            <div id="gst_row" style="<?php echo ($supplier_data['has_gst'] ?? 'Yes') === 'Yes' ? '' : 'display:none;'; ?> margin-top: 10px;">
                                <label class="required" for="gst_cert_doc">Upload GST Certificate</label>
                                <?php if ($mode === 'view'): ?>
                                    <div class="view-data-box"><?php echo ($supplier_data['gst_cert_doc'] ?? null) ? '<a href="' . htmlspecialchars($supplier_data['gst_cert_doc']) . '" target="_blank" style="color: #0047b3;">View Digital Copy</a>' : 'No file uploaded'; ?></div>
                                <?php else: ?>
                                    <input type="file" id="gst_cert_doc" name="gst_cert_doc" accept=".pdf,.jpg,.jpeg,.png" <?php echo ($supplier_data['has_gst'] ?? 'Yes') === 'Yes' && !($supplier_data['gst_cert_doc'] ?? null) ? 'required' : ''; ?>>
                                    <div style="font-size: 0.75rem; color: #64748b; margin-top: 5px;">
                                        <?php echo ($supplier_data['gst_cert_doc'] ?? null) ? 'Already uploaded: <a href="' . htmlspecialchars($supplier_data['gst_cert_doc']) . '" target="_blank">View Existing</a>. Re-upload to replace.' : 'Mandatory for all vendors.'; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Cancelled Cheque -->
                        <div class="doc-item">
                            <label class="required">Do you have a Cancelled Cheque? *</label>
                            <?php if ($mode === 'view'): ?>
                                <div class="view-data-box"><?php echo $supplier_data['has_cheque'] ?? 'Yes'; ?></div>
                            <?php else: ?>
                                <div style="display: flex; gap: 15px; margin: 8px 0;">
                                    <label style="font-weight: normal;"><input type="radio" name="has_cheque" value="Yes" <?php echo ($supplier_data['has_cheque'] ?? 'Yes') === 'Yes' ? 'checked' : ''; ?> onClick="toggleDocRow('cheque_row', true, 'cancelled_cheque_doc')"> Yes</label>
                                    <label style="font-weight: normal;"><input type="radio" name="has_cheque" value="No" <?php echo ($supplier_data['has_cheque'] ?? '') === 'No' ? 'checked' : ''; ?> onClick="toggleDocRow('cheque_row', false, 'cancelled_cheque_doc')"> No</label>
                                </div>
                            <?php endif; ?>

                            <div id="cheque_row" style="<?php echo ($supplier_data['has_cheque'] ?? 'Yes') === 'Yes' ? '' : 'display:none;'; ?> margin-top: 10px;">
                                <label class="required" for="cancelled_cheque_doc">Upload Cancelled Cheque</label>
                                <?php if ($mode === 'view'): ?>
                                    <div class="view-data-box"><?php echo ($supplier_data['cancelled_cheque_doc'] ?? null) ? '<a href="' . htmlspecialchars($supplier_data['cancelled_cheque_doc']) . '" target="_blank" style="color: #0047b3;">View Digital Copy</a>' : 'No file uploaded'; ?></div>
                                <?php else: ?>
                                    <input type="file" id="cancelled_cheque_doc" name="cancelled_cheque_doc" accept=".pdf,.jpg,.jpeg,.png" <?php echo ($supplier_data['has_cheque'] ?? 'Yes') === 'Yes' && !($supplier_data['cancelled_cheque_doc'] ?? null) ? 'required' : ''; ?>>
                                    <div style="font-size: 0.75rem; color: #64748b; margin-top: 5px;">
                                        <?php echo ($supplier_data['cancelled_cheque_doc'] ?? null) ? 'Already uploaded: <a href="' . htmlspecialchars($supplier_data['cancelled_cheque_doc']) . '" target="_blank">View Existing</a>. Re-upload to replace.' : 'Must match registered company name.'; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- MSME Certificate -->
                        <div class="doc-item">
                            <label class="required">Do you have an MSME Certificate?</label>
                            <?php if ($mode === 'view'): ?>
                                <div class="view-data-box"><?php echo $supplier_data['has_msme'] ?? 'No'; ?></div>
                            <?php else: ?>
                                <div style="display: flex; gap: 15px; margin: 8px 0;">
                                    <label style="font-weight: normal;"><input type="radio" name="has_msme" value="Yes" <?php echo ($supplier_data['has_msme'] ?? '') === 'Yes' ? 'checked' : ''; ?> onClick="toggleDocRow('msme_row', true, 'msme_cert_doc')"> Yes</label>
                                    <label style="font-weight: normal;"><input type="radio" name="has_msme" value="No" <?php echo ($supplier_data['has_msme'] ?? 'No') === 'No' ? 'checked' : ''; ?> onClick="toggleDocRow('msme_row', false, 'msme_cert_doc')"> No</label>
                                </div>
                            <?php endif; ?>

                            <div id="msme_row" style="<?php echo ($supplier_data['has_msme'] ?? '') === 'Yes' ? '' : 'display:none;'; ?> margin-top: 10px;">
                                <label class="required" for="msme_cert_doc">Upload MSME Certificate</label>
                                <?php if ($mode === 'view'): ?>
                                    <div class="view-data-box"><?php echo ($supplier_data['msme_cert_doc'] ?? null) ? '<a href="' . htmlspecialchars($supplier_data['msme_cert_doc']) . '" target="_blank" style="color: #0047b3;">View Digital Copy</a>' : 'No file uploaded'; ?></div>
                                <?php else: ?>
                                    <input type="file" id="msme_cert_doc" name="msme_cert_doc" accept=".pdf,.jpg,.jpeg,.png" <?php echo ($supplier_data['has_msme'] ?? '') === 'Yes' && !($supplier_data['msme_cert_doc'] ?? null) ? 'required' : ''; ?>>
                                    <div style="font-size: 0.75rem; color: #64748b; margin-top: 5px;">
                                        <?php echo ($supplier_data['msme_cert_doc'] ?? null) ? 'Already uploaded: <a href="' . htmlspecialchars($supplier_data['msme_cert_doc']) . '" target="_blank">View Existing</a>. Re-upload to replace.' : 'Optional.'; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                </div>


                <!-- BUSINESS/COMPANY DETAIL -->
                <div id="business-detail" class="glass-card" style="margin-bottom: 32px;">
                    <div class="section-title" style="margin-top: 0; padding-top: 0;">Business/Company Detail</div>
                    
                    <div class="form-row">
                        <div class="form-group full-width">
                            <label class="required" for="company_name">Name of Company</label>
                            <?php if ($mode === 'view'): ?>
                                    <div class="view-data-box"><?php echo htmlspecialchars($supplier_data['company_name']); ?></div>
                            <?php else: ?>
                                    <input type="text" id="company_name" name="company_name" 
                                        value="<?php echo htmlspecialchars($supplier_data['company_name'] ?? ''); ?>" required>
                            <?php endif; ?>
                            <div class="error-message"></div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group full-width">
                            <label class="required" for="company_address">Company Address</label>
                            <?php if ($mode === 'view'): ?>
                                    <div class="view-data-box" style="min-height: 80px; align-items: flex-start; padding-top: 12px;">
                                        <?php echo nl2br(htmlspecialchars($supplier_data['company_address'])); ?>
                                    </div>
                            <?php else: ?>
                                    <textarea id="company_address" name="company_address" rows="3" required><?php echo htmlspecialchars($supplier_data['company_address'] ?? ''); ?></textarea>
                            <?php endif; ?>
                            <div class="error-message"></div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="required" for="city">City</label>
                            <?php if ($mode === 'view'): ?>
                                    <div class="view-data-box"><?php echo htmlspecialchars($supplier_data['city']); ?></div>
                            <?php else: ?>
                                    <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($supplier_data['city'] ?? ''); ?>" required>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="required" for="state">State</label>
                            <?php if ($mode === 'view'): ?>
                                    <div class="view-data-box"><?php echo htmlspecialchars($supplier_data['state']); ?></div>
                            <?php else: ?>
                                    <input type="text" id="state" name="state" value="<?php echo htmlspecialchars($supplier_data['state'] ?? ''); ?>" required>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="required" for="postal_code">Postal Code</label>
                            <?php if ($mode === 'view'): ?>
                                    <div class="view-data-box"><?php echo htmlspecialchars($supplier_data['postal_code']); ?></div>
                            <?php else: ?>
                                    <input type="text" id="postal_code" name="postal_code" value="<?php echo htmlspecialchars($supplier_data['postal_code'] ?? ''); ?>" required>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="required" for="country">Country</label>
                            <?php if ($mode === 'view'): ?>
                                    <div class="view-data-box"><?php echo htmlspecialchars($supplier_data['country']); ?></div>
                            <?php else: ?>
                                    <input type="text" id="country" name="country" value="<?php echo htmlspecialchars($supplier_data['country'] ?? ''); ?>" required>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="supplier_website">Supplier Website (If Any)</label>
                            <?php if ($mode === 'view'): ?>
                                    <div class="view-data-box"><?php echo htmlspecialchars($supplier_data['supplier_website'] ?: 'N/A'); ?></div>
                            <?php else: ?>
                                    <input type="url" id="supplier_website" name="supplier_website" value="<?php echo htmlspecialchars($supplier_data['supplier_website'] ?? ''); ?>">
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-row">
                        <label>Nature of Business:</label>
                        <?php if ($mode === 'view'): ?>
                                <div class="view-data-box"><?php echo htmlspecialchars($supplier_data['nature_of_business'] ?: 'N/A'); ?></div>
                        <?php else: ?>
                                <div class="checkbox-group">
                                    <?php
                                    $natures = ['Manufacturer', 'Traders', 'Service Provider', 'Consulting Company', 'Other'];
                                    $selected_natures = explode(", ", $supplier_data['nature_of_business'] ?? '');
                                    foreach ($natures as $n):
                                        ?>
                                        <label><input type="checkbox" name="nature_of_business[]" value="<?php echo $n; ?>" 
                                            <?php echo in_array($n, $selected_natures) ? 'checked' : ''; ?>> <?php echo $n; ?></label>
                                    <?php endforeach; ?>
                                </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="product_services_type">Type of Product/Services</label>
                            <?php if ($mode === 'view'): ?>
                                    <div class="view-data-box"><?php echo htmlspecialchars($supplier_data['product_services_type'] ?: 'N/A'); ?></div>
                            <?php else: ?>
                                    <input type="text" id="product_services_type" name="product_services_type" value="<?php echo htmlspecialchars($supplier_data['product_services_type'] ?? ''); ?>">
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="market_type">Domestic/International</label>
                            <?php if ($mode === 'view'): ?>
                                    <div class="view-data-box"><?php echo htmlspecialchars($supplier_data['market_type'] ?: 'Domestic'); ?></div>
                            <?php else: ?>
                                    <select id="market_type" name="market_type">
                                        <option value="Domestic" <?php echo ($supplier_data['market_type'] ?? '') === 'Domestic' ? 'selected' : ''; ?>>Domestic</option>
                                        <option value="International" <?php echo ($supplier_data['market_type'] ?? '') === 'International' ? 'selected' : ''; ?>>International</option>
                                        <option value="Both" <?php echo ($supplier_data['market_type'] ?? '') === 'Both' ? 'selected' : ''; ?>>Both</option>
                                    </select>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                    <!-- COMMUNICATION DETAIL -->
                    <div id="communication-detail" class="glass-card" style="margin-bottom: 32px;">
                        <div class="section-title" style="margin-top: 0;">Communication Detail</div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="contact_first_name">First Name</label>
                                <?php if ($mode === 'view'): ?>
                                        <div class="view-data-box"><?php echo htmlspecialchars($supplier_data['contact_first_name'] ?: 'N/A'); ?></div>
                                <?php else: ?>
                                        <input type="text" id="contact_first_name" name="contact_first_name" value="<?php echo htmlspecialchars($supplier_data['contact_first_name'] ?? ''); ?>">
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label for="contact_middle_name">Middle Name</label>
                                <?php if ($mode === 'view'): ?>
                                        <div class="view-data-box"><?php echo htmlspecialchars($supplier_data['contact_middle_name'] ?: 'N/A'); ?></div>
                                <?php else: ?>
                                        <input type="text" id="contact_middle_name" name="contact_middle_name" value="<?php echo htmlspecialchars($supplier_data['contact_middle_name'] ?? ''); ?>">
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label for="contact_last_name">Last Name</label>
                                <?php if ($mode === 'view'): ?>
                                        <div class="view-data-box"><?php echo htmlspecialchars($supplier_data['contact_last_name'] ?: 'N/A'); ?></div>
                                <?php else: ?>
                                        <input type="text" id="contact_last_name" name="contact_last_name" value="<?php echo htmlspecialchars($supplier_data['contact_last_name'] ?? ''); ?>">
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="required" for="mobile_number">Mobile Number</label>
                                <?php if ($mode === 'view'): ?>
                                        <div class="view-data-box"><?php echo htmlspecialchars($supplier_data['mobile_number']); ?></div>
                                <?php else: ?>
                                        <input type="tel" id="mobile_number" name="mobile_number" value="<?php echo htmlspecialchars($supplier_data['mobile_number'] ?? ''); ?>" required pattern="\d{10}">
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label for="alt_mobile_number">Alternate Mobile Number</label>
                                <?php if ($mode === 'view'): ?>
                                        <div class="view-data-box"><?php echo htmlspecialchars($supplier_data['alt_mobile_number'] ?: 'N/A'); ?></div>
                                <?php else: ?>
                                        <input type="tel" id="alt_mobile_number" name="alt_mobile_number" value="<?php echo htmlspecialchars($supplier_data['alt_mobile_number'] ?? ''); ?>" pattern="\d{10}" title="Exactly 10 digits required">
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                <!-- BUSINESS CONTACT DETAIL -->
                <div id="business-contact-detail" class="glass-card" style="margin-bottom: 32px;">
                    <div class="section-title" style="margin-top: 0;">Business Contact Detail</div>
                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="landline_number">Landline Number</label>
                            <?php if ($mode === 'view'): ?>
                                    <div class="view-data-box"><?php echo htmlspecialchars($supplier_data['landline_number'] ?: 'N/A'); ?></div>
                            <?php else: ?>
                                    <input type="text" id="landline_number" name="landline_number" value="<?php echo htmlspecialchars($supplier_data['landline_number'] ?? ''); ?>" pattern="\d{10,12}" title="Between 10 to 12 digits required">
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                    <!-- INTERNAL PROCESS -->
                    <div id="internal-process" class="glass-card" style="margin-bottom: 32px;">
                        <div class="section-title" style="margin-top: 0;">Internal Process</div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="supplier_type">Supplier Type</label>
                                <?php if ($mode === 'view'): ?>
                                        <div class="view-data-box"><?php echo htmlspecialchars($supplier_data['supplier_type'] ?: 'N/A'); ?></div>
                                <?php else: ?>
                                        <input type="text" id="supplier_type" name="supplier_type" value="<?php echo htmlspecialchars($supplier_data['supplier_type'] ?? ''); ?>">
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label for="item_main_group">Item Supplied Main Group</label>
                                <?php if ($mode === 'view'): ?>
                                        <div class="view-data-box"><?php echo htmlspecialchars($supplier_data['item_main_group'] ?: 'N/A'); ?></div>
                                <?php else: ?>
                                        <input type="text" id="item_main_group" name="item_main_group" value="<?php echo htmlspecialchars($supplier_data['item_main_group'] ?? ''); ?>">
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <!-- TAX & LEGAL DETAIL -->
                    <div id="tax-legal-detail" class="glass-card" style="margin-bottom: 32px;">
                        <div class="section-title" style="margin-top: 0;">Tax & Legal Detail</div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Registered Under MSME</label>
                                <?php if ($mode === 'view'): ?>
                                        <div class="view-data-box"><?php echo htmlspecialchars($supplier_data['registered_msme'] ?: 'No'); ?></div>
                                <?php else: ?>
                                        <div class="checkbox-group">
                                            <label><input type="radio" name="registered_msme" value="Yes" <?php echo ($supplier_data['registered_msme'] ?? '') === 'Yes' ? 'checked' : ''; ?>> Yes</label>
                                            <label><input type="radio" name="registered_msme" value="No" <?php echo ($supplier_data['registered_msme'] ?? 'No') === 'No' ? 'checked' : ''; ?>> No</label>
                                        </div>
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label class="required" for="msme_reg_number">MSME Regd. Number</label>
                                <?php if ($mode === 'view'): ?>
                                        <div class="view-data-box"><?php echo htmlspecialchars($supplier_data['msme_reg_number'] ?: 'N/A'); ?></div>
                                <?php else: ?>
                                        <input type="text" id="msme_reg_number" name="msme_reg_number" value="<?php echo htmlspecialchars($supplier_data['msme_reg_number'] ?? ''); ?>">
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label class="required" for="msme_type">MSME Type</label>
                                <?php if ($mode === 'view'): ?>
                                        <div class="view-data-box"><?php echo htmlspecialchars($supplier_data['msme_type'] ?: 'N/A'); ?></div>
                                <?php else: ?>
                                        <input type="text" id="msme_type" name="msme_type" value="<?php echo htmlspecialchars($supplier_data['msme_type'] ?? ''); ?>">
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="itr_status">ITR Status</label>
                                <?php if ($mode === 'view'): ?>
                                        <div class="view-data-box"><?php echo htmlspecialchars($supplier_data['itr_status'] ?: 'N/A'); ?></div>
                                <?php else: ?>
                                        <input type="text" id="itr_status" name="itr_status" value="<?php echo htmlspecialchars($supplier_data['itr_status'] ?? ''); ?>">
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label class="required" for="pan_number">PAN Number</label>
                                <?php if ($mode === 'view'): ?>
                                        <div class="view-data-box"><?php echo htmlspecialchars($supplier_data['pan_number']); ?></div>
                                <?php else: ?>
                                        <input type="text" id="pan_number" name="pan_number" value="<?php echo htmlspecialchars($supplier_data['pan_number'] ?? ''); ?>" required>
                                <?php endif; ?>
                                <div class="error-message"></div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Under GST Registration</label>
                                <?php if ($mode === 'view'): ?>
                                        <div class="view-data-box"><?php echo htmlspecialchars($supplier_data['under_gst'] ?: 'No'); ?></div>
                                <?php else: ?>
                                        <div class="checkbox-group">
                                            <label><input type="radio" name="under_gst" value="Yes" <?php echo ($supplier_data['under_gst'] ?? '') === 'Yes' ? 'checked' : ''; ?>> Yes</label>
                                            <label><input type="radio" name="under_gst" value="No" <?php echo ($supplier_data['under_gst'] ?? 'No') === 'No' ? 'checked' : ''; ?>> No</label>
                                        </div>
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label class="required" for="gst_reg_number">GST Regd. Number</label>
                                <?php if ($mode === 'view'): ?>
                                        <div class="view-data-box"><?php echo htmlspecialchars($supplier_data['gst_reg_number'] ?: 'N/A'); ?></div>
                                <?php else: ?>
                                        <input type="text" id="gst_reg_number" name="gst_reg_number" value="<?php echo htmlspecialchars($supplier_data['gst_reg_number'] ?? ''); ?>">
                                <?php endif; ?>
                                <div class="error-message"></div>
                            </div>
                            <div class="form-group">
                                <label for="tan_number">TAN Number</label>
                                <?php if ($mode === 'view'): ?>
                                        <div class="view-data-box"><?php echo htmlspecialchars($supplier_data['tan_number'] ?: 'N/A'); ?></div>
                                <?php else: ?>
                                        <input type="text" id="tan_number" name="tan_number" value="<?php echo htmlspecialchars($supplier_data['tan_number'] ?? ''); ?>">
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- BANK DETAILS -->
                    <div id="bank-detail" class="glass-card" style="margin-bottom: 32px;">
                        <div class="section-title" style="margin-top: 0;">Bank Detail</div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="required" for="bank_name">Bank Name</label>
                                <?php if ($mode === 'view'): ?>
                                        <div class="view-data-box"><?php echo htmlspecialchars($supplier_data['bank_name']); ?></div>
                                <?php else: ?>
                                        <input type="text" id="bank_name" name="bank_name" value="<?php echo htmlspecialchars($supplier_data['bank_name'] ?? ''); ?>" required>
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label class="required" for="account_type">Account Type</label>
                                <?php if ($mode === 'view'): ?>
                                        <div class="view-data-box"><?php echo htmlspecialchars($supplier_data['account_type']); ?></div>
                                <?php else: ?>
                                        <select id="account_type" name="account_type" required>
                                            <option value="">Select</option>
                                            <option value="Savings" <?php echo ($supplier_data['account_type'] ?? '') === 'Savings' ? 'selected' : ''; ?>>Savings</option>
                                            <option value="Current" <?php echo ($supplier_data['account_type'] ?? '') === 'Current' ? 'selected' : ''; ?>>Current</option>
                                        </select>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="required" for="account_number">Account Number</label>
                                <?php if ($mode === 'view'): ?>
                                        <div class="view-data-box"><?php echo htmlspecialchars($supplier_data['account_number']); ?></div>
                                <?php else: ?>
                                        <input type="text" id="account_number" name="account_number" value="<?php echo htmlspecialchars($supplier_data['account_number'] ?? ''); ?>" required>
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label class="required" for="ifsc_code">Bank IFSC Number</label>
                                <?php if ($mode === 'view'): ?>
                                        <div class="view-data-box"><?php echo htmlspecialchars($supplier_data['ifsc_code']); ?></div>
                                <?php else: ?>
                                        <input type="text" id="ifsc_code" name="ifsc_code" value="<?php echo htmlspecialchars($supplier_data['ifsc_code'] ?? ''); ?>" required>
                                <?php endif; ?>
                                <div class="error-message"></div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group full-width">
                                <label class="required" for="bank_branch_address">Bank Branch Address</label>
                                <?php if ($mode === 'view'): ?>
                                        <div class="view-data-box"><?php echo htmlspecialchars($supplier_data['bank_branch_address']); ?></div>
                                <?php else: ?>
                                        <input type="text" id="bank_branch_address" name="bank_branch_address" value="<?php echo htmlspecialchars($supplier_data['bank_branch_address'] ?? ''); ?>" required>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="required" for="bank_city">City</label>
                                <?php if ($mode === 'view'): ?>
                                        <div class="view-data-box"><?php echo htmlspecialchars($supplier_data['bank_city']); ?></div>
                                <?php else: ?>
                                        <input type="text" id="bank_city" name="bank_city" value="<?php echo htmlspecialchars($supplier_data['bank_city'] ?? ''); ?>" required>
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label class="required" for="bank_state">State</label>
                                <?php if ($mode === 'view'): ?>
                                        <div class="view-data-box"><?php echo htmlspecialchars($supplier_data['bank_state']); ?></div>
                                <?php else: ?>
                                        <input type="text" id="bank_state" name="bank_state" value="<?php echo htmlspecialchars($supplier_data['bank_state'] ?? ''); ?>" required>
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label for="bank_postal_code">Postal Code</label>
                                <?php if ($mode === 'view'): ?>
                                        <div class="view-data-box"><?php echo htmlspecialchars($supplier_data['bank_postal_code'] ?: 'N/A'); ?></div>
                                <?php else: ?>
                                        <input type="text" id="bank_postal_code" name="bank_postal_code" value="<?php echo htmlspecialchars($supplier_data['bank_postal_code'] ?? ''); ?>">
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top: 30px; padding: 20px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                        <label style="display: flex; align-items: flex-start; gap: 12px; font-weight: normal; text-transform: none; <?php echo ($mode === 'view') ? '' : 'cursor: pointer;'; ?>">
                            <input type="checkbox" name="declaration" value="1" style="margin-top: 4px;" required <?php echo ($supplier_data['declaration_accepted'] ?? 0) ? 'checked' : ''; ?> <?php echo ($mode === 'view') ? 'disabled' : ''; ?>>
                            <span style="font-size: 0.9rem; color: #1e293b; line-height: 1.5;">
                                I hereby confirm that the documents submitted are valid, authentic, and belong to the entity registered above. I understand that providing false information may lead to blacklisting.
                            </span>
                        </label>
                    </div>


                    <?php if ($mode !== 'view'): ?>
                            <div style="margin-top: 32px; text-align: center;">
                                <button type="submit" class="btn-primary" style="width: auto; padding: 14px 60px; border-radius: 8px; font-size: 1.1rem;">
                                    <?php echo ($mode === 'new') ? 'SUBMIT APPLICATION' : 'UPDATE PROFILE'; ?>
                                </button>
                            </div>
                    <?php endif; ?>
            </form>
        </main>
    </div>
    <!-- OCR Library -->
    <script src='https://unpkg.com/tesseract.js@v2.1.0/dist/tesseract.min.js'></script>
    <script src="../assets/js/script.js"></script>
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

        function toggleField(id, show) {
            const el = document.getElementById(id);
            if(el) el.style.display = show ? 'block' : 'none';
        }
        function toggleDocRow(rowId, show, inputId) {
            const row = document.getElementById(rowId);
            const input = document.getElementById(inputId);
            if(row) row.style.display = show ? 'block' : 'none';
            if(input) {
                if(show) {
                    // Only make required if no file exists yet
                    if(!input.parentElement.querySelector('a')) {
                        input.required = true;
                    }
                } else {
                    input.required = false;
                }
            }
        }
    </script>
</body>

</html>