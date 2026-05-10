<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Study Buddy Finder</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container" style="max-width: 450px;">
        <h2>Login</h2>

        <!-- Success Message - shown after registration -->
        <?php if(isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
            <div class="success-message">
                Account created successfully! Please log in.
            </div>
        <?php endif; ?>

        <!-- Error Messages - shown if login fails -->
        <?php if(isset($_GET['error'])): ?>
            <div class="error-message">
                <?php 
                    if($_GET['error'] == 'invalid_password') {
                        echo '✗ Invalid password. Please try again.';
                    } elseif($_GET['error'] == 'user_not_found') {
                        echo 'Username not found. Please check and try again or create an account.';
                    }
                ?>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form action="process_login.php" method="POST">
            <label for="username">Username</label>
            <input type="text" name="username" id="username" placeholder="Enter your username" required>
            
            <label for="password">Password</label>
            <input type="password" name="password" id="password" placeholder="Enter your password" required>
            
            <!-- Submit Button -->
            <button type="submit" style="width: 100%; margin-top: 20px; padding: 14px;">Login</button>
        </form>

        <!-- Footer Links -->
        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
            <p>Don't have an account? <a href="register.php" style="font-weight: 600;">Sign up here</a></p>
            <a href="index.php" style="font-size: 0.95em; color: #999;">← Back to Home</a>
        </div>
    </div>
</body>
</html>