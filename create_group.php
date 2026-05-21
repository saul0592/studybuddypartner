<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];

//Get partner and subject
if (isset ($_GET['partner_id']) && isset ($_GET['subject'])) {
    $partner_id = intval($_GET['partner_id']);
    $subject = $_GET['subject'];

    //determine the group number
    $count_query = $conn->query("SELECT COUNT(*) as total FROM Groups");
    $count_result = $count_query->fetch_assoc();
    $group_number = $count_result['total'] + 1;
    $group_name = "Group " . $group_number;


    // creating group entry
    $stmt = $conn->prepare("INSERT INTO Groups (GroupName, Subject) VALUES (?, ?)");
    $stmt->bind_param("ss", $group_name, $subject);
    $stmt->execute();
    $new_group_id = $conn->insert_id;
    
    //Add members at the same time
    $add_members = $conn->prepare("INSERT INTO GroupMembers (GroupID, StudentID) VALUES (?, ?), (?, ?)");
    $add_members->bind_param("iiii", $new_group_id, $user_id, $new_group_id, $partner_id);
    $add_members->execute();

    //go straight to the new group chat page
    header("Location: group_message.php?group_id=" . $new_group_id);
    exit();
}
else {
    header("Location: welcome.php");
    exit();
}
?>