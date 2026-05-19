<?php
/**
 * CANCEL PAIRING FILE
 * Allows students to cancel confirmed study partnerships
 * Notifies the other student that the meeting was cancelled
 */

session_start();  // Get user session
include 'db.php';  // Connect to database

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: welcome.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$pairing_id = $_GET['id'];

// Find who the other student in this pairing is
$find = $conn->prepare("SELECT StudentID1, StudentID2 FROM Pairings WHERE PairingID = ?");
$find->bind_param("i", $pairing_id);
$find->execute();
$pair = $find->get_result()->fetch_assoc();

if ($pair) {
    // Determine which ID belongs to the partner
    $other_user = ($pair['StudentID1'] == $user_id) ? $pair['StudentID2'] : $pair['StudentID1'];

    // 2. Mark the request as 'declined' and update the time to NOW
    // This allows the welcome.php "30 MINUTE" check to catch it
    $notify = $conn->prepare("UPDATE StudyRequests SET Status = 'declined', CreatedAt = NOW() WHERE (RequesterID = ? AND ReceiverID = ?) OR (RequesterID = ? AND ReceiverID = ?)");
    $notify->bind_param("iiii", $user_id, $other_user, $other_user, $user_id);
    $notify->execute();

    // 3. Delete the meeting from the calendar (Pairings table)
    $delete = $conn->prepare("DELETE FROM Pairings WHERE PairingID = ?");
    $delete->bind_param("i", $pairing_id);
    $delete->execute();

    // 4. Notify the other user that the pairing was cancelled
    $notif = $conn->prepare("INSERT INTO Notifications (UserID, ActorID, Type, ItemID, Message) VALUES (?, ?, ?, ?, ?)");
    $type = 'pairing_cancelled';
    $msg = 'Your study meeting was cancelled by your partner.';
    $notif->bind_param("iisis", $other_user, $user_id, $type, $pairing_id, $msg);
    $notif->execute();

    // 4. Return to dashboard with a success flag
    header("Location: welcome.php?cancelled=success");
    exit();
} else {
    // If no pairing found, just go back
    header("Location: welcome.php");
    exit();
}
?>