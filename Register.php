<?php
session_start();
include_once("db.php");

// Redirect if not logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// ── Fetch all active cases from DB ──────────────────────────────────────────
$cases = [];
$sql    = "SELECT * FROM tblcaseregister WHERE CaseStatus='Active' ORDER BY CreatedAt DESC";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $cases[] = $row;
    }
}

$year = date("Y");

// ── Auto-generate next Case ID ───────────────────────────────────────────────
// FIX: Use MAX instead of COUNT so deleting records never causes duplicate IDs.
// Extracts the numeric suffix from the last CASE-YYYY-NNN entry for this year.
$yearPrefix  = "CASE-$year-";
$maxSql      = "SELECT CaseID FROM tblcaseregister WHERE CaseID LIKE '$yearPrefix%' ORDER BY CaseID DESC LIMIT 1";
$maxResult   = mysqli_query($conn, $maxSql);
if ($maxResult && mysqli_num_rows($maxResult) > 0) {
    $maxRow     = mysqli_fetch_assoc($maxResult);
    $lastNumber = (int) substr($maxRow['CaseID'], strrpos($maxRow['CaseID'], '-') + 1);
    $nextNumber = $lastNumber + 1;
} else {
    $nextNumber = 1; // Very first case of the year
}
$AutoCaseID = $yearPrefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BEMS — Register New Case</title>
    <link href="https://fonts.googleapis.com/css2?family=Source+Serif+4:wght@400;600;700&family=Source+Sans+3:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --navy:    #0d2240;
            --navy2:   #163356;
            --gold:    #c8972a;
            --gold-lt: #e8b84b;
            --bg:      #f4f1eb;
            --bg2:     #ece8df;
            --white:   #ffffff;
            --text:    #1a1a1a;
            --muted:   #5c5c5c;
            --border:  #d0c9bc;
            --red:     #9b1c1c;
            --green:   #1a5c36;
            --focus:   #163356;
        }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Source Sans 3', sans-serif;
            min-height: 100vh;
        }

        /* ─── BANNER ─── */
        .gov-banner {
            background: var(--navy);
            color: #a8bcd4;
            font-size: 11.5px;
            letter-spacing: 0.04em;
            padding: 6px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .gov-banner strong { color: #fff; }

        /* ─── HEADER ─── */
        .top-header {
            background: var(--navy2);
            border-bottom: 4px solid var(--gold);
            padding: 0 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 76px;
        }
        .header-left { display: flex; align-items: center; gap: 16px; }
        .seal {
            width: 52px; height: 52px;
            border-radius: 50%;
            border: 2px solid var(--gold);
            background: var(--navy);
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; flex-shrink: 0;
        }
        .header-titles h1 {
            font-family: 'Source Serif 4', serif;
            font-size: 17px; font-weight: 700;
            color: #fff; line-height: 1.2;
        }
        .header-titles p {
            font-size: 10.5px; color: #a8bcd4;
            letter-spacing: 0.08em; text-transform: uppercase; margin-top: 2px;
        }
        .back-btn {
            display: flex; align-items: center; gap: 7px;
            padding: 8px 18px;
            background: transparent;
            border: 1px solid rgba(200,151,42,0.45);
            border-radius: 4px;
            color: var(--gold-lt);
            font-size: 12px; font-weight: 600;
            letter-spacing: 0.06em; text-transform: uppercase;
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
        }
        .back-btn:hover { background: var(--gold); border-color: var(--gold); color: var(--navy); }

        /* ─── BREADCRUMB ─── */
        .breadcrumb-bar {
            background: var(--bg2);
            border-bottom: 1px solid var(--border);
            padding: 8px 32px;
            font-size: 12px; color: var(--muted);
        }
        .breadcrumb-bar span { color: var(--navy); font-weight: 600; }

        /* ─── MAIN WRAP ─── */
        .main-wrap {
            max-width: 1100px;
            margin: 0 auto;
            padding: 40px 32px 80px;
        }

        /* ─── PAGE TITLE ─── */
        .page-title-bar {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            margin-bottom: 28px;
            padding-bottom: 18px;
            border-bottom: 2px solid var(--border);
        }
        .page-title-bar h2 {
            font-family: 'Source Serif 4', serif;
            font-size: 24px; font-weight: 700;
            color: var(--navy);
        }
        .page-title-bar h2 small {
            display: block;
            font-family: 'Source Sans 3', sans-serif;
            font-size: 13px; font-weight: 400;
            color: var(--muted); margin-top: 4px;
        }
        .form-ref {
            font-size: 11px; color: var(--muted);
            letter-spacing: 0.1em; text-transform: uppercase;
            text-align: right; line-height: 1.7;
        }
        .form-ref strong { color: var(--navy); }

        /* ─── FORM CARD ─── */
        .form-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-top: 4px solid var(--navy);
            border-radius: 4px;
            overflow: hidden;
        }
        .form-section {
            padding: 28px 32px;
            border-bottom: 1px solid var(--border);
        }
        .form-section:last-child { border-bottom: none; }
        .section-label {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: var(--navy);
            margin-bottom: 22px;
        }
        .section-label::after { content: ''; flex: 1; height: 1px; background: var(--border); }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px 20px;
        }
        .form-grid .span-2 { grid-column: span 2; }
        .form-grid .span-4 { grid-column: span 4; }

        @media (max-width: 860px) {
            .form-grid { grid-template-columns: repeat(2, 1fr); }
            .form-grid .span-2 { grid-column: span 2; }
            .form-grid .span-4 { grid-column: span 2; }
        }
        @media (max-width: 560px) {
            .form-grid { grid-template-columns: 1fr; }
            .form-grid .span-2, .form-grid .span-4 { grid-column: span 1; }
            .main-wrap { padding: 24px 16px 60px; }
            .top-header { flex-direction: column; gap: 10px; padding: 14px; text-align: center; }
        }

        /* ─── FORM FIELDS ─── */
        .field-group { display: flex; flex-direction: column; gap: 6px; }
        .field-group label {
            font-size: 11.5px; font-weight: 600;
            color: var(--navy); letter-spacing: 0.04em; text-transform: uppercase;
        }
        .field-group label .req { color: var(--red); margin-left: 2px; }
        .field-group input,
        .field-group select,
        .field-group textarea {
            width: 100%; padding: 9px 12px;
            font-size: 14px; font-family: 'Source Sans 3', sans-serif;
            color: var(--text); background: var(--bg);
            border: 1px solid var(--border); border-radius: 3px;
            outline: none; transition: border-color 0.2s, background 0.2s;
            -webkit-appearance: none;
        }
        .field-group select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%235c5c5c' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 12px center;
            padding-right: 32px; cursor: pointer;
        }
        .field-group input:focus,
        .field-group select:focus,
        .field-group textarea:focus {
            border-color: var(--navy); background: var(--white);
            box-shadow: 0 0 0 3px rgba(13,34,64,0.08);
        }
        .field-group textarea { resize: vertical; min-height: 90px; }
        .field-hint { font-size: 11px; color: var(--muted); margin-top: 2px; }

        /* readonly field */
        .field-readonly {
            background: var(--bg2) !important;
            color: var(--navy) !important;
            font-weight: 700 !important;
            letter-spacing: 0.06em !important;
            cursor: default !important;
            border-color: var(--border) !important;
        }

        /* ─── FILE UPLOAD ─── */
        .file-drop-area {
            border: 2px dashed var(--border); border-radius: 4px;
            background: var(--bg); padding: 28px; text-align: center;
            cursor: pointer; transition: border-color 0.2s, background 0.2s;
            position: relative;
        }
        .file-drop-area:hover, .file-drop-area.dragover { border-color: var(--navy); background: #eef0f5; }
        .file-drop-area input[type="file"] {
            position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
        }
        .file-drop-icon { font-size: 28px; margin-bottom: 8px; }
        .file-drop-text { font-size: 14px; font-weight: 600; color: var(--navy); }
        .file-drop-sub { font-size: 12px; color: var(--muted); margin-top: 4px; }
        .file-list { margin-top: 12px; text-align: left; display: flex; flex-direction: column; gap: 6px; }
        .file-item {
            display: flex; align-items: center; gap: 8px; font-size: 12px;
            background: var(--bg2); border: 1px solid var(--border);
            border-radius: 3px; padding: 6px 10px; color: var(--navy);
        }

        /* ─── FORM ACTIONS ─── */
        .form-actions {
            background: var(--bg2); border-top: 1px solid var(--border);
            padding: 20px 32px; display: flex;
            align-items: center; justify-content: space-between; gap: 12px;
        }
        .action-note { font-size: 12px; color: var(--muted); display: flex; align-items: center; gap: 6px; }
        .btn-reset {
            padding: 10px 24px; background: var(--white); border: 1px solid var(--border);
            border-radius: 3px; color: var(--muted); font-size: 13px; font-weight: 600;
            letter-spacing: 0.04em; cursor: pointer; transition: all 0.2s;
            font-family: 'Source Sans 3', sans-serif;
        }
        .btn-reset:hover { border-color: var(--red); color: var(--red); }
        .btn-submit {
            padding: 11px 36px; background: var(--navy); border: none; border-radius: 3px;
            color: #fff; font-size: 13px; font-weight: 700; letter-spacing: 0.08em;
            text-transform: uppercase; cursor: pointer; transition: background 0.2s;
            font-family: 'Source Sans 3', sans-serif; display: flex; align-items: center; gap: 8px;
        }
        .btn-submit:hover { background: var(--gold); color: var(--navy); }

        /* ─── NOTICE ─── */
        .notice {
            margin-top: 24px; background: #fffbeb;
            border: 1px solid #e6d28a; border-left: 4px solid var(--gold);
            border-radius: 4px; padding: 13px 18px;
            display: flex; align-items: flex-start; gap: 10px;
            font-size: 12.5px; color: #6b5a1e; line-height: 1.6;
        }

        /* ─── RECORDS TABLE SECTION ─── */
        .records-section { margin-top: 48px; }
        .records-title-bar {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 20px; padding-bottom: 16px; border-bottom: 2px solid var(--border);
        }
        .records-title-bar h3 {
            font-family: 'Source Serif 4', serif;
            font-size: 20px; font-weight: 700; color: var(--navy);
        }
        .records-title-bar h3 small {
            display: block; font-family: 'Source Sans 3', sans-serif;
            font-size: 12px; font-weight: 400; color: var(--muted); margin-top: 3px;
        }
        .records-controls { display: flex; align-items: center; gap: 10px; }
        .search-input-wrap { position: relative; }
        .search-input-wrap svg {
            position: absolute; left: 10px; top: 50%; transform: translateY(-50%);
            color: var(--muted); pointer-events: none;
        }
        .search-input-wrap input {
            padding: 8px 12px 8px 32px; font-size: 13px;
            font-family: 'Source Sans 3', sans-serif; background: var(--white);
            border: 1px solid var(--border); border-radius: 3px; outline: none;
            color: var(--text); width: 220px; transition: border-color 0.2s, box-shadow 0.2s;
        }
        .search-input-wrap input:focus { border-color: var(--navy); box-shadow: 0 0 0 3px rgba(13,34,64,0.08); }
        .records-count-badge {
            background: var(--navy); color: #fff; font-size: 11px; font-weight: 700;
            letter-spacing: 0.08em; padding: 5px 12px; border-radius: 20px;
        }

        /* ─── TABLE ─── */
        .table-card {
            background: var(--white); border: 1px solid var(--border);
            border-top: 4px solid var(--gold); border-radius: 4px; overflow: hidden;
        }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        thead { background: var(--navy); }
        thead th {
            padding: 13px 14px; text-align: left; color: #a8bcd4;
            font-size: 10.5px; font-weight: 700; letter-spacing: 0.12em;
            text-transform: uppercase; white-space: nowrap;
            border-right: 1px solid rgba(255,255,255,0.06);
        }
        thead th:last-child { border-right: none; text-align: center; }
        tbody tr { border-bottom: 1px solid var(--border); transition: background 0.15s; }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: #f8f5ef; }
        tbody td { padding: 12px 14px; color: var(--text); vertical-align: middle; }
        tbody td:last-child { text-align: center; }
        .td-case-id {
            font-weight: 700; color: var(--navy);
            font-family: 'Source Serif 4', serif; font-size: 13px; white-space: nowrap;
        }
        .td-title { max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

        .badge {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 3px 10px; border-radius: 20px; font-size: 11px;
            font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; white-space: nowrap;
        }
        .badge-criminal { background: #fce8e8; color: #7a1515; border: 1px solid #f5c6c6; }
        .badge-civil    { background: #e8f0fc; color: #1a3a7a; border: 1px solid #c6d8f5; }
        .badge-cyber    { background: #e8f5ec; color: #1a5c36; border: 1px solid #c6e8ce; }

        .action-btns { display: flex; align-items: center; justify-content: center; gap: 6px; }
        .icon-btn {
            width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;
            border-radius: 4px; border: 1px solid var(--border); background: var(--bg);
            cursor: pointer; transition: all 0.18s; color: var(--muted);
        }
        .icon-btn:hover.view-btn { background: #e8f0fc; border-color: #b0c8f0; color: #1a3a7a; }
        .icon-btn:hover.edit-btn { background: #fffbe8; border-color: #e6d28a; color: #7a5a10; }
        .icon-btn:hover.del-btn  { background: #fce8e8; border-color: #f5c6c6; color: var(--red); }

        .table-empty { text-align: center; padding: 52px 20px; color: var(--muted); }
        .table-empty .empty-icon { font-size: 36px; margin-bottom: 10px; }
        .table-empty p { font-size: 14px; }

        .table-footer {
            background: var(--bg2); border-top: 1px solid var(--border);
            padding: 12px 20px; display: flex; align-items: center;
            justify-content: space-between; font-size: 12px; color: var(--muted);
        }
        .pagination { display: flex; align-items: center; gap: 4px; }
        .page-btn {
            width: 28px; height: 28px; display: flex; align-items: center; justify-content: center;
            border: 1px solid var(--border); border-radius: 3px; background: var(--white);
            font-size: 12px; font-weight: 600; color: var(--navy);
            cursor: pointer; transition: all 0.15s; font-family: 'Source Sans 3', sans-serif;
        }
        .page-btn:hover, .page-btn.active { background: var(--navy); color: #fff; border-color: var(--navy); }
        .page-btn:disabled { opacity: 0.4; cursor: not-allowed; }

        /* ─── MODALS ─── */
        .modal-overlay {
            position: fixed; inset: 0;
            background: rgba(13,34,64,0.55); backdrop-filter: blur(3px);
            z-index: 1000; display: flex; align-items: center; justify-content: center;
            padding: 24px; opacity: 0; pointer-events: none; transition: opacity 0.25s;
        }
        .modal-overlay.open { opacity: 1; pointer-events: all; }
        .modal {
            background: var(--white); border: 1px solid var(--border);
            border-top: 4px solid var(--navy); border-radius: 6px;
            width: 100%; max-width: 680px; max-height: 90vh; overflow-y: auto;
            transform: translateY(-16px); transition: transform 0.25s;
        }
        .modal-overlay.open .modal { transform: translateY(0); }
        .modal-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 20px 28px; border-bottom: 1px solid var(--border); background: var(--bg2);
        }
        .modal-header h4 {
            font-family: 'Source Serif 4', serif; font-size: 17px; font-weight: 700; color: var(--navy);
        }
        .modal-header h4 small {
            display: block; font-family: 'Source Sans 3', sans-serif;
            font-size: 11px; font-weight: 400; color: var(--muted); margin-top: 2px;
        }
        .modal-close {
            width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;
            background: transparent; border: 1px solid var(--border); border-radius: 4px;
            cursor: pointer; color: var(--muted); font-size: 18px; line-height: 1; transition: all 0.15s;
        }
        .modal-close:hover { background: var(--red); color: #fff; border-color: var(--red); }
        .modal-body { padding: 24px 28px; }
        .modal-footer {
            padding: 16px 28px; border-top: 1px solid var(--border);
            background: var(--bg2); display: flex; justify-content: flex-end; gap: 10px;
        }
        .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px 20px; }
        .detail-group { display: flex; flex-direction: column; gap: 4px; }
        .detail-group.span-2 { grid-column: span 2; }
        .detail-label {
            font-size: 10.5px; font-weight: 700; letter-spacing: 0.1em;
            text-transform: uppercase; color: var(--muted);
        }
        .detail-value {
            font-size: 14px; color: var(--text); font-weight: 500;
            padding: 8px 10px; background: var(--bg); border: 1px solid var(--border);
            border-radius: 3px; min-height: 36px; display: flex; align-items: center;
        }
        .detail-value.description-val { align-items: flex-start; min-height: 80px; line-height: 1.6; }

        /* ─── FOOTER ─── */
        .gov-footer {
            background: var(--navy); border-top: 3px solid var(--gold);
            padding: 18px 32px; display: flex; align-items: center;
            justify-content: space-between; font-size: 11px; color: #6a88a4; letter-spacing: 0.04em;
        }
        .gov-footer strong { color: #a8bcd4; }
    </style>
</head>
<body>

<div class="gov-banner">
    <span>🔒 <strong>OFFICIAL GOVERNMENT PORTAL</strong> — Authorized personnel only. All activity is monitored and logged.</span>
</div>

<header class="top-header">
    <div class="header-left">
        <div class="seal">⚖️</div>
        <div class="header-titles">
            <h1>Blockchain Evidence Management System</h1>
            <p>Ministry of Justice &nbsp;|&nbsp; Digital Forensics Division</p>
        </div>
    </div>
    <a href="dashboard.php" class="back-btn">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="15 18 9 12 15 6"/></svg>
        Back to Dashboard
    </a>
</header>

<div class="breadcrumb-bar">
    <a href="dashboard.php" style="color:var(--muted);text-decoration:none;">Home</a>
    &rsaquo; <span>Register New Case</span>
</div>

<main class="main-wrap">

    <div class="page-title-bar">
        <h2>
            Register New Case
            <small>Complete all required fields. This record will be immutably anchored to the blockchain upon submission.</small>
        </h2>
        <div class="form-ref">
            <!-- <strong>FORM REF: BEMS <?php echo $year; ?></strong><br> -->
            Fields marked <span style="color:var(--red);">*</span> are mandatory
        </div>
    </div>

    <div class="form-card">
        <form id="caseForm" method="POST" enctype="multipart/form-data" onsubmit="return handleFormSubmit(event)">

            <!-- Section 1: Case Information -->
            <div class="form-section">
                <div class="section-label">Case Information</div>
                <div class="form-grid">

                    <div class="field-group">
                        <label>Case ID <span class="req">*</span></label>
                        <!-- AUTO-FILLED & READONLY — value comes from PHP -->
                        <input type="text" name="CaseId" id="txtCaseId"
                               value="<?php echo htmlspecialchars($AutoCaseID); ?>"
                               class="field-readonly" readonly>
                        <span class="field-hint">Auto-generated — read only</span>
                    </div>

                    <div class="field-group span-2">
                        <label>Case Title <span class="req">*</span></label>
                        <input type="text" name="CaseTitle" id="txtCaseTitle" placeholder="Enter full case title" required>
                    </div>

                    <div class="field-group">
                        <label>Case Type <span class="req">*</span></label>
                        <select name="CaseType" id="lstCaseType" required>
                            <option value="">— Select Type —</option>
                            <option value="Criminal">Criminal</option>
                            <option value="Civil">Civil</option>
                            <option value="CyberCrime">Cyber Crime</option>
                        </select>
                    </div>

                    <div class="field-group">
                        <label>Date of Incident <span class="req">*</span></label>
                        <input type="date" name="DateOfIncident" id="txtDateOfIncident" required>
                    </div>

                    <div class="field-group span-2">
                        <label>Location of Incident <span class="req">*</span></label>
                        <input type="text" name="LocationOfIncident" id="txtLocationOfIncident" placeholder="Street, City, State" required>
                    </div>

                    <div class="field-group span-4">
                        <label>Case Description <span class="req">*</span></label>
                        <textarea name="CaseDescription" id="txtCaseDescription" rows="4"
                                  placeholder="Provide a detailed description of the incident and relevant facts..."
                                  required minlength="10"></textarea>
                    </div>

                </div>
            </div>

            <!-- Section 2: Complainant Details -->
            <div class="form-section">
                <div class="section-label">Complainant Details</div>
                <div class="form-grid">

                    <div class="field-group span-2">
                        <label>Full Name <span class="req">*</span></label>
                        <input type="text" name="ComplainantName" id="txtComplainantName" placeholder="As per official ID" required>
                    </div>

                    <div class="field-group">
                        <label>Phone Number <span class="req">*</span></label>
                        <input type="text" name="ComplainantPhone" id="txtComplainantPhone" placeholder="10-digit number"
                               pattern="[0-9]{10}" maxlength="10" oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
                        <span class="field-hint">Digits only, no spaces or dashes</span>
                    </div>

                    <div class="field-group">
                        <label>Email Address <span class="req">*</span></label>
                        <input type="email" name="ComplainantEmail" id="txtComplainantEmail" placeholder="official@domain.gov.in" required>
                    </div>

                </div>
            </div>

            <!-- Section 3: Evidence Documents -->
            <div class="form-section">
                <div class="section-label">Evidence Documents</div>
                <div class="file-drop-area" id="dropArea">
                    <input type="file" name="DocumentPath[]" id="txtDocumentPath" multiple
                           accept=".pdf,.jpg,.jpeg,.png" onchange="updateFileList(this.files)">
                    <div class="file-drop-icon">📂</div>
                    <div class="file-drop-text">Click to select files or drag &amp; drop here</div>
                    <div class="file-drop-sub">Accepted formats: PDF, JPG, PNG &nbsp;|&nbsp; Max 5 MB per file</div>
                </div>
                <div class="file-list" id="fileList"></div>
            </div>

            <!-- Actions -->
            <div class="form-actions">
                <div class="action-note">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    Submission will be cryptographically signed and logged to the blockchain.
                </div>
                <div style="display:flex;gap:12px;">
                    <button type="button" class="btn-reset" onclick="resetForm()">Clear Form</button>
                    <button type="submit" id="btnSave" name="register" class="btn-submit">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="20 6 9 17 4 12"/></svg>
                        <span id="btnSaveText">Submit &amp; Register Case</span>
                    </button>
                </div>
            </div>

        </form>
    </div>

    <div class="notice">
        <span style="font-size:17px;flex-shrink:0;">⚠️</span>
        <div>
            <strong>Official Use Only.</strong> Filing a false or misleading case record is a punishable offence under the Evidence Act.
            Ensure all information provided is accurate and verifiable.
        </div>
    </div>

    <!-- ═══ RECORDS TABLE ═══ -->
    <div class="records-section">
        <div class="records-title-bar">
            <h3>
                Registered Cases
                <small>All immutably recorded case entries.</small>
            </h3>
            <div class="records-controls">
                <div class="search-input-wrap">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <input type="text" id="searchInput" placeholder="Search cases…" oninput="filterTable()">
                </div>
                <span class="records-count-badge" id="recordCount">0 Records</span>
            </div>
        </div>

        <div class="table-card">
            <div class="table-wrap">
                <table id="casesTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Case ID</th>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Date of Incident</th>
                            <th>Location</th>
                            <th>Complainant</th>
                            <th>Contact</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody"></tbody>
                </table>
            </div>
            <div class="table-footer">
                <span id="paginationInfo">Showing 0–0 of 0 records</span>
                <div class="pagination" id="paginationControls"></div>
            </div>
        </div>
    </div>

</main>

<footer class="gov-footer">
    <span>© <?php echo $year; ?> <strong>Ministry of Justice — Digital Forensics Division</strong>. All rights reserved.</span>
</footer>


<!-- ═══ VIEW MODAL ═══ -->
<div class="modal-overlay" id="viewModal">
    <div class="modal">
        <div class="modal-header">
            <h4>Case Details <small id="viewModalSubtitle">—</small></h4>
            <button class="modal-close" onclick="closeModal('viewModal')">&#x2715;</button>
        </div>
        <div class="modal-body">
            <div class="detail-grid">
                <div class="detail-group">
                    <div class="detail-label">Case ID</div>
                    <div class="detail-value" id="v_caseId">—</div>
                </div>
                <div class="detail-group">
                    <div class="detail-label">Case Type</div>
                    <div class="detail-value" id="v_caseType">—</div>
                </div>
                <div class="detail-group span-2">
                    <div class="detail-label">Case Title</div>
                    <div class="detail-value" id="v_caseTitle">—</div>
                </div>
                <div class="detail-group">
                    <div class="detail-label">Date of Incident</div>
                    <div class="detail-value" id="v_date">—</div>
                </div>
                <div class="detail-group">
                    <div class="detail-label">Location</div>
                    <div class="detail-value" id="v_location">—</div>
                </div>
                <div class="detail-group span-2">
                    <div class="detail-label">Case Description</div>
                    <div class="detail-value description-val" id="v_description">—</div>
                </div>
                <div class="detail-group">
                    <div class="detail-label">Complainant Name</div>
                    <div class="detail-value" id="v_complainantName">—</div>
                </div>
                <div class="detail-group">
                    <div class="detail-label">Phone</div>
                    <div class="detail-value" id="v_phone">—</div>
                </div>
                <div class="detail-group span-2">
                    <div class="detail-label">Email</div>
                    <div class="detail-value" id="v_email">—</div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-reset" onclick="closeModal('viewModal')">Close</button>
        </div>
    </div>
</div>


<!-- ═══ EDIT MODAL ═══ -->
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <div class="modal-header">
            <h4>Edit Case Record <small id="editModalSubtitle">—</small></h4>
            <button class="modal-close" onclick="closeModal('editModal')">&#x2715;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="edit_rowIndex">
            <input type="hidden" id="edit_caseUId">
            <div class="detail-grid">
                <div class="detail-group">
                    <div class="detail-label">Case ID</div>
                    <div class="detail-value" id="edit_caseId_display" style="font-weight:700;color:var(--navy);letter-spacing:0.06em;">—</div>
                </div>
                <div class="detail-group">
                    <div class="detail-label">Case Type <span style="color:var(--red)">*</span></div>
                    <select class="detail-value" id="edit_caseType"
                            style="font-size:14px;cursor:pointer;
                                   background-image:url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%235c5c5c' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E\");
                                   background-repeat:no-repeat;background-position:right 10px center;padding-right:30px;">
                        <option value="Criminal">Criminal</option>
                        <option value="Civil">Civil</option>
                        <option value="CyberCrime">Cyber Crime</option>
                    </select>
                </div>
                <div class="detail-group span-2">
                    <div class="detail-label">Case Title <span style="color:var(--red)">*</span></div>
                    <input class="detail-value" id="edit_caseTitle" style="font-size:14px;" required>
                </div>
                <div class="detail-group">
                    <div class="detail-label">Date of Incident <span style="color:var(--red)">*</span></div>
                    <input class="detail-value" type="date" id="edit_date" style="font-size:14px;" required>
                </div>
                <div class="detail-group">
                    <div class="detail-label">Location <span style="color:var(--red)">*</span></div>
                    <input class="detail-value" id="edit_location" style="font-size:14px;" required>
                </div>
                <div class="detail-group span-2">
                    <div class="detail-label">Case Description <span style="color:var(--red)">*</span></div>
                    <textarea class="detail-value description-val" id="edit_description"
                              style="font-size:14px;resize:vertical;font-family:'Source Sans 3',sans-serif;" required></textarea>
                </div>
                <div class="detail-group">
                    <div class="detail-label">Complainant Name <span style="color:var(--red)">*</span></div>
                    <input class="detail-value" id="edit_complainantName" style="font-size:14px;" required>
                </div>
                <div class="detail-group">
                    <div class="detail-label">Phone <span style="color:var(--red)">*</span></div>
                    <input class="detail-value" id="edit_phone" maxlength="10" pattern="[0-9]{10}" style="font-size:14px;" required>
                </div>
                <div class="detail-group span-2">
                    <div class="detail-label">Email <span style="color:var(--red)">*</span></div>
                    <input class="detail-value" type="email" id="edit_email" style="font-size:14px;" required>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-reset" onclick="closeModal('editModal')">Cancel</button>
            <button class="btn-submit" onclick="saveEdit()">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="20 6 9 17 4 12"/></svg>
                Save Changes
            </button>
        </div>
    </div>
</div>


<!-- ═══ DELETE MODAL ═══ -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal" style="max-width:440px;">
        <div class="modal-header" style="border-top-color:var(--red);">
            <h4>Confirm Deletion</h4>
            <button class="modal-close" onclick="closeModal('deleteModal')">&#x2715;</button>
        </div>
        <div class="modal-body" style="text-align:center;padding:32px 28px;">
            <div style="font-size:44px;margin-bottom:14px;">🗑️</div>
            <p style="font-size:15px;font-weight:600;color:var(--navy);margin-bottom:8px;">Delete this case record?</p>
            <p style="font-size:13px;color:var(--muted);line-height:1.6;">
                Case <strong id="deleteCaseIdLabel" style="color:var(--navy);">—</strong> will be permanently removed.
                This action cannot be undone.
            </p>
        </div>
        <div class="modal-footer">
            <button class="btn-reset" onclick="closeModal('deleteModal')">Cancel</button>
            <button class="btn-submit" onclick="confirmDelete()" style="background:var(--red);">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                Yes, Delete Record
            </button>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
/* ══════════════════════════════════════════════════════════════
   DATA — injected from PHP using EXACT DB column names
   DB columns: CaseUId, CaseID, CaseTitle, CaseType,
               DateOfIncident, LocationOfIncident, CaseDescription,
               ComplainantName, ComplainantPhone, ComplainantEmail
══════════════════════════════════════════════════════════════ */
let cases = <?php
echo json_encode(
    array_map(function($row) {
        $r = array_change_key_case($row, CASE_LOWER);
        return [
            'caseUId'            => $r['caseuid']            ?? '',
            'caseId'             => $r['caseid']             ?? '',
            'caseTitle'          => $r['casetitle']          ?? '',
            'caseType'           => $r['casetype']           ?? '',
            'dateOfIncident'     => $r['dateofincident']     ?? '',
            'locationOfIncident' => $r['locationofincident'] ?? '',
            'caseDescription'    => $r['casedescription']    ?? '',
            'complainantName'    => $r['complainantname']    ?? '',
            'complainantPhone'   => $r['complainantphone']   ?? '',
            'complainantEmail'   => $r['complainantemail']   ?? '',
            'documentPath'       => $r['documentpath']       ?? null,
            'createdAt'          => $r['createdat']          ?? '',
        ];
    }, $cases),
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
); ?>;

let filteredCases = [...cases];
let currentPage   = 1;
const pageSize    = 10;
let deleteTargetIndex = null;

/* ══════════════════════════════════════════
   FORM SUBMIT → Flag=Save
══════════════════════════════════════════ */
function handleFormSubmit(e) {
    e.preventDefault();

    const phone = document.getElementById('txtComplainantPhone').value.trim();
    if (!/^\d{10}$/.test(phone)) {
        showAlert('warning', 'Invalid Phone Number', 'Please enter a valid 10-digit phone number.');
        return false;
    }

    const btn = document.getElementById('btnSave');
    btn.disabled = true;
    document.getElementById('btnSaveText').textContent = 'Submitting…';

    const formData = new FormData(document.getElementById('caseForm'));
    formData.append('Flag', 'Save');

    fetch('RegisterOperation.php', { method: 'POST', body: formData })
    .then(r => r.text())
    .then(data => {
        try {
            const res = JSON.parse(data);
            if (res.Status === 'success') {
                const capturedCaseId = res.CaseID || document.getElementById('txtCaseId').value.trim();
                const newCase = {
                    caseUId:            res.CaseUId  || '',
                    caseId:             capturedCaseId,
                    caseTitle:          document.getElementById('txtCaseTitle').value.trim(),
                    caseType:           document.getElementById('lstCaseType').value,
                    dateOfIncident:     document.getElementById('txtDateOfIncident').value,
                    locationOfIncident: document.getElementById('txtLocationOfIncident').value.trim(),
                    caseDescription:    document.getElementById('txtCaseDescription').value.trim(),
                    complainantName:    document.getElementById('txtComplainantName').value.trim(),
                    complainantPhone:   phone,
                    complainantEmail:   document.getElementById('txtComplainantEmail').value.trim(),
                    documentPath:       null,
                    createdAt:          new Date().toISOString()
                };
                cases.unshift(newCase);
                filteredCases = [...cases];
                currentPage = 1;
                renderTable();

                resetForm();
                // Increment Case ID for next submission
                const parts = capturedCaseId.split('-');
                if (parts.length === 3) {
                    document.getElementById('txtCaseId').value =
                        'CASE-' + parts[1] + '-' + String(parseInt(parts[2], 10) + 1).padStart(3, '0');
                }

                Swal.fire({
                    icon: 'success',
                    title: 'Case Registered Successfully!',
                    html: `<div style="text-align:left;padding:10px;">
                               <p><strong>Case ID:</strong> ${escHtml(newCase.caseId)}</p>
                           </div>`,
                    confirmButtonColor: '#0d2240',
                    confirmButtonText: 'OK',
                    timer: 4000,
                    timerProgressBar: true
                });
            } else {
                showAlert('error', 'Registration Failed', res.Message || 'Failed to register case.');
            }
        } catch(err) {
            console.error('Raw response:', data);
            showAlert('error', 'Parse Error', 'Could not read server response. Check console for details.');
        }
    })
    .catch(err => {
        console.error('Fetch error:', err);
        showAlert('error', 'Connection Error', 'Could not connect to the server. Please try again.');
    })
    .finally(() => {
        btn.disabled = false;
        document.getElementById('btnSaveText').innerHTML = 'Submit &amp; Register Case';
    });

    return false;
}

/* ══════════════════════════════════════════
   AUTO CASE ID
══════════════════════════════════════════ */
function generateNextCaseId() {
    const current = document.getElementById('txtCaseId').value;
    const parts   = current.split('-');
    if (parts.length === 3) {
        const next = 'CASE-' + parts[1] + '-' + String(parseInt(parts[2], 10) + 1).padStart(3, '0');
        document.getElementById('txtCaseId').value = next;
    }
}

function resetForm() {
    const caseIdVal = document.getElementById('txtCaseId').value;
    document.getElementById('caseForm').reset();
    document.getElementById('txtCaseId').value = caseIdVal;
    clearFiles();
}

/* ══════════════════════════════════════════
   TABLE RENDER
══════════════════════════════════════════ */
function renderTable() {
    const tbody        = document.getElementById('tableBody');
    const totalRecords = filteredCases.length;
    const totalPages   = Math.max(1, Math.ceil(totalRecords / pageSize));
    if (currentPage > totalPages) currentPage = totalPages;

    const start    = (currentPage - 1) * pageSize;
    const end      = Math.min(start + pageSize, totalRecords);
    const pageData = filteredCases.slice(start, end);

    document.getElementById('recordCount').textContent =
        `${totalRecords} Record${totalRecords !== 1 ? 's' : ''}`;
    document.getElementById('paginationInfo').textContent = totalRecords === 0
        ? 'No records found'
        : `Showing ${start + 1}–${end} of ${totalRecords} records`;

    if (totalRecords === 0) {
        tbody.innerHTML = `<tr><td colspan="9">
            <div class="table-empty">
                <div class="empty-icon">📋</div>
                <p>No case records found. Register a new case above.</p>
            </div></td></tr>`;
        renderPagination(0, 0);
        return;
    }

    tbody.innerHTML = pageData.map((c, i) => {
        const realIndex = cases.indexOf(c);
        const rowNum    = start + i + 1;
        return `
        <tr>
            <td style="color:var(--muted);font-size:12px;">${rowNum}</td>
            <td class="td-case-id">${escHtml(c.caseId)}</td>
            <td class="td-title" title="${escHtml(c.caseTitle)}">${escHtml(c.caseTitle)}</td>
            <td>${typeBadge(c.caseType)}</td>
            <td style="white-space:nowrap;">${formatDate(c.dateOfIncident)}</td>
            <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                title="${escHtml(c.locationOfIncident)}">${escHtml(c.locationOfIncident)}</td>
            <td>${escHtml(c.complainantName)}</td>
            <td style="font-size:12px;color:var(--muted);">${escHtml(c.complainantPhone)}</td>
            <td>
                <div class="action-btns">
                    <button class="icon-btn view-btn" title="View" onclick="openView(${realIndex})">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                    <button class="icon-btn edit-btn" title="Edit" onclick="openEdit(${realIndex})">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                    </button>
                    
                </div>
            </td>
        </tr>`;
    }).join('');

    renderPagination(totalPages, currentPage);
}

function typeBadge(type) {
    const map = {
        Criminal:   ['badge-criminal', '🔴 Criminal'],
        Civil:      ['badge-civil',    '🔵 Civil'],
        CyberCrime: ['badge-cyber',    '🟢 Cyber Crime']
    };
    const [cls, label] = map[type] || ['', escHtml(type)];
    return `<span class="badge ${cls}">${label}</span>`;
}

function formatDate(d) {
    if (!d) return '—';
    const parts = d.split('-');
    if (parts.length < 3) return d;
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return `${parseInt(parts[2])} ${months[parseInt(parts[1])-1]} ${parts[0]}`;
}

function escHtml(str) {
    return String(str ?? '')
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ══════════════════════════════════════════
   PAGINATION
══════════════════════════════════════════ */
function renderPagination(totalPages, current) {
    const ctrl = document.getElementById('paginationControls');
    if (totalPages <= 1) { ctrl.innerHTML = ''; return; }
    let html = `<button class="page-btn" onclick="goPage(${current-1})" ${current===1?'disabled':''}>&#8249;</button>`;
    for (let p = 1; p <= totalPages; p++) {
        html += `<button class="page-btn ${p===current?'active':''}" onclick="goPage(${p})">${p}</button>`;
    }
    html += `<button class="page-btn" onclick="goPage(${current+1})" ${current===totalPages?'disabled':''}>&#8250;</button>`;
    ctrl.innerHTML = html;
}

function goPage(p) {
    const total = Math.ceil(filteredCases.length / pageSize);
    if (p < 1 || p > total) return;
    currentPage = p;
    renderTable();
}

/* ══════════════════════════════════════════
   SEARCH
══════════════════════════════════════════ */
function filterTable() {
    const q = document.getElementById('searchInput').value.toLowerCase().trim();
    filteredCases = q
        ? cases.filter(c =>
            (c.caseId             || '').toLowerCase().includes(q) ||
            (c.caseTitle          || '').toLowerCase().includes(q) ||
            (c.caseType           || '').toLowerCase().includes(q) ||
            (c.complainantName    || '').toLowerCase().includes(q) ||
            (c.locationOfIncident || '').toLowerCase().includes(q))
        : [...cases];
    currentPage = 1;
    renderTable();
}

/* ══════════════════════════════════════════
   VIEW MODAL
══════════════════════════════════════════ */
function openView(idx) {
    const c = cases[idx];
    document.getElementById('viewModalSubtitle').textContent   = c.caseId;
    document.getElementById('v_caseId').textContent            = c.caseId;
    document.getElementById('v_caseType').textContent          = c.caseType;
    document.getElementById('v_caseTitle').textContent         = c.caseTitle;
    document.getElementById('v_date').textContent              = formatDate(c.dateOfIncident);
    document.getElementById('v_location').textContent          = c.locationOfIncident;
    document.getElementById('v_description').textContent       = c.caseDescription;
    document.getElementById('v_complainantName').textContent   = c.complainantName;
    document.getElementById('v_phone').textContent             = c.complainantPhone;
    document.getElementById('v_email').textContent             = c.complainantEmail;
    openModal('viewModal');
}

/* ══════════════════════════════════════════
   EDIT MODAL
══════════════════════════════════════════ */
function openEdit(idx) {
    const c = cases[idx];
    document.getElementById('editModalSubtitle').textContent      = `Editing: ${c.caseId}`;
    document.getElementById('edit_rowIndex').value                = idx;
    document.getElementById('edit_caseUId').value                 = c.caseUId;
    document.getElementById('edit_caseId_display').textContent    = c.caseId;
    document.getElementById('edit_caseType').value                = c.caseType;
    document.getElementById('edit_caseTitle').value               = c.caseTitle;
    document.getElementById('edit_date').value                    = c.dateOfIncident;
    document.getElementById('edit_location').value                = c.locationOfIncident;
    document.getElementById('edit_description').value             = c.caseDescription;
    document.getElementById('edit_complainantName').value         = c.complainantName;
    document.getElementById('edit_phone').value                   = c.complainantPhone;
    document.getElementById('edit_email').value                   = c.complainantEmail;
    openModal('editModal');
}

function saveEdit() {
    const idx     = parseInt(document.getElementById('edit_rowIndex').value);
    const caseUId = document.getElementById('edit_caseUId').value;
    const phone   = document.getElementById('edit_phone').value.trim();

    if (!phone || !/^\d{10}$/.test(phone)) {
        showAlert('warning', 'Invalid Phone', 'Phone must be exactly 10 digits.');
        return;
    }

    const updated = {
        caseUId:            caseUId,
        caseId:             cases[idx].caseId,
        caseTitle:          document.getElementById('edit_caseTitle').value.trim(),
        caseType:           document.getElementById('edit_caseType').value,
        dateOfIncident:     document.getElementById('edit_date').value,
        locationOfIncident: document.getElementById('edit_location').value.trim(),
        caseDescription:    document.getElementById('edit_description').value.trim(),
        complainantName:    document.getElementById('edit_complainantName').value.trim(),
        complainantPhone:   phone,
        complainantEmail:   document.getElementById('edit_email').value.trim(),
        documentPath:       cases[idx].documentPath,
        createdAt:          cases[idx].createdAt
    };

    const fd = new FormData();
    fd.append('Flag',                'UpdateDetails');
    fd.append('CaseUId',             caseUId);
    fd.append('CaseId',              updated.caseId);
    fd.append('CaseTitle',           updated.caseTitle);
    fd.append('CaseType',            updated.caseType);
    fd.append('DateOfIncident',      updated.dateOfIncident);
    fd.append('LocationOfIncident',  updated.locationOfIncident);
    fd.append('CaseDescription',     updated.caseDescription);
    fd.append('ComplainantName',     updated.complainantName);
    fd.append('ComplainantPhone',    updated.complainantPhone);
    fd.append('ComplainantEmail',    updated.complainantEmail);

    fetch('RegisterOperation.php', { method: 'POST', body: fd })
    .then(r => r.text())
    .then(data => {
        try {
            const res = JSON.parse(data);
            if (res.Status === 'success' || res.Status === 'Update') {
                cases[idx] = updated;
                filteredCases = [...cases];
                filterTable();
                closeModal('editModal');
                showAlert('success', 'Record Updated', `Case ${updated.caseId} updated successfully.`);
            } else {
                showAlert('error', 'Update Failed', res.Message || 'Could not update record.');
            }
        } catch(e) {
            showAlert('error', 'Parse Error', 'Could not read server response.');
        }
    })
    .catch(() => showAlert('error', 'Connection Error', 'Could not connect to the server.'));
}

/* ══════════════════════════════════════════
   DELETE MODAL
══════════════════════════════════════════ */
// function openDelete(idx) {
//     deleteTargetIndex = idx;
//     document.getElementById('deleteCaseIdLabel').textContent = cases[idx].caseId;
//     openModal('deleteModal');
// }

// function confirmDelete() {
//     if (deleteTargetIndex === null) return;
//     const c  = cases[deleteTargetIndex];

//     const fd = new FormData();
//     fd.append('Flag',    'DeleteDetails');
//     fd.append('CaseUId', c.caseUId);

//     fetch('RegisterOperation.php', { method: 'POST', body: fd })
//     .then(r => r.text())
//     .then(data => {
//         try {
//             const res = JSON.parse(data);
//             if (res.Status === 'Delete') {
//                 cases.splice(deleteTargetIndex, 1);
//                 filteredCases = [...cases];
//                 deleteTargetIndex = null;
//                 currentPage = 1;
//                 renderTable();
//                 closeModal('deleteModal');
//                 showAlert('success', 'Record Deleted', `Case ${c.caseId} has been removed.`);
//             } else {
//                 showAlert('error', 'Delete Failed', res.Message || 'Could not delete record.');
//             }
//         } catch(e) {
//             showAlert('error', 'Parse Error', 'Could not read server response.');
//         }
//     })
//     .catch(() => showAlert('error', 'Connection Error', 'Could not connect to the server.'));
// }

/* ══════════════════════════════════════════
   MODAL UTILS
══════════════════════════════════════════ */
function openModal(id)  { document.getElementById(id).classList.add('open');    document.body.style.overflow = 'hidden'; }
function closeModal(id) { document.getElementById(id).classList.remove('open'); document.body.style.overflow = ''; }

document.querySelectorAll('.modal-overlay').forEach(o => {
    o.addEventListener('click', e => { if (e.target === o) closeModal(o.id); });
});
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.open').forEach(m => closeModal(m.id));
});

/* ══════════════════════════════════════════
   SWEETALERT2 HELPER
══════════════════════════════════════════ */
function showAlert(type, title, message, callback) {
    Swal.fire({
        icon: type, title, text: message,
        confirmButtonColor: '#0d2240',
        confirmButtonText: 'OK',
        ...(type === 'success' ? { timer: 3000, timerProgressBar: true } : {})
    }).then(() => { if (typeof callback === 'function') callback(); });
}

/* ══════════════════════════════════════════
   FILE UPLOAD
══════════════════════════════════════════ */
function updateFileList(files) {
    const list  = document.getElementById('fileList');
    list.innerHTML = '';
    const icons = { pdf:'📄', jpg:'🖼️', jpeg:'🖼️', png:'🖼️' };
    Array.from(files).forEach(file => {
        const ext  = file.name.split('.').pop().toLowerCase();
        const size = (file.size / 1024).toFixed(1);
        const item = document.createElement('div');
        item.className = 'file-item';
        item.innerHTML = `<span>${icons[ext]||'📎'}</span>
                          <span style="flex:1;">${file.name}</span>
                          <span style="color:var(--muted);font-size:11px;">${size} KB</span>`;
        list.appendChild(item);
    });
}

function clearFiles() { document.getElementById('fileList').innerHTML = ''; }

const dropArea = document.getElementById('dropArea');
['dragenter','dragover'].forEach(ev => dropArea.addEventListener(ev, () => dropArea.classList.add('dragover')));
['dragleave','drop'].forEach(ev => dropArea.addEventListener(ev, () => dropArea.classList.remove('dragover')));

/* ══════════════════════════════════════════
   INIT
══════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', () => {
    filteredCases = [...cases];
    renderTable();
});
</script>

</body>
</html>