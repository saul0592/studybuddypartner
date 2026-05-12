<?php
/**
 * MESSAGES PAGE
 * Direct messaging between study partners
 * Users can only message their assigned study partner
 * Messages are stored in the Messages table
 */

session_start(); // Start user session
include 'db.php'; // Connect to database

// Check if user is logged in, redirect if not
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];

// --- 1. FIND USER'S STUDY PARTNER ---
// Query Pairings table to find the assigned partner
$partner_query = $conn->prepare("
    SELECT 
        CASE 
            WHEN StudentID1 = ? THEN s2.StudentName 
            WHEN StudentID2 = ? THEN s1.StudentName 
        END AS PartnerName
    FROM Pairings p
    JOIN Students s1 ON p.StudentID1 = s1.StudentID
    JOIN Students s2 ON p.StudentID2 = s2.StudentID
    WHERE StudentID1 = ? OR StudentID2 = ?
");
$partner_query->bind_param("iiii", $user_id, $user_id, $user_id, $user_id);
$partner_query->execute();
$partner_result = $partner_query->get_result();
$partner = $partner_result->fetch_assoc();

// If no partner found, user cannot use messaging
if (!$partner || !$partner['PartnerName']) {
    $has_partner = false;
    $partner_name = null;
} else {
    $has_partner = true;
    $partner_name = $partner['PartnerName'];
}

// --- 2. HANDLE MESSAGE DELETION ---
if (isset($_GET['del'])) {
    $message_id = $_GET['del'];
    // Only allow deleting user's own messages (security)
    $delete_query = $conn->prepare("DELETE FROM Messages WHERE MessageID = ? AND SenderName = ?");
    $delete_query->bind_param("is", $message_id, $user_name);
    $delete_query->execute();
    header("Location: message.php");
    exit();
}

// --- 3. HANDLE MESSAGE SENDING ---
if (isset($_POST['submit']) && $has_partner) {
    $message_text = $_POST['message'];

    // Insert message using prepared statement
    $insert_query = $conn->prepare("INSERT INTO Messages (SenderName, ReceiverName, MessageText) VALUES (?, ?, ?)");
    $insert_query->bind_param("sss", $user_name, $partner_name, $message_text);
    $insert_query->execute();

    // Show success message
    echo "<div class='success-message' style='text-align:center;'>Message sent to {$partner_name}!</div>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Messages</title>
    <!--connects css file-->
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!--navigation link to dashboard and welcome message-->
    <nav>
        <div><strong style= "color: #4738ec;">Welcome, <?php echo $user_name;?></strong></div>
        <div><a href="welcome.php">Main page</a></div>
    </nav>
    <!--message history section-->
    <div class="container" style="max-width: 800px; width: 95%;">
        <?php if (!$has_partner): ?>
            <!-- No partner message -->
            <div class="card" style="display: block; width: 100%; padding: 25px; text-align: center;">
                <h3 style="color: #4738ec">No Study Partner Yet</h3>
                <p>You need to find and pair with a study partner before you can send messages.</p>
                <a href="welcome.php" class="btn" style="display: inline-block; margin-top: 15px;">Find a Partner</a>
            </div>
        <?php else: ?>
            <!-- Message history with partner -->
            <div class="card" style="display: block; width: 100%; padding: 25px;">
                <h3 style="color: #4738ec">Messages with <?php echo htmlspecialchars($partner_name); ?></h3>
                <div style="margin-top:20px;">
                    <?php
                    // Get messages only between user and their partner
                    $message_query = $conn->prepare("
                        SELECT * FROM Messages 
                        WHERE (SenderName = ? AND ReceiverName = ?) 
                           OR (SenderName = ? AND ReceiverName = ?) 
                        ORDER BY SentAt DESC
                    ");
                    $message_query->bind_param("ssss", $user_name, $partner_name, $partner_name, $user_name);
                    $message_query->execute();
                    $messages = $message_query->get_result();

                    if ($messages->num_rows == 0) {
                        echo "<p style='text-align: center; color: #666;'>No messages yet. Start the conversation!</p>";
                    } else {
                        while ($row = $messages->fetch_assoc()) {
                            if ($row['SenderName'] == $user_name) { // User sent message
                                $label = "You"; // Display as "You"
                                $bgColor = "#e0e7ff"; // Light blue for sent messages
                                $nameColor = "#5034da"; // Text color
                            } else { // Partner sent message
                                $label = $row['SenderName']; // Display partner's name
                                $bgColor = "#b5b9be"; // Light gray for received
                                $nameColor = "#4738ec";
                            }
                            // Creates boxes for each message
                            echo "<div style='background: $bgColor; padding: 15px; border-radius: 8px; margin-bottom: 10px; border: 1px solid #ddd;'>";
                            // Show the sender
                            echo "<b style='color: $nameColor; font-weight: bold;'>$label</b><br>";
                            // Displays the message
                            echo "<span>" . htmlspecialchars($row['MessageText']) . "</span>";
                            // Delete link section (only for user's messages)
                            if ($row['SenderName'] == $user_name) {
                                echo "<div style='text-align: right; margin-top: 10px;'>";
                                echo "<a href='message.php?del=" . $row['MessageID'] . "' style='color: #ff4d4d; font-size: 11px; text-decoration: none;'>[Delete]</a>";
                                echo "</div>";
                            }
                            echo "</div>";
                        }
                    }
                    ?>
                </div>
            </div>
            <!-- Send a new message -->
            <div class="card" style="margin-top: 20px; padding: 25px;">
                <h3 style="color: #4738ec">Send Message to <?php echo htmlspecialchars($partner_name); ?></h3>
                <form method="post">
                    <!-- Hidden field for receiver (automatically set to partner) -->
                    <input type="hidden" name="receiver" value="<?php echo htmlspecialchars($partner_name); ?>">
                    <!-- Textarea for user's message -->
                    <textarea name="message" placeholder="Type your message here..." required style="width:100%; height:100px; padding: 10px;"></textarea>
                    <!-- Submission button -->
                    <button type="submit" name="submit" class="btn" style="width:100%; margin-top:10px;">Send Message</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>