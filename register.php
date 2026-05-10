<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Study Buddy</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container" style="max-width: 550px;">
        <h2>Create Account</h2>
        <p style="margin-bottom: 25px; color: #666;">Join Study Buddy Finder and find your perfect study partner.</p>
        
        <!-- Error Message - shown if username already exists -->
        <?php if(isset($_GET['error'])): ?>
            <div class="error-message" style="color: red; margin-bottom: 15px;">
                This username already exists. Please choose a different one.
            </div>
        <?php endif; ?>
        
        <!-- Registration Form -->
        <form action="process_register.php" method="POST">
            <!-- Username Input -->
            <label for="username">Username</label>
            <input type="text" name="username" id="username" placeholder="Choose a unique username" required>
            
            <!-- Password Input -->
            <label for="password">Password</label>
            <input type="password" name="password" id="password" placeholder="Create a strong password" required>
            
            <!-- Full Name Input -->
            <label for="fullname">Full Name</label>
            <input type="text" name="fullname" id="fullname" placeholder="Your full name" required>
            
            <!-- Major Selection - dynamically loads subjects -->
            <label for="major">Major</label>
            <select name="major" id="major" onchange="updateSubjects()" required>
                <option value="">Select Your Major --</option>
                <option value="Computer Science">Computer Science</option>
                <option value="Mathematics">Mathematics</option>
                <option value="English">English / Literacy</option>
            </select>
            
            <!-- Subject Selection - populated based on selected major -->
            <label for="subject">Current Subject to Study</label>
            <select name="subject" id="subject" required disabled>
                <option value="">-- First, select a major --</option>
            </select>
            
            <!-- Study Time Preference -->
            <label for="time">Preferred Study Time</label>
            <select name="time" id="time" required>
                <option value="">-- Select Time Preference --</option>
                <option value="Morning">Morning (6am - 12pm)</option>
                <option value="Afternoon">Afternoon (12pm - 6pm)</option>
                <option value="Evening">Evening (6pm - 12am)</option>
                <option value="Flexible">Flexible</option>
            </select>
            
            <!-- Email Input -->
            <label for="email">Email Address</label>
            <input type="email" name="email" id="email" placeholder="your.email@example.com" required>
            
            <!-- Bio/About Yourself -->
            <label for="bio">Bio</label>
            <textarea name="bio" id="bio" placeholder="Tell potential buddies about your goals and study style..." rows="4" style="resize: vertical;"></textarea>
            
            <!-- Submit Button -->
            <button type="submit" style="width: 100%; margin-top: 20px; padding: 14px;">Create Account</button>
        </form>

        <!-- Footer Links -->
        <div style="text-align: center; margin-top: 25px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
            <p>Already have an account? <a href="login.php" style="font-weight: 600;">Login here</a></p>
        </div>
    </div>

    <!-- JavaScript Logic to handle the dynamic subject list -->
    <script>
    function updateSubjects() {
        const majorSelect = document.getElementById('major');
        const subjectSelect = document.getElementById('subject');
        const selectedMajor = majorSelect.value;

        // Map of majors to their available subjects
        const subjectData = {
            'Computer Science': ['Calculus', 'Data Structures', 'Web Development', 'Computer Architecture', 'C++ Programming'],
            'Mathematics': ['Algebra 1', 'Algebra 2', 'Calculus 1', 'Calculus 2', 'Calculus 3', 'Linear Algebra'],
            'English': ['Literacy & Composition', 'Creative Writing', 'American Literature', 'Critical Thinking']
        };

        // Reset the subject dropdown
        subjectSelect.innerHTML = '<option value="">-- Select a Course --</option>';

        if (selectedMajor && subjectData[selectedMajor]) {
            subjectSelect.disabled = false;
            
            subjectData[selectedMajor].forEach(function(course) {
                let option = document.createElement('option');
                option.value = course;
                option.textContent = course;
                subjectSelect.appendChild(option);
            });
        } else {
            subjectSelect.disabled = true;
            subjectSelect.innerHTML = '<option value="">-- First, select a major --</option>';
        }
    }
    </script>
</body>
</html>