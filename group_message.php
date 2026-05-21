<?php
session_start();
include 'db.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];

// Track which group room is open via GET or POST
$group_id = null;
if (isset($_GET['group_id'])) {
    $group_id = (int)$_GET['group_id'];
} elseif (isset($_POST['group_id'])) {
    $group_id = (int)$_POST['group_id'];
}

// Notifications: unread count and recent list (for navbar)
$unread_count = 0;
$notifications = [];
$notif_stmt = $conn->prepare("SELECT NotificationID, ActorID, Type, ItemID, Message, IsRead, CreatedAt FROM Notifications WHERE UserID = ? ORDER BY CreatedAt DESC LIMIT 10");
$notif_stmt->bind_param("i", $user_id);
$notif_stmt->execute();
$notif_res = $notif_stmt->get_result();
while ($n = $notif_res->fetch_assoc()) { 
    $notifications[] = $n; 
    if ($n['IsRead'] == 0) {
        $unread_count++; 
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Remove member from group
    if (isset($_POST['action']) && $_POST['action'] === 'remove_member'){
        $del = $conn->prepare("DELETE FROM GroupMembers WHERE GroupID = ? AND StudentID = ?");
        $del->bind_param("ii", $group_id, $_POST['student_id']);
        $del->execute();
    }
    
    // Add member to group
    else if (isset($_POST['action']) && $_POST['action'] === 'add_member'){
        $buddy_id = (int)$_POST['buddy_id'];
        $check = $conn->query("SELECT * FROM GroupMembers WHERE GroupID = $group_id AND StudentID = $buddy_id");
        if ($check->num_rows == 0) {
            $add = $conn->prepare("INSERT INTO GroupMembers (GroupID, StudentID) VALUES (?, ?)");
            $add->bind_param("ii", $group_id, $buddy_id);
            $add->execute();
        }
    }
    
    // Send group message and add notifications
    else if (isset($_POST['message']) && trim($_POST['message']) !== '') {
        $msg_text = trim($_POST['message']);
        $insert = $conn->prepare("INSERT INTO Messages (SenderID, SenderName, GroupID, MessageText) VALUES (?, ?, ?, ?)");
        $insert->bind_param("isis", $user_id, $user_name, $group_id, $msg_text);
        $insert->execute();
        
        $group_message_id = $conn->insert_id;
        if ($group_message_id) {
            $notif_stmt = $conn->prepare("INSERT INTO Notifications (UserID, ActorID, Type, ItemID, Message, IsRead) VALUES (?, ?, 'group_message', ?, ?, 0)");
            
            $mems = $conn->prepare("SELECT StudentID FROM GroupMembers WHERE GroupID = ?");
            $mems->bind_param("i", $group_id);
            $mems->execute();
            $mems_res = $mems->get_result();
            
            while ($mrow = $mems_res->fetch_assoc()) {
                $mid = $mrow['StudentID'];
                if ($mid == $user_id) continue; 
                
                $short = mb_substr($msg_text, 0, 160);
                $notif_stmt->bind_param("iiis", $mid, $user_id, $group_message_id, $short);
                $notif_stmt->execute();
            }
        }
    }
    header("Location: group_message.php?group_id=$group_id");
    exit();
}

// Get user's groups
$my_groups = [];
$gstmt = $conn->prepare("SELECT g.GroupID, g.GroupName FROM GroupMembers gm JOIN Groups g ON gm.GroupID = g.GroupID WHERE gm.StudentID = ?");
$gstmt->bind_param("i", $user_id);
$gstmt->execute();
$gres = $gstmt->get_result();
while ($row = $gres->fetch_assoc()) {
    $my_groups[] = $row;
}

if (!$group_id && count($my_groups) > 0) {
    $group_id = $my_groups[0]['GroupID'];
}

// Get active group name and members list
$current_group_name = "";
$group_members = [];
if ($group_id){
    $gn = $conn->query("SELECT GroupName FROM Groups WHERE GroupID = $group_id LIMIT 1");
    if ($row = $gn->fetch_assoc()) {
        $current_group_name = $row['GroupName'];
    }
    $mem = $conn->query("SELECT s.StudentID, s.StudentName From GroupMembers gm JOIN Students s ON gm.StudentID = s.StudentID WHERE gm.GroupID = $group_id");
    while ($row = $mem->fetch_assoc()) {
        $group_members[] = $row;
    }
}

// Get chat logs
$messages = [];
if ($group_id) {
    $msg = $conn->prepare("SELECT * FROM Messages WHERE GroupID = ? ORDER BY SentAt ASC");
    $msg->bind_param("i", $group_id);
    $msg->execute();
    $msg_res = $msg->get_result();
    while ($row = $msg_res->fetch_assoc()) {
        $messages[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Group Chat</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <nav>
        <div><strong>Welcome, <?php echo htmlspecialchars($user_name); ?></strong></div>
        <div style="display: flex; gap: 12px; align-items: center;">
            <div style="position: relative;">
                <a href="#" id="notifToggle" style="position: relative; text-decoration: none;">Notifications
                    <?php if ($unread_count > 0): ?>
                        <span style="background:#ff3b30;color:#fff;padding:2px 7px;border-radius:12px;font-size:12px;margin-left:8px;"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </a>
                <div id="notifMenu" style="display:none; position: absolute; right: 0; top: 22px; width: 320px; background: #fff; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 6px 18px rgba(0,0,0,0.08); z-index: 20;">
                    <div style="padding: 10px; border-bottom: 1px solid #f0f0f0;"><strong>Notifications</strong></div>
                    <div style="max-height: 280px; overflow: auto;">
                        <?php if (count($notifications) == 0): ?>
                            <div style="padding: 12px; color: #666;">No notifications</div>
                        <?php else: foreach ($notifications as $n): ?>
                            <div style="padding: 10px; border-bottom: 1px solid #f7f7f7; background: <?php echo $n['IsRead'] == 0 ? '#f5f7ff' : '#fff'; ?>;">
                                <div style="font-size: 13px; color: #222;">
                                    <?php echo htmlspecialchars($n['Message']); ?>
                                </div>
                                <div style="font-size: 11px; color: #888; margin-top: 6px; display:flex; justify-content:space-between;">
                                    <span><?php echo htmlspecialchars($n['Type']); ?></span>
                                    <a href="notifications_mark_read.php?id=<?php echo $n['NotificationID']; ?>" style="color:#667eea; text-decoration:none;">Mark read</a>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>
            <a href="message.php">Private Messages</a>
            <a href="edit_profile.php">Edit Profile</a>
            <a href="logout.php">Logout</a>
            <a href="group_message.php" style="font-weight: bold;">Group Messages</a>
            <a href="welcome.php"> Return page</a>
        </div>
    </nav>
    <script>
        document.addEventListener('DOMContentLoaded', function(){
            var t = document.getElementById('notifToggle');
            var m = document.getElementById('notifMenu');
            t && t.addEventListener('click', function(e){ e.preventDefault(); m.style.display = m.style.display === 'none' ? 'block' : 'none'; });

            setInterval(function(){
                fetch('notifications_count.php')
                .then(r => r.json())
                .then(data => {
                    var badge = t.querySelector('span');
                    if (data.unread && data.unread > 0) {
                        if (!badge) {
                            var s = document.createElement('span'); s.style.cssText = 'background:#ff3b30;color:#fff;padding:2px 7px;border-radius:12px;font-size:12px;margin-left:8px;'; s.textContent = data.unread; t.appendChild(s);
                        } else { badge.textContent = data.unread; }
                    } else if (badge) {
                        badge.remove();
                    }
                })
                .catch(()=>{});
            }, 12000);
        });
    </script>

<div class="container">
    <h2>Group Workspace</h2>
    
    <?php if (count($my_groups) == 0): ?>
        <p>No group spaces found. Create one from the dashboard.</p>
    <?php else: ?>
        
        <div style="display: flex; gap: 20px; align-items: flex-start; width: 100%;">
            
            <div style="width: 250px; flex-shrink: 0; background: #fff; border: 1px solid #ccc; border-radius: 8px; padding: 15px; box-sizing: border-box;">
                <h4 style="margin-top: 0; margin-bottom: 10px;">Your Active Groups</h4>
                <?php foreach ($my_groups as $g): ?>
                    <div style="margin-bottom: 8px; padding: 10px; border: 1px solid #eee; border-radius: 4px; background: <?php echo ($group_id == $g['GroupID'] ? '#eef2ff' : '#fafafa'); ?>;">
                        <a href="group_message.php?group_id=<?php echo $g['GroupID']; ?>" style="text-decoration: none; color: black; font-weight: <?php echo ($group_id == $g['GroupID'] ? 'bold' : 'normal'); ?>;">
                            <?php echo htmlspecialchars($g['GroupName']); ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>

            <div style="flex-grow: 1; background: #fff; border: 1px solid #ccc; border-radius: 8px; padding: 20px; box-sizing: border-box;">
                <?php if (!$group_id): ?>
                    <p>Select a group from the sidebar to start chatting.</p>
                <?php else: ?>
                    
                    <h3 style="margin-top: 0; margin-bottom: 5px;">Active Room: <?php echo htmlspecialchars($current_group_name); ?></h3>
                    <p style="color: grey; font-size: 0.9em; margin-top: 0; margin-bottom: 15px;">
                        <strong>Members:</strong> 
                        <?php 
                        $names = [];
                        foreach ($group_members as $m) { $names[] = $m['StudentName']; }
                        echo htmlspecialchars(implode(', ', $names)); 
                        ?>
                    </p>
                    <hr>

                    <div style="height: 300px; overflow-y: scroll; border: 1px solid #ddd; background: #fafafa; padding: 10px; margin-top: 15px; margin-bottom: 15px; border-radius: 4px;">
                        <?php if (count($messages) == 0): ?>
                            <p style="text-align: center; color: grey; padding-top: 20px;">No messages here yet.</p>
                        <?php else: ?>
                            <?php foreach ($messages as $row): $isMe = ($row['SenderID'] == $user_id); ?>
                                <div style="margin-bottom: 10px; text-align: <?php echo $isMe ? 'right' : 'left'; ?>;">
                                    <div style="display: inline-block; padding: 8px 12px; border-radius: 8px; text-align: left; max-width: 70%; background: <?php echo $isMe ? '#dcfce7' : '#e5e7eb'; ?>; border: 1px solid #ddd;">
                                        <small style="font-weight: bold; color: #444; display: block; margin-bottom: 2px;">
                                            <?php echo $isMe ? 'You' : htmlspecialchars($row['SenderName']); ?>
                                        </small>
                                        <span><?php echo nl2br(htmlspecialchars($row['MessageText'])); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <form method="POST" style="margin-bottom: 30px;">
                        <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
                        <textarea name="message" required placeholder="Type group message here..." style="width: 100%; height: 60px; padding: 8px; border-radius: 4px; border: 1px solid #ccc; font-family: inherit; box-sizing: border-box;"></textarea>
                        <br>
                        <button type="submit" style="margin-top: 5px; padding: 6px 12px; cursor: pointer;">Send Message</button>
                    </form>

                    <hr>

                    <h4 style="margin-bottom: 10px;">Group Members Settings</h4>
                    
                    <div style="display:flex; flex-direction:column; gap:8px; margin-bottom:20px;">
                        <?php foreach ($group_members as $member): ?>
                            <div style="display:flex; align-items:center; justify-content:space-between; padding:8px; border:1px solid #eee; border-radius:6px; background:#fafafa;">
                                <div><strong><?php echo htmlspecialchars($member['StudentName']); ?></strong></div>
                                <div>
                                    <?php if ($member['StudentID'] != $user_id): ?>
                                        <form method="POST" style="display:inline; margin:0;" onsubmit="return confirm('Remove member?');">
                                            <input type="hidden" name="action" value="remove_member">
                                            <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
                                            <input type="hidden" name="student_id" value="<?php echo $member['StudentID']; ?>">
                                            <button type="submit" style="background:#ffcccc; color:#cc0000; border:1px solid #cc0000; padding:6px 10px; border-radius:4px; cursor:pointer; font-size:0.9em;">Remove</button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: grey; font-style: italic;">(You)</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <h5>Add New Student</h5>
                    <form method="POST" style="margin: 0;">
                        <input type="hidden" name="action" value="add_member">
                        <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
                        <select name="buddy_id" required style="padding: 6px; border-radius: 4px; border: 1px solid #ccc; width: 250px;">
                            <option value="" disabled selected>Select student name...</option>
                            <?php
                            $all_students = $conn->query("SELECT StudentID, StudentName FROM Students WHERE StudentID != $user_id ORDER BY StudentName ASC");
                            while ($student = $all_students->fetch_assoc()): ?>
                                <option value="<?php echo $student['StudentID']; ?>"><?php echo htmlspecialchars($student['StudentName']); ?></option>
                            <?php endwhile; ?>
                        </select>
                        <button type="submit" style="padding: 6px 12px; cursor: pointer; margin-left: 5px;">Add Into Group</button>
                    </form>

                <?php endif; ?>
            </div>

        </div>
    <?php endif; ?>
</div>
</body>
</html>