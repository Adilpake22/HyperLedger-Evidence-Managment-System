<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
include_once("db.php");

// Check if user is logged in
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
   FLAG: Save — Register new case
════════════════════════════════════════════════════ */
if ($Flag == 'Save') {

    $CaseUId            = getUId();
    $CaseID             = SanitizeInput($conn, $_POST['CaseId']);
    $CaseTitle          = SanitizeInput($conn, $_POST['CaseTitle']);
    $CaseType           = SanitizeInput($conn, $_POST['CaseType']);
    $DateOfIncident     = SanitizeInput($conn, $_POST['DateOfIncident']);
    $LocationOfIncident = SanitizeInput($conn, $_POST['LocationOfIncident']);
    $CaseDescription    = SanitizeInput($conn, $_POST['CaseDescription']);
    $ComplainantName    = SanitizeInput($conn, $_POST['ComplainantName']);
    $ComplainantPhone   = SanitizeInput($conn, $_POST['ComplainantPhone']);
    $ComplainantEmail   = SanitizeInput($conn, $_POST['ComplainantEmail']);

    // Duplicate CaseID check
    $check = mysqli_query($conn, "SELECT CaseID FROM tblcaseregister WHERE CaseID = '$CaseID'");
    if (mysqli_num_rows($check) > 0) {
        echo json_encode(['Status' => 'Fail', 'Message' => 'Case ID already exists. Please use a different Case ID.']);
        exit();
    }

    // Blockchain hash
    $BlockchainHash = hash('sha256', $CaseUId . $CaseID . $CaseTitle . date('Y-m-d H:i:s'));

    // Handle file uploads
    $documentPaths = [];
    if (isset($_FILES['DocumentPath']) && !empty($_FILES['DocumentPath']['name'][0])) {
        $folder = "CaseFolder/$CaseUId";
        if (!file_exists($folder)) mkdir($folder, 0777, true);

        $fileCount = count($_FILES['DocumentPath']['name']);
        for ($i = 0; $i < $fileCount; $i++) {
            if ($_FILES['DocumentPath']['error'][$i] == UPLOAD_ERR_OK) {
                $file_name = $_FILES['DocumentPath']['name'][$i];
                $file_tmp  = $_FILES['DocumentPath']['tmp_name'][$i];
                $file_size = $_FILES['DocumentPath']['size'][$i];
                $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                if (in_array($file_ext, ['pdf','jpg','jpeg','png']) && $file_size <= 5242880) {
                    $new_filename = time() . '_' . $i . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file_name);
                    $destination  = "$folder/$new_filename";
                    if (move_uploaded_file($file_tmp, $destination)) {
                        $documentPaths[] = $destination;
                    }
                }
            }
        }
    }
    $DocumentPath = !empty($documentPaths) ? mysqli_real_escape_string($conn, json_encode($documentPaths)) : null;
    $docVal       = $DocumentPath ? "'$DocumentPath'" : "NULL";

    $sql = "INSERT INTO tblcaseregister
                (CaseUId, CaseID, CaseTitle, CaseType, DateOfIncident,
                 LocationOfIncident, CaseDescription, ComplainantName,
                 ComplainantPhone, ComplainantEmail, DocumentPath, CaseStatus)
            VALUES
                (UUID(), '$CaseID', '$CaseTitle', '$CaseType', '$DateOfIncident',
                 '$LocationOfIncident', '$CaseDescription', '$ComplainantName',
                 '$ComplainantPhone', '$ComplainantEmail', $docVal, 'Active')";

    if (mysqli_query($conn, $sql)) {
        // Fetch the newly inserted CaseUId to return it
        $fetchUId = mysqli_query($conn, "SELECT CaseUId FROM tblcaseregister WHERE CaseID='$CaseID' LIMIT 1");
        $fetchRow = mysqli_fetch_assoc($fetchUId);
        echo json_encode([
            'Status'         => 'success',
            'Message'        => 'Case registered successfully',
            'CaseUId'        => $fetchRow['CaseUId'] ?? $CaseUId,
            'CaseID'         => $CaseID,
            'BlockchainHash' => $BlockchainHash
        ]);
    } else {
        echo json_encode(['Status' => 'Fail', 'Message' => 'Error inserting record: ' . mysqli_error($conn)]);
    }
}

