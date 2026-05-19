<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) { header('Location: welcome.php'); exit(); }
$user_id = $_SESSION['user_id'];
$id = (int)$_GET['id'];

$stmt = $conn->prepare("UPDATE Notifications SET IsRead = 1 WHERE NotificationID = ? AND UserID = ?");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();

header('Location: welcome.php');
exit();
?>
