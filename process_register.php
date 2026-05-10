<?php
/**
 * REGISTRATION PROCESSING FILE
 * Creates new user accounts and stores them in the database
 * Passwords are hashed for security
 */

include 'db.php';  // Connect to database

// Only process if form was submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data from registration form
    $user = $_POST['username'];
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);  // Hash password for security
    $name = $_POST['fullname'];
    $major = $_POST['major'];
    $subject = $_POST['subject'];
    $time = $_POST['time'];
    $email = $_POST['email'];
    $bio = $_POST['bio'];

    // Insert new student into database
    $stmt = $conn->prepare("INSERT INTO Students (Username, Password, StudentName, Major, Subject, PreferredStudyTime, ContactInfo, Bio) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $user, $pass, $name, $major, $subject, $time, $email, $bio);
    
    // Check if registration was successful
    if ($stmt->execute()) {
        // Success - redirect to login with success message
        header("Location: login.php?msg=success");
    } else {
        // Error - likely duplicate username
        header("Location: register.php?error=username_exists");
    }
}
?>