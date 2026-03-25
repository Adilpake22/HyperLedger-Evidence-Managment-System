<?php
session_start();
include_once("db.php");
require_once 'Blockchain.php';

if (!isset($_SESSION['user'])) {
    echo json_encode(['Status' => 'Fail', 'Message' => 'User not logged in']);
    exit();
}

$RegisteredBy       = $_SESSION['user'];
$RegisteredByUserID = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

function SanitizeInput($conn, $user_input) {
    $input = trim($user_input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    $input = mysqli_real_escape_string($conn, $input);
    return $input;
}

function getUId() {
    global $conn;
    $result = mysqli_query($conn, "SELECT UUID() AS UID");
    $row    = mysqli_fetch_assoc($result);
    return $row['UID'];
}

$Flag          = isset($_POST['Flag']) ? $_POST['Flag'] : '';
$ResponseArray = [];

/* ════════════════════════════════════════════════════
   FLAG: Save — Submit new evidence → Block #N
════════════════════════════════════════════════════ */
if ($Flag == 'Save') {

    $EvidenceID        = SanitizeInput($conn, $_POST['EvidenceID']);
    $CaseID            = SanitizeInput($conn, $_POST['CaseID']);
    $EvidenceType      = SanitizeInput($conn, $_POST['EvidenceType']);
    $EvidenceStatus    = SanitizeInput($conn, $_POST['EvidenceStatus']);
    $Description       = SanitizeInput($conn, $_POST['Description']);
    $SubmittedBy       = SanitizeInput($conn, $_POST['SubmittedBy']);
    $AuthorityName     = SanitizeInput($conn, $_POST['AuthorityName']);
    $SubmissionDate    = SanitizeInput($conn, $_POST['SubmissionDate']);
    $LocationRecovered = SanitizeInput($conn, $_POST['LocationRecovered']);

    // Duplicate EvidenceID check
    $check = mysqli_query($conn, "SELECT EvidenceID FROM tblevidence WHERE EvidenceID='$EvidenceID'");
    if (mysqli_num_rows($check) > 0) {
        echo json_encode(['Status' => 'Fail', 'Message' => 'Evidence ID already exists.']);
        exit();
    }

    // ── Get the last block's hash from DB to build the chain ────────────────
    $lastRow = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT BlockchainHash FROM tblevidence WHERE RecordStatus='Active' ORDER BY CreatedAt DESC LIMIT 1"));
    $genesisHash = '0000000000000000000000000000000000000000000000000000000000000000';
    $previousHash = $lastRow ? $lastRow['BlockchainHash'] : $genesisHash;

    // ── Generate new block hash ──────────────────────────────────────────────
    $EvidenceUId     = getUId();
    $BlockchainHash  = hash('sha256',
        $EvidenceUId . $EvidenceID . $CaseID . $EvidenceType .
        $SubmittedBy . $SubmissionDate . date('Y-m-d H:i:s') . $previousHash
    );

    // ── Handle file uploads ──────────────────────────────────────────────────
    $filePaths = [];
    if (isset($_FILES['EvidenceFiles']) && !empty($_FILES['EvidenceFiles']['name'][0])) {
        $folder = "EvidenceFolder/$EvidenceUId";
        if (!file_exists($folder)) mkdir($folder, 0777, true);

        $fileCount = count($_FILES['EvidenceFiles']['name']);
        for ($i = 0; $i < $fileCount; $i++) {
            if ($_FILES['EvidenceFiles']['error'][$i] == UPLOAD_ERR_OK) {
                $file_name = $_FILES['EvidenceFiles']['name'][$i];
                $file_tmp  = $_FILES['EvidenceFiles']['tmp_name'][$i];
                $file_size = $_FILES['EvidenceFiles']['size'][$i];
                $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                if (in_array($file_ext, ['pdf','jpg','jpeg','png','mp4','mp3']) && $file_size <= 5242880) {
                    $new_filename = time() . '_' . $i . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file_name);
                    $destination  = "$folder/$new_filename";
                    if (move_uploaded_file($file_tmp, $destination)) {
                        $filePaths[] = $destination;
                    }
                }
            }
        }
    }
    $FilePathsJson = !empty($filePaths) ? mysqli_real_escape_string($conn, json_encode($filePaths)) : null;
    $fileVal       = $FilePathsJson ? "'$FilePathsJson'" : "NULL";

    $sql = "INSERT INTO tblevidence
                (EvidenceUId, EvidenceID, CaseID, EvidenceType, EvidenceStatus,
                 Description, SubmittedBy, AuthorityName, SubmissionDate,
                 LocationRecovered, FilePaths, BlockchainHash, PreviousHash, RecordStatus)
            VALUES
                ('$EvidenceUId', '$EvidenceID', '$CaseID', '$EvidenceType', '$EvidenceStatus',
                 '$Description', '$SubmittedBy', '$AuthorityName', '$SubmissionDate',
                 '$LocationRecovered', $fileVal, '$BlockchainHash', '$previousHash', 'Active')";

    if (mysqli_query($conn, $sql)) {
        echo json_encode([
            'Status'         => 'success',
            'Message'        => 'Evidence submitted successfully and sealed on the blockchain.',
            'EvidenceID'     => $EvidenceID,
            'EvidenceUId'    => $EvidenceUId,
            'BlockchainHash' => $BlockchainHash,
            'PreviousHash'   => $previousHash
        ]);
    } else {
        echo json_encode(['Status' => 'Fail', 'Message' => 'DB error: ' . mysqli_error($conn)]);
    }
}

