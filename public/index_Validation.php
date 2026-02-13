<?php
session_start();
$mode = 'edit'; // set 'view' if needed
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Supplier Portal | Single File Validation</title>
  <style>
    body { font-family: Arial, sans-serif; background:#f6f7fb; margin:0; padding:24px; }
    .card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:18px; max-width:1100px; margin:0 auto 18px; }
    .title { font-size:18px; font-weight:700; margin:0 0 12px; }
    .row { display:flex; gap:14px; flex-wrap:wrap; }
    .form-group { flex:1; min-width:260px; }
    label { display:block; font-size:13px; color:#111827; font-weight:700; margin:10px 0 6px; }
    input[type="text"], select { width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:8px; outline:none; }
    input[type="text"]:focus, select:focus { border-color:#2563eb; box-shadow:0 0 0 3px rgba(37,99,235,.15); }
    .radio { display:flex; gap:16px; align-items:center; margin-top:6px; }
    .radio label { display:flex; gap:8px; align-items:center; font-weight:600; margin:0; }
    .error-message { font-size:12px; margin-top:6px; color:#dc2626; min-height:16px; }
    .hint { font-size:12px; color:#64748b; margin-top:6px; }
    .hidden { display:none !important; }
    .required-star::after { content:" *"; color:#dc2626; font-weight:800; }
    .btn { background:#2563eb; color:#fff; border:none; padding:12px 18px; border-radius:10px; font-weight:800; cursor:pointer; }
    .btn:disabled { opacity:.6; cursor:not-allowed; }
    .bar { display:flex; justify-content:space-between; align-items:center; max-width:1100px; margin:0 auto 12px; }
  </style>
</head>
<body>

<div class="bar">
  <div style="font-weight:900;">JIL – VMS (Validation Demo)</div>
  <div style="color:#64748b; font-size:13px;">Mode: <b><?php echo htmlspecialchars($mode); ?></b></div>
</div>

<form id="supplierForm" action="" method="POST" novalidate>

  <div class="card">
    <p class="title">Tax & Legal Detail</p>

    <div class="row">
      <!-- ITR -->
      <div class="form-group">
        <label class="required-star">ITR Status</label>
        <div class="radio">
          <label><input type="radio" name="itr_status" value="Yes"> Yes</label>
          <label><input type="radio" name="itr_status" value="No" checked> No</label>
        </div>
        <div class="hint">If Yes → PAN becomes mandatory.</div>
      </div>

      <!-- PAN -->
      <div class="form-group" id="panGroup">
        <label id="panLabel">PAN Number</label>
        <input type="text" id="pan_number" name="pan_number" placeholder="ABCDE1234F" autocomplete="off" />
        <div class="error-message" id="panErr"></div>
      </div>
    </div>

    <div class="row">
      <!-- GST -->
      <div class="form-group">
        <label class="required-star">Under GST Registration</label>
        <div class="radio">
          <label><input type="radio" name="under_gst" value="Yes"> Yes</label>
          <label><input type="radio" name="under_gst" value="No" checked> No</label>
        </div>
        <div class="hint">If Yes → GST number becomes mandatory.</div>
      </div>

      <!-- GST Number -->
      <div class="form-group" id="gstGroup">
        <label id="gstLabel">GST Regd. Number</label>
        <input type="text" id="gst_reg_number" name="gst_reg_number" placeholder="22ABCDE1234F1Z5" autocomplete="off" />
        <div class="error-message" id="gstErr"></div>
      </div>
    </div>

    <div class="row">
      <!-- MSME -->
      <div class="form-group">
        <label class="required-star">Registered Under MSME</label>
        <div class="radio">
          <label><input type="radio" name="registered_msme" value="Yes"> Yes</label>
          <label><input type="radio" name="registered_msme" value="No" checked> No</label>
        </div>
        <div class="hint">If Yes → MSME(Udyam) + MSME Type become mandatory.</div>
      </div>

      <!-- MSME Number -->
      <div class="form-group" id="msmeNoGroup">
        <label id="msmeNoLabel">MSME Regd. Number (Udyam)</label>
        <input type="text" id="msme_reg_number" name="msme_reg_number" placeholder="UDYAM-PB-12-1234567" autocomplete="off" />
        <div class="error-message" id="msmeNoErr"></div>
      </div>

      <!-- MSME Type -->
      <div class="form-group" id="msmeTypeGroup">
        <label id="msmeTypeLabel">MSME Type</label>
        <input type="text" id="msme_type" name="msme_type" placeholder="Micro / Small / Medium" autocomplete="off" />
        <div class="error-message" id="msmeTypeErr"></div>
      </div>
    </div>
  </div>

  <div class="card" style="display:flex; justify-content:flex-end; gap:10px; align-items:center;">
    <button class="btn" type="submit" id="submitBtn">SUBMIT</button>
  </div>

</form>

<script>
(() => {
  // ---------- REGEX ----------
  const PAN_RE   = /^[A-Z]{5}[0-9]{4}[A-Z]$/;
  const GST_RE   = /^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/;
  const UDYAM_RE = /^UDYAM-[A-Z]{2}-[0-9]{2}-[0-9]{7}$/;

  // ---------- HELPERS ----------
  const qs = (s) => document.querySelector(s);
  const qsa = (s) => [...document.querySelectorAll(s)];
  const upperTrim = (v) => (v || "").toString().trim().toUpperCase();
  const isYes = (v) => upperTrim(v) === "YES";
  const radioVal = (name) => (qs(`input[name="${name}"]:checked`)?.value || "No");

  function setError(inputEl, errEl, msg) {
    if (!inputEl || !errEl) return;
    errEl.textContent = msg || "";
    inputEl.style.borderColor = msg ? "#dc2626" : "#cbd5e1";
  }

  function setMandatory(labelEl, isReq) {
    if (!labelEl) return;
    labelEl.classList.toggle("required-star", !!isReq);
  }

  function toggleGroup(groupEl, show) {
    if (!groupEl) return;
    groupEl.classList.toggle("hidden", !show);
  }

  // ---------- ELEMENTS ----------
  const form = qs("#supplierForm");

  const panGroup = qs("#panGroup");
  const gstGroup = qs("#gstGroup");
  const msmeNoGroup = qs("#msmeNoGroup");
  const msmeTypeGroup = qs("#msmeTypeGroup");

  const panInput = qs("#pan_number");
  const gstInput = qs("#gst_reg_number");
  const msmeNoInput = qs("#msme_reg_number");
  const msmeTypeInput = qs("#msme_type");

  const panErr = qs("#panErr");
  const gstErr = qs("#gstErr");
  const msmeNoErr = qs("#msmeNoErr");
  const msmeTypeErr = qs("#msmeTypeErr");

  const panLabel = qs("#panLabel");
  const gstLabel = qs("#gstLabel");
  const msmeNoLabel = qs("#msmeNoLabel");
  const msmeTypeLabel = qs("#msmeTypeLabel");

  // ---------- CONDITIONAL RULES ----------
  function applyRules() {
    const itr = radioVal("itr_status");
    const gst = radioVal("under_gst");
    const msme = radioVal("registered_msme");

    const itrYes = isYes(itr);
    const gstYes = isYes(gst);
    const msmeYes = isYes(msme);

    // show/hide
    toggleGroup(panGroup, itrYes);
    toggleGroup(gstGroup, gstYes);
    toggleGroup(msmeNoGroup, msmeYes);
    toggleGroup(msmeTypeGroup, msmeYes);

    // required markers (visual)
    setMandatory(panLabel, itrYes);
    setMandatory(gstLabel, gstYes);
    setMandatory(msmeNoLabel, msmeYes);
    setMandatory(msmeTypeLabel, msmeYes);

    // also flip HTML required (works with browser validation if you remove novalidate)
    panInput.required = itrYes;
    gstInput.required = gstYes;
    msmeNoInput.required = msmeYes;
    msmeTypeInput.required = msmeYes;

    // when turned off, clear errors (optional)
    if (!itrYes) setError(panInput, panErr, "");
    if (!gstYes) setError(gstInput, gstErr, "");
    if (!msmeYes) {
      setError(msmeNoInput, msmeNoErr, "");
      setError(msmeTypeInput, msmeTypeErr, "");
    }
  }

  // ---------- PER-FIELD VALIDATION ON BLUR ----------
  function validatePAN() {
    const itrYes = isYes(radioVal("itr_status"));
    if (!itrYes) return true;

    const pan = upperTrim(panInput.value);
    panInput.value = pan;

    if (!pan) return setError(panInput, panErr, "PAN Number is required (ITR Status = Yes)."), false;
    if (!PAN_RE.test(pan)) return setError(panInput, panErr, "Invalid PAN. Example: ABCDE1234F"), false;

    setError(panInput, panErr, "");
    return true;
  }

  function validateGST() {
    const gstYes = isYes(radioVal("under_gst"));
    if (!gstYes) return true;

    const gst = upperTrim(gstInput.value);
    gstInput.value = gst;

    if (!gst) return setError(gstInput, gstErr, "GST Regd. Number is required (Under GST = Yes)."), false;
    if (!GST_RE.test(gst)) return setError(gstInput, gstErr, "Invalid GST. Example: 22ABCDE1234F1Z5"), false;

    // optional: PAN inside GST should match PAN format (positions 3-12)
    const panInside = gst.substring(2, 12);
    if (!PAN_RE.test(panInside)) return setError(gstInput, gstErr, "GST looks invalid (PAN part mismatch)."), false;

    setError(gstInput, gstErr, "");
    return true;
  }

  function validateMSME() {
    const msmeYes = isYes(radioVal("registered_msme"));
    if (!msmeYes) return true;

    const udyam = upperTrim(msmeNoInput.value);
    msmeNoInput.value = udyam;

    let ok = true;

    if (!udyam) { setError(msmeNoInput, msmeNoErr, "MSME Regd. Number is required (MSME = Yes)."); ok = false; }
    else if (!UDYAM_RE.test(udyam)) { setError(msmeNoInput, msmeNoErr, "Invalid UDYAM. Example: UDYAM-PB-12-1234567"); ok = false; }
    else setError(msmeNoInput, msmeNoErr, "");

    const t = (msmeTypeInput.value || "").trim();
    if (!t) { setError(msmeTypeInput, msmeTypeErr, "MSME Type is required (MSME = Yes)."); ok = false; }
    else setError(msmeTypeInput, msmeTypeErr, "");

    return ok;
  }

  // attach blur events
  panInput.addEventListener("blur", validatePAN);
  gstInput.addEventListener("blur", validateGST);
  msmeNoInput.addEventListener("blur", validateMSME);
  msmeTypeInput.addEventListener("blur", validateMSME);

  // when parent radios change, re-apply rules and validate visible stuff
  ["itr_status", "under_gst", "registered_msme"].forEach(name => {
    qsa(`input[name="${name}"]`).forEach(r => r.addEventListener("change", () => {
      applyRules();
      // validate currently relevant fields immediately
      validatePAN(); validateGST(); validateMSME();
    }));
  });

  // submit hard gate
  form.addEventListener("submit", (e) => {
    applyRules();
    const ok = validatePAN() & validateGST() & validateMSME(); // single pass
    if (!ok) {
      e.preventDefault();
      // auto scroll to first error
      const firstErr = [panErr, gstErr, msmeNoErr, msmeTypeErr].find(el => el.textContent.trim().length);
      if (firstErr) firstErr.scrollIntoView({ behavior: "smooth", block: "center" });
    }
  });

  // init
  applyRules();
})();
</script>

</body>
</html>
