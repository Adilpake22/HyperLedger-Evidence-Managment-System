<?php
session_start();
require_once 'db.php';

// Initialize response
$response = array();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Get form data
    $username = trim($_POST['UserName']);
    $password = trim($_POST['Password']);
    
    // Validate input
    if (empty($username) || empty($password)) {
        echo json_encode(array('status' => 'error', 'message' => 'Username and password are required'));
        exit();
    }
    
    // Prepare SQL statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT * FROM tblusers WHERE UserName = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Verify password (assuming passwords are stored as plain text or hashed)
        // If passwords are hashed, use: password_verify($password, $user['Password'])
        if ($password === $user['Password']) {
            // Login successful
            $_SESSION['user'] = $user['UserName'];
            
            // Store user ID if it exists (check for various possible column names)
            if (isset($user['UserUId'])) {
                $_SESSION['userId'] = $user['UserUId'];
            } elseif (isset($user['id'])) {
                $_SESSION['userId'] = $user['id'];
            } elseif (isset($user['ID'])) {
                $_SESSION['userId'] = $user['ID'];
            }
            
            echo json_encode(array('status' => 'success', 'message' => 'Login successful'));
        } else {
            // Invalid password
            echo json_encode(array('status' => 'error', 'message' => 'Invalid username or password'));
        }
    } else {
        // User not found
        echo json_encode(array('status' => 'error', 'message' => 'Invalid username or password'));
    }
    
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(array('status' => 'error', 'message' => 'Invalid request method'));
}
?>