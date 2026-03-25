<?php
require_once 'Blockchain.php';
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // GET FORM DATA
    $evidenceID        = $_POST['EvidenceID'];
    $caseID            = $_POST['CaseID'];
    $evidenceType      = $_POST['EvidenceType'];
    $description       = isset($_POST['Description']) ? $_POST['Description'] : '';
    $submittedBy       = isset($_POST['SubmittedBy']) ? $_POST['SubmittedBy'] : '';
    $authorityName     = isset($_POST['AuthorityName']) ? $_POST['AuthorityName'] : '';
    $submissionDate    = $_POST['SubmissionDate'];
    $locationRecovered = isset($_POST['LocationRecovered']) ? $_POST['LocationRecovered'] : '';

    // FILE UPLOAD
    $uploadDir = "uploads/";
    $filePaths = [];

    if (!empty($_FILES['file']['name'])) {
        $file     = $_FILES['file']['name'];
        $temp     = $_FILES['file']['tmp_name'];
        $filePath = $uploadDir . $file;
        if (move_uploaded_file($temp, $filePath)) {
            $filePaths[] = $filePath;
        }
    }

    $filePathsJson = json_encode($filePaths);

    // SHA256 FILE HASH
    $fileHash = '';
    if (!empty($filePaths)) {
        $fileHash = hash_file('sha256', $filePaths[0]);
    }

    // CREATE BLOCKCHAIN
    $chain         = new EvidenceBlockchain();
    $evidenceBlock = $chain->addEvidence(
        $caseID,
        $filePathsJson,
        $submittedBy,
        $description
    );

    // GET HASHES
    $blockHash  = $evidenceBlock->hash;
    $prevHash   = $evidenceBlock->previousHash;
    $blockIndex = $evidenceBlock->index;
    $chainValid = $chain->isChainValid() ? 'YES ✅' : 'NO ❌';

    // SAVE TO DATABASE
    $sql = "INSERT INTO tblevidence (
                EvidenceID,
                CaseID,
                EvidenceType,
                EvidenceStatus,
                Description,
                SubmittedBy,
                AuthorityName,
                SubmissionDate,
                LocationRecovered,
                FilePaths,
                BlockchainHash,
                RecordStatus,
                CreatedAt
            ) VALUES (
                '$evidenceID',
                '$caseID',
                '$evidenceType',
                'Pending',
                '$description',
                '$submittedBy',
                '$authorityName',
                '$submissionDate',
                '$locationRecovered',
                '$filePathsJson',
                '$blockHash',
                'Active',
                NOW()
            )";

    if ($conn->query($sql)) {
        echo "<script>
            alert(
                'Evidence Submitted Successfully! ✅\n\n' +
                'Evidence ID   : $evidenceID\n' +
                'Case ID       : $caseID\n\n' +
                '--- BLOCKCHAIN INFO ---\n' +
                'Block Index   : $blockIndex\n' +
                'Block Hash    : $blockHash\n' +
                'Prev Hash     : $prevHash\n' +
                'File Hash     : $fileHash\n' +
                'Chain Valid   : $chainValid'
            );
            window.location='dashboard.php';
        </script>";
    } else {
        echo "<script>alert('Database Error: " . $conn->error . "');</script>";
    }
}
?>