/* ════════════════════════════════════════════════════
   FLAG: ShowRecord — Get all active cases
════════════════════════════════════════════════════ */
else if ($Flag == 'ShowRecord') {
    $sql    = "SELECT * FROM tblcaseregister WHERE CaseStatus='Active' ORDER BY CreatedAt DESC";
    $result = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($result)) {
        $ResponseArray[] = $row;
    }
    echo json_encode($ResponseArray);
}

/* ════════════════════════════════════════════════════
   FLAG: GetData — Get single case by CaseUId
════════════════════════════════════════════════════ */
else if ($Flag == 'GetData') {
    $CaseUId = SanitizeInput($conn, $_POST['CaseUId']);
    $result  = mysqli_query($conn, "SELECT * FROM tblcaseregister WHERE CaseUId='$CaseUId'");
    if ($result && mysqli_num_rows($result) > 0) {
        echo json_encode(mysqli_fetch_assoc($result));
    } else {
        echo json_encode(['Status' => 'Fail', 'Message' => 'No data found for this CaseUId']);
    }
}

/* ════════════════════════════════════════════════════
   FLAG: UpdateDetails — Edit existing case by CaseUId
════════════════════════════════════════════════════ */
else if ($Flag == 'UpdateDetails') {
    $CaseUId            = SanitizeInput($conn, $_POST['CaseUId']);
    $CaseTitle          = SanitizeInput($conn, $_POST['CaseTitle']);
    $CaseType           = SanitizeInput($conn, $_POST['CaseType']);
    $DateOfIncident     = SanitizeInput($conn, $_POST['DateOfIncident']);
    $LocationOfIncident = SanitizeInput($conn, $_POST['LocationOfIncident']);
    $CaseDescription    = SanitizeInput($conn, $_POST['CaseDescription']);
    $ComplainantName    = SanitizeInput($conn, $_POST['ComplainantName']);
    $ComplainantPhone   = SanitizeInput($conn, $_POST['ComplainantPhone']);
    $ComplainantEmail   = SanitizeInput($conn, $_POST['ComplainantEmail']);

    $sql = "UPDATE tblcaseregister SET
                CaseTitle          = '$CaseTitle',
                CaseType           = '$CaseType',
                DateOfIncident     = '$DateOfIncident',
                LocationOfIncident = '$LocationOfIncident',
                CaseDescription    = '$CaseDescription',
                ComplainantName    = '$ComplainantName',
                ComplainantPhone   = '$ComplainantPhone',
                ComplainantEmail   = '$ComplainantEmail'
            WHERE CaseUId = '$CaseUId'";

    if (mysqli_query($conn, $sql)) {
        echo json_encode(['Status' => 'success', 'Message' => 'Case updated successfully']);
    } else {
        echo json_encode(['Status' => 'Fail', 'Message' => 'Error updating record: ' . mysqli_error($conn)]);
    }
}

/* ════════════════════════════════════════════════════
   FLAG: DeleteDetails — Soft-delete case by CaseUId
════════════════════════════════════════════════════ */
else if ($Flag == 'DeleteDetails') {
    $CaseUId = SanitizeInput($conn, $_POST['CaseUId']);
    $sql     = "UPDATE tblcaseregister SET CaseStatus='Deleted' WHERE CaseUId='$CaseUId'";
    if (mysqli_query($conn, $sql)) {
        echo json_encode(['Status' => 'Delete', 'Message' => 'Case deleted successfully']);
    } else {
        echo json_encode(['Status' => 'Fail', 'Message' => 'Error: ' . mysqli_error($conn)]);
    }
}

else {
    echo json_encode(['Status' => 'Fail', 'Message' => 'Invalid operation']);
}
?>