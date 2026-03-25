<?php
session_start();

// LOGIN LOGIC
if (isset($_POST['login'])) {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    if ($user == "admin" && $pass == "1234") {
        $_SESSION['user'] = $user;
    } else {
        $error = "Invalid Login!";
    }
}

// LOGOUT
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: app.php");
}

// SAVE EVIDENCE
if (isset($_POST['submit_evidence'])) {
    $case_id = $_POST['case_id'];

    $file = $_FILES['file']['name'];
    $temp = $_FILES['file']['tmp_name'];

    if (!is_dir("uploads")) {
        mkdir("uploads");
    }

    move_uploaded_file($temp, "uploads/" . $file);

    $msg = "Evidence submitted successfully!";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Evidence System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #eef2f7, #dbeafe);
            font-family: Arial;
        }
        .card {
            border-radius: 15px;
        }
        .btn-main {
            background: #3b82f6;
            color: white;
        }
        .btn-main:hover {
            background: #2563eb;
        }
    </style>
</head>

<body>

<div class="container mt-5">

<?php if (!isset($_SESSION['user'])) { ?>

    <!-- LOGIN -->
    <div class="card p-4 mx-auto" style="max-width:400px;">
        <h3 class="text-center">Login</h3>

        <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

        <form method="POST">
            <input type="text" name="username" class="form-control mb-3" placeholder="Username" required>
            <input type="password" name="password" class="form-control mb-3" placeholder="Password" required>

            <button name="login" class="btn btn-main w-100">Login</button>
        </form>

        <small class="text-center mt-3 d-block">Demo: admin / 1234</small>
    </div>

<?php } else { ?>

    <!-- DASHBOARD -->
    <div class="d-flex justify-content-between mb-4">
        <h4>Welcome, <?php echo $_SESSION['user']; ?> 👋</h4>
        <a href="?logout=true" class="btn btn-danger">Logout</a>
    </div>

    <?php if (isset($msg)) echo "<div class='alert alert-success'>$msg</div>"; ?>

    <!-- FEATURES -->
    <div class="mb-4">
        <button class="btn btn-main me-2" onclick="showForm()">Submit Evidence</button>
    </div>

    <!-- EVIDENCE FORM -->
    <div id="formBox" style="display:none;">
        <div class="card p-4 shadow">
            <h4 class="mb-3">Submit Evidence</h4>

            <form method="POST" enctype="multipart/form-data">
                <input type="text" name="case_id" class="form-control mb-3" placeholder="Case ID" required>

                <select name="type" class="form-select mb-3">
                    <option>Document</option>
                    <option>Image</option>
                    <option>Video</option>
                </select>

                <textarea name="desc" class="form-control mb-3" placeholder="Description"></textarea>

                <input type="file" name="file" class="form-control mb-3" required>

                <button name="submit_evidence" class="btn btn-success">Submit</button>
            </form>
        </div>
    </div>

<?php } ?>

</div>

<script>
function showForm() {
    document.getElementById("formBox").style.display = "block";
}
</script>

</body>
</html>