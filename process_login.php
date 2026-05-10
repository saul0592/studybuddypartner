<?php
/**
 * LOGIN PROCESSING FILE
 * Handles user authentication by verifying username and password
 * Creates session variables for logged-in users
 */

session_start();  // Start user session
include 'db.php';  // Connect to database

// Only process if form was submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    // Query database for student with this username
    $stmt = $conn->prepare("SELECT * FROM Students WHERE Username = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if user exists and verify password
    if ($row = $result->fetch_assoc()) {
        // Password matches - log them in
        if (password_verify($pass, $row['Password'])) {
            // Store user data in session
            $_SESSION['user_id'] = $row['StudentID'];
            $_SESSION['name'] = $row['StudentName'];
            $_SESSION['major'] = $row['Major'];
            $_SESSION['subject'] = $row['Subject'];
            // Redirect to dashboard
            header("Location: welcome.php");
        } else { 
            // Wrong password
            header("Location: login.php?error=invalid_password");
        }
    } else { 
        // Username doesn't exist
        header("Location: login.php?error=user_not_found");
    }
}
?>