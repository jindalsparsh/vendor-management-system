<?php
session_start();
require_once '../includes/db_connect.php';

// If already logged in, redirect to index
if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true) {
    header("Location: index.php");
    exit();
}

$mode = 'new';
$supplier_data = null;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Registration | Jagatjit Industries</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .registration-layout {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .main-content {
            margin-left: 0 !important;
            padding: 0 !important;
        }

        .sidebar {
            display: none !important;
        }
    </style>
</head>

<body>
    <div class="registration-layout">
        <main class="main-content">
            <header class="top-bar"
                style="margin-bottom: 32px; border-bottom: 2px solid #e2e8f0; padding-bottom: 20px;">
                <div style="display: flex; align-items: center; gap: 20px;">
                    <img src="../assets/img/logo.jpg" alt="Jagatjit Industries" style="height: 60px;">
                    <div>
                        <h1 class="page-title" style="margin: 0; font-size: 1.75rem;">Vendor Registration</h1>
                        <p style="color: #64748b; margin-top: 4px;">Join the Jagatjit Industries supplier network</p>
                    </div>
                </div>
                <div>
                    <a href="login.php" class="btn-view" style="color: #3b82f6; border: 1px solid #3b82f6;">Back to
                        Login</a>
                </div>
            </header>

            <?php if (isset($_GET['error'])): ?>
                <div class="error-banner" style="margin-bottom: 24px;">
                    <b>Validation Failed:</b> <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <form action="process.php" method="POST" enctype="multipart/form-data">
                <!-- ACCOUNT CREDENTIALS -->
                <div id="account-credentials" class="glass-card"
                    style="margin-bottom: 32px; border-left: 4px solid #3b82f6;">
                    <div class="section-title" style="margin-top: 0; padding-top: 0; color: #1e40af;">Account
                        Credentials</div>
                    <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 20px;">These will be your login
                        credentials for the portal.</p>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="required" for="email">Login Email (Username)</label>
                            <input type="email" id="email" name="email" required
                                placeholder="Enter your professional email">
                            <div class="error-message"></div>
                        </div>
                        <div class="form-group">
                            <label class="required" for="password">Create Password</label>
                            <input type="password" id="password" name="password" required
                                placeholder="Create a strong password">
                            <div class="error-message"></div>
                        </div>
                    </div>
                </div>

                <!-- DOCUMENTS (Step 1) -->
                <div id="documents-compliance" class="glass-card"
                    style="margin-bottom: 32px; border-left: 4px solid #10b981;">
                    <div class="section-title" style="margin-top: 0; padding-top: 0; color: #059669;">Step 1: Upload
                        Documents (Auto-fill Enabled)</div>
                    <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 20px;">Upload your GST Certificate to
                        automatically fill your business details.</p>

                    <div class="form-row">
                        <div class="form-group full-width">
                            <label class="required">Vendor Risk Classification</label>
                            <div style="display: flex; gap: 20px; margin-top: 10px;">
                                <label
                                    style="display: flex; align-items: center; gap: 8px; font-weight: normal; text-transform: none;">
                                    <input type="radio" name="risk_classification" value="Low" checked> Low Risk
                                    (Supplies)
                                </label>
                                <label
                                    style="display: flex; align-items: center; gap: 8px; font-weight: normal; text-transform: none;">
                                    <input type="radio" name="risk_classification" value="Medium"> Medium Risk
                                    (Services)
                                </label>
                                <label
                                    style="display: flex; align-items: center; gap: 8px; font-weight: normal; text-transform: none;">
                                    <input type="radio" name="risk_classification" value="High"> High Risk (IT/SaaS)
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="doc-grid"
                        style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 20px;">

                        <!-- PAN Card -->
                        <div class="doc-item">
                            <label class="required">Do you have a PAN Card? *</label>
                            <div style="display: flex; gap: 15px; margin: 8px 0;">
                                <label style="font-weight: normal;"><input type="radio" name="has_pan" value="Yes"
                                        checked onclick="toggleDocRow('pan_row', true, 'pan_card_doc')"> Yes</label>
                                <label style="font-weight: normal;"><input type="radio" name="has_pan" value="No"
                                        onclick="toggleDocRow('pan_row', false, 'pan_card_doc')"> No</label>
                            </div>
                            <div id="pan_row" style="margin-top: 10px;">
                                <label class="required" for="pan_card_doc">Upload PAN Card (PDF/Image)</label>
                                <input type="file" id="pan_card_doc" name="pan_card_doc" accept=".pdf,.jpg,.jpeg,.png"
                                    required>
                            </div>
                        </div>

                        <!-- GST Registration -->
                        <div class="doc-item">
                            <label class="required">Do you have a GST Registration? *</label>
                            <div style="display: flex; gap: 15px; margin: 8px 0;">
                                <label style="font-weight: normal;"><input type="radio" name="has_gst" value="Yes"
                                        checked onclick="toggleDocRow('gst_row', true, 'gst_cert_doc')"> Yes</label>
                                <label style="font-weight: normal;"><input type="radio" name="has_gst" value="No"
                                        onclick="toggleDocRow('gst_row', false, 'gst_cert_doc')"> No</label>
                            </div>
                            <div id="gst_row" style="margin-top: 10px;">
                                <label class="required" for="gst_cert_doc">Upload GST Certificate</label>
                                <input type="file" id="gst_cert_doc" name="gst_cert_doc" accept=".pdf,.jpg,.jpeg,.png"
                                    required>
                            </div>
                        </div>

                        <!-- Cancelled Cheque -->
                        <div class="doc-item">
                            <label class="required">Do you have a Cancelled Cheque? *</label>
                            <div style="display: flex; gap: 15px; margin: 8px 0;">
                                <label style="font-weight: normal;"><input type="radio" name="has_cheque" value="Yes"
                                        checked onclick="toggleDocRow('cheque_row', true, 'cancelled_cheque_doc')">
                                    Yes</label>
                                <label style="font-weight: normal;"><input type="radio" name="has_cheque" value="No"
                                        onclick="toggleDocRow('cheque_row', false, 'cancelled_cheque_doc')"> No</label>
                            </div>
                            <div id="cheque_row" style="margin-top: 10px;">
                                <label class="required" for="cancelled_cheque_doc">Upload Cancelled Cheque</label>
                                <input type="file" id="cancelled_cheque_doc" name="cancelled_cheque_doc"
                                    accept=".pdf,.jpg,.jpeg,.png" required>
                            </div>
                        </div>

                        <!-- MSME Certificate -->
                        <div class="doc-item">
                            <label class="required">Do you have an MSME Certificate?</label>
                            <div style="display: flex; gap: 15px; margin: 8px 0;">
                                <label style="font-weight: normal;"><input type="radio" name="has_msme" value="Yes"
                                        onclick="toggleDocRow('msme_row', true, 'msme_cert_doc')"> Yes</label>
                                <label style="font-weight: normal;"><input type="radio" name="has_msme" value="No"
                                        checked onclick="toggleDocRow('msme_row', false, 'msme_cert_doc')"> No</label>
                            </div>
                            <div id="msme_row" style="display:none; margin-top: 10px;">
                                <label class="required" for="msme_cert_doc">Upload MSME Certificate</label>
                                <input type="file" id="msme_cert_doc" name="msme_cert_doc"
                                    accept=".pdf,.jpg,.jpeg,.png">
                            </div>
                        </div>

                    </div>
                </div>

                <!-- BUSINESS/COMPANY DETAIL -->
                <div id="business-detail" class="glass-card" style="margin-bottom: 32px;">
                    <div class="section-title" style="margin-top: 0; padding-top: 0;">Step 2: Business/Company Detail
                    </div>

                    <div class="form-row">
                        <div class="form-group full-width">
                            <label class="required" for="company_name">Name of Company</label>
                            <input type="text" id="company_name" name="company_name" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group full-width">
                            <label class="required" for="company_address">Company Address</label>
                            <textarea id="company_address" name="company_address" rows="3" required></textarea>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="required" for="city">City</label>
                            <input type="text" id="city" name="city" required>
                        </div>
                        <div class="form-group">
                            <label class="required" for="state">State</label>
                            <input type="text" id="state" name="state" required>
                        </div>
                        <div class="form-group">
                            <label class="required" for="postal_code">Postal Code</label>
                            <input type="text" id="postal_code" name="postal_code" required>
                        </div>
                        <div class="form-group">
                            <label class="required" for="country">Country</label>
                            <input type="text" id="country" name="country" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="supplier_website">Supplier Website (If Any)</label>
                            <input type="url" id="supplier_website" name="supplier_website">
                        </div>
                    </div>

                    <div class="form-row">
                        <label>Nature of Business:</label>
                        <div class="checkbox-group">
                            <label><input type="checkbox" name="nature_of_business[]" value="Manufacturer">
                                Manufacturer</label>
                            <label><input type="checkbox" name="nature_of_business[]" value="Traders"> Traders</label>
                            <label><input type="checkbox" name="nature_of_business[]" value="Service Provider"> Service
                                Provider</label>
                            <label><input type="checkbox" name="nature_of_business[]" value="Consulting Company">
                                Consulting Company</label>
                            <label><input type="checkbox" name="nature_of_business[]" value="Other"> Other</label>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="product_services_type">Type of Product/Services</label>
                            <input type="text" id="product_services_type" name="product_services_type">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="market_type">Domestic/International</label>
                            <select id="market_type" name="market_type">
                                <option value="Domestic">Domestic</option>
                                <option value="International">International</option>
                                <option value="Both">Both</option>
                            </select>
                        </div>
                    </div>

                    <!-- NEW FIELDS: PAN, MSME, GST -->
                    <div class="form-row">
                        <div class="form-group">
                            <label class="required" for="pan_number">PAN Number</label>
                            <input type="text" id="pan_number" name="pan_number" required placeholder="ABCDE1234F"
                                style="text-transform: uppercase;">
                        </div>
                    </div>

                    <div class="form-row" style="margin-top: 20px;">
                        <div class="form-group">
                            <label>Registered Under MSME?</label>
                            <div class="radio-group" style="display: flex; gap: 20px; margin-top: 8px;">
                                <label><input type="radio" name="registered_msme" value="Yes"
                                        onclick="toggleField('msme_field', true)"> Yes</label>
                                <label><input type="radio" name="registered_msme" value="No" checked
                                        onclick="toggleField('msme_field', false)"> No</label>
                            </div>
                        </div>
                        <div class="form-group" id="msme_field" style="display: none;">
                            <label class="required" for="msme_reg_number">MSME Registration Number</label>
                            <input type="text" id="msme_reg_number" name="msme_reg_number"
                                placeholder="UDYAM-XX-00-0000000">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Registered Under GST?</label>
                            <div class="radio-group" style="display: flex; gap: 20px; margin-top: 8px;">
                                <label><input type="radio" name="under_gst" value="Yes" checked
                                        onclick="toggleField('gst_field', true)"> Yes</label>
                                <label><input type="radio" name="under_gst" value="No"
                                        onclick="toggleField('gst_field', false)"> No</label>
                            </div>
                        </div>
                        <div class="form-group" id="gst_field">
                            <label class="required" for="gst_reg_number">GST Registration Number</label>
                            <input type="text" id="gst_reg_number" name="gst_reg_number" placeholder="22AAAAA0000A1Z5"
                                style="text-transform: uppercase;">
                        </div>
                    </div>
                </div>

                <!-- COMMUNICATION DETAIL -->
                <div id="communication-detail" class="glass-card" style="margin-bottom: 32px;">
                    <div class="section-title" style="margin-top: 0;">Step 3: Communication Detail</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="contact_first_name">First Name</label>
                            <input type="text" id="contact_first_name" name="contact_first_name">
                        </div>
                        <div class="form-group">
                            <label for="contact_middle_name">Middle Name</label>
                            <input type="text" id="contact_middle_name" name="contact_middle_name">
                        </div>
                        <div class="form-group">
                            <label for="contact_last_name">Last Name</label>
                            <input type="text" id="contact_last_name" name="contact_last_name">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="required" for="mobile_number">Mobile Number</label>
                            <input type="tel" id="mobile_number" name="mobile_number" required pattern="\d{10}"
                                title="Exactly 10 digits required">
                        </div>
                        <div class="form-group">
                            <label for="alt_mobile_number">Alternate Mobile Number</label>
                            <input type="tel" id="alt_mobile_number" name="alt_mobile_number" pattern="\d{10}"
                                title="Exactly 10 digits required">
                        </div>
                        <div class="form-group">
                            <label for="landline_number">Landline Number</label>
                            <input type="tel" id="landline_number" name="landline_number" pattern="\d{10,12}"
                                title="Between 10 to 12 digits required">
                        </div>
                    </div>
                </div>

                <!-- BANK DETAILS (Simplified) -->
                <div id="bank-detail" class="glass-card" style="margin-bottom: 32px;">
                    <div class="section-title" style="margin-top: 0;">Step 4: Bank Detail</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="required" for="bank_name">Bank Name</label>
                            <input type="text" id="bank_name" name="bank_name" required>
                        </div>
                        <div class="form-group">
                            <label class="required" for="account_number">Account Number</label>
                            <input type="text" id="account_number" name="account_number" required>
                        </div>
                        <div class="form-group">
                            <label class="required" for="ifsc_code">Bank IFSC Number</label>
                            <input type="text" id="ifsc_code" name="ifsc_code" required>
                        </div>
                    </div>
                </div>


                <div style="text-align: center; margin-top: 40px;">
                    <div style="margin-bottom: 20px; text-align: left;">
                        <label
                            style="display: flex; align-items: flex-start; gap: 12px; font-weight: normal; cursor: pointer;">
                            <input type="checkbox" name="declaration" value="1" required style="margin-top: 4px;">
                            <span style="font-size: 0.9rem; color: #1e293b;">
                                I hereby confirm that all provided information is accurate.
                            </span>
                        </label>
                    </div>
                    <button type="submit" class="btn-primary"
                        style="width: auto; padding: 14px 80px; font-size: 1.1rem;">
                        SUBMIT REGISTRATION
                    </button>
                    <p style="margin-top: 15px; font-size: 0.875rem; color: #64748b;">
                        Already registered? <a href="login.php" style="color: #3b82f6; font-weight: 600;">Sign In</a>
                    </p>
                </div>
            </form>
        </main>
    </div>
    <!-- OCR Library -->
    <script src='https://unpkg.com/tesseract.js@v2.1.0/dist/tesseract.min.js'></script>
    <script src="../assets/js/script.js"></script>
    <script>
        function toggleField(id, show) {
            const el = document.getElementById(id);
            if (el) el.style.display = show ? 'block' : 'none';
        }
        function toggleDocRow(rowId, show, inputId) {
            const row = document.getElementById(rowId);
            const input = document.getElementById(inputId);
            if (row) row.style.display = show ? 'block' : 'none';
            if (input) input.required = show;
        }
    </script>
</body>

</html>