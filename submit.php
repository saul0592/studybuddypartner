<?php
include 'db.php'; // Connects to the database

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submission Result - Study Buddy</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container" style="max-width: 500px; text-align: center;">
        <?php
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $name = $_POST['name'];
            $subject = $_POST['subject'];
            $avail = $_POST['availability'];
            $contact = $_POST['contact'];

            $stmt = mysqli_prepare($conn, "INSERT INTO Students (StudentName, Subject, Availability, ContactInfo) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'ssss', $name, $subject, $avail, $contact);

            if (mysqli_stmt_execute($stmt)) {
                echo '<div style="text-align: center; padding: 40px;">';
                echo '<h2 style="color: #4CAF50; margin-bottom: 20px;">✓ Successfully Registered!</h2>';
                echo '<div class="success-message">';
                echo '<p>Welcome to Study Buddy Finder, <strong>' . htmlspecialchars($name) . '</strong>!</p>';
                echo '<p>Your registration has been completed. You can now log in with your credentials.</p>';
                echo '</div>';
                echo '<div class="button-group" style="margin-top: 30px;">';
                echo '<a href="login.php" class="btn">🔐 Go to Login</a>';
                echo '<a href="index.php" class="btn" style="background: #95a5a6;">🏠 Back to Home</a>';
                echo '</div>';
                echo '</div>';
            } else {
                echo '<h2 style="color: #e74c3c;">✗ Registration Error</h2>';
                echo '<div class="error-message">';
                echo '<p>There was an error during registration:</p>';
                echo '<p>' . htmlspecialchars(mysqli_error($conn)) . '</p>';
                echo '</div>';
                echo '<div class="button-group" style="margin-top: 30px;">';
                echo '<a href="register.php" class="btn">← Try Again</a>';
                echo '</div>';
            }

            mysqli_stmt_close($stmt);
        } else {
            header("Location: index.php");
        }
        ?>
    </div>
</body>
</html>