<?php
$localhost = "localhost";
$root = "root";
$password = "";
$database = "db_evidemo22";

$conn = new mysqli($localhost, $root, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}
?>