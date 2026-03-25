<?php
session_start();
include_once("db.php");

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// ── Fetch all CASES ───────────────────────────────────────────────────────────
$allCases = [];
$result   = mysqli_query($conn, "SELECT * FROM tblcaseregister ORDER BY CreatedAt DESC");
if ($result) while ($row = mysqli_fetch_assoc($result)) $allCases[] = $row;

// ── Case status counts (uses CaseStatus column) ───────────────────────────────
$caseStatuses = ['Active', 'Submitted', 'Pending', 'Verified', 'Rejected', 'Deleted'];
$caseCounts   = array_fill_keys($caseStatuses, 0);
foreach ($allCases as $c) {
    $st = $c['CaseStatus'] ?? 'Active';
    if (isset($caseCounts[$st])) $caseCounts[$st]++;
}
$totalCases = count($allCases);

// ── Fetch all EVIDENCE ────────────────────────────────────────────────────────
$allEvidence = [];
$result2     = mysqli_query($conn, "SELECT * FROM tblevidence ORDER BY CreatedAt DESC");
if ($result2) while ($row = mysqli_fetch_assoc($result2)) $allEvidence[] = $row;

// ── Evidence status counts ────────────────────────────────────────────────────
// EvidenceStatus = the actual workflow status (Submitted, Pending, Verified, Rejected, Active)
// RecordStatus   = soft-delete flag (Active / Deleted)
// Card "Deleted"  → RecordStatus = 'Deleted'
// All other cards → filter by EvidenceStatus WHERE RecordStatus != 'Deleted'

$evidenceCardStatuses = ['Active', 'Submitted', 'Pending', 'Verified', 'Rejected'];
$evidenceCounts       = array_fill_keys($evidenceCardStatuses, 0);
$evidenceCounts['Deleted'] = 0;

foreach ($allEvidence as $e) {
    // Deleted card — soft-deleted records
    if (($e['RecordStatus'] ?? '') === 'Deleted') {
        $evidenceCounts['Deleted']++;
        continue; // don't also count in workflow status
    }
    $st = $e['EvidenceStatus'] ?? 'Active';
    if (isset($evidenceCounts[$st])) $evidenceCounts[$st]++;
}
$totalEvidence = count($allEvidence);

$year = date("Y");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BEMS — Explorer Cases</title>
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

    --c-active:   #1a5c36; --bg-active:   #e8f5ec; --bdr-active:   #c6e8ce; --bar-active:   #2d8a50;
    --c-submitted:#1a3a7a; --bg-submitted:#e8f0fc; --bdr-submitted:#c6d8f5; --bar-submitted:#2a5fc8;
    --c-pending:  #7a5a10; --bg-pending:  #fffbe8; --bdr-pending:  #e6d28a; --bar-pending:  #c8972a;
    --c-verified: #155724; --bg-verified: #d4edda; --bdr-verified: #b8dfc3; --bar-verified: #1a7a3c;
    --c-rejected: #7a1515; --bg-rejected: #fce8e8; --bdr-rejected: #f5c6c6; --bar-rejected: #c0392b;
    --c-deleted:  #4a4a4a; --bg-deleted:  #f0f0f0; --bdr-deleted:  #cccccc; --bar-deleted:  #6c757d;
}

body { background: var(--bg); color: var(--text); font-family: 'Source Sans 3', sans-serif; min-height: 100vh; }