/* ════════════════════════════════════════════════════
   FLAG: UpdateDetails
   ─────────────────────────────────────────────────
   OLD BEHAVIOUR: simple SQL UPDATE (overwrites data silently)
   NEW BEHAVIOUR:
     1. Fetch the current record (to diff changed fields + get current hash)
     2. UPDATE tblevidence with new values + a brand-new BlockchainHash
        (PreviousHash = old BlockchainHash, so the chain stays linked)
     3. INSERT a full audit row into tblevidencehistory
   This means every edit appends a new block — original is never lost.
════════════════════════════════════════════════════ */
else if ($Flag == 'UpdateDetails') {

    $EvidenceUId       = SanitizeInput($conn, $_POST['EvidenceUId']);
    $EvidenceType      = SanitizeInput($conn, $_POST['EvidenceType']);
    $EvidenceStatus    = SanitizeInput($conn, $_POST['EvidenceStatus']);
    $Description       = SanitizeInput($conn, $_POST['Description']);
    $SubmittedBy       = SanitizeInput($conn, $_POST['SubmittedBy']);
    $AuthorityName     = SanitizeInput($conn, $_POST['AuthorityName']);
    $SubmissionDate    = SanitizeInput($conn, $_POST['SubmissionDate']);
    $LocationRecovered = SanitizeInput($conn, $_POST['LocationRecovered']);
    $ModifyReason      = SanitizeInput($conn, $_POST['ModifyReason'] ?? 'No reason provided');
    $ModifiedBy        = $RegisteredBy;

    // ── 1. Fetch current record ──────────────────────────────────────────────
    $current = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM tblevidence WHERE EvidenceUId='$EvidenceUId' LIMIT 1"));

    if (!$current) {
        echo json_encode(['Status' => 'Fail', 'Message' => 'Evidence record not found.']);
        exit();
    }

    // ── 2. Diff — which fields actually changed? ─────────────────────────────
    $changedFields = [];
    $fieldsToCheck = [
        'EvidenceType'      => $EvidenceType,
        'EvidenceStatus'    => $EvidenceStatus,
        'Description'       => $Description,
        'SubmittedBy'       => $SubmittedBy,
        'AuthorityName'     => $AuthorityName,
        'SubmissionDate'    => $SubmissionDate,
        'LocationRecovered' => $LocationRecovered,
    ];
    foreach ($fieldsToCheck as $field => $newVal) {
        $oldVal = $current[$field] ?? '';
        if (trim($oldVal) !== trim($newVal)) {
            $changedFields[$field] = ['old' => $oldVal, 'new' => $newVal];
        }
    }

    // ── 3. Build new blockchain block ────────────────────────────────────────
    $oldHash        = $current['BlockchainHash'];   // previous block's hash
    $HistoryUId     = getUId();
    $newHash        = hash('sha256',
        $HistoryUId . $EvidenceUId . $current['EvidenceID'] . $current['CaseID'] .
        $EvidenceType . $EvidenceStatus . $SubmittedBy . $SubmissionDate .
        $ModifyReason . date('Y-m-d H:i:s') . $oldHash
    );

    $changedJson = mysqli_real_escape_string($conn, json_encode($changedFields));

    // ── 4. Update tblevidence (latest values + new hash, PreviousHash = oldHash) ──
    $updateSql = "UPDATE tblevidence SET
                    EvidenceType      = '$EvidenceType',
                    EvidenceStatus    = '$EvidenceStatus',
                    Description       = '$Description',
                    SubmittedBy       = '$SubmittedBy',
                    AuthorityName     = '$AuthorityName',
                    SubmissionDate    = '$SubmissionDate',
                    LocationRecovered = '$LocationRecovered',
                    BlockchainHash    = '$newHash',
                    PreviousHash      = '$oldHash'
                  WHERE EvidenceUId = '$EvidenceUId'";

    if (!mysqli_query($conn, $updateSql)) {
        echo json_encode(['Status' => 'Fail', 'Message' => 'Update error: ' . mysqli_error($conn)]);
        exit();
    }

    // ── 5. Append audit row to tblevidencehistory ────────────────────────────
    $historySql = "INSERT INTO tblevidencehistory
                    (HistoryUId, EvidenceUId, EvidenceID, CaseID,
                     BlockType, BlockchainHash, PreviousHash,
                     ModifiedBy, ModifyReason, ChangedFields)
                   VALUES
                    ('$HistoryUId',
                     '$EvidenceUId',
                     '" . mysqli_real_escape_string($conn, $current['EvidenceID']) . "',
                     '" . mysqli_real_escape_string($conn, $current['CaseID'])     . "',
                     'EVIDENCE_MODIFY',
                     '$newHash',
                     '$oldHash',
                     '" . mysqli_real_escape_string($conn, $ModifiedBy) . "',
                     '" . mysqli_real_escape_string($conn, $ModifyReason) . "',
                     '$changedJson')";

    if (!mysqli_query($conn, $historySql)) {
        // Not fatal — the update succeeded, history insert failed. Log it.
        error_log('BEMS history insert failed: ' . mysqli_error($conn));
    }

    echo json_encode([
        'Status'          => 'success',
        'Message'         => 'Evidence updated. A new blockchain block has been created.',
        'NewHash'         => $newHash,
        'PreviousHash'    => $oldHash,
        'ChangedFields'   => count($changedFields),
        'EvidenceID'      => $current['EvidenceID'],
    ]);
}

