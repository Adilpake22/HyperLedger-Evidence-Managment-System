<?php
require_once 'db.php';
require_once 'Blockchain.php';

// Get all ACTIVE evidence (original submission blocks)
$sql    = "SELECT * FROM tblevidence WHERE RecordStatus='Active' ORDER BY CreatedAt ASC";
$result = $conn->query($sql);

// Get all modification blocks from history table
$histSql    = "SELECT * FROM tblevidencehistory ORDER BY CreatedAt ASC";
$histResult = $conn->query($histSql);
$histBlocks = [];
if ($histResult) {
    while ($h = $histResult->fetch_assoc()) $histBlocks[] = $h;
}

// Genesis Block
$chain        = new EvidenceBlockchain();
$genesisBlock = $chain->chain[0];

// Total blocks = 1 genesis + evidence submissions + modification blocks
$totalEvidRows = $result->num_rows;
$totalModRows  = count($histBlocks);
$totalBlocks   = 1 + $totalEvidRows + $totalModRows;

// ── Pre-scan chain status ─────────────────────────────────────────
$chainTampered = false;
$tempResult    = $conn->query("SELECT BlockchainHash, PreviousHash FROM tblevidence WHERE RecordStatus='Active' ORDER BY CreatedAt ASC");
$scanPrev      = $genesisBlock->hash;
while ($scanRow = $tempResult->fetch_assoc()) {
    if ($scanRow['PreviousHash'] !== $scanPrev) {
        $chainTampered = true;
        break;
    }
    $scanPrev = $scanRow['BlockchainHash'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Blockchain Evidence Explorer</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  :root {
    --navy:        #0a1628; --navy2:       #0d1f3c; --navy3: #122448;
    --gold:        #c8963e; --gold-light:  #e8b96a; --gold-pale: #f5dfa0;
    --cream:       #f4f0e6; --cream2:      #ede8da;
    --green:       #1a6b3c; --green-light: #2d9e5f;
    --red:         #8b1a1a; --red-bright:  #ff4444;
    --purple:      #4a1a7a; --purple-light:#7a4aaa;
    --text-dark:   #1a1a2a; --text-mid: #4a4a6a; --text-light: #8a8aaa;
    --border:      #c8963e44; --shadow: 0 4px 24px rgba(0,0,0,0.18);
  }
  body { background: var(--cream); color: var(--text-dark); font-family: 'Georgia','Times New Roman',serif; min-height: 100vh; }

  .top-banner { background:var(--navy); padding:6px 0; text-align:center; font-size:11px; color:var(--gold-light); letter-spacing:2px; font-family:'Arial',sans-serif; border-bottom:2px solid var(--gold); }

  header { background:linear-gradient(135deg,var(--navy),var(--navy2) 60%,var(--navy3)); padding:28px 40px; display:flex; align-items:center; gap:28px; border-bottom:3px solid var(--gold); }
  .emblem { width:72px; height:72px; background:var(--gold); border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:36px; flex-shrink:0; border:3px solid var(--gold-light); }
  .header-dept  { font-size:11px; letter-spacing:4px; color:var(--gold-light); text-transform:uppercase; font-family:'Arial',sans-serif; margin-bottom:4px; }
  .header-title { font-size:clamp(18px,3vw,26px); color:#fff; font-weight:bold; }
  .header-sub   { font-size:12px; color:var(--gold-pale); font-family:'Arial',sans-serif; letter-spacing:1px; margin-top:4px; }
  .header-badge { background:rgba(200,150,62,0.15); border:1px solid var(--gold); border-radius:6px; padding:10px 18px; text-align:center; flex-shrink:0; margin-left:auto; }
  .badge-num   { font-size:28px; font-weight:bold; color:var(--gold-light); display:block; line-height:1; }
  .badge-label { font-size:10px; color:var(--gold-pale); letter-spacing:2px; text-transform:uppercase; font-family:'Arial',sans-serif; margin-top:4px; }

  .nav-bar { background:var(--navy2); display:flex; align-items:center; padding:0 40px; border-bottom:2px solid var(--gold); font-family:'Arial',sans-serif; }
  .nav-item { padding:12px 20px; font-size:12px; letter-spacing:1px; color:var(--gold-pale); text-transform:uppercase; cursor:pointer; border-bottom:3px solid transparent; }
  .nav-item.active { border-bottom-color:var(--gold); color:var(--gold-light); }

  main { max-width:1100px; margin:0 auto; padding:32px 20px; }

  /* Stats */
  .stats-row { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:16px; margin-bottom:36px; }
  .stat-card { background:#fff; border:1px solid var(--border); border-top:3px solid var(--gold); border-radius:6px; padding:16px 20px; display:flex; align-items:center; gap:14px; }
  .stat-card.tampered-card { border-top-color:var(--red-bright); background:#fff5f5; }
  .stat-icon { width:44px; height:44px; border-radius:8px; background:var(--navy); display:flex; align-items:center; justify-content:center; font-size:20px; flex-shrink:0; }
  .stat-val  { font-size:24px; font-weight:bold; color:var(--navy); line-height:1; }
  .stat-val.tampered { color:var(--red-bright); font-size:18px; }
  .stat-lbl  { font-size:10px; color:var(--text-mid); letter-spacing:1px; text-transform:uppercase; font-family:'Arial',sans-serif; margin-top:3px; }

  /* Section heading */
  .section-heading { display:flex; align-items:center; gap:14px; margin-bottom:20px; padding-bottom:12px; border-bottom:2px solid var(--cream2); }
  .section-heading-icon { width:36px; height:36px; background:var(--navy); border-radius:6px; display:flex; align-items:center; justify-content:center; font-size:18px; }
  .section-heading-title { font-size:16px; font-weight:bold; color:var(--navy); letter-spacing:1px; text-transform:uppercase; font-family:'Arial',sans-serif; }
  .section-heading-sub   { font-size:11px; color:var(--text-light); font-family:'Arial',sans-serif; margin-top:2px; }
  .section-badge { margin-left:auto; background:var(--navy); color:var(--gold-light); font-size:10px; letter-spacing:2px; padding:4px 12px; border-radius:20px; font-family:'Arial',sans-serif; text-transform:uppercase; }

  /* Block cards */
  .block-card { background:#fff; border:1px solid var(--border); border-radius:8px; margin-bottom:16px; box-shadow:var(--shadow); overflow:hidden; }
  .block-card.tampered-block { border:2px solid var(--red-bright); box-shadow:0 4px 24px rgba(255,68,68,0.25); }
  /* MODIFICATION blocks get a purple accent */
  .block-card.modify-block  { border-top:3px solid var(--purple); }

  /* Card header */
  .card-header { background:linear-gradient(90deg,var(--navy),var(--navy3)); padding:14px 20px; display:flex; align-items:center; gap:14px; border-bottom:2px solid var(--gold); }
  .card-header.genesis-header  { background:linear-gradient(90deg,#2a1800,#3d2800); }
  .card-header.tampered-header { background:linear-gradient(90deg,#3a0000,#5a0000); border-bottom-color:var(--red-bright); }
  .card-header.modify-header   { background:linear-gradient(90deg,#1a0a2e,#2e1050); border-bottom-color:var(--purple-light); }

  .block-num { width:38px; height:38px; border-radius:50%; border:2px solid var(--gold); display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:bold; color:var(--gold-light); flex-shrink:0; font-family:'Arial',sans-serif; }
  .block-num.tampered-num { border-color:var(--red-bright); color:var(--red-bright); }
  .block-num.modify-num   { border-color:var(--purple-light); color:#c8aaff; }

  .card-header-title { font-size:15px; font-weight:bold; color:#fff; letter-spacing:1px; font-family:'Arial',sans-serif; }
  .card-header-sub   { font-size:10px; color:var(--gold-pale); font-family:'Arial',sans-serif; letter-spacing:1px; margin-top:2px; }

  .card-status { margin-left:auto; background:rgba(26,107,60,0.3); border:1px solid var(--green-light); color:#6dffaa; font-size:10px; letter-spacing:2px; padding:4px 12px; border-radius:20px; font-family:'Arial',sans-serif; text-transform:uppercase; }
  .card-status.tampered-status { background:rgba(255,68,68,0.2); border-color:var(--red-bright); color:var(--red-bright); animation:blink 1.4s ease-in-out infinite; }
  .card-status.modify-status   { background:rgba(122,74,170,0.3); border-color:var(--purple-light); color:#c8aaff; }
  @keyframes blink { 0%,100%{opacity:1} 50%{opacity:.5} }

  /* Mini cards */
  .mini-cards { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:12px; padding:18px; }
  .mini-card { background:var(--cream); border:1px solid var(--cream2); border-left:3px solid var(--gold); border-radius:6px; padding:12px 14px; }
  .mini-card.hash-card   { border-left-color:var(--green);        background:#f0f8f4; grid-column:1/-1; }
  .mini-card.prev-card   { border-left-color:var(--red);          background:#fdf4f0; grid-column:1/-1; }
  .mini-card.alert-card  { border-left-color:var(--red-bright);   background:#fff0f0; grid-column:1/-1; }
  .mini-card.reason-card { border-left-color:var(--purple-light); background:#f5f0ff; grid-column:1/-1; }
  .mini-card.diff-card   { border-left-color:var(--purple);       background:#f0eaff; grid-column:1/-1; }

  .mini-card-label { font-size:9px; letter-spacing:2px; text-transform:uppercase; color:var(--text-light); font-family:'Arial',sans-serif; margin-bottom:6px; display:flex; align-items:center; gap:6px; }
  .mini-card-label::before { content:''; width:6px; height:6px; border-radius:50%; background:var(--gold); flex-shrink:0; }
  .mini-card.hash-card   .mini-card-label::before { background:var(--green); }
  .mini-card.prev-card   .mini-card-label::before { background:var(--red); }
  .mini-card.alert-card  .mini-card-label::before { background:var(--red-bright); }
  .mini-card.reason-card .mini-card-label::before { background:var(--purple-light); }
  .mini-card.diff-card   .mini-card-label::before { background:var(--purple); }

  .mini-card-value { font-size:13px; color:var(--text-dark); font-family:'Georgia',serif; word-break:break-all; line-height:1.5; }
  .mini-card.hash-card  .mini-card-value { font-family:'Courier New',monospace; font-size:12px; color:var(--green); font-weight:bold; }
  .mini-card.prev-card  .mini-card-value { font-family:'Courier New',monospace; font-size:12px; color:var(--red); font-weight:bold; }
  .mini-card.alert-card .mini-card-value { font-family:'Courier New',monospace; font-size:11px; color:#8b0000; line-height:1.9; }

  .hash-mismatch { color:var(--red-bright) !important; text-decoration:line-through; opacity:.8; }
  .hash-correct  { color:var(--green) !important; }

  /* Diff table inside diff-card */
  .diff-table { width:100%; border-collapse:collapse; font-size:12px; font-family:'Arial',sans-serif; margin-top:6px; }
  .diff-table th { background:rgba(74,26,122,0.1); padding:6px 10px; text-align:left; color:var(--purple); font-size:10px; letter-spacing:1px; text-transform:uppercase; border:1px solid rgba(74,26,122,0.15); }
  .diff-table td { padding:6px 10px; border:1px solid rgba(74,26,122,0.1); vertical-align:top; }
  .diff-old { color:var(--red); text-decoration:line-through; opacity:.8; }
  .diff-new { color:var(--green); font-weight:bold; }

  /* Chain connector */
  .chain-connector { display:flex; align-items:center; justify-content:center; padding:4px 0; gap:12px; }
  .chain-line { width:2px; height:32px; background:linear-gradient(180deg,var(--gold),var(--navy)); }
  .chain-line.broken { background:linear-gradient(180deg,var(--red-bright),#8b0000); }
  .chain-line.modify-line { background:linear-gradient(180deg,var(--purple-light),var(--purple)); }
  .chain-arrow-label { font-size:10px; color:var(--gold); letter-spacing:2px; font-family:'Arial',sans-serif; text-transform:uppercase; }
  .chain-arrow-label.broken { color:var(--red-bright); }
  .chain-arrow-label.modify { color:var(--purple-light); }

  /* Empty state */
  .empty-state { background:#fff; border:1px solid var(--border); border-radius:8px; padding:48px; text-align:center; }
  .empty-state-icon  { font-size:48px; margin-bottom:16px; }
  .empty-state-title { font-size:16px; color:var(--navy); font-weight:bold; font-family:'Arial',sans-serif; }

  footer { background:var(--navy); border-top:3px solid var(--gold); padding:20px 40px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-top:40px; }
  .footer-left  { font-size:11px; color:var(--gold-pale); font-family:'Arial',sans-serif; }
  .footer-right { font-size:10px; color:#ffffff44; font-family:'Arial',sans-serif; letter-spacing:2px; text-transform:uppercase; }
</style>
</head>
<body>

<div class="top-banner">GOVERNMENT OF INDIA &nbsp;|&nbsp; MINISTRY OF HOME AFFAIRS &nbsp;|&nbsp; DIGITAL EVIDENCE MANAGEMENT SYSTEM</div>

<header>
  <div class="emblem">⚖️</div>
  <div>
    <div class="header-dept">Ministry of Home Affairs — Cyber &amp; Digital Forensics Division</div>
    <div class="header-title">Blockchain Evidence Explorer</div>
    <div class="header-sub">Immutable Ledger &nbsp;•&nbsp; SHA-256 Secured &nbsp;•&nbsp; Full Audit Trail</div>
  </div>
  <div class="header-badge">
    <span class="badge-num"><?= $totalBlocks ?></span>
    <div class="badge-label">Total Blocks</div>
  </div>
</header>

<div class="nav-bar">
  <div class="nav-item active">🔗 Chain Explorer</div>
  <div class="nav-item" style="color:#6a88a4;">📋 Evidence Records</div>
  <div class="nav-item" style="color:#6a88a4;">📁 Case Management</div>
</div>

<main>

  <!-- Stats -->
  <div class="stats-row">
    <div class="stat-card">
      <div class="stat-icon">⛓️</div>
      <div><div class="stat-val"><?= $totalBlocks ?></div><div class="stat-lbl">Total Blocks</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">🗂️</div>
      <div><div class="stat-val"><?= $totalEvidRows ?></div><div class="stat-lbl">Evidence Submissions</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">✏️</div>
      <div><div class="stat-val"><?= $totalModRows ?></div><div class="stat-lbl">Modification Blocks</div></div>
    </div>
    <div class="stat-card <?= $chainTampered ? 'tampered-card' : '' ?>">
      <div class="stat-icon"><?= $chainTampered ? '⚠️' : '✅' ?></div>
      <div>
        <div class="stat-val <?= $chainTampered ? 'tampered' : '' ?>"><?= $chainTampered ? '⚠ TAMPERED' : 'Valid' ?></div>
        <div class="stat-lbl">Chain Status</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">🔐</div>
      <div><div class="stat-val">SHA-256</div><div class="stat-lbl">Algorithm</div></div>
    </div>
  </div>

  <!-- Legend -->
  <div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:24px;font-family:'Arial',sans-serif;font-size:11px;">
    <div style="display:flex;align-items:center;gap:6px;"><span style="width:14px;height:14px;background:var(--gold);border-radius:3px;display:inline-block;"></span> Submission Block</div>
    <div style="display:flex;align-items:center;gap:6px;"><span style="width:14px;height:14px;background:var(--purple-light);border-radius:3px;display:inline-block;"></span> Modification Block</div>
    <div style="display:flex;align-items:center;gap:6px;"><span style="width:14px;height:14px;background:var(--red-bright);border-radius:3px;display:inline-block;"></span> Tampered / Chain Break</div>
  </div>

  <!-- Section heading -->
  <div class="section-heading">
    <div class="section-heading-icon">🔗</div>
    <div>
      <div class="section-heading-title">Blockchain Ledger</div>
      <div class="section-heading-sub">Purple blocks = modifications. Each edit creates a new block linked to the previous hash.</div>
    </div>
    <div class="section-badge"><?= $chainTampered ? '⚠ CHAIN BROKEN' : '🔒 Secured' ?></div>
  </div>

  <!-- ══ GENESIS BLOCK ══ -->
  <div class="block-card">
    <div class="card-header genesis-header">
      <div class="block-num">00</div>
      <div>
        <div class="card-header-title">🌐 Genesis Block</div>
        <div class="card-header-sub">ORIGIN BLOCK — ROOT OF THE CHAIN</div>
      </div>
      <div class="card-status">✔ Verified</div>
    </div>
    <div class="mini-cards">
      <div class="mini-card"><div class="mini-card-label">Block Index</div><div class="mini-card-value">0 (Genesis)</div></div>
      <div class="mini-card"><div class="mini-card-label">Timestamp</div><div class="mini-card-value"><?= date('d M Y — H:i:s', $genesisBlock->timestamp) ?></div></div>
      <div class="mini-card"><div class="mini-card-label">Algorithm</div><div class="mini-card-value">SHA-256</div></div>
      <div class="mini-card hash-card"><div class="mini-card-label">🔐 Block Hash</div><div class="mini-card-value"><?= htmlspecialchars($genesisBlock->hash) ?></div></div>
      <div class="mini-card prev-card"><div class="mini-card-label">⛓️ Previous Hash</div><div class="mini-card-value">0000…0000 (No Previous Block)</div></div>
    </div>
  </div>

  <?php
  // ══════════════════════════════════════════════════════════
  //  Build a merged timeline:
  //  evidence submissions + modification blocks, sorted by CreatedAt
  // ══════════════════════════════════════════════════════════
  $timeline = [];

  $result->data_seek(0);
  while ($row = $result->fetch_assoc()) {
      $timeline[] = ['type' => 'SUBMISSION', 'data' => $row, 'ts' => $row['CreatedAt']];
  }
  foreach ($histBlocks as $h) {
      $timeline[] = ['type' => 'MODIFICATION', 'data' => $h, 'ts' => $h['CreatedAt']];
  }

  // Sort chronologically
  usort($timeline, fn($a, $b) => strcmp($a['ts'], $b['ts']));

  $blockNum    = 1;
  $prevHashVal = $genesisBlock->hash;

  if (empty($timeline)):
  ?>
  <div class="chain-connector"><div class="chain-line"></div></div>
  <div class="empty-state">
    <div class="empty-state-icon">📭</div>
    <div class="empty-state-title">No Evidence Records Found</div>
  </div>
  <?php else: foreach ($timeline as $entry):
      $isModify = ($entry['type'] === 'MODIFICATION');
      $row      = $entry['data'];

      if ($isModify) {
          // Modification block — check its PreviousHash links correctly
          $storedPrev = $row['PreviousHash']     ?? '';
          $isTampered = ($storedPrev !== $prevHashVal);
          $currHash   = $row['BlockchainHash']   ?? '';
      } else {
          // Submission block — normal chain check
          $storedPrev = $row['PreviousHash']     ?? '';
          $isTampered = ($storedPrev !== $prevHashVal);
          $currHash   = $row['BlockchainHash']   ?? '';
      }
  ?>

  <!-- Chain connector -->
  <div class="chain-connector">
    <div class="chain-line <?= $isTampered ? 'broken' : ($isModify ? 'modify-line' : '') ?>"></div>
    <div class="chain-arrow-label <?= $isTampered ? 'broken' : ($isModify ? 'modify' : '') ?>">
      <?php if ($isTampered)   echo '⚠ CHAIN BROKEN';
            elseif ($isModify) echo '✏ Modification — Linked via Hash';
            else               echo '⬇ Linked via Hash'; ?>
    </div>
    <div class="chain-line <?= $isTampered ? 'broken' : ($isModify ? 'modify-line' : '') ?>"></div>
  </div>

  <!-- Block Card -->
  <div class="block-card <?= $isTampered ? 'tampered-block' : ($isModify ? 'modify-block' : '') ?>">
    <div class="card-header <?= $isTampered ? 'tampered-header' : ($isModify ? 'modify-header' : '') ?>">
      <div class="block-num <?= $isTampered ? 'tampered-num' : ($isModify ? 'modify-num' : '') ?>">
        <?= str_pad($blockNum, 2, '0', STR_PAD_LEFT) ?>
      </div>
      <div>
        <div class="card-header-title">
          <?php if ($isTampered)   echo '⚠️ Block #' . $blockNum . ' — TAMPERED';
                elseif ($isModify) echo '✏️ Modification Block #' . $blockNum . ' — ' . htmlspecialchars($row['EvidenceID']);
                else               echo '🔒 Submission Block #' . $blockNum . ' — ' . htmlspecialchars($row['EvidenceID']); ?>
        </div>
        <div class="card-header-sub">
          <?php if ($isTampered)   echo 'HASH MISMATCH DETECTED';
                elseif ($isModify) echo 'EVIDENCE MODIFICATION — NEW BLOCK APPENDED TO CHAIN';
                else               echo 'ORIGINAL EVIDENCE SUBMISSION — IMMUTABLE ENTRY'; ?>
        </div>
      </div>
      <div class="card-status <?= $isTampered ? 'tampered-status' : ($isModify ? 'modify-status' : '') ?>">
        <?= $isTampered ? '⚠ TAMPERED' : ($isModify ? '✏ MODIFIED' : '✔ Verified') ?>
      </div>
    </div>

    <div class="mini-cards">

      <?php if ($isModify): ?>
        <!-- MODIFICATION BLOCK FIELDS -->
        <div class="mini-card"><div class="mini-card-label">Block Type</div><div class="mini-card-value" style="color:var(--purple);font-weight:bold;">EVIDENCE_MODIFY</div></div>
        <div class="mini-card"><div class="mini-card-label">Evidence ID</div><div class="mini-card-value"><?= htmlspecialchars($row['EvidenceID']) ?></div></div>
        <div class="mini-card"><div class="mini-card-label">Case ID</div><div class="mini-card-value"><?= htmlspecialchars($row['CaseID']) ?></div></div>
        <div class="mini-card"><div class="mini-card-label">Modified By</div><div class="mini-card-value"><?= htmlspecialchars($row['ModifiedBy']) ?></div></div>
        <div class="mini-card"><div class="mini-card-label">Modified At</div><div class="mini-card-value"><?= htmlspecialchars($row['CreatedAt']) ?></div></div>

        <!-- Reason card -->
        <div class="mini-card reason-card">
          <div class="mini-card-label">📝 Reason for Modification</div>
          <div class="mini-card-value" style="color:var(--purple);font-style:italic;">
            "<?= htmlspecialchars($row['ModifyReason']) ?>"
          </div>
        </div>

        <!-- Changed fields diff -->
        <?php
          $changedFields = [];
          if (!empty($row['ChangedFields'])) {
              $changedFields = json_decode($row['ChangedFields'], true) ?? [];
          }
          if (!empty($changedFields)):
        ?>
        <div class="mini-card diff-card">
          <div class="mini-card-label">🔄 Changed Fields</div>
          <table class="diff-table">
            <tr><th>Field</th><th>Previous Value</th><th>New Value</th></tr>
            <?php foreach ($changedFields as $field => $vals): ?>
            <tr>
              <td style="font-weight:bold;color:var(--text-dark);"><?= htmlspecialchars($field) ?></td>
              <td><span class="diff-old"><?= htmlspecialchars($vals['old'] ?? '—') ?></span></td>
              <td><span class="diff-new"><?= htmlspecialchars($vals['new'] ?? '—') ?></span></td>
            </tr>
            <?php endforeach; ?>
          </table>
        </div>
        <?php endif; ?>

      <?php else: ?>
        <!-- SUBMISSION BLOCK FIELDS -->
        <div class="mini-card"><div class="mini-card-label">Block Type</div><div class="mini-card-value" style="color:var(--green);font-weight:bold;">EVIDENCE_SUBMIT</div></div>
        <div class="mini-card"><div class="mini-card-label">Evidence ID</div><div class="mini-card-value"><?= htmlspecialchars($row['EvidenceID']) ?></div></div>
        <div class="mini-card"><div class="mini-card-label">Case ID</div><div class="mini-card-value"><?= htmlspecialchars($row['CaseID']) ?></div></div>
        <div class="mini-card"><div class="mini-card-label">Evidence Type</div><div class="mini-card-value"><?= htmlspecialchars($row['EvidenceType']) ?></div></div>
        <div class="mini-card"><div class="mini-card-label">Status</div><div class="mini-card-value"><?= htmlspecialchars($row['EvidenceStatus']) ?></div></div>
        <div class="mini-card"><div class="mini-card-label">Submitted By</div><div class="mini-card-value"><?= htmlspecialchars($row['SubmittedBy']) ?></div></div>
        <div class="mini-card"><div class="mini-card-label">Authority</div><div class="mini-card-value"><?= htmlspecialchars($row['AuthorityName'] ?? '—') ?></div></div>
        <div class="mini-card"><div class="mini-card-label">Submission Date</div><div class="mini-card-value"><?= htmlspecialchars($row['SubmissionDate']) ?></div></div>
        <div class="mini-card"><div class="mini-card-label">Created At</div><div class="mini-card-value"><?= htmlspecialchars($row['CreatedAt']) ?></div></div>
        <div class="mini-card" style="grid-column:1/-1;"><div class="mini-card-label">File Path</div><div class="mini-card-value" style="font-family:'Courier New',monospace;font-size:11px;color:var(--text-mid);"><?= htmlspecialchars($row['FilePaths'] ?? '—') ?></div></div>
      <?php endif; ?>

      <!-- Hash cards (same for both types) -->
      <div class="mini-card hash-card">
        <div class="mini-card-label">🔐 Block Hash (Current)</div>
        <div class="mini-card-value"><?= htmlspecialchars($currHash) ?></div>
      </div>
      <div class="mini-card prev-card" style="<?= $isTampered ? 'border-left-color:var(--red-bright);background:#fff0f0;' : '' ?>">
        <div class="mini-card-label">⛓️ Previous Hash (Stored at <?= $isModify ? 'Modification' : 'Submission' ?>)</div>
        <div class="mini-card-value <?= $isTampered ? 'hash-mismatch' : '' ?>">
          <?= htmlspecialchars($storedPrev ?: '—') ?>
        </div>
      </div>

      <?php if ($isTampered): ?>
      <div class="mini-card alert-card">
        <div class="mini-card-label" style="color:var(--red-bright);font-weight:bold;">⚠ TAMPER ALERT</div>
        <div class="mini-card-value">
          <span style="color:var(--text-mid);font-size:10px;">STORED AT SUBMISSION:</span><br>
          <span class="hash-mismatch"><?= htmlspecialchars($storedPrev) ?></span><br><br>
          <span style="color:var(--text-mid);font-size:10px;">ACTUAL PREVIOUS BLOCK HASH NOW:</span><br>
          <span class="hash-correct"><?= htmlspecialchars($prevHashVal) ?></span>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </div>

  <?php
      $prevHashVal = $currHash;
      $blockNum++;
    endforeach; endif;
  ?>

</main>

<footer>
  <div style="font-size:24px;">⚖️</div>
  <div class="footer-left">Government of India &nbsp;•&nbsp; Ministry of Home Affairs<br>Digital Evidence Management System &nbsp;•&nbsp; Blockchain Secured</div>
  <div class="footer-right"> &nbsp;|&nbsp; Restricted Access &nbsp;|&nbsp; <?= date('Y') ?></div>
</footer>

</body>
</html>