.gov-banner {
    background: var(--navy); color: #a8bcd4;
    font-size: 11.5px; letter-spacing: .04em; padding: 6px 32px;
    display: flex; align-items: center; justify-content: space-between;
}
.gov-banner strong { color: #fff; }

.top-header {
    background: var(--navy2); border-bottom: 4px solid var(--gold);
    padding: 0 32px; display: flex; align-items: center; justify-content: space-between; min-height: 76px;
}
.header-left { display: flex; align-items: center; gap: 16px; }
.seal {
    width: 52px; height: 52px; border-radius: 50%;
    border: 2px solid var(--gold); background: var(--navy);
    display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;
}
.header-titles h1 { font-family: 'Source Serif 4', serif; font-size: 17px; font-weight: 700; color: #fff; line-height: 1.2; }
.header-titles p  { font-size: 10.5px; color: #a8bcd4; letter-spacing: .08em; text-transform: uppercase; margin-top: 2px; }
.back-btn {
    display: flex; align-items: center; gap: 7px; padding: 8px 18px;
    background: transparent; border: 1px solid rgba(200,151,42,.45); border-radius: 4px;
    color: var(--gold-lt); font-size: 12px; font-weight: 600; letter-spacing: .06em;
    text-transform: uppercase; text-decoration: none; transition: all .2s;
}
.back-btn:hover { background: var(--gold); border-color: var(--gold); color: var(--navy); }

.breadcrumb-bar { background: var(--bg2); border-bottom: 1px solid var(--border); padding: 8px 32px; font-size: 12px; color: var(--muted); }
.breadcrumb-bar a { color: var(--muted); text-decoration: none; }
.breadcrumb-bar span { color: var(--navy); font-weight: 600; }

.main-wrap { max-width: 1280px; margin: 0 auto; padding: 36px 32px 80px; }

.page-title-bar {
    display: flex; align-items: flex-end; justify-content: space-between;
    margin-bottom: 36px; padding-bottom: 18px; border-bottom: 2px solid var(--border);
}
.page-title-bar h2 { font-family: 'Source Serif 4', serif; font-size: 24px; font-weight: 700; color: var(--navy); }
.page-title-bar h2 small { display: block; font-family: 'Source Sans 3', sans-serif; font-size: 13px; font-weight: 400; color: var(--muted); margin-top: 4px; }
.form-ref { font-size: 11px; color: var(--muted); letter-spacing: .1em; text-transform: uppercase; text-align: right; line-height: 1.7; }
.form-ref strong { color: var(--navy); }

/* ── MODULE ── */
.module-section { margin-bottom: 52px; }
.module-header {
    display: flex; align-items: center; gap: 14px;
    margin-bottom: 20px; padding-bottom: 14px; border-bottom: 2px solid var(--border);
}
.module-icon {
    width: 44px; height: 44px; border-radius: 4px;
    background: var(--navy2); border: 1px solid rgba(200,151,42,.35);
    display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;
}
.module-title-wrap h3 { font-family: 'Source Serif 4', serif; font-size: 18px; font-weight: 700; color: var(--navy); }
.module-title-wrap p  { font-size: 12px; color: var(--muted); margin-top: 2px; }
.module-total {
    margin-left: auto; background: var(--navy); color: var(--gold-lt);
    font-family: 'Source Serif 4', serif; font-size: 28px; font-weight: 700;
    padding: 6px 18px; border-radius: 4px; line-height: 1; border: 1px solid rgba(200,151,42,.3);
}
.module-total small {
    display: block; font-family: 'Source Sans 3', sans-serif;
    font-size: 9px; font-weight: 600; letter-spacing: .12em;
    text-transform: uppercase; color: #a8bcd4; margin-top: 2px;
}

/* ── STATUS CARDS ── */
.status-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 14px; }
@media (max-width: 1100px) { .status-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 600px)  { .status-grid { grid-template-columns: repeat(2, 1fr); } }

.status-card {
    background: var(--white); border: 1px solid var(--border); border-radius: 4px;
    padding: 18px 18px 15px; position: relative; overflow: hidden;
    cursor: pointer; transition: transform .18s, box-shadow .18s, border-color .18s, background .18s;
    animation: fadeUp .4s ease both; user-select: none;
}
.status-card:nth-child(1){animation-delay:.04s} .status-card:nth-child(2){animation-delay:.08s}
.status-card:nth-child(3){animation-delay:.12s} .status-card:nth-child(4){animation-delay:.16s}
.status-card:nth-child(5){animation-delay:.20s} .status-card:nth-child(6){animation-delay:.24s}
@keyframes fadeUp { from{opacity:0;transform:translateY(12px)} to{opacity:1;transform:translateY(0)} }

.status-card::before { content:''; position:absolute; top:0; left:0; right:0; height:4px; transition:height .18s; }
.status-card:hover, .status-card.selected { transform:translateY(-3px); box-shadow:0 8px 24px rgba(0,0,0,.1); }
.status-card.selected::before { height:6px; }

.sc-active::before    { background: var(--bar-active); }
.sc-submitted::before { background: var(--bar-submitted); }
.sc-pending::before   { background: var(--bar-pending); }
.sc-verified::before  { background: var(--bar-verified); }
.sc-rejected::before  { background: var(--bar-rejected); }
.sc-deleted::before   { background: var(--bar-deleted); }

.sc-active.selected    { border-color:var(--bdr-active);    background:var(--bg-active); }
.sc-submitted.selected { border-color:var(--bdr-submitted); background:var(--bg-submitted); }
.sc-pending.selected   { border-color:var(--bdr-pending);   background:var(--bg-pending); }
.sc-verified.selected  { border-color:var(--bdr-verified);  background:var(--bg-verified); }
.sc-rejected.selected  { border-color:var(--bdr-rejected);  background:var(--bg-rejected); }
.sc-deleted.selected   { border-color:var(--bdr-deleted);   background:var(--bg-deleted); }

.sc-row  { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; }
.sc-icon { font-size:20px; }
.sc-dot  { width:8px; height:8px; border-radius:50%; }
.sc-active .sc-dot    { background:var(--bar-active); }
.sc-submitted .sc-dot { background:var(--bar-submitted); }
.sc-pending .sc-dot   { background:var(--bar-pending); animation:blink 2s infinite; }
.sc-verified .sc-dot  { background:var(--bar-verified); }
.sc-rejected .sc-dot  { background:var(--bar-rejected); }
.sc-deleted .sc-dot   { background:var(--bar-deleted); }
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:.25} }

.sc-label { font-size:9.5px; font-weight:700; letter-spacing:.14em; text-transform:uppercase; color:var(--muted); margin-bottom:4px; }
.sc-count { font-family:'Source Serif 4',serif; font-size:34px; font-weight:700; line-height:1; }
.sc-active .sc-count    { color:var(--bar-active); }
.sc-submitted .sc-count { color:var(--bar-submitted); }
.sc-pending .sc-count   { color:var(--bar-pending); }
.sc-verified .sc-count  { color:var(--bar-verified); }
.sc-rejected .sc-count  { color:var(--bar-rejected); }
.sc-deleted .sc-count   { color:var(--bar-deleted); }
.sc-pct { font-size:11px; color:var(--muted); margin-top:4px; font-weight:600; }
.click-hint { font-size:9px; letter-spacing:.1em; text-transform:uppercase; color:var(--muted); margin-top:10px; opacity:.65; display:flex; align-items:center; gap:4px; }