/* ════════════════════════════════════════════════════
   FLAG: ShowRecord
════════════════════════════════════════════════════ */
else if ($Flag == 'ShowRecord') {
    $sql    = "SELECT * FROM tblevidence WHERE RecordStatus='Active' ORDER BY CreatedAt DESC";
    $result = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($result)) {
        $ResponseArray[] = $row;
    }
    echo json_encode($ResponseArray);
}

/* ════════════════════════════════════════════════════
   FLAG: GetData
════════════════════════════════════════════════════ */
else if ($Flag == 'GetData') {
    $EvidenceUId = SanitizeInput($conn, $_POST['EvidenceUId']);
    $result      = mysqli_query($conn, "SELECT * FROM tblevidence WHERE EvidenceUId='$EvidenceUId'");
    if ($result && mysqli_num_rows($result) > 0) {
        echo json_encode(mysqli_fetch_assoc($result));
    } else {
        echo json_encode(['Status' => 'Fail', 'Message' => 'No data found.']);
    }
}

/* ════════════════════════════════════════════════════
   FLAG: GetHistory — fetch all modification blocks for an evidence
════════════════════════════════════════════════════ */
else if ($Flag == 'GetHistory') {
    $EvidenceUId = SanitizeInput($conn, $_POST['EvidenceUId']);
    $result = mysqli_query($conn,
        "SELECT * FROM tblevidencehistory WHERE EvidenceUId='$EvidenceUId' ORDER BY CreatedAt ASC");
    $history = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $history[] = $row;
    }
    echo json_encode(['Status' => 'success', 'History' => $history]);
}

/* ════════════════════════════════════════════════════
   FLAG: DeleteDetails — soft delete
════════════════════════════════════════════════════ */
else if ($Flag == 'DeleteDetails') {
    $EvidenceUId = SanitizeInput($conn, $_POST['EvidenceUId']);
    $sql = "UPDATE tblevidence SET RecordStatus='Deleted' WHERE EvidenceUId='$EvidenceUId'";
    if (mysqli_query($conn, $sql)) {
        echo json_encode(['Status' => 'Delete', 'Message' => 'Evidence record deleted.']);
    } else {
        echo json_encode(['Status' => 'Fail', 'Message' => 'Error: ' . mysqli_error($conn)]);
    }
}

else {
    echo json_encode(['Status' => 'Fail', 'Message' => 'Invalid operation flag.']);
}
?>