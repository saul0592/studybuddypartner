<?php
include 'db.php';

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug - Add Test Data</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container" style="max-width: 600px;">
        <h2>🐛 Debug: Add Test Data</h2>
        
        <?php
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_test_data'])) {
            $majors = ['Computer Science', 'Business', 'Nursing', 'Psychology'];
            $subjects = ['Calculus', 'Data Structures', 'Marketing 101', 'Anatomy'];
            $times = ['Morning', 'Afternoon', 'Evening', 'Flexible'];

            $count = 0;
            for ($i = 0; $i < 100; $i++) {
                $u = "user" . $i . rand(10, 99);
                $p = password_hash("password123", PASSWORD_DEFAULT);
                $n = "Student " . $i;
                $m = $majors[array_rand($majors)];
                $s = $subjects[array_rand($subjects)];
                $t = $times[array_rand($times)];
                $e = $u . "@example.com";
                $bio = "I'm studying " . $s . " and looking for study partners!";

                $stmt = $conn->prepare("INSERT INTO Students (Username, Password, StudentName, Major, Subject, PreferredStudyTime, ContactInfo, Bio) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssssss", $u, $p, $n, $m, $s, $t, $e, $bio);
                if ($stmt->execute()) {
                    $count++;
                }
            }

            echo '<div class="success-message" style="font-size: 1.1em;">';
            echo 'Successfully added ' . $count . ' mock test users!<br>';
            echo '<strong>Test Credentials:</strong><br>';
            echo 'Username: user0xx to user99xx<br>';
            echo 'Password: password123';
            echo '</div>';
            echo '<div style="margin-top: 30px;">';
            echo '<a href="login.php" class="btn">🔐 Go to Login</a>';
            echo '</div>';
        } else {
            ?>
            <p style="color: #666; margin-bottom: 20px;">
            This page creates 100 test user accounts for development and testing purposes.
            </p>
            
            <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <strong style="color: #856404;">Warning:</strong><br>
                <span style="color: #856404;">This will create 100 dummy accounts in your database. Use this only for development/testing.</span>
            </div>

            <form method="POST">
                <button type="submit" name="add_test_data" value="1" onclick="return confirm('Are you sure? This will add 100 test users.');" style="background: #e74c3c; padding: 12px 30px;">
                Add 100 Test Users
                </button>
            </form>

            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
                <h3>Test Credentials</h3>
                <p style="font-size: 0.95em; color: #666;">
                    Once test data is added, you can login with:<br>
                    <strong>Username:</strong> user0xx to user99xx (example: user010)<br>
                    <strong>Password:</strong> password123
                </p>
            </div>
        <?php
        }
        ?>
    </div>
</body>
</html>