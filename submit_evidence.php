<?php
session_start();
include_once("db.php");
require_once 'Blockchain.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$evidenceList = [];
$sql    = "SELECT * FROM tblevidence WHERE RecordStatus='Active' ORDER BY CreatedAt DESC";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $evidenceList[] = $row;
    }
}

$year = date("Y");
$prefix      = "EVID-$year-";
$countResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM tblevidence WHERE EvidenceID LIKE '$prefix%'");
$countRow    = mysqli_fetch_assoc($countResult);
$AutoEvidID  = $prefix . str_pad(intval($countRow['total']) + 1, 3, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BEMS — Submit Evidence</title>
    <link href="https://fonts.googleapis.com/css2?family=Source+Serif+4:wght@400;600;700&family=Source+Sans+3:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --navy:#0d2240; --navy2:#163356; --gold:#c8972a; --gold-lt:#e8b84b;
            --bg:#f4f1eb; --bg2:#ece8df; --white:#ffffff; --text:#1a1a1a;
            --muted:#5c5c5c; --border:#d0c9bc; --red:#9b1c1c;
            --purple:#4a1a7a; --purple-lt:#7a4aaa;
        }
        body { background:var(--bg); color:var(--text); font-family:'Source Sans 3',sans-serif; min-height:100vh; }
        .gov-banner { background:var(--navy); color:#a8bcd4; font-size:11.5px; letter-spacing:.04em; padding:6px 32px; display:flex; align-items:center; justify-content:space-between; }
        .gov-banner strong { color:#fff; }
        .top-header { background:var(--navy2); border-bottom:4px solid var(--gold); padding:0 32px; display:flex; align-items:center; justify-content:space-between; min-height:76px; }
        .header-left { display:flex; align-items:center; gap:16px; }
        .seal { width:52px; height:52px; border-radius:50%; border:2px solid var(--gold); background:var(--navy); display:flex; align-items:center; justify-content:center; font-size:22px; flex-shrink:0; }
        .header-titles h1 { font-family:'Source Serif 4',serif; font-size:17px; font-weight:700; color:#fff; line-height:1.2; }
        .header-titles p  { font-size:10.5px; color:#a8bcd4; letter-spacing:.08em; text-transform:uppercase; margin-top:2px; }
        .back-btn { display:flex; align-items:center; gap:7px; padding:8px 18px; background:transparent; border:1px solid rgba(200,151,42,.45); border-radius:4px; color:var(--gold-lt); font-size:12px; font-weight:600; letter-spacing:.06em; text-transform:uppercase; text-decoration:none; transition:all .2s; }
        .back-btn:hover { background:var(--gold); border-color:var(--gold); color:var(--navy); }
        .breadcrumb-bar { background:var(--bg2); border-bottom:1px solid var(--border); padding:8px 32px; font-size:12px; color:var(--muted); }
        .breadcrumb-bar a { color:var(--muted); text-decoration:none; }
        .breadcrumb-bar span { color:var(--navy); font-weight:600; }
        .main-wrap { max-width:1100px; margin:0 auto; padding:40px 32px 80px; }
        .page-title-bar { display:flex; align-items:flex-end; justify-content:space-between; margin-bottom:28px; padding-bottom:18px; border-bottom:2px solid var(--border); }
        .page-title-bar h2 { font-family:'Source Serif 4',serif; font-size:24px; font-weight:700; color:var(--navy); }
        .page-title-bar h2 small { display:block; font-family:'Source Sans 3',sans-serif; font-size:13px; font-weight:400; color:var(--muted); margin-top:4px; }
        .form-ref { font-size:11px; color:var(--muted); letter-spacing:.1em; text-transform:uppercase; text-align:right; line-height:1.7; }
        .form-ref strong { color:var(--navy); }
        .form-card { background:var(--white); border:1px solid var(--border); border-top:4px solid var(--navy); border-radius:4px; overflow:hidden; }
        .form-section { padding:28px 32px; border-bottom:1px solid var(--border); }
        .form-section:last-child { border-bottom:none; }
        .section-label { display:flex; align-items:center; gap:10px; font-size:10.5px; font-weight:700; letter-spacing:.15em; text-transform:uppercase; color:var(--navy); margin-bottom:22px; }
        .section-label::after { content:''; flex:1; height:1px; background:var(--border); }
        .form-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:18px 20px; }
        .form-grid .span-2 { grid-column:span 2; }
        .form-grid .span-4 { grid-column:span 4; }
        @media(max-width:860px){.form-grid{grid-template-columns:repeat(2,1fr);}.form-grid .span-4{grid-column:span 2;}}
        @media(max-width:560px){.form-grid{grid-template-columns:1fr;}.form-grid .span-2,.form-grid .span-4{grid-column:span 1;}.main-wrap{padding:24px 16px 60px;}.top-header{flex-direction:column;gap:10px;padding:14px;}}
        .field-group { display:flex; flex-direction:column; gap:6px; }
        .field-group label { font-size:11.5px; font-weight:600; color:var(--navy); letter-spacing:.04em; text-transform:uppercase; }
        .field-group label .req { color:var(--red); margin-left:2px; }
        .field-group input,.field-group select,.field-group textarea { width:100%; padding:9px 12px; font-size:14px; font-family:'Source Sans 3',sans-serif; color:var(--text); background:var(--bg); border:1px solid var(--border); border-radius:3px; outline:none; transition:border-color .2s,background .2s; -webkit-appearance:none; }
        .field-group select { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%235c5c5c' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 12px center; padding-right:32px; cursor:pointer; }
        .field-group input:focus,.field-group select:focus,.field-group textarea:focus { border-color:var(--navy); background:var(--white); box-shadow:0 0 0 3px rgba(13,34,64,.08); }
        .field-group textarea { resize:vertical; min-height:90px; }
        .field-hint { font-size:11px; color:var(--muted); margin-top:2px; }
        .field-readonly { background:var(--bg2)!important; color:var(--navy)!important; font-weight:700!important; letter-spacing:.06em!important; cursor:default!important; }
        .file-drop-area { border:2px dashed var(--border); border-radius:4px; background:var(--bg); padding:28px; text-align:center; cursor:pointer; transition:border-color .2s,background .2s; position:relative; }
        .file-drop-area:hover,.file-drop-area.dragover { border-color:var(--navy); background:#eef0f5; }
        .file-drop-area input[type="file"] { position:absolute; inset:0; opacity:0; cursor:pointer; width:100%; height:100%; }
        .file-drop-icon { font-size:28px; margin-bottom:8px; }
        .file-drop-text { font-size:14px; font-weight:600; color:var(--navy); }
        .file-drop-sub  { font-size:12px; color:var(--muted); margin-top:4px; }
        .file-list { margin-top:12px; display:flex; flex-direction:column; gap:6px; text-align:left; }
        .file-item { display:flex; align-items:center; gap:8px; font-size:12px; background:var(--bg2); border:1px solid var(--border); border-radius:3px; padding:6px 10px; color:var(--navy); }
        .form-actions { background:var(--bg2); border-top:1px solid var(--border); padding:20px 32px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
        .action-note { font-size:12px; color:var(--muted); display:flex; align-items:center; gap:6px; }
        .btn-reset { padding:10px 24px; background:var(--white); border:1px solid var(--border); border-radius:3px; color:var(--muted); font-size:13px; font-weight:600; letter-spacing:.04em; cursor:pointer; transition:all .2s; font-family:'Source Sans 3',sans-serif; }
        .btn-reset:hover { border-color:var(--red); color:var(--red); }
        .btn-submit { padding:11px 36px; background:var(--navy); border:none; border-radius:3px; color:#fff; font-size:13px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; cursor:pointer; transition:background .2s; font-family:'Source Sans 3',sans-serif; display:flex; align-items:center; gap:8px; }
        .btn-submit:hover { background:var(--gold); color:var(--navy); }
        .notice { margin-top:24px; background:#fffbeb; border:1px solid #e6d28a; border-left:4px solid var(--gold); border-radius:4px; padding:13px 18px; display:flex; align-items:flex-start; gap:10px; font-size:12.5px; color:#6b5a1e; line-height:1.6; }
        .records-section { margin-top:48px; }
        .records-title-bar { display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; padding-bottom:16px; border-bottom:2px solid var(--border); }
        .records-title-bar h3 { font-family:'Source Serif 4',serif; font-size:20px; font-weight:700; color:var(--navy); }
        .records-title-bar h3 small { display:block; font-family:'Source Sans 3',sans-serif; font-size:12px; font-weight:400; color:var(--muted); margin-top:3px; }
        .records-controls { display:flex; align-items:center; gap:10px; }
        .search-input-wrap { position:relative; }
        .search-input-wrap svg { position:absolute; left:10px; top:50%; transform:translateY(-50%); color:var(--muted); pointer-events:none; }
        .search-input-wrap input { padding:8px 12px 8px 32px; font-size:13px; font-family:'Source Sans 3',sans-serif; background:var(--white); border:1px solid var(--border); border-radius:3px; outline:none; color:var(--text); width:220px; transition:border-color .2s,box-shadow .2s; }
        .search-input-wrap input:focus { border-color:var(--navy); box-shadow:0 0 0 3px rgba(13,34,64,.08); }
        .records-count-badge { background:var(--navy); color:#fff; font-size:11px; font-weight:700; letter-spacing:.08em; padding:5px 12px; border-radius:20px; }
        .table-card { background:var(--white); border:1px solid var(--border); border-top:4px solid var(--gold); border-radius:4px; overflow:hidden; }
        .table-wrap { overflow-x:auto; }
        table { width:100%; border-collapse:collapse; font-size:13px; }
        thead { background:var(--navy); }
        thead th { padding:13px 14px; text-align:left; color:#a8bcd4; font-size:10.5px; font-weight:700; letter-spacing:.12em; text-transform:uppercase; white-space:nowrap; border-right:1px solid rgba(255,255,255,.06); }
        thead th:last-child { border-right:none; text-align:center; }
        tbody tr { border-bottom:1px solid var(--border); transition:background .15s; }
        tbody tr:last-child { border-bottom:none; }
        tbody tr:hover { background:#f8f5ef; }
        tbody td { padding:12px 14px; color:var(--text); vertical-align:middle; }
        tbody td:last-child { text-align:center; }
        .td-id { font-weight:700; color:var(--navy); font-family:'Source Serif 4',serif; font-size:13px; white-space:nowrap; }
        .td-trunc { max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .badge { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; white-space:nowrap; }
        .badge-document { background:#e8f0fc; color:#1a3a7a; border:1px solid #c6d8f5; }
        .badge-image    { background:#f5e8fc; color:#5a1a7a; border:1px solid #ddc6f5; }
        .badge-video    { background:#fce8e8; color:#7a1515; border:1px solid #f5c6c6; }
        .badge-audio    { background:#e8f5ec; color:#1a5c36; border:1px solid #c6e8ce; }
        .badge-other    { background:var(--bg2); color:var(--muted); border:1px solid var(--border); }
        .badge-pending  { background:#fffbe8; color:#7a5a10; border:1px solid #e6d28a; }
        .badge-verified { background:#e8f5ec; color:#1a5c36; border:1px solid #c6e8ce; }
        .badge-rejected { background:#fce8e8; color:#7a1515; border:1px solid #f5c6c6; }
        .action-btns { display:flex; align-items:center; justify-content:center; gap:6px; }
        .icon-btn { width:30px; height:30px; display:flex; align-items:center; justify-content:center; border-radius:4px; border:1px solid var(--border); background:var(--bg); cursor:pointer; transition:all .18s; color:var(--muted); }
        .icon-btn.view-btn:hover  { background:#e8f0fc; border-color:#b0c8f0; color:#1a3a7a; }
        .icon-btn.block-btn:hover { background:#f0eaff; border-color:#c8aaff; color:var(--purple); }
        .icon-btn.del-btn:hover   { background:#fce8e8; border-color:#f5c6c6; color:var(--red); }
        .table-empty { text-align:center; padding:52px 20px; color:var(--muted); }
        .table-empty .empty-icon { font-size:36px; margin-bottom:10px; }
        .table-empty p { font-size:14px; }
        .table-footer { background:var(--bg2); border-top:1px solid var(--border); padding:12px 20px; display:flex; align-items:center; justify-content:space-between; font-size:12px; color:var(--muted); }
        .pagination { display:flex; align-items:center; gap:4px; }
        .page-btn { width:28px; height:28px; display:flex; align-items:center; justify-content:center; border:1px solid var(--border); border-radius:3px; background:var(--white); font-size:12px; font-weight:600; color:var(--navy); cursor:pointer; transition:all .15s; font-family:'Source Sans 3',sans-serif; }
        .page-btn:hover,.page-btn.active { background:var(--navy); color:#fff; border-color:var(--navy); }
        .page-btn:disabled { opacity:.4; cursor:not-allowed; }
        .modal-overlay { position:fixed; inset:0; background:rgba(13,34,64,.55); backdrop-filter:blur(3px); z-index:1000; display:flex; align-items:center; justify-content:center; padding:24px; opacity:0; pointer-events:none; transition:opacity .25s; }
        .modal-overlay.open { opacity:1; pointer-events:all; }
        .modal { background:var(--white); border:1px solid var(--border); border-top:4px solid var(--navy); border-radius:6px; width:100%; max-width:700px; max-height:90vh; overflow-y:auto; transform:translateY(-16px); transition:transform .25s; }
        .modal-overlay.open .modal { transform:translateY(0); }
        .modal-header { display:flex; align-items:center; justify-content:space-between; padding:20px 28px; border-bottom:1px solid var(--border); background:var(--bg2); }
        .modal-header h4 { font-family:'Source Serif 4',serif; font-size:17px; font-weight:700; color:var(--navy); }
        .modal-header h4 small { display:block; font-family:'Source Sans 3',sans-serif; font-size:11px; font-weight:400; color:var(--muted); margin-top:2px; }
        .modal-close { width:30px; height:30px; display:flex; align-items:center; justify-content:center; background:transparent; border:1px solid var(--border); border-radius:4px; cursor:pointer; color:var(--muted); font-size:18px; line-height:1; transition:all .15s; }
        .modal-close:hover { background:var(--red); color:#fff; border-color:var(--red); }
        .modal-body { padding:24px 28px; }
        .modal-footer { padding:16px 28px; border-top:1px solid var(--border); background:var(--bg2); display:flex; justify-content:flex-end; gap:10px; }
        .detail-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px 20px; }
        .detail-group { display:flex; flex-direction:column; gap:4px; }
        .detail-group.span-2 { grid-column:span 2; }
        .detail-label { font-size:10.5px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:var(--muted); }
        .detail-value { font-size:14px; color:var(--text); font-weight:500; padding:8px 10px; background:var(--bg); border:1px solid var(--border); border-radius:3px; min-height:36px; display:flex; align-items:center; }
        .detail-value.tall { align-items:flex-start; min-height:72px; line-height:1.6; }
        /* History timeline */
        .history-section { margin-top:20px; }
        .history-title { font-size:11px; font-weight:700; letter-spacing:.12em; text-transform:uppercase; color:var(--navy); margin-bottom:14px; padding-bottom:8px; border-bottom:1px solid var(--border); display:flex; align-items:center; gap:8px; }
        .timeline { display:flex; flex-direction:column; }
        .timeline-block { display:flex; gap:14px; }
        .timeline-spine { display:flex; flex-direction:column; align-items:center; flex-shrink:0; }
        .t-dot { width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:12px; flex-shrink:0; border:2px solid; }
        .t-dot.origin { background:#e8f0fc; border-color:#b0c8f0; color:#1a3a7a; }
        .t-dot.modify { background:#f0eaff; border-color:#c8aaff; color:var(--purple); }
        .t-line { width:2px; flex:1; min-height:12px; background:var(--border); margin:3px 0; }
        .t-card { flex:1; background:var(--bg); border:1px solid var(--border); border-radius:4px; padding:12px 14px; margin-bottom:8px; }
        .t-card.origin-card { border-left:3px solid #2a5fc8; }
        .t-card.modify-card { border-left:3px solid var(--purple-lt); }
        .t-card-head { display:flex; align-items:center; justify-content:space-between; margin-bottom:6px; }
        .t-type { font-size:10px; font-weight:700; letter-spacing:.12em; text-transform:uppercase; }
        .t-type.origin { color:#1a3a7a; }
        .t-type.modify { color:var(--purple); }
        .t-date { font-size:11px; color:var(--muted); }
        .t-by   { font-size:12px; color:var(--navy); font-weight:600; margin-bottom:4px; }
        .t-hash { font-family:'Courier New',monospace; font-size:10px; color:var(--muted); word-break:break-all; margin-top:4px; }
        .t-reason { font-size:12px; color:var(--text); font-style:italic; margin-top:4px; background:#f0eaff; border-radius:3px; padding:4px 8px; }
        .t-diff { margin-top:6px; }
        .t-diff-row { display:flex; gap:8px; font-size:11px; padding:3px 0; border-bottom:1px solid rgba(74,26,122,.08); align-items:baseline; }
        .t-diff-row:last-child { border-bottom:none; }
        .t-diff-field { font-weight:700; color:var(--text); min-width:100px; flex-shrink:0; }
        .t-diff-old { color:var(--red); text-decoration:line-through; opacity:.8; flex:1; }
        .t-diff-new { color:#1a5c36; font-weight:600; flex:1; }
        .history-loading { text-align:center; padding:20px; color:var(--muted); font-size:13px; }
        .history-empty   { text-align:center; padding:16px; color:var(--muted); font-size:12px; background:var(--bg); border-radius:4px; }
        /* New Block modal */
        .modal.purple-top { border-top-color:var(--purple); }
        .modal-header.purple-head { background:#1a0a2e; border-bottom-color:var(--purple-lt); }
        .modal-header.purple-head h4 { color:#fff; }
        .modal-header.purple-head h4 small { color:#a88bcc; }
        .nb-field { display:flex; flex-direction:column; gap:6px; margin-bottom:16px; }
        .nb-label { font-size:11px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:var(--navy); }
        .nb-label .req { color:var(--red); }
        .nb-input { width:100%; padding:9px 12px; font-size:14px; font-family:'Source Sans 3',sans-serif; color:var(--text); background:var(--bg); border:1px solid var(--border); border-radius:3px; outline:none; transition:border-color .2s; -webkit-appearance:none; }
        .nb-input:focus { border-color:var(--purple); background:var(--white); box-shadow:0 0 0 3px rgba(74,26,122,.08); }
        .nb-input[readonly] { background:var(--bg2); color:var(--muted); cursor:default; }
        .nb-textarea { resize:vertical; min-height:80px; }
        .nb-select { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%235c5c5c' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 12px center; padding-right:32px; cursor:pointer; }
        .nb-notice { background:#f0eaff; border:1px solid #c8aaff; border-left:4px solid var(--purple-lt); border-radius:3px; padding:10px 14px; font-size:12px; color:var(--purple); line-height:1.6; margin-bottom:18px; }
        .btn-purple { background:var(--purple); color:#fff; }
        .btn-purple:hover { background:var(--purple-lt); }
        .nb-grid { display:grid; grid-template-columns:1fr 1fr; gap:0 16px; }
        .nb-grid .nb-field { grid-column:span 1; }
        .nb-grid .nb-field.span-2 { grid-column:span 2; }
        .gov-footer { background:var(--navy); border-top:3px solid var(--gold); padding:18px 32px; display:flex; align-items:center; justify-content:space-between; font-size:11px; color:#6a88a4; letter-spacing:.04em; }
        .gov-footer strong { color:#a8bcd4; }
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
            <p>Ministry of Justice &nbsp;|&nbsp; Digital Forensics Division &nbsp;|&nbsp; Portal v2.0</p>
        </div>
    </div>
    <a href="dashboard.php" class="back-btn">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="15 18 9 12 15 6"/></svg>
        Back to Dashboard
    </a>
</header>

<div class="breadcrumb-bar">
    <a href="dashboard.php">Home</a> &rsaquo; <span>Submit Evidence</span>
</div>

<main class="main-wrap">

    <div class="page-title-bar">
        <h2>Submit Evidence
            <small>Each submission is cryptographically sealed. Original blocks are immutable — changes create new blocks.</small>
        </h2>
        <div class="form-ref"><strong><?php echo $year; ?></strong><br>Fields marked <span style="color:var(--red);">*</span> are mandatory</div>
    </div>

    <div class="form-card">
        <form id="evidenceForm" method="POST" enctype="multipart/form-data" onsubmit="return handleFormSubmit(event)">
            <div class="form-section">
                <div class="section-label">Section 1 — Evidence Reference</div>
                <div class="form-grid">
                    <div class="field-group">
                        <label>Evidence ID <span class="req">*</span></label>
                        <input type="text" name="EvidenceID" id="txtEvidenceID" value="<?php echo htmlspecialchars($AutoEvidID); ?>" class="field-readonly" readonly>
                        <span class="field-hint">Auto-generated — read only</span>
                    </div>
                    <div class="field-group">
                        <label>Case ID <span class="req">*</span></label>
                        <input type="text" name="CaseID" id="txtCaseID" placeholder="e.g. CASE-2025-001" required>
                    </div>
                    <div class="field-group">
                        <label>Evidence Type <span class="req">*</span></label>
                        <select name="EvidenceType" id="lstEvidenceType" required>
                            <option value="">— Select Type —</option>
                            <option value="Document">Document</option>
                            <option value="Image">Image</option>
                            <option value="Video">Video</option>
                            <option value="Audio">Audio</option>
                        </select>
                    </div>
                    <div class="field-group">
                        <label>Evidence Status</label>
                        <select name="EvidenceStatus" id="lstEvidenceStatus">
                            <option value="Pending">Pending</option>
                            <option value="Verified">Verified</option>
                            <option value="Rejected">Rejected</option>
                        </select>
                    </div>
                    <div class="field-group span-4">
                        <label>Evidence Description</label>
                        <textarea name="Description" id="txtDescription" placeholder="Provide a factual description of the evidence..."></textarea>
                    </div>
                </div>
            </div>
            <div class="form-section">
                <div class="section-label">Section 2 — Submission Details</div>
                <div class="form-grid">
                    <div class="field-group span-2">
                        <label>Submitted By <span class="req">*</span></label>
                        <input type="text" name="SubmittedBy" id="txtSubmittedBy" placeholder="Full name of submitting officer" required>
                    </div>
                    <div class="field-group">
                        <label>Authority Name</label>
                        <input type="text" name="AuthorityName" id="txtAuthorityName" placeholder="Dept. or authority name">
                    </div>
                    <div class="field-group">
                        <label>Submission Date <span class="req">*</span></label>
                        <input type="date" name="SubmissionDate" id="txtSubmissionDate" required>
                    </div>
                    <div class="field-group span-2">
                        <label>Location Recovered From</label>
                        <input type="text" name="LocationRecovered" id="txtLocationRecovered" placeholder="Address or location of recovery">
                    </div>
                </div>
            </div>
            <div class="form-section">
                <div class="section-label">Section 3 — Evidence Files</div>
                <div class="file-drop-area" id="dropArea">
                    <input type="file" name="EvidenceFiles[]" id="evidenceFiles" multiple accept=".pdf,.jpg,.jpeg,.png,.mp4,.mp3" onchange="updateFileList(this.files)">
                    <div class="file-drop-icon">📂</div>
                    <div class="file-drop-text">Click to select files or drag &amp; drop here</div>
                    <div class="file-drop-sub">Accepted: PDF, JPG, PNG, MP4, MP3 &nbsp;|&nbsp; Max 5 MB per file</div>
                </div>
                <div class="file-list" id="fileList"></div>
            </div>
            <div class="form-actions">
                <div class="action-note">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    All submissions are SHA-256 hashed and permanently sealed — original blocks cannot be modified.
                </div>
                <div style="display:flex;gap:12px;">
                    <button type="button" class="btn-reset" onclick="resetForm()">Clear Form</button>
                    <button type="submit" id="btnSave" class="btn-submit">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="20 6 9 17 4 12"/></svg>
                        <span id="btnSaveText">Submit Evidence</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="notice">
        <span style="font-size:17px;flex-shrink:0;">⚠️</span>
        <div><strong>Immutability Notice.</strong> Once submitted, evidence blocks cannot be edited or overwritten. If corrections are needed, use the <strong>➕ New Block</strong> button — the original block is permanently preserved in the chain.</div>
    </div>

    <div class="records-section">
        <div class="records-title-bar">
            <h3>Submitted Evidence Records <small>Original blocks are immutable. Use ➕ to append an updated version block.</small></h3>
            <div class="records-controls">
                <div class="search-input-wrap">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" id="searchInput" placeholder="Search evidence…" oninput="filterTable()">
                </div>
                <span class="records-count-badge" id="recordCount">0 Records</span>
            </div>
        </div>
        <div class="table-card">
            <div class="table-wrap">
                <table>
                    <thead><tr>
                        <th>#</th><th>Evidence ID</th><th>Case ID</th><th>Type</th>
                        <th>Status</th><th>Submitted By</th><th>Authority</th><th>Date</th><th>Actions</th>
                    </tr></thead>
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

<!-- VIEW + HISTORY MODAL -->
<div class="modal-overlay" id="viewModal">
    <div class="modal">
        <div class="modal-header">
            <h4>Evidence Details <small id="viewModalSub">—</small></h4>
            <button class="modal-close" onclick="closeModal('viewModal')">&#x2715;</button>
        </div>
        <div class="modal-body">
            <div class="detail-grid">
                <div class="detail-group"><div class="detail-label">Evidence ID</div><div class="detail-value" id="v_evidId">—</div></div>
                <div class="detail-group"><div class="detail-label">Case ID</div><div class="detail-value" id="v_caseId">—</div></div>
                <div class="detail-group"><div class="detail-label">Evidence Type</div><div class="detail-value" id="v_type">—</div></div>
                <div class="detail-group"><div class="detail-label">Status</div><div class="detail-value" id="v_status">—</div></div>
                <div class="detail-group span-2"><div class="detail-label">Description</div><div class="detail-value tall" id="v_desc">—</div></div>
                <div class="detail-group"><div class="detail-label">Submitted By</div><div class="detail-value" id="v_submittedBy">—</div></div>
                <div class="detail-group"><div class="detail-label">Authority</div><div class="detail-value" id="v_authority">—</div></div>
                <div class="detail-group"><div class="detail-label">Submission Date</div><div class="detail-value" id="v_date">—</div></div>
                <div class="detail-group"><div class="detail-label">Location Recovered</div><div class="detail-value" id="v_location">—</div></div>
            </div>
            <div class="history-section">
                <div class="history-title">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                    Block History — Full Audit Trail
                </div>
                <div id="historyContainer"><div class="history-loading">Loading block history…</div></div>
            </div>
        </div>
        <div class="modal-footer"><button class="btn-reset" onclick="closeModal('viewModal')">Close</button></div>
    </div>
</div>

<!-- NEW BLOCK MODAL -->
<div class="modal-overlay" id="newBlockModal">
    <div class="modal purple-top">
        <div class="modal-header purple-head">
            <h4>➕ New Block <small id="nbModalSub">Appending new version to chain</small></h4>
            <button class="modal-close" onclick="closeModal('newBlockModal')" style="border-color:rgba(255,255,255,.2);color:#a88bcc;">&#x2715;</button>
        </div>
        <div class="modal-body">
            <div class="nb-notice">🔗 The <strong>original block is immutable</strong> and will never be changed. This form creates a <strong>new block</strong> appended to the chain, linked to the previous block's hash.</div>
            <input type="hidden" id="nb_evidUId">
            <input type="hidden" id="nb_prevHash">
            <div class="nb-grid">
                <div class="nb-field"><label class="nb-label">Evidence ID</label><input class="nb-input" id="nb_evidId" readonly></div>
                <div class="nb-field"><label class="nb-label">Case ID</label><input class="nb-input" id="nb_caseId" readonly></div>
                <div class="nb-field">
                    <label class="nb-label">Evidence Type <span class="req">*</span></label>
                    <select class="nb-input nb-select" id="nb_type">
                        <option value="Document">Document</option><option value="Image">Image</option>
                        <option value="Video">Video</option><option value="Audio">Audio</option>
                    </select>
                </div>
                <div class="nb-field">
                    <label class="nb-label">Evidence Status <span class="req">*</span></label>
                    <select class="nb-input nb-select" id="nb_status">
                        <option value="Pending">Pending</option><option value="Verified">Verified</option><option value="Rejected">Rejected</option>
                    </select>
                </div>
                <div class="nb-field"><label class="nb-label">Submitted By <span class="req">*</span></label><input class="nb-input" id="nb_submittedBy" placeholder="Officer name"></div>
                <div class="nb-field"><label class="nb-label">Authority Name</label><input class="nb-input" id="nb_authority" placeholder="Dept. or authority"></div>
                <div class="nb-field"><label class="nb-label">Submission Date <span class="req">*</span></label><input type="date" class="nb-input" id="nb_date"></div>
                <div class="nb-field"><label class="nb-label">Location Recovered</label><input class="nb-input" id="nb_location" placeholder="Location"></div>
                <div class="nb-field span-2"><label class="nb-label">Description</label><textarea class="nb-input nb-textarea" id="nb_desc" placeholder="Updated description..."></textarea></div>
                <div class="nb-field span-2"><label class="nb-label">Reason for New Block <span class="req">*</span></label><textarea class="nb-input nb-textarea" id="nb_reason" placeholder="Mandatory: Why is this new block being created? e.g. Status updated after lab analysis..."></textarea></div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-reset" onclick="closeModal('newBlockModal')">Cancel</button>
            <button class="btn-submit btn-purple" onclick="submitNewBlock()">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="20 6 9 17 4 12"/></svg>
                Append New Block
            </button>
        </div>
    </div>
</div>

<!-- DELETE MODAL -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal" style="max-width:440px;">
        <div class="modal-header" style="border-top-color:var(--red);">
            <h4>Confirm Deletion</h4>
            <button class="modal-close" onclick="closeModal('deleteModal')">&#x2715;</button>
        </div>
        <div class="modal-body" style="text-align:center;padding:32px 28px;">
            <div style="font-size:44px;margin-bottom:14px;">🗑️</div>
            <p style="font-size:15px;font-weight:600;color:var(--navy);margin-bottom:8px;">Soft-delete this evidence record?</p>
            <p style="font-size:13px;color:var(--muted);line-height:1.6;">Evidence <strong id="deleteEvidLabel" style="color:var(--navy);">—</strong> will be marked as Deleted. The block stays in the chain but is hidden from active views.</p>
        </div>
        <div class="modal-footer">
            <button class="btn-reset" onclick="closeModal('deleteModal')">Cancel</button>
            <button class="btn-submit" onclick="confirmDelete()" style="background:var(--red);">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6M9 6V4h6v2"/></svg>
                Yes, Mark Deleted
            </button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let records = <?php echo json_encode(
    array_map(function($row) {
        $r = array_change_key_case($row, CASE_LOWER);
        return [
            'evidUId'          => $r['evidenceuid']       ?? '',
            'evidId'           => $r['evidenceid']        ?? '',
            'caseId'           => $r['caseid']            ?? '',
            'evidType'         => $r['evidencetype']      ?? '',
            'evidStatus'       => $r['evidencestatus']    ?? '',
            'description'      => $r['description']       ?? '',
            'submittedBy'      => $r['submittedby']       ?? '',
            'authorityName'    => $r['authorityname']     ?? '',
            'submissionDate'   => $r['submissiondate']    ?? '',
            'locationRecovered'=> $r['locationrecovered'] ?? '',
            'blockchainHash'   => $r['blockchainhash']    ?? '',
            'filePaths'        => $r['filepaths']         ?? null,
            'createdAt'        => $r['createdat']         ?? '',
        ];
    }, $evidenceList),
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
); ?>;

let filtered=[ ...records],currentPage=1,pageSize=10,deleteTargetIndex=null;

function handleFormSubmit(e){
    e.preventDefault();
    const btn=document.getElementById('btnSave');
    btn.disabled=true; document.getElementById('btnSaveText').textContent='Submitting…';
    const fd=new FormData(document.getElementById('evidenceForm'));
    fd.append('Flag','Save');
    fetch('EvidenceOperation.php',{method:'POST',body:fd}).then(r=>r.text()).then(data=>{
        try{
            const res=JSON.parse(data);
            if(res.Status==='success'){
                const cid=res.EvidenceID||document.getElementById('txtEvidenceID').value.trim();
                records.unshift({evidUId:res.EvidenceUId||'',evidId:cid,
                    caseId:document.getElementById('txtCaseID').value.trim(),
                    evidType:document.getElementById('lstEvidenceType').value,
                    evidStatus:document.getElementById('lstEvidenceStatus').value,
                    description:document.getElementById('txtDescription').value.trim(),
                    submittedBy:document.getElementById('txtSubmittedBy').value.trim(),
                    authorityName:document.getElementById('txtAuthorityName').value.trim(),
                    submissionDate:document.getElementById('txtSubmissionDate').value,
                    locationRecovered:document.getElementById('txtLocationRecovered').value.trim(),
                    blockchainHash:res.BlockchainHash||'',filePaths:null,createdAt:new Date().toISOString()});
                filtered=[...records];currentPage=1;renderTable();resetForm();
                const p=cid.split('-');
                if(p.length===3) document.getElementById('txtEvidenceID').value='EVID-'+p[1]+'-'+String(parseInt(p[2],10)+1).padStart(3,'0');
                Swal.fire({icon:'success',title:'Evidence Submitted!',
                    html:`<div style="text-align:left;padding:10px;font-size:13px;line-height:1.8;"><p><strong>Evidence ID:</strong> ${escHtml(cid)}</p><p style="font-size:11px;margin-top:8px;color:#555;"><strong>🔐 Block Hash:</strong><br><code style="font-size:10px;word-break:break-all;color:#1a5c36;">${res.BlockchainHash||'—'}</code></p></div>`,
                    confirmButtonColor:'#0d2240',timer:5000,timerProgressBar:true});
            } else { showAlert('error','Submission Failed',res.Message||'Failed to submit.'); }
        }catch(err){showAlert('error','Parse Error','Could not read server response.');}
    }).catch(()=>showAlert('error','Connection Error','Could not connect.'))
    .finally(()=>{btn.disabled=false;document.getElementById('btnSaveText').textContent='Submit Evidence';});
    return false;
}
function resetForm(){const s=document.getElementById('txtEvidenceID').value;document.getElementById('evidenceForm').reset();document.getElementById('txtEvidenceID').value=s;clearFiles();}

function renderTable(){
    const tbody=document.getElementById('tableBody'),total=filtered.length,pages=Math.max(1,Math.ceil(total/pageSize));
    if(currentPage>pages)currentPage=pages;
    const start=(currentPage-1)*pageSize,end=Math.min(start+pageSize,total),page=filtered.slice(start,end);
    document.getElementById('recordCount').textContent=`${total} Record${total!==1?'s':''}`;
    document.getElementById('paginationInfo').textContent=total===0?'No records found':`Showing ${start+1}–${end} of ${total} records`;
    if(total===0){tbody.innerHTML=`<tr><td colspan="9"><div class="table-empty"><div class="empty-icon">🗂️</div><p>No evidence records found.</p></div></td></tr>`;renderPagination(0,0);return;}
    tbody.innerHTML=page.map((r,i)=>{const ri=records.indexOf(r);return`<tr>
        <td style="color:var(--muted);font-size:12px;">${start+i+1}</td>
        <td class="td-id">${escHtml(r.evidId)}</td>
        <td class="td-id" style="font-family:'Source Sans 3',sans-serif;font-size:13px;">${escHtml(r.caseId)}</td>
        <td>${typeBadge(r.evidType)}</td><td>${statusBadge(r.evidStatus)}</td>
        <td>${escHtml(r.submittedBy)}</td>
        <td class="td-trunc" title="${escHtml(r.authorityName)}">${escHtml(r.authorityName||'—')}</td>
        <td style="white-space:nowrap;">${formatDate(r.submissionDate)}</td>
        <td><div class="action-btns">
            <button class="icon-btn view-btn" title="View &amp; History" onclick="openView(${ri})">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
            <button class="icon-btn block-btn" title="Append New Block" onclick="openNewBlock(${ri})">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            </button>
            
        </div></td></tr>`;}).join('');
    renderPagination(pages,currentPage);
}
function typeBadge(t){const m={Document:['badge-document','📄 Document'],Image:['badge-image','🖼️ Image'],Video:['badge-video','🎬 Video'],Audio:['badge-audio','🎵 Audio']};const[cls,lbl]=m[t]||['badge-other',escHtml(t)];return`<span class="badge ${cls}">${lbl}</span>`;}
function statusBadge(s){const m={Pending:['badge-pending','⏳ Pending'],Verified:['badge-verified','✅ Verified'],Rejected:['badge-rejected','❌ Rejected']};const[cls,lbl]=m[s]||['badge-other',escHtml(s)];return`<span class="badge ${cls}">${lbl}</span>`;}
function formatDate(d){if(!d)return'—';const p=String(d).split('-');if(p.length<3)return d;const mo=['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];return`${parseInt(p[2])} ${mo[parseInt(p[1])-1]} ${p[0]}`;}
function escHtml(s){return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
function renderPagination(pages,cur){const ctrl=document.getElementById('paginationControls');if(pages<=1){ctrl.innerHTML='';return;}let h=`<button class="page-btn" onclick="goPage(${cur-1})" ${cur===1?'disabled':''}>&#8249;</button>`;for(let p=1;p<=pages;p++)h+=`<button class="page-btn ${p===cur?'active':''}" onclick="goPage(${p})">${p}</button>`;h+=`<button class="page-btn" onclick="goPage(${cur+1})" ${cur===pages?'disabled':''}>&#8250;</button>`;ctrl.innerHTML=h;}
function goPage(p){const t=Math.ceil(filtered.length/pageSize);if(p<1||p>t)return;currentPage=p;renderTable();}
function filterTable(){const q=document.getElementById('searchInput').value.toLowerCase().trim();filtered=q?records.filter(r=>[r.evidId,r.caseId,r.evidType,r.evidStatus,r.submittedBy].some(v=>(v||'').toLowerCase().includes(q))):[...records];currentPage=1;renderTable();}

function openView(idx){
    const r=records[idx];
    document.getElementById('viewModalSub').textContent=r.evidId;
    document.getElementById('v_evidId').textContent=r.evidId;
    document.getElementById('v_caseId').textContent=r.caseId;
    document.getElementById('v_type').textContent=r.evidType;
    document.getElementById('v_status').textContent=r.evidStatus;
    document.getElementById('v_desc').textContent=r.description||'—';
    document.getElementById('v_submittedBy').textContent=r.submittedBy;
    document.getElementById('v_authority').textContent=r.authorityName||'—';
    document.getElementById('v_date').textContent=formatDate(r.submissionDate);
    document.getElementById('v_location').textContent=r.locationRecovered||'—';
    document.getElementById('historyContainer').innerHTML='<div class="history-loading">Loading block history…</div>';
    openModal('viewModal');
    loadHistory(r.evidUId,r);
}
function loadHistory(evidUId,orig){
    const fd=new FormData();fd.append('Flag','GetHistory');fd.append('EvidenceUId',evidUId);
    fetch('EvidenceOperation.php',{method:'POST',body:fd}).then(r=>r.json()).then(res=>{
        const container=document.getElementById('historyContainer'),history=res.History||[];
        let html='<div class="timeline">';
        html+=`<div class="timeline-block"><div class="timeline-spine"><div class="t-dot origin">📦</div>${history.length>0?'<div class="t-line"></div>':''}</div><div class="t-card origin-card"><div class="t-card-head"><span class="t-type origin">EVIDENCE_SUBMIT — Original Block</span><span class="t-date">${escHtml(orig.createdAt)}</span></div><div class="t-by">👤 ${escHtml(orig.submittedBy)}</div><div class="t-hash">🔐 ${escHtml(orig.blockchainHash||'—')}</div></div></div>`;
        history.forEach((h,i)=>{
            const cf=(()=>{try{return JSON.parse(h.ChangedFields||'{}');}catch(e){return{};}})();
            const isLast=(i===history.length-1);
            html+=`<div class="timeline-block"><div class="timeline-spine"><div class="t-dot modify">✏️</div>${!isLast?'<div class="t-line"></div>':''}</div><div class="t-card modify-card"><div class="t-card-head"><span class="t-type modify">EVIDENCE_MODIFY — Block #${i+1}</span><span class="t-date">${escHtml(h.CreatedAt)}</span></div><div class="t-by">👤 ${escHtml(h.ModifiedBy)}</div><div class="t-reason">📝 "${escHtml(h.ModifyReason)}"</div><div class="t-hash">🔐 ${escHtml(h.BlockchainHash)}</div>${Object.keys(cf).length>0?`<div class="t-diff">${Object.entries(cf).map(([f,v])=>`<div class="t-diff-row"><span class="t-diff-field">${escHtml(f)}</span><span class="t-diff-old">${escHtml(v.old||'—')}</span><span>→</span><span class="t-diff-new">${escHtml(v.new||'—')}</span></div>`).join('')}</div>`:''}</div></div>`;
        });
        if(history.length===0)html+='<div class="history-empty">No modification blocks yet. Original submission is the only block for this evidence.</div>';
        html+='</div>';
        container.innerHTML=html;
    }).catch(()=>{document.getElementById('historyContainer').innerHTML='<div class="history-empty">Could not load block history.</div>';});
}

function openNewBlock(idx){
    const r=records[idx];
    document.getElementById('nbModalSub').textContent=`Appending to: ${r.evidId}`;
    document.getElementById('nb_evidUId').value=r.evidUId;
    document.getElementById('nb_prevHash').value=r.blockchainHash;
    document.getElementById('nb_evidId').value=r.evidId;
    document.getElementById('nb_caseId').value=r.caseId;
    document.getElementById('nb_type').value=r.evidType;
    document.getElementById('nb_status').value=r.evidStatus;
    document.getElementById('nb_submittedBy').value=r.submittedBy;
    document.getElementById('nb_authority').value=r.authorityName||'';
    document.getElementById('nb_date').value=r.submissionDate;
    document.getElementById('nb_location').value=r.locationRecovered||'';
    document.getElementById('nb_desc').value=r.description||'';
    document.getElementById('nb_reason').value='';
    openModal('newBlockModal');
}
function submitNewBlock(){
    const reason=document.getElementById('nb_reason').value.trim();
    if(!reason){showAlert('warning','Required','Reason for new block is mandatory.');return;}
    if(!document.getElementById('nb_submittedBy').value.trim()){showAlert('warning','Required','Submitted By cannot be empty.');return;}
    if(!document.getElementById('nb_date').value){showAlert('warning','Required','Submission Date cannot be empty.');return;}
    const fd=new FormData();
    fd.append('Flag','UpdateDetails');
    fd.append('EvidenceUId',document.getElementById('nb_evidUId').value);
    fd.append('EvidenceType',document.getElementById('nb_type').value);
    fd.append('EvidenceStatus',document.getElementById('nb_status').value);
    fd.append('Description',document.getElementById('nb_desc').value.trim());
    fd.append('SubmittedBy',document.getElementById('nb_submittedBy').value.trim());
    fd.append('AuthorityName',document.getElementById('nb_authority').value.trim());
    fd.append('SubmissionDate',document.getElementById('nb_date').value);
    fd.append('LocationRecovered',document.getElementById('nb_location').value.trim());
    fd.append('ModifyReason',reason);
    fetch('EvidenceOperation.php',{method:'POST',body:fd}).then(r=>r.json()).then(res=>{
        if(res.Status==='success'){
            closeModal('newBlockModal');
            Swal.fire({icon:'success',title:'New Block Appended!',
                html:`<div style="text-align:left;padding:10px;font-size:13px;line-height:1.9;"><p><strong>Evidence ID:</strong> ${escHtml(res.EvidenceID||'')}</p><p><strong>Fields Changed:</strong> ${res.ChangedFields??0}</p><hr style="margin:10px 0;border-color:#eee;"><p style="font-size:11px;color:#555;"><strong>🔗 New Block Hash:</strong><br><code style="font-size:10px;word-break:break-all;color:#1a5c36;">${res.NewHash||'—'}</code></p><p style="font-size:11px;color:#555;margin-top:6px;"><strong>⛓️ Previous Hash:</strong><br><code style="font-size:10px;word-break:break-all;color:#9b1c1c;">${res.PreviousHash||'—'}</code></p></div>`,
                confirmButtonColor:'#4a1a7a'});
            const idx=records.findIndex(r=>r.evidUId===document.getElementById('nb_evidUId').value);
            if(idx>-1){records[idx].evidType=document.getElementById('nb_type').value;records[idx].evidStatus=document.getElementById('nb_status').value;records[idx].submittedBy=document.getElementById('nb_submittedBy').value.trim();records[idx].authorityName=document.getElementById('nb_authority').value.trim();records[idx].submissionDate=document.getElementById('nb_date').value;records[idx].locationRecovered=document.getElementById('nb_location').value.trim();records[idx].description=document.getElementById('nb_desc').value.trim();records[idx].blockchainHash=res.NewHash||records[idx].blockchainHash;filtered=[...records];renderTable();}
        } else {showAlert('error','Failed',res.Message||'Could not append new block.');}
    }).catch(()=>showAlert('error','Connection Error','Could not connect.'));
}

function openDelete(idx){deleteTargetIndex=idx;document.getElementById('deleteEvidLabel').textContent=records[idx].evidId;openModal('deleteModal');}
function confirmDelete(){
    if(deleteTargetIndex===null)return;
    const r=records[deleteTargetIndex];const fd=new FormData();
    fd.append('Flag','DeleteDetails');fd.append('EvidenceUId',r.evidUId);
    fetch('EvidenceOperation.php',{method:'POST',body:fd}).then(res=>res.json()).then(data=>{
        if(data.Status==='Delete'){records.splice(deleteTargetIndex,1);filtered=[...records];deleteTargetIndex=null;currentPage=1;renderTable();closeModal('deleteModal');showAlert('success','Deleted',`Evidence ${r.evidId} marked as deleted.`);}
        else{showAlert('error','Failed',data.Message||'Could not delete.');}
    }).catch(()=>showAlert('error','Connection Error','Could not connect.'));
}

function openModal(id){document.getElementById(id).classList.add('open');document.body.style.overflow='hidden';}
function closeModal(id){document.getElementById(id).classList.remove('open');document.body.style.overflow='';}
document.querySelectorAll('.modal-overlay').forEach(o=>o.addEventListener('click',e=>{if(e.target===o)closeModal(o.id);}));
document.addEventListener('keydown',e=>{if(e.key==='Escape')document.querySelectorAll('.modal-overlay.open').forEach(m=>closeModal(m.id));});

function showAlert(type,title,message){Swal.fire({icon:type,title,text:message,confirmButtonColor:'#0d2240',...(type==='success'?{timer:3000,timerProgressBar:true}:{})});}

function updateFileList(files){const list=document.getElementById('fileList');list.innerHTML='';const icons={pdf:'📄',jpg:'🖼️',jpeg:'🖼️',png:'🖼️',mp4:'🎬',mp3:'🎵'};Array.from(files).forEach(file=>{const ext=file.name.split('.').pop().toLowerCase();const size=(file.size/1024).toFixed(1);const item=document.createElement('div');item.className='file-item';item.innerHTML=`<span>${icons[ext]||'📎'}</span><span style="flex:1;">${file.name}</span><span style="color:var(--muted);font-size:11px;">${size} KB</span>`;list.appendChild(item);});}
function clearFiles(){document.getElementById('fileList').innerHTML='';}

const dropArea=document.getElementById('dropArea');
['dragenter','dragover'].forEach(ev=>dropArea.addEventListener(ev,()=>dropArea.classList.add('dragover')));
['dragleave','drop'].forEach(ev=>dropArea.addEventListener(ev,()=>dropArea.classList.remove('dragover')));

document.addEventListener('DOMContentLoaded',()=>{filtered=[...records];renderTable();});
</script>
</body>
</html>