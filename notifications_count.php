<?php
header('Content-Type: application/json');
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) { echo json_encode(['unread' => 0]); exit(); }
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT COUNT(*) AS unread FROM Notifications WHERE UserID = ? AND IsRead = 0");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
echo json_encode(['unread' => (int)$res['unread']]);
exit();
?>
