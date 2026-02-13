document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form');

    // Helper function to show error (Scoped to DOMContentLoaded)
    const showError = (input, message) => {
        if (!input) return;
        input.classList.add('input-error');
        const errorDiv = input.nextElementSibling;
        if (errorDiv && errorDiv.classList.contains('error-message')) {
            errorDiv.innerText = message;
            errorDiv.style.display = 'block';
        }
    };

    // Helper to clear error
    const clearError = (input) => {
        if (!input) return;
        input.classList.remove('input-error');
        const errorDiv = input.nextElementSibling;
        if (errorDiv && errorDiv.classList.contains('error-message')) {
            errorDiv.style.display = 'none';
        }
    };

    // --- ADDRESS AUTO-COMPLETION ---
    const setupAddressAutoFill = (pinId, cityId, stateId, countryId) => {
        const pinInput = document.getElementById(pinId);
        const cityInput = document.getElementById(cityId);
        const stateInput = document.getElementById(stateId);
        const countryInput = countryId ? document.getElementById(countryId) : null;

        if (pinInput) {
            pinInput.addEventListener('input', function () {
                const pincode = this.value.trim();

                // Clear errors on input
                clearError(pinInput);

                // Only trigger if 6 digits
                if (/^\d{6}$/.test(pincode)) {
                    // Show loading indication
                    if (cityInput) cityInput.value = "Fetching...";
                    if (stateInput) stateInput.value = "Fetching...";
                    if (countryInput) countryInput.value = "Fetching...";

                    fetch(`https://api.postalpincode.in/pincode/${pincode}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data && data[0].Status === 'Success') {
                                const details = data[0].PostOffice[0];
                                if (cityInput) cityInput.value = details.District;
                                if (stateInput) stateInput.value = details.State;
                                if (countryInput) countryInput.value = details.Country;
                            } else {
                                // Invalid PIN
                                if (cityInput) cityInput.value = "";
                                if (stateInput) stateInput.value = "";
                                if (countryInput) countryInput.value = "";
                                showError(pinInput, 'Invalid PIN Code.');
                            }
                        })
                        .catch(err => {
                            console.error("API Error", err);
                            // Reset on error
                            if (cityInput) cityInput.value = "";
                            if (stateInput) stateInput.value = "";
                        });
                }
            });
        }
    };

    // Activate Auto-fill for Business Address
    setupAddressAutoFill('postal_code', 'city', 'state', 'country');
    // Activate Auto-fill for Bank Address
    setupAddressAutoFill('bank_postal_code', 'bank_city', 'bank_state', null);


    // --- GST AUTO-FILL (OCR) ---
    // Wrap in DOMContentLoaded to ensure all elements are available
    document.addEventListener('DOMContentLoaded', function () {
        console.log('GST OCR: Initializing...');

        const gstInput = document.getElementById('gst_cert_doc');
        const gstNumberInput = document.getElementById('gst_reg_number');
        const companyNameInput = document.getElementById('company_name');
        const addressInput = document.getElementById('company_address');
        const panInput = document.getElementById('pan_number');

        console.log('GST OCR: Elements found:', {
            gstInput: !!gstInput,
            gstNumberInput: !!gstNumberInput,
            companyNameInput: !!companyNameInput,
            panInput: !!panInput,
            tesseractAvailable: typeof Tesseract !== 'undefined'
        });

        // Status visualizer
        const gstLabel = gstInput?.previousElementSibling;
        const originalLabelText = gstLabel ? gstLabel.innerText : "GST Registration Certificate";

        if (gstInput) {
            console.log('GST OCR: Event listener attached to gst_cert_doc');
            gstInput.addEventListener('change', function (e) {
                const file = e.target.files[0];
                console.log('GST OCR: File selected:', file?.name, file?.type);
                if (!file) return;

                // Simple check for image type
                if (!file.type.startsWith('image/')) {
                    console.warn('GST OCR: File is not an image, skipping OCR');
                    alert('Please upload an image file (JPG, PNG) for OCR to work. PDF files are not supported yet.');
                    return;
                }

                // Check if Tesseract is available
                if (typeof Tesseract === 'undefined') {
                    console.error('GST OCR: Tesseract library not loaded!');
                    alert('OCR library failed to load. Please refresh the page and try again.');
                    return;
                }

                // Visual Feedback
                if (gstLabel) gstLabel.innerText = "Scanning Certificate... ⏳";
                console.log('GST OCR: Starting Tesseract recognition...');

                Tesseract.recognize(
                    file,
                    'eng',
                    { logger: m => console.log('Tesseract:', m) }
                ).then(({ data: { text } }) => {
                    console.log("GST OCR: Extracted Text:", text);

                    // 1. Extract GSTIN (Format: 22AAAAA0000A1Z5)
                    const gstRegex = /\d{2}[A-Z]{5}\d{4}[A-Z]{1}[A-Z\d]{1}[Z]{1}[A-Z\d]{1}/;
                    const gstMatch = text.match(gstRegex);

                    if (gstMatch) {
                        const gstNumber = gstMatch[0];
                        console.log('GST OCR: Found GSTIN:', gstNumber);
                        if (gstNumberInput) {
                            gstNumberInput.value = gstNumber;
                            gstNumberInput.classList.add('flash-success'); // Add CSS class for effect
                            // Remove error if any
                            clearError(gstNumberInput);
                        }

                        // 2. Extract PAN from GSTIN (Chars 3-12)
                        if (panInput) {
                            const pan = gstNumber.substring(2, 12);
                            console.log('GST OCR: Extracted PAN:', pan);
                            panInput.value = pan;
                            clearError(panInput);
                        }
                    } else {
                        console.warn('GST OCR: No GSTIN found in extracted text');
                    }

                    // 3. Attempt to extract Company Name (Heuristic: Lines likely to be name)
                    // Look for lines near top, avoiding keywords like "Government", "India", "Form", "Registration"
                    const lines = text.split('\n').map(l => l.trim()).filter(l => l.length > 3);
                    let potentialName = "";

                    const ignoreKeywords = ["GOVERNMENT", "INDIA", "FORM", "REGISTRATION", "CERTIFICATE", "GST", "TAX", "DEPARTMENT"];

                    for (let line of lines) {
                        const upperLine = line.toUpperCase();
                        if (!ignoreKeywords.some(keyword => upperLine.includes(keyword)) && /^[A-Z\s\.]+$/.test(upperLine)) {
                            // If line is all caps and not a keyword, it's a strong candidate for Legal Name
                            potentialName = line;
                            break;
                        }
                    }

                    if (potentialName && companyNameInput && !companyNameInput.value) {
                        console.log('GST OCR: Found company name:', potentialName);
                        companyNameInput.value = potentialName;
                        clearError(companyNameInput);
                    } else {
                        console.log('GST OCR: No company name extracted or field already filled');
                    }

                    // Restore Label
                    if (gstLabel) gstLabel.innerText = originalLabelText + " ✅ Scanned";
                    console.log('GST OCR: Scan completed successfully');

                }).catch(err => {
                    console.error('GST OCR: Error during recognition:', err);
                    if (gstLabel) gstLabel.innerText = originalLabelText + " ❌ Scan Failed";
                    alert('OCR failed. Please try with a clearer image or enter details manually.');
                });
            });
        } else {
            console.error('GST OCR: gst_cert_doc input element not found!');
        }
    });


    // --- FORM VALIDATION ---
    if (form) {
        form.addEventListener('submit', function (e) {
            let isValid = true;

            // Clear all previous errors
            document.querySelectorAll('.error-message').forEach(el => el.style.display = 'none');
            document.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));

            // Local wrapper to track validity
            const validate = (input, message) => {
                showError(input, message);
                isValid = false;
            };

            // 0. Email & Password
            const email = document.getElementById('email');
            if (email) {
                if (!email.value.trim()) validate(email, 'Email is required.');
                else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) validate(email, 'Please enter a valid email address.');
            }

            const password = document.getElementById('password');
            if (password) {
                if (!password.value.trim()) validate(password, 'Password is required.');
                else if (password.value.length < 8) validate(password, 'Password must be at least 8 characters.');
            }

            // 1. Company Name
            const companyName = document.getElementById('company_name');
            if (companyName && !companyName.value.trim()) validate(companyName, 'Company Name is required.');

            // 2. Address
            const companyAddress = document.getElementById('company_address');
            if (companyAddress && !companyAddress.value.trim()) validate(companyAddress, 'Address is required.');

            // 3. City, State, Country, Postal Code
            const requiredFields = ['city', 'state', 'postal_code', 'country'];
            requiredFields.forEach(id => {
                const el = document.getElementById(id);
                if (el && !el.value.trim()) validate(el, 'This field is required.');
            });

            // 4. Mobile Number
            const mobile = document.getElementById('mobile_number');
            if (mobile) {
                if (!mobile.value.trim()) validate(mobile, 'Mobile number is required.');
                else if (!/^\d{10}$/.test(mobile.value.trim())) validate(mobile, 'Please enter a valid 10-digit mobile number.');
            }

            // 5. PAN Number (India format: 5 letters, 4 digits, 1 letter)
            const pan = document.getElementById('pan_number');
            if (pan) {
                if (pan.value.trim()) {
                    if (!/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/.test(pan.value.trim().toUpperCase())) {
                        validate(pan, 'Invalid PAN format (e.g., ABCDE1234F).');
                    }
                } else if (pan.hasAttribute('required')) {
                    validate(pan, 'PAN Number is required.');
                }
            }

            // 6. MSME Number (Format: UDYAM-XX-00-0000000)
            const msme = document.getElementById('msme_reg_number');
            const registeredMsme = document.querySelector('input[name="registered_msme"]:checked');
            if (registeredMsme && registeredMsme.value === 'Yes') {
                if (!msme.value.trim()) {
                    validate(msme, 'MSME Number is required for registered vendors.');
                } else if (!/^UDYAM-[A-Z]{2}-[0-9]{2}-[0-9]{7}$/i.test(msme.value.trim())) {
                    validate(msme, 'Invalid MSME format (e.g., UDYAM-PB-12-1234567).');
                }
            }

            // 7. GST Validation
            const gst = document.getElementById('gst_reg_number');
            const underGst = document.querySelector('input[name="under_gst"]:checked');
            if (underGst && underGst.value === 'Yes') {
                if (!gst.value.trim()) {
                    validate(gst, 'GST Number is required since you are registered.');
                } else if (!/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/.test(gst.value.trim().toUpperCase())) {
                    validate(gst, 'Invalid GSTIN format.');
                }
            }

            // 8. Bank Details
            const bankFields = ['bank_name', 'account_type', 'account_number', 'ifsc_code', 'bank_branch_address', 'bank_city', 'bank_state'];
            bankFields.forEach(id => {
                const el = document.getElementById(id);
                if (el && !el.value.trim()) validate(el, 'This field is required.');
            });

            // IFSC Validation (4 letters, 0, 6 chars)
            const ifsc = document.getElementById('ifsc_code');
            if (ifsc && ifsc.value.trim() && !/^[A-Z]{4}0[A-Z0-9]{6}$/.test(ifsc.value.trim().toUpperCase())) {
                validate(ifsc, 'Invalid IFSC Code format.');
            }

            if (!isValid) {
                e.preventDefault();
                // Scroll to first error
                const firstError = document.querySelector('.input-error');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
    }
});