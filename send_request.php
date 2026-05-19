<?php
/**
 * SEND STUDY REQUEST FILE
 * Allows students to send study partnership requests to other students
 * Prevents duplicate requests and old declined requests
 */

session_start();  // Get user session
include 'db.php';  // Connect to database

// Check if user is logged in and receiver ID exists
if (!isset($_SESSION['user_id']) || !isset($_POST['receiver_id'])) {
    header("Location: welcome.php");
    exit();
}

$sender_id = $_SESSION['user_id'];        // Person sending the request
$receiver_id = $_POST['receiver_id'];      // Person receiving the request

// Check if request already exists (pending or accepted)
$check = $conn->prepare("SELECT * FROM StudyRequests WHERE RequesterID = ? AND ReceiverID = ? AND (Status = 'pending' OR Status = 'accepted')");
$check->bind_param("ii", $sender_id, $receiver_id);
$check->execute();
$existing = $check->get_result();

if ($existing->num_rows == 0) {
    // Clean up any old declined requests
    $clean = $conn->prepare("DELETE FROM StudyRequests WHERE RequesterID = ? AND ReceiverID = ? AND Status = 'declined'");
    $clean->bind_param("ii", $sender_id, $receiver_id);
    $clean->execute();

    // Create the new study request
    $stmt = $conn->prepare("INSERT INTO StudyRequests (RequesterID, ReceiverID, Status) VALUES (?, ?, 'pending')");
    $stmt->bind_param("ii", $sender_id, $receiver_id);
    
    if ($stmt->execute()) {
        // Insert a notification for the receiver to inform them of the incoming request
        $request_id = $stmt->insert_id;
        $notif = $conn->prepare("INSERT INTO Notifications (UserID, ActorID, Type, ItemID, Message) VALUES (?, ?, ?, ?, ?)");
        $type = 'request_sent';
        $message_text = 'You have a new study request.';
        $notif->bind_param("iisis", $receiver_id, $sender_id, $type, $request_id, $message_text);
        $notif->execute();

        header("Location: welcome.php?status=request_sent");
    } else {
        header("Location: welcome.php?error=request_failed");
    }
} else {
    header("Location: welcome.php?status=request_exists");
}
?>