/* ── TABLE PANEL ── */
.table-panel { display:none; margin-top:20px; animation:fadeUp .3s ease both; }
.table-panel.visible { display:block; }

.table-panel-header {
    display:flex; align-items:center; justify-content:space-between;
    padding:14px 20px; background:var(--navy); border-radius:4px 4px 0 0;
}
.table-panel-title { font-family:'Source Serif 4',serif; font-size:16px; font-weight:700; color:#fff; display:flex; align-items:center; gap:10px; }
.tpt-badge {
    background:rgba(200,151,42,.25); border:1px solid rgba(200,151,42,.4); color:var(--gold-lt);
    font-family:'Source Sans 3',sans-serif; font-size:11px; font-weight:700;
    letter-spacing:.08em; text-transform:uppercase; padding:3px 10px; border-radius:20px;
}
.panel-controls { display:flex; align-items:center; gap:10px; }
.panel-search-wrap { position:relative; }
.panel-search-wrap svg { position:absolute; left:9px; top:50%; transform:translateY(-50%); color:#a8bcd4; pointer-events:none; }
.panel-search-wrap input {
    padding:7px 12px 7px 30px; font-size:12px; font-family:'Source Sans 3',sans-serif;
    background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.15); border-radius:3px;
    color:#fff; width:200px; outline:none; transition:border-color .2s;
}
.panel-search-wrap input::placeholder { color:#6a88a4; }
.panel-search-wrap input:focus { border-color:var(--gold); }
.close-panel-btn {
    display:flex; align-items:center; gap:6px; padding:7px 14px;
    background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.15); border-radius:3px;
    color:#a8bcd4; font-size:11px; font-weight:600; letter-spacing:.06em; text-transform:uppercase;
    cursor:pointer; font-family:'Source Sans 3',sans-serif; transition:all .15s;
}
.close-panel-btn:hover { background:rgba(200,151,42,.2); border-color:var(--gold); color:var(--gold-lt); }

.table-card { background:var(--white); border:1px solid var(--border); border-top:none; border-radius:0 0 4px 4px; overflow:hidden; }
.table-wrap { overflow-x:auto; }
table { width:100%; border-collapse:collapse; font-size:13px; }
thead { background:var(--bg2); }
thead th {
    padding:11px 14px; text-align:left; color:var(--navy);
    font-size:10px; font-weight:700; letter-spacing:.12em; text-transform:uppercase; white-space:nowrap;
    border-right:1px solid var(--border); border-bottom:1px solid var(--border);
}
thead th:last-child { border-right:none; text-align:center; }
tbody tr { border-bottom:1px solid var(--border); transition:background .12s; }
tbody tr:last-child { border-bottom:none; }
tbody tr:hover { background:#f8f5ef; }
tbody td { padding:11px 14px; color:var(--text); vertical-align:middle; }
tbody td:last-child { text-align:center; }

.td-id   { font-weight:700; color:var(--navy); font-family:'Source Serif 4',serif; white-space:nowrap; }
.td-clip { max-width:150px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }

.badge {
    display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:20px;
    font-size:10px; font-weight:700; letter-spacing:.06em; text-transform:uppercase; white-space:nowrap;
}
.badge-Active    { background:var(--bg-active);   color:var(--c-active);   border:1px solid var(--bdr-active); }
.badge-Submitted { background:var(--bg-submitted);color:var(--c-submitted);border:1px solid var(--bdr-submitted); }
.badge-Pending   { background:var(--bg-pending);  color:var(--c-pending);  border:1px solid var(--bdr-pending); }
.badge-Verified  { background:var(--bg-verified); color:var(--c-verified); border:1px solid var(--bdr-verified); }
.badge-Rejected  { background:var(--bg-rejected); color:var(--c-rejected); border:1px solid var(--bdr-rejected); }
.badge-Deleted   { background:var(--bg-deleted);  color:var(--c-deleted);  border:1px solid var(--bdr-deleted); }

.type-badge { display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:3px; font-size:10px; font-weight:600; white-space:nowrap; }
.badge-criminal { background:#fce8e8; color:#7a1515; border:1px solid #f5c6c6; }
.badge-civil    { background:#e8f0fc; color:#1a3a7a; border:1px solid #c6d8f5; }
.badge-cyber    { background:#e8f5ec; color:#1a5c36; border:1px solid #c6e8ce; }

.action-btns { display:flex; align-items:center; justify-content:center; gap:6px; }
.icon-btn {
    width:28px; height:28px; display:flex; align-items:center; justify-content:center;
    border-radius:4px; border:1px solid var(--border); background:var(--bg);
    cursor:pointer; transition:all .15s; color:var(--muted);
}
.icon-btn.view-btn:hover { background:#e8f0fc; border-color:#b0c8f0; color:#1a3a7a; }

.table-empty { text-align:center; padding:44px 20px; color:var(--muted); }
.table-empty .empty-icon { font-size:32px; margin-bottom:8px; }
.table-empty p { font-size:13px; }

.table-footer {
    background:var(--bg2); border-top:1px solid var(--border);
    padding:10px 18px; display:flex; align-items:center; justify-content:space-between;
    font-size:11px; color:var(--muted);
}
.pagination { display:flex; align-items:center; gap:4px; }
.page-btn {
    width:26px; height:26px; display:flex; align-items:center; justify-content:center;
    border:1px solid var(--border); border-radius:3px; background:var(--white);
    font-size:11px; font-weight:600; color:var(--navy);
    cursor:pointer; transition:all .12s; font-family:'Source Sans 3',sans-serif;
}
.page-btn:hover, .page-btn.active { background:var(--navy); color:#fff; border-color:var(--navy); }
.page-btn:disabled { opacity:.4; cursor:not-allowed; }

/* ── MODALS ── */
.modal-overlay {
    position:fixed; inset:0; background:rgba(13,34,64,.55); backdrop-filter:blur(3px);
    z-index:1000; display:flex; align-items:center; justify-content:center;
    padding:24px; opacity:0; pointer-events:none; transition:opacity .25s;
}
.modal-overlay.open { opacity:1; pointer-events:all; }
.modal {
    background:var(--white); border:1px solid var(--border); border-top:4px solid var(--navy);
    border-radius:6px; width:100%; max-width:700px; max-height:90vh; overflow-y:auto;
    transform:translateY(-16px); transition:transform .25s;
}
.modal-overlay.open .modal { transform:translateY(0); }
.modal-header {
    display:flex; align-items:center; justify-content:space-between;
    padding:18px 26px; border-bottom:1px solid var(--border); background:var(--bg2);
}
.modal-header h4 { font-family:'Source Serif 4',serif; font-size:17px; font-weight:700; color:var(--navy); }
.modal-header h4 small { display:block; font-family:'Source Sans 3',sans-serif; font-size:11px; font-weight:400; color:var(--muted); margin-top:2px; }
.modal-close {
    width:30px; height:30px; display:flex; align-items:center; justify-content:center;
    background:transparent; border:1px solid var(--border); border-radius:4px;
    cursor:pointer; color:var(--muted); font-size:18px; transition:all .15s;
}
.modal-close:hover { background:var(--red); color:#fff; border-color:var(--red); }
.modal-body { padding:22px 26px; }
.modal-footer { padding:14px 26px; border-top:1px solid var(--border); background:var(--bg2); display:flex; justify-content:flex-end; }
.detail-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px 20px; }
.detail-group { display:flex; flex-direction:column; gap:4px; }
.detail-group.span-2 { grid-column:span 2; }
.detail-label { font-size:10px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:var(--muted); }
.detail-value {
    font-size:13.5px; color:var(--text); font-weight:500;
    padding:8px 10px; background:var(--bg); border:1px solid var(--border);
    border-radius:3px; min-height:36px; display:flex; align-items:center;
}
.detail-value.desc-val { align-items:flex-start; min-height:72px; line-height:1.6; }
.btn-reset {
    padding:9px 22px; background:var(--white); border:1px solid var(--border);
    border-radius:3px; color:var(--muted); font-size:13px; font-weight:600;
    cursor:pointer; font-family:'Source Sans 3',sans-serif; transition:all .2s;
}
.btn-reset:hover { border-color:var(--red); color:var(--red); }

/* ── FOOTER ── */
.gov-footer {
    background:var(--navy); border-top:3px solid var(--gold);
    padding:18px 32px; display:flex; align-items:center; justify-content:space-between;
    font-size:11px; color:#6a88a4; letter-spacing:.04em;
}
.gov-footer strong { color:#a8bcd4; }
.module-divider { border:none; border-top:1px dashed var(--border); margin:8px 0 44px; }

@media (max-width:700px) { .main-wrap{padding:24px 16px 60px;} .top-header{flex-direction:column;gap:10px;padding:14px;} }
</style>
</head>
<body>

<div class="gov-banner">
    <span>🔒 <strong>OFFICIAL GOVERNMENT PORTAL</strong> — Authorized personnel only. All activity is monitored and logged.</span>
    <span>CLASSIFICATION: <strong>RESTRICTED</strong></span>
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
    <a href="dashboard.php">Home</a> &rsaquo; <span>Explorer Cases</span>
</div>

<main class="main-wrap">

    <div class="page-title-bar">
        <h2>
            Explorer Cases
            <small>Click any status card to expand and view its records. Covers Case Registry &amp; Evidence Vault.</small>
        </h2>
        <div class="form-ref">
            <!-- <strong>BEMS/EXP/<?php echo $year; ?></strong><br> -->
            <!-- As of: <strong><?php echo date('d M Y, H:i'); ?></strong> -->
        </div>
    </div>

    <!-- ════════════════════════════════════
         MODULE 1 — CASE REGISTRY
    ════════════════════════════════════ -->
    <div class="module-section">
        <div class="module-header">
            <div class="module-icon">📁</div>
            <div class="module-title-wrap">
                <h3>Case Registry</h3>
                <p>Module 01 — Click a status card to view case records</p>
            </div>
            <div class="module-total"><?php echo $totalCases; ?><small>Total Cases</small></div>
        </div>

        <?php
        $caseCardMap = [
            'Active'    => ['sc-active',    '📋', 'Active'],
            // 'Submitted' => ['sc-submitted', '📤', 'Submitted'],
          'Pending'   => ['sc-pending',   '⏳', 'Pending'],
          'Verified'  => ['sc-verified',  '✅', 'Verified'],
          'Rejected'  => ['sc-rejected',  '❌', 'Rejected'],
            // 'Deleted'   => ['sc-deleted',   '🗑️', 'Deleted'],
        ];
        ?> 
        <div class="status-grid">
            <?php foreach ($caseCardMap as $st => [$cls, $icon, $label]):
                $cnt = $caseCounts[$st] ?? 0;
                $pct = $totalCases ? round(($cnt / $totalCases) * 100, 1) : 0;
            ?>
            <div class="status-card <?php echo $cls; ?>"
                 onclick="togglePanel('case','<?php echo $st; ?>',this)">
                <div class="sc-row">
                    <span class="sc-icon"><?php echo $icon; ?></span>
                    <span class="sc-dot"></span>
                </div>
                <div class="sc-label"><?php echo $label; ?></div>
                <div class="sc-count"><?php echo $cnt; ?></div>
                <div class="sc-pct"><?php echo $pct; ?>% of total</div>
                <div class="click-hint">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                    Click to view
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="table-panel" id="casePanelWrap">
            <div class="table-panel-header">
                <div class="table-panel-title">
                    <span id="casePanelIcon">📋</span>
                    <span id="casePanelTitle">Cases</span>
                    <span class="tpt-badge" id="casePanelBadge">0 Records</span>
                </div>
                <div class="panel-controls">
                    <div class="panel-search-wrap">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="text" id="caseSearch" placeholder="Search…" oninput="searchPanel('case')">
                    </div>
                    <button class="close-panel-btn" onclick="closePanel('case')">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        Close
                    </button>
                </div>
            </div>
            <div class="table-card">
                <div class="table-wrap">
                    <table>
                        <thead><tr>
                            <th>#</th><th>Case ID</th><th>Title</th><th>Type</th>
                            <th>Date of Incident</th><th>Location</th><th>Complainant</th>
                            <th>Phone</th><th>Status</th><th>Action</th>
                        </tr></thead>
                        <tbody id="caseTableBody"></tbody>
                    </table>
                </div>
                <div class="table-footer">
                    <span id="casePagInfo">—</span>
                    <div class="pagination" id="casePagCtrl"></div>
                </div>
            </div>
        </div>
    </div>

    <hr class="module-divider">

    <!-- ════════════════════════════════════
         MODULE 2 — EVIDENCE VAULT
         Cards filter by EvidenceStatus.
         "Deleted" card filters by RecordStatus='Deleted'.
    ════════════════════════════════════ -->
    <div class="module-section">
        <div class="module-header">
            <div class="module-icon">📦</div>
            <div class="module-title-wrap">
                <h3>Evidence Vault</h3>
                <p>Module 02 — Click a status card to view evidence records</p>
            </div>
            <div class="module-total"><?php echo $totalEvidence; ?><small>Total Evidence</small></div>
        </div>

        <?php
        $evidenceCardMap = [
            // 'Active'    => ['sc-active',    '📋', 'Active'],
            // 'Submitted' => ['sc-submitted', '📤', 'Submitted'],
            'Pending'   => ['sc-pending',   '⏳', 'Pending'],
            'Verified'  => ['sc-verified',  '✅', 'Verified'],
            'Rejected'  => ['sc-rejected',  '❌', 'Rejected'],
            // 'Deleted'   => ['sc-deleted',   '🗑️', 'Deleted'],
        ];
        ?>
        <div class="status-grid">
            <?php foreach ($evidenceCardMap as $st => [$cls, $icon, $label]):
                $cnt = $evidenceCounts[$st] ?? 0;
                $pct = $totalEvidence ? round(($cnt / $totalEvidence) * 100, 1) : 0;
            ?>
            <div class="status-card <?php echo $cls; ?>"
                 onclick="togglePanel('evidence','<?php echo $st; ?>',this)">
                <div class="sc-row">
                    <span class="sc-icon"><?php echo $icon; ?></span>
                    <span class="sc-dot"></span>
                </div>
                <div class="sc-label"><?php echo $label; ?></div>
                <div class="sc-count"><?php echo $cnt; ?></div>
                <div class="sc-pct"><?php echo $pct; ?>% of total</div>
                <div class="click-hint">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                    Click to view
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="table-panel" id="evidencePanelWrap">
            <div class="table-panel-header">
                <div class="table-panel-title">
                    <span id="evidencePanelIcon">📦</span>
                    <span id="evidencePanelTitle">Evidence</span>
                    <span class="tpt-badge" id="evidencePanelBadge">0 Records</span>
                </div>
                <div class="panel-controls">
                    <div class="panel-search-wrap">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="text" id="evidenceSearch" placeholder="Search…" oninput="searchPanel('evidence')">
                    </div>
                    <button class="close-panel-btn" onclick="closePanel('evidence')">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        Close
                    </button>
                </div>
            </div>
            <div class="table-card">
                <div class="table-wrap">
                    <table>
                        <thead><tr>
                            <th>#</th><th>Evidence ID</th><th>Case ID</th><th>Type</th>
                            <th>Submitted By</th><th>Authority</th><th>Submission Date</th>
                            <th>Location Recovered</th><th>Evidence Status</th><th>Action</th>
                        </tr></thead>
                        <tbody id="evidenceTableBody"></tbody>
                    </table>
                </div>
                <div class="table-footer">
                    <span id="evidencePagInfo">—</span>
                    <div class="pagination" id="evidencePagCtrl"></div>
                </div>
            </div>
        </div>
    </div>

</main>

<footer class="gov-footer">
    <span>© <?php echo $year; ?> <strong>Ministry of Justice — Digital Forensics Division</strong>. All rights reserved.</span>
    <!-- <span>BEMS Portal v2.0 &nbsp;|&nbsp; Build: 2025.02 &nbsp;|&nbsp; <strong>TLS 1.3 Encrypted</strong></span> -->
</footer>

<!-- CASE MODAL -->
<div class="modal-overlay" id="caseModal">
    <div class="modal">
        <div class="modal-header">
            <h4>Case Details <small id="caseModalSub">—</small></h4>
            <button class="modal-close" onclick="closeModal('caseModal')">&#x2715;</button>
        </div>
        <div class="modal-body">
            <div class="detail-grid">
                <div class="detail-group"><div class="detail-label">Case ID</div><div class="detail-value" id="cm_id">—</div></div>
                <div class="detail-group"><div class="detail-label">Status</div><div class="detail-value" id="cm_status">—</div></div>
                <div class="detail-group"><div class="detail-label">Case Type</div><div class="detail-value" id="cm_type">—</div></div>
                <div class="detail-group"><div class="detail-label">Date of Incident</div><div class="detail-value" id="cm_date">—</div></div>
                <div class="detail-group span-2"><div class="detail-label">Case Title</div><div class="detail-value" id="cm_title">—</div></div>
                <div class="detail-group span-2"><div class="detail-label">Location</div><div class="detail-value" id="cm_location">—</div></div>
                <div class="detail-group span-2"><div class="detail-label">Description</div><div class="detail-value desc-val" id="cm_desc">—</div></div>
                <div class="detail-group"><div class="detail-label">Complainant</div><div class="detail-value" id="cm_name">—</div></div>
                <div class="detail-group"><div class="detail-label">Phone</div><div class="detail-value" id="cm_phone">—</div></div>
                <div class="detail-group span-2"><div class="detail-label">Email</div><div class="detail-value" id="cm_email">—</div></div>
            </div>
        </div>
        <div class="modal-footer"><button class="btn-reset" onclick="closeModal('caseModal')">Close</button></div>
    </div>
</div>

<!-- EVIDENCE MODAL -->
<div class="modal-overlay" id="evidenceModal">
    <div class="modal">
        <div class="modal-header">
            <h4>Evidence Details <small id="evidenceModalSub">—</small></h4>
            <button class="modal-close" onclick="closeModal('evidenceModal')">&#x2715;</button>
        </div>
        <div class="modal-body">
            <div class="detail-grid">
                <div class="detail-group"><div class="detail-label">Evidence ID</div><div class="detail-value" id="em_id">—</div></div>
                <div class="detail-group"><div class="detail-label">Evidence Status</div><div class="detail-value" id="em_evidencestatus">—</div></div>
                <div class="detail-group"><div class="detail-label">Case ID</div><div class="detail-value" id="em_caseid">—</div></div>
                <div class="detail-group"><div class="detail-label">Evidence Type</div><div class="detail-value" id="em_type">—</div></div>
                <div class="detail-group"><div class="detail-label">Submitted By</div><div class="detail-value" id="em_submittedby">—</div></div>
                <div class="detail-group"><div class="detail-label">Authority Name</div><div class="detail-value" id="em_authority">—</div></div>
                <div class="detail-group"><div class="detail-label">Submission Date</div><div class="detail-value" id="em_date">—</div></div>
                <div class="detail-group"><div class="detail-label">Location Recovered</div><div class="detail-value" id="em_location">—</div></div>
                <div class="detail-group"><div class="detail-label">Record Status</div><div class="detail-value" id="em_recordstatus">—</div></div>
                <div class="detail-group span-2"><div class="detail-label">Description</div><div class="detail-value desc-val" id="em_desc">—</div></div>
            </div>
        </div>
        <div class="modal-footer"><button class="btn-reset" onclick="closeModal('evidenceModal')">Close</button></div>
    </div>
</div>

<script>
/* ── DATA ─────────────────────────────────────────────────── */
const allCases = <?php echo json_encode(array_map(function($row){
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
        'caseStatus'         => $r['casestatus']         ?? 'Active',
    ];
}, $allCases), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>;

const allEvidence = <?php echo json_encode(array_map(function($row){
    $r = array_change_key_case($row, CASE_LOWER);
    return [
        'evidenceUId'       => $r['evidenceuid']       ?? '',
        'evidenceId'        => $r['evidenceid']        ?? '',
        'caseId'            => $r['caseid']            ?? '',
        'evidenceType'      => $r['evidencetype']      ?? '',
        'evidenceStatus'    => $r['evidencestatus']    ?? 'Active',   // workflow status
        'description'       => $r['description']       ?? '',
        'submittedBy'       => $r['submittedby']       ?? '',
        'authorityName'     => $r['authorityname']     ?? '',
        'submissionDate'    => $r['submissiondate']    ?? '',
        'locationRecovered' => $r['locationrecovered'] ?? '',
        'recordStatus'      => $r['recordstatus']      ?? 'Active',   // soft-delete flag
    ];
}, $allEvidence), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>;

const PAGE_SIZE = 10;
const ICONS = { Active:'📋', Submitted:'📤', Pending:'⏳', Verified:'✅', Rejected:'❌', Deleted:'🗑️' };

/* ── PANEL STATE ──────────────────────────────────────────── */
const state = {
    case:     { filter: null, filtered: [], page: 1, selectedCard: null },
    evidence: { filter: null, filtered: [], page: 1, selectedCard: null },
};

function togglePanel(module, status, cardEl) {
    const s     = state[module];
    const panel = document.getElementById(module + 'PanelWrap');
    if (s.filter === status && panel.classList.contains('visible')) {
        closePanel(module); return;
    }
    if (s.selectedCard) s.selectedCard.classList.remove('selected');
    s.selectedCard = cardEl;
    cardEl.classList.add('selected');
    s.filter = status;
    s.page   = 1;
    document.getElementById(module + 'Search').value = '';

    if (module === 'case') {
        // Cases: filter by CaseStatus
        s.filtered = allCases.filter(c => (c.caseStatus || 'Active') === status);
    } else {
        // Evidence: "Deleted" card → RecordStatus='Deleted'
        //           all other cards → EvidenceStatus matches AND RecordStatus != 'Deleted'
        if (status === 'Deleted') {
            s.filtered = allEvidence.filter(e => (e.recordStatus || '') === 'Deleted');
        } else {
            s.filtered = allEvidence.filter(e =>
                (e.evidenceStatus || 'Active') === status &&
                (e.recordStatus   || 'Active') !== 'Deleted'
            );
        }
    }

    document.getElementById(module + 'PanelIcon').textContent  = ICONS[status] || '•';
    document.getElementById(module + 'PanelTitle').textContent =
        status + (module === 'case' ? ' Cases' : ' Evidence');

    panel.classList.add('visible');
    panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    renderTable(module);
}

function closePanel(module) {
    document.getElementById(module + 'PanelWrap').classList.remove('visible');
    const s = state[module];
    if (s.selectedCard) { s.selectedCard.classList.remove('selected'); s.selectedCard = null; }
    s.filter = null;
}

function searchPanel(module) {
    const q  = document.getElementById(module + 'Search').value.toLowerCase().trim();
    const s  = state[module];
    const st = s.filter;

    if (module === 'case') {
        const base = allCases.filter(c => (c.caseStatus || 'Active') === st);
        s.filtered = !q ? base : base.filter(c =>
            [c.caseId, c.caseTitle, c.caseType, c.complainantName, c.locationOfIncident]
            .some(v => (v||'').toLowerCase().includes(q))
        );
    } else {
        let base;
        if (st === 'Deleted') {
            base = allEvidence.filter(e => (e.recordStatus||'') === 'Deleted');
        } else {
            base = allEvidence.filter(e =>
                (e.evidenceStatus||'Active') === st &&
                (e.recordStatus||'Active')   !== 'Deleted'
            );
        }
        s.filtered = !q ? base : base.filter(e =>
            [e.evidenceId, e.caseId, e.evidenceType, e.submittedBy, e.authorityName, e.locationRecovered]
            .some(v => (v||'').toLowerCase().includes(q))
        );
    }
    s.page = 1;
    renderTable(module);
}

/* ── TABLE RENDER ─────────────────────────────────────────── */
function renderTable(module) {
    const s          = state[module];
    const total      = s.filtered.length;
    const totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));
    if (s.page > totalPages) s.page = totalPages;
    const start = (s.page - 1) * PAGE_SIZE;
    const end   = Math.min(start + PAGE_SIZE, total);
    const rows  = s.filtered.slice(start, end);

    document.getElementById(module + 'PanelBadge').textContent =
        `${total} Record${total !== 1 ? 's' : ''}`;
    document.getElementById(module + 'PagInfo').textContent =
        total === 0 ? 'No records found' : `Showing ${start+1}–${end} of ${total}`;

    const tbody = document.getElementById(module + 'TableBody');

    if (!total) {
        const emIcon = module === 'case' ? '📋' : '📦';
        tbody.innerHTML = `<tr><td colspan="10">
            <div class="table-empty">
                <div class="empty-icon">${emIcon}</div>
                <p>No records found for this status.</p>
            </div></td></tr>`;
        document.getElementById(module + 'PagCtrl').innerHTML = '';
        return;
    }

    if (module === 'case') {
        tbody.innerHTML = rows.map((c, i) => {
            const ri = allCases.indexOf(c);
            return `<tr>
                <td style="color:var(--muted);font-size:11px;">${start+i+1}</td>
                <td class="td-id">${esc(c.caseId)}</td>
                <td class="td-clip" title="${esc(c.caseTitle)}">${esc(c.caseTitle)}</td>
                <td>${typeBadge(c.caseType)}</td>
                <td style="white-space:nowrap;font-size:12px;">${fmtDate(c.dateOfIncident)}</td>
                <td class="td-clip" title="${esc(c.locationOfIncident)}">${esc(c.locationOfIncident)}</td>
                <td>${esc(c.complainantName)}</td>
                <td style="font-size:11px;color:var(--muted);">${esc(c.complainantPhone)}</td>
                <td><span class="badge badge-${esc(c.caseStatus||'Active')}">${esc(c.caseStatus||'Active')}</span></td>
                <td><div class="action-btns">
                    <button class="icon-btn view-btn" title="View" onclick="openCaseModal(${ri})">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div></td>
            </tr>`;
        }).join('');
    } else {
        tbody.innerHTML = rows.map((e, i) => {
            const ri = allEvidence.indexOf(e);
            // Show EvidenceStatus badge; if deleted show RecordStatus
            const displayStatus = (e.recordStatus === 'Deleted') ? 'Deleted' : (e.evidenceStatus || 'Active');
            return `<tr>
                <td style="color:var(--muted);font-size:11px;">${start+i+1}</td>
                <td class="td-id">${esc(e.evidenceId)}</td>
                <td style="font-size:12px;color:var(--navy);font-weight:600;">${esc(e.caseId)}</td>
                <td style="font-size:12px;">${esc(e.evidenceType)}</td>
                <td>${esc(e.submittedBy)}</td>
                <td style="font-size:12px;color:var(--muted);">${esc(e.authorityName)}</td>
                <td style="font-size:12px;white-space:nowrap;">${fmtDate(e.submissionDate)}</td>
                <td class="td-clip" title="${esc(e.locationRecovered)}">${esc(e.locationRecovered)}</td>
                <td><span class="badge badge-${esc(displayStatus)}">${esc(displayStatus)}</span></td>
                <td><div class="action-btns">
                    <button class="icon-btn view-btn" title="View" onclick="openEvidenceModal(${ri})">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div></td>
            </tr>`;
        }).join('');
    }

    renderPag(module + 'PagCtrl', totalPages, s.page, module);
}

function renderPag(ctrlId, totalPages, current, module) {
    const ctrl = document.getElementById(ctrlId);
    if (totalPages <= 1) { ctrl.innerHTML = ''; return; }
    let h = `<button class="page-btn" ${current===1?'disabled':''} onclick="goPage('${module}',${current-1})">&#8249;</button>`;
    for (let p = 1; p <= totalPages; p++)
        h += `<button class="page-btn ${p===current?'active':''}" onclick="goPage('${module}',${p})">${p}</button>`;
    h += `<button class="page-btn" ${current===totalPages?'disabled':''} onclick="goPage('${module}',${current+1})">&#8250;</button>`;
    ctrl.innerHTML = h;
}

function goPage(module, p) {
    const s = state[module];
    const t = Math.ceil(s.filtered.length / PAGE_SIZE);
    if (p < 1 || p > t) return;
    s.page = p; renderTable(module);
}

/* ── MODALS ───────────────────────────────────────────────── */
function openCaseModal(idx) {
    const c = allCases[idx];
    document.getElementById('caseModalSub').textContent = c.caseId;
    document.getElementById('cm_id').textContent        = c.caseId;
    document.getElementById('cm_status').innerHTML      = `<span class="badge badge-${c.caseStatus||'Active'}">${c.caseStatus||'Active'}</span>`;
    document.getElementById('cm_type').textContent      = c.caseType;
    document.getElementById('cm_date').textContent      = fmtDate(c.dateOfIncident);
    document.getElementById('cm_title').textContent     = c.caseTitle;
    document.getElementById('cm_location').textContent  = c.locationOfIncident;
    document.getElementById('cm_desc').textContent      = c.caseDescription;
    document.getElementById('cm_name').textContent      = c.complainantName;
    document.getElementById('cm_phone').textContent     = c.complainantPhone;
    document.getElementById('cm_email').textContent     = c.complainantEmail;
    openModal('caseModal');
}

function openEvidenceModal(idx) {
    const e = allEvidence[idx];
    document.getElementById('evidenceModalSub').textContent       = e.evidenceId;
    document.getElementById('em_id').textContent                  = e.evidenceId;
    document.getElementById('em_evidencestatus').innerHTML        = `<span class="badge badge-${esc(e.evidenceStatus||'Active')}">${esc(e.evidenceStatus||'Active')}</span>`;
    document.getElementById('em_caseid').textContent              = e.caseId;
    document.getElementById('em_type').textContent                = e.evidenceType;
    document.getElementById('em_submittedby').textContent         = e.submittedBy;
    document.getElementById('em_authority').textContent           = e.authorityName;
    document.getElementById('em_date').textContent                = fmtDate(e.submissionDate);
    document.getElementById('em_location').textContent            = e.locationRecovered;
    document.getElementById('em_recordstatus').innerHTML          = `<span class="badge badge-${esc(e.recordStatus||'Active')}">${esc(e.recordStatus||'Active')}</span>`;
    document.getElementById('em_desc').textContent                = e.description;
    openModal('evidenceModal');
}

function openModal(id)  { document.getElementById(id).classList.add('open');    document.body.style.overflow='hidden'; }
function closeModal(id) { document.getElementById(id).classList.remove('open'); document.body.style.overflow=''; }

document.querySelectorAll('.modal-overlay').forEach(o => {
    o.addEventListener('click', e => { if (e.target===o) closeModal(o.id); });
});
document.addEventListener('keydown', e => {
    if (e.key==='Escape') document.querySelectorAll('.modal-overlay.open').forEach(m=>closeModal(m.id));
});

/* ── HELPERS ──────────────────────────────────────────────── */
function typeBadge(t) {
    const m = { Criminal:['badge-criminal','🔴 Criminal'], Civil:['badge-civil','🔵 Civil'], CyberCrime:['badge-cyber','🟢 Cyber Crime'] };
    const [cls,label] = m[t] || ['',''];
    return `<span class="type-badge ${cls}">${label||esc(t)}</span>`;
}
function fmtDate(d) {
    if (!d) return '—';
    const p = d.split('-');
    if (p.length<3) return d;
    const mo = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return `${parseInt(p[2])} ${mo[parseInt(p[1])-1]} ${p[0]}`;
}
function esc(s) {
    return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>

</body>
</html>