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

// If group creation parameters are missing, show a simple page with a return button.
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Group</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { font-family: Arial, sans-serif; background: #f5f7ff; margin: 0; padding: 0; }
        .page-shell { max-width: 680px; margin: 100px auto; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 12px 30px rgba(0,0,0,0.08); text-align: center; }
        .back-button { display: inline-block; padding: 12px 22px; background: #4f46e5; color: #fff; border-radius: 8px; text-decoration: none; font-weight: 600; margin-top: 20px; }
        .back-button:hover { background: #4338ca; }
    </style>
</head>
<body>
    <div class="page-shell">
        <h1>Return to Dashboard</h1>
        <p>It looks like this page was opened without a partner selected. Use the button below to go back to your welcome page.</p>
        <a href="welcome.php" class="back-button">Back to Welcome</a>
    </div>
</body>
</html>