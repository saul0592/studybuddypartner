<?php
/**
 * EDIT PROFILE PAGE
 * Allows logged-in users to update their profile information
 * Protected page - requires login
 */

session_start();
include 'db.php';

// Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$user_id = $_SESSION['user_id'];

// Handle Form Submission (when user clicks Save Changes)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['fullname'];
    $major = $_POST['major'];
    $subject = $_POST['subject'];
    $bio = $_POST['bio'];

    // Update student record in database
    $update = $conn->prepare("UPDATE Students SET StudentName=?, Major=?, Subject=?, Bio=? WHERE StudentID=?");
    $update->bind_param("ssssi", $name, $major, $subject, $bio, $user_id);
    
    if ($update->execute()) {
        // Update session variables with new data
        $_SESSION['name'] = $name;
        $_SESSION['major'] = $major;
        $_SESSION['subject'] = $subject;
        $msg = "Profile updated successfully!";
    }
}

// Fetch current profile data from database
$stmt = $conn->prepare("SELECT * FROM Students WHERE StudentID = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Study Buddy</title>
    <link rel="stylesheet" href="style.css">
</head>
<body onload="updateSubjects('<?php echo $user_data['Subject']; ?>')">
    <nav>
        <div>
            <strong>Edit Profile</strong>
        </div>
        <div>
            <a href="welcome.php">Back to Dashboard</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="container" style="max-width: 550px;">
        <h2>Edit Your Profile</h2>
        
        <?php if(isset($msg)): ?>
            <div class="success-message">
                 <?php echo $msg; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <label for="fullname">Full Name</label>
            <input type="text" name="fullname" id="fullname" value="<?php echo htmlspecialchars($user_data['StudentName']); ?>" required>
            
            <label for="major">Major</label>
            <select name="major" id="major" onchange="updateSubjects()" required>
                <option value="">-- Select Your Major --</option>
                <option value="Computer Science" <?php if($user_data['Major'] == 'Computer Science') echo 'selected'; ?>>Computer Science</option>
                <option value="Mathematics" <?php if($user_data['Major'] == 'Mathematics') echo 'selected'; ?>>Mathematics</option>
                <option value="English" <?php if($user_data['Major'] == 'English') echo 'selected'; ?>>English / Literacy</option>
            </select>
            
            <label for="subject">Current Subject to Study</label>
            <select name="subject" id="subject" required>
                <option value="">-- Select a Subject --</option>
            </select>
            
            <label for="bio">Bio</label>
            <textarea name="bio" id="bio" placeholder="Tell other students about your study goals..." rows="5" style="resize: vertical;"><?php echo htmlspecialchars($user_data['Bio']); ?></textarea>
            
            <div class="btn-group">
                <button type="submit" style="padding: 12px 30px;">Save Changes</button>
                <a href="welcome.php" class="btn" style="background: #95a5a6; padding: 12px 30px; text-decoration: none; display: inline-block; border-radius: 4px;">Cancel</a>
            </div>
        </form>
    </div>

    <script>
    function updateSubjects(existingSubject = '') {
        const majorSelect = document.getElementById('major');
        const subjectSelect = document.getElementById('subject');
        const selectedMajor = majorSelect.value;

        const subjectData = {
            'Computer Science': ['Calculus', 'Data Structures', 'Web Development', 'Computer Architecture', 'C++ Programming'],
            'Mathematics': ['Algebra 1', 'Algebra 2', 'Calculus 1', 'Calculus 2', 'Calculus 3', 'Linear Algebra'],
            'English': ['Literacy & Composition', 'Creative Writing', 'American Literature', 'Critical Thinking']
        };

        // Clear existing options
        subjectSelect.innerHTML = '<option value="">-- Select a Course --</option>';

        if (selectedMajor && subjectData[selectedMajor]) {
            subjectSelect.disabled = false;
            
            subjectData[selectedMajor].forEach(function(course) {
                let option = document.createElement('option');
                option.value = course;
                option.textContent = course;
                
                // If the user already has this subject saved, select it
                if (course === existingSubject) {
                    option.selected = true;
                }
                
                subjectSelect.appendChild(option);
            });
        } else {
            subjectSelect.disabled = true;
        }
    }
    </script>
</body>
</html>