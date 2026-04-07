<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: index.html");
    exit();
}

include_once("db.php");

// ── Live counts from DB ───────────────────────────────────────────────────────
$totalCases    = 0;
$totalEvidence = 0;

$r1 = mysqli_query($conn, "SELECT COUNT(*) AS total FROM tblcaseregister WHERE CaseStatus != 'Deleted'");
if ($r1) $totalCases = (int) mysqli_fetch_assoc($r1)['total'];

$r2 = mysqli_query($conn, "SELECT COUNT(*) AS total FROM tblevidence WHERE RecordStatus != 'Deleted'");
if ($r2) $totalEvidence = (int) mysqli_fetch_assoc($r2)['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BEMS — Evidence Management Portal</title>
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
        }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Source Sans 3', sans-serif;
            min-height: 100vh;
        }

        /* ─── HEADER BANNER ─── */
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

        .top-header {
            background: var(--navy2);
            border-bottom: 4px solid var(--gold);
            padding: 0 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 80px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .seal {
            width: 56px; height: 56px;
            border-radius: 50%;
            border: 2px solid var(--gold);
            background: var(--navy);
            display: flex; align-items: center; justify-content: center;
            font-size: 24px; flex-shrink: 0;
        }

        .header-titles h1 {
            font-family: 'Source Serif 4', serif;
            font-size: 18px; font-weight: 700;
            color: #fff; letter-spacing: 0.01em; line-height: 1.2;
        }
        .header-titles p {
            font-size: 11px; color: #a8bcd4;
            letter-spacing: 0.08em; text-transform: uppercase; margin-top: 2px;
        }

        .header-right { display: flex; align-items: center; gap: 24px; }

        .user-pill {
            display: flex; align-items: center; gap: 10px;
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.12);
            padding: 8px 16px; border-radius: 4px;
        }
        .user-pill .avatar {
            width: 30px; height: 30px;
            background: var(--gold); border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 700; color: var(--navy);
        }
        .user-pill .name  { font-size: 13px; color: #e0e8f0; font-weight: 600; }
        .user-pill .role  { font-size: 10px; color: #7a96b0; letter-spacing: 0.06em; text-transform: uppercase; }

        .logout-btn {
            display: flex; align-items: center; gap: 7px;
            padding: 9px 20px; background: transparent;
            border: 1px solid rgba(200,151,42,0.5); border-radius: 4px;
            color: var(--gold-lt); font-size: 12px; font-weight: 600;
            letter-spacing: 0.06em; text-transform: uppercase;
            text-decoration: none; transition: all 0.2s;
        }
        .logout-btn:hover { background: var(--gold); border-color: var(--gold); color: var(--navy); }

        /* ─── BREADCRUMB ─── */
        .breadcrumb-bar {
            background: var(--bg2); border-bottom: 1px solid var(--border);
            padding: 8px 32px; font-size: 12px; color: var(--muted); letter-spacing: 0.03em;
        }
        .breadcrumb-bar span { color: var(--navy); font-weight: 600; }

        /* ─── MAIN ─── */
        .main-wrap { max-width: 1200px; margin: 0 auto; padding: 40px 32px 80px; }

        /* ─── SECTION HEADER ─── */
        .section-header {
            display: flex; align-items: flex-end; justify-content: space-between;
            margin-bottom: 32px; padding-bottom: 20px; border-bottom: 2px solid var(--border);
        }
        .section-header h2 {
            font-family: 'Source Serif 4', serif;
            font-size: 26px; font-weight: 700; color: var(--navy); letter-spacing: -0.01em;
        }
        .section-header h2 small {
            display: block; font-family: 'Source Sans 3', sans-serif;
            font-size: 13px; font-weight: 400; color: var(--muted); margin-top: 4px; letter-spacing: 0.02em;
        }
        .date-badge { font-size: 12px; color: var(--muted); text-align: right; line-height: 1.6; }
        .date-badge strong { color: var(--navy); display: block; font-size: 14px; }

        /* ─── STAT STRIP ─── */
        .stat-strip {
            display: grid; grid-template-columns: repeat(4, 1fr);
            gap: 1px; background: var(--border);
            border: 1px solid var(--border); border-radius: 6px;
            overflow: hidden; margin-bottom: 36px;
        }
        .stat-item {
            background: var(--white); padding: 18px 22px;
            display: flex; align-items: center; gap: 14px;
        }
        .stat-icon {
            width: 40px; height: 40px; border-radius: 4px;
            background: var(--bg2); border: 1px solid var(--border);
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; flex-shrink: 0;
        }
        .stat-label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.1em; color: var(--muted); margin-bottom: 2px; }
        .stat-value { font-size: 22px; font-weight: 700; color: var(--navy); font-family: 'Source Serif 4', serif; line-height: 1; }

        /* ─── FEATURE CARDS ─── */
        .cards-grid {
            display: grid; grid-template-columns: repeat(3, 1fr);
            gap: 20px; margin-bottom: 36px;
        }

        @media (max-width: 860px) {
            .cards-grid { grid-template-columns: 1fr; }
            .stat-strip { grid-template-columns: repeat(2, 1fr); }
            .top-header { flex-direction: column; gap: 12px; padding: 16px; text-align: center; }
            .header-right { flex-direction: column; gap: 8px; }
        }

        .feat-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-top: 4px solid var(--navy);
            border-radius: 4px;
            padding: 28px 24px 24px;
            text-decoration: none; color: inherit;
            display: flex; flex-direction: column;
            transition: border-top-color 0.2s, box-shadow 0.2s, transform 0.2s;
        }
        .feat-card:hover {
            border-top-color: var(--gold);
            box-shadow: 0 6px 24px rgba(13,34,64,0.1);
            transform: translateY(-2px);
        }

        .feat-card .card-ref {
            font-size: 10px; letter-spacing: 0.15em;
            text-transform: uppercase; color: var(--muted); margin-bottom: 14px;
        }
        .feat-card .card-icon-wrap {
            width: 48px; height: 48px;
            background: var(--bg2); border: 1px solid var(--border); border-radius: 4px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; margin-bottom: 16px;
        }
        .feat-card h3 {
            font-family: 'Source Serif 4', serif;
            font-size: 18px; font-weight: 700; color: var(--navy); margin-bottom: 8px;
        }
        .feat-card p {
            font-size: 13.5px; color: var(--muted);
            line-height: 1.65; flex: 1; margin-bottom: 22px;
        }
        .feat-card .card-access {
            display: flex; align-items: center; justify-content: space-between;
            border-top: 1px solid var(--border); padding-top: 14px;
        }
        .card-access-label {
            font-size: 11px; text-transform: uppercase;
            letter-spacing: 0.1em; color: var(--navy); font-weight: 600;
        }
        .card-access-btn {
            display: flex; align-items: center; gap: 6px;
            background: var(--navy); color: #fff;
            font-size: 12px; font-weight: 600; letter-spacing: 0.06em;
            text-transform: uppercase; padding: 7px 16px; border-radius: 3px;
            transition: background 0.2s;
        }
        .feat-card:hover .card-access-btn { background: var(--gold); color: var(--navy); }

        /* ─── NOTICE ─── */
        .notice {
            background: #fffbeb; border: 1px solid #e6d28a;
            border-left: 4px solid var(--gold); border-radius: 4px;
            padding: 14px 20px; display: flex; align-items: flex-start; gap: 12px;
            font-size: 13px; color: #6b5a1e; line-height: 1.6;
        }

        /* ─── FOOTER ─── */
        .gov-footer {
            background: var(--navy); border-top: 3px solid var(--gold);
            padding: 20px 32px; display: flex; align-items: center;
            justify-content: space-between; font-size: 11px; color: #6a88a4; letter-spacing: 0.04em;
        }
        .gov-footer strong { color: #a8bcd4; }
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
    <div class="header-right">
        <div class="user-pill">
            <div class="avatar"><?php echo strtoupper(substr(htmlspecialchars($_SESSION['user']), 0, 1)); ?></div>
            <div>
                <div class="name"><?php echo htmlspecialchars($_SESSION['user']); ?></div>
                <div class="role">Authorized Officer</div>
            </div>
        </div>
        <a href="logout.php" class="logout-btn">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            Sign Out
        </a>
    </div>
</header>

<div class="breadcrumb-bar">
    Home &rsaquo; <span>Dashboard</span>
</div>

<main class="main-wrap">

    <div class="section-header">
        <h2>
            Officer Dashboard
            <small>Select a module below to proceed. All actions are audit-logged on the blockchain.</small>
        </h2>
        <div class="date-badge">
            <strong><?php echo date("d F Y"); ?></strong>
        </div>
    </div>

    <!-- LIVE STAT STRIP -->
    <div class="stat-strip">
        <div class="stat-item">
            <div class="stat-icon">🔗</div>
            <div>
                <div class="stat-label">Chain Status</div>
                <div class="stat-value" style="color:#186b2e;font-size:14px;font-family:'Source Sans 3',sans-serif;font-weight:700;margin-top:3px;">● Online</div>
            </div>
        </div>
        <div class="stat-item">
            <div class="stat-icon">📁</div>
            <div>
                <div class="stat-label">Total Cases</div>
                <div class="stat-value"><?php echo $totalCases; ?></div>
            </div>
        </div>
        <div class="stat-item">
            <div class="stat-icon">📦</div>
            <div>
                <div class="stat-label">Evidence Items</div>
                <div class="stat-value"><?php echo $totalEvidence; ?></div>
            </div>
        </div>
        <div class="stat-item">
            <div class="stat-icon">🔐</div>
            <div>
                <div class="stat-label">Integrity</div>
                <div class="stat-value" style="color:#186b2e;font-size:14px;font-family:'Source Sans 3',sans-serif;font-weight:700;margin-top:3px;">Verified</div>
            </div>
        </div>
    </div>

    <div class="cards-grid">

        <a href="Register.php" class="feat-card">
            <div class="card-ref">Module 01 &mdash; Case Registry</div>
            <div class="card-icon-wrap">📁</div>
            <h3>Register Case</h3>
            <p>Initiate a new legal case record. The case metadata will be hashed and anchored to the blockchain with an immutable timestamp.</p>
            <div class="card-access">
                <span class="card-access-label">Access Module</span>
                <span class="card-access-btn">Proceed &rarr;</span>
            </div>
        </a>

        <a href="submit_evidence.php" class="feat-card">
            <div class="card-ref">Module 02 &mdash; Evidence Vault</div>
            <div class="card-icon-wrap">📤</div>
            <h3>Submit Evidence</h3>
            <p>Upload and register digital evidence files. Each submission is cryptographically sealed and assigned a verified on-chain hash.</p>
            <div class="card-access">
                <span class="card-access-label">Access Module</span>
                <span class="card-access-btn">Proceed &rarr;</span>
            </div>
        </a>

        <!-- ✅ FIXED: was href="#" — now points to explorer_cases.php -->
        <a href="explorer_cases.php" class="feat-card">
            <div class="card-ref">Module 03 &mdash; Case Explorer</div>
            <div class="card-icon-wrap">🔍</div>
            <h3>Explore Cases</h3>
            <p>Browse all registered cases, verify chain-of-custody integrity, and retrieve full audit trails for individual evidence items.</p>
            <div class="card-access">
                <span class="card-access-label">Access Module</span>
                <span class="card-access-btn">Proceed &rarr;</span>
            </div>
        </a>

    </div>

</main>

<footer class="gov-footer">
    <span>© <?php echo date("Y"); ?> <strong>Ministry of Justice — Digital Forensics Division</strong>. All rights reserved.</span>
    <!-- <span>BEMS Portal v2.0 &nbsp;|&nbsp; Build: 2025.02 &nbsp;|&nbsp; <strong>TLS 1.3 Encrypted</strong></span> -->
</footer>

    <div class="stat-strip">
        <div class="stat-item">
            <div class="stat-icon">🔗</div>
            <div>
                <div class="stat-label">Chain Status</div>
                <div class="stat-value" style="color:#186b2e;font-size:14px;font-family:'Source Sans 3',sans-serif;font-weight:700;margin-top:3px;">● Online</div>
            </div>
        </div>
        <div class="stat-item">
            <div class="stat-icon">📁</div>
            <div>
                <div class="stat-label">Total Cases</div>
                <div class="stat-value"><?php echo $totalCases; ?></div>
            </div>
        </div>
        <div class="stat-item">
            <div class="stat-icon">📦</div>
            <div>
                <div class="stat-label">Evidence Items</div>
                <div class="stat-value"><?php echo $totalEvidence; ?></div>
            </div>
        </div>
        <div class="stat-item">
            <div class="stat-icon">🔐</div>
            <div>
                <div class="stat-label">Integrity</div>
                <div class="stat-value" style="color:#186b2e;font-size:14px;font-family:'Source Sans 3',sans-serif;font-weight:700;margin-top:3px;">Verified</div>
            </div>
        </div>
    </div>

    <div class="cards-grid">

        <a href="Register.php" class="feat-card">
            <div class="card-ref">Module 01 &mdash; Case Registry</div>
            <div class="card-icon-wrap">📁</div>
            <h3>Register Case</h3>
            <p>Initiate a new legal case record. The case metadata will be hashed and anchored to the blockchain with an immutable timestamp.</p>
            <div class="card-access">
                <span class="card-access-label">Access Module</span>
                <span class="card-access-btn">Proceed &rarr;</span>
            </div>
        </a>

        <a href="submit_evidence.php" class="feat-card">
            <div class="card-ref">Module 02 &mdash; Evidence Vault</div>
            <div class="card-icon-wrap">📤</div>
            <h3>Submit Evidence</h3>
            <p>Upload and register digital evidence files. Each submission is cryptographically sealed and assigned a verified on-chain hash.</p>
            <div class="card-access">
                <span class="card-access-label">Access Module</span>
                <span class="card-access-btn">Proceed &rarr;</span>
            </div>
        </a>

        <!-- ✅ FIXED: was href="#" — now points to explorer_cases.php -->
        <a href="explorer_cases.php" class="feat-card">
            <div class="card-ref">Module 03 &mdash; Case Explorer</div>
            <div class="card-icon-wrap">🔍</div>
            <h3>Explore Cases</h3>
            <p>Browse all registered cases, verify chain-of-custody integrity, and retrieve full audit trails for individual evidence items.</p>
            <div class="card-access">
                <span class="card-access-label">Access Module</span>
                <span class="card-access-btn">Proceed &rarr;</span>
            </div>
        </a>

    </div>
</body>
</html>
