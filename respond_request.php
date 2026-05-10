<?php
/**
 * RESPOND TO REQUEST FILE
 * Allows students to accept or decline study partnership requests
 * Creates confirmed pairings when requests are accepted
 */

session_start();  // Get user session
include 'db.php';  // Connect to database

// Check if form was submitted and user is logged in
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $request_id = $_POST['request_id'];  // Which request to respond to
    $action = $_POST['action'];           // 'accepted' or 'declined'
    $user_id = $_SESSION['user_id'];      // Who is responding

    // Update the request status
    $stmt = $conn->prepare("UPDATE StudyRequests SET Status = ? WHERE RequestID = ? AND ReceiverID = ?");
    $stmt->bind_param("sii", $action, $request_id, $user_id);
    
    // If accepted, create a confirmed pairing
    if ($stmt->execute() && $action == 'accepted') {
        // Get who sent the request
        $req_info = $conn->prepare("SELECT RequesterID FROM StudyRequests WHERE RequestID = ?");
        $req_info->bind_param("i", $request_id);
        $req_info->execute();
        $res = $req_info->get_result()->fetch_assoc();
        $requester_id = $res['RequesterID'];

        // Create confirmed pairing (shows in calendar)
        $pair = $conn->prepare("INSERT INTO Pairings (StudentID1, StudentID2) VALUES (?, ?)");
        $pair->bind_param("ii", $requester_id, $user_id);
        $pair->execute();
    }
}

header("Location: welcome.php");
exit();
?>