<?php
/**
 * DASHBOARD / WELCOME PAGE
 * Main page after login - shows study partners, pending requests, and confirmed pairings
 * Protected page - requires login
 */

session_start();
include 'db.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$user_id = $_SESSION['user_id'];
$major = $_SESSION['major'];
$subject = $_SESSION['subject'];

// --- NOTIFICATIONS: unread count and recent list ---
$unread_count = 0;
$notifications = [];
$notif_stmt = $conn->prepare("SELECT NotificationID, ActorID, Type, ItemID, Message, IsRead, CreatedAt FROM Notifications WHERE UserID = ? ORDER BY CreatedAt DESC LIMIT 10");
$notif_stmt->bind_param("i", $user_id);
$notif_stmt->execute();
$notif_res = $notif_stmt->get_result();
while ($n = $notif_res->fetch_assoc()) { $notifications[] = $n; if ($n['IsRead'] == 0) $unread_count++; }

// --- 1. SEARCH & FILTER LOGIC ---
// Allow users to filter by major and subject
$filter_major = isset($_GET['filter_major']) ? $_GET['filter_major'] : $major;
$filter_subject = isset($_GET['filter_subject']) ? $_GET['filter_subject'] : $subject;

// Find all students with same major/subject (excluding: current user and students already in pairings)
$query = "SELECT * FROM Students 
          WHERE Major = ? AND Subject = ? AND StudentID != ? 
          AND StudentID NOT IN (SELECT StudentID1 FROM Pairings)
          AND StudentID NOT IN (SELECT StudentID2 FROM Pairings)";

$stmt = $conn->prepare($query);
$stmt->bind_param("ssi", $filter_major, $filter_subject, $user_id);
$stmt->execute();
$partners = $stmt->get_result();

// --- 2. GET PENDING REQUESTS ---
// Show incoming study requests waiting for user's response
$pending_query = "SELECT * FROM StudyRequests WHERE ReceiverID = ? AND Status = 'pending'";
$pending_stmt = $conn->prepare($pending_query);
$pending_stmt->bind_param("i", $user_id);
$pending_stmt->execute();
$pending_requests = $pending_stmt->get_result();

// --- 3. CHECK FOR RECENT CANCELLATIONS ---
// Notify user of recent meeting cancellations (within last 30 minutes)
$cancel_check = $conn->prepare("SELECT * FROM StudyRequests 
                                WHERE (RequesterID = ? OR ReceiverID = ?) 
                                AND Status = 'declined' 
                                AND CreatedAt > DATE_SUB(NOW(), INTERVAL 30 MINUTE)");
$cancel_check->bind_param("ii", $user_id, $user_id);
$cancel_check->execute();
$cancellation_alert = $cancel_check->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Study Buddy</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .dashboard-layout { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; }
        .alert-banner { background: #fee2e2; border: 1px solid #ef4444; color: #b91c1c; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; }
        .calendar-card { background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .sidebar-title { border-bottom: 2px solid #667eea; padding-bottom: 10px; margin-bottom: 15px; }
        .success-message { background: #d1fae5; color: #065f46; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #10b981; }
    </style>
</head>
<body onload="updateSubjects('<?php echo $filter_subject; ?>')">
    <nav>
        <div><strong>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></strong></div>
        <div style="display: flex; gap: 12px; align-items: center;">
            <!-- Notifications dropdown -->
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
            <a href="message.php">Messages</a>
            <a href="edit_profile.php">Edit Profile</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>
    <script>
        // toggle notifications dropdown
        document.addEventListener('DOMContentLoaded', function(){
            var t = document.getElementById('notifToggle');
            var m = document.getElementById('notifMenu');
            t && t.addEventListener('click', function(e){ e.preventDefault(); m.style.display = m.style.display === 'none' ? 'block' : 'none'; });

            // simple polling for unread count every 12 seconds
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
        <!-- SYSTEM NOTIFICATIONS -->
        <?php if(isset($_GET['status']) && $_GET['status'] == 'request_sent'): ?>
            <div class="success-message">Study request sent successfully!</div>
        <?php endif; ?>

        <?php if(isset($_GET['cancelled']) && $_GET['cancelled'] == 'success'): ?>
            <div class="success-message">Meeting cancelled. You are now available for new partners.</div>
        <?php endif; ?>

        <!-- CANCELLATION NOTIFICATION FROM OTHER USER -->
        <?php if($cancellation_alert->num_rows > 0): ?>
            <div class="alert-banner">
                A recent study session was cancelled. Your schedule has been updated and you are visible to new partners.
            </div>
        <?php endif; ?>

        <div class="dashboard-layout">
            <!-- LEFT COLUMN -->
            <div class="main-content">
                
                <?php if($pending_requests->num_rows > 0): ?>
                    <h2>📬 Pending Requests</h2>
                    <?php while($req = $pending_requests->fetch_assoc()): 
                        $requester_query = $conn->prepare("SELECT * FROM Students WHERE StudentID = ?");
                        $requester_query->bind_param("i", $req['RequesterID']);
                        $requester_query->execute();
                        $requester = $requester_query->get_result()->fetch_assoc();
                    ?>
                        <div class="card" style="border-left: 4px solid #f5576c; display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <strong>👤 <?php echo htmlspecialchars($requester['StudentName']); ?></strong><br>
                                <small><?php echo htmlspecialchars($requester['Major']); ?> | <?php echo htmlspecialchars($requester['Subject']); ?></small>
                            </div>
                            <form action="respond_request.php" method="POST" style="display: flex; gap: 5px;">
                                <input type="hidden" name="request_id" value="<?php echo $req['RequestID']; ?>">
                                <button type="submit" name="action" value="accepted" class="btn" style="background: #10b981;">Accept</button>
                                <button type="submit" name="action" value="declined" class="btn btn-decline">Decline</button>
                            </form>
                        </div>
                    <?php endwhile; ?>
                    <hr>
                <?php endif; ?>

                <div class="search-container" style="background: #f8fafc; padding: 20px; border-radius: 12px; margin-bottom: 25px; border: 1px solid #e2e8f0;">
                    <h3>Filter Partners</h3>
                    <form method="GET" style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 10px;">
                        <select name="filter_major" id="major" onchange="updateSubjects(document.getElementById('subject').value)">
                            <option value="Computer Science" <?php if($filter_major == 'Computer Science') echo 'selected'; ?>>Computer Science</option>
                            <option value="Mathematics" <?php if($filter_major == 'Mathematics') echo 'selected'; ?>>Mathematics</option>
                            <option value="English" <?php if($filter_major == 'English') echo 'selected'; ?>>English</option>
                        </select>
                        <select name="filter_subject" id="subject">
                            <option value="">All Subjects</option>
                        </select>
                        <button type="submit" class="btn">Filter</button>
                    </form>
                </div>
                <h2>Available Partners</h2>
                    <?php if($partners->num_rows > 0): ?>
                        <?php while($row = $partners->fetch_assoc()): ?>
                            <div class="card" style="display: flex; align-items: center; justify-content: space-between; padding: 15px; margin-bottom: 10px; border: 1px solid #eee; border-radius: 8px;">
                                <div style="flex: 1;">
                                    <strong style="font-size: 1.1em; color: #2c3e50;"><?php echo htmlspecialchars($row['StudentName']); ?></strong><br>
                                    
                                    <!-- Display the Student's Subject -->
                                    <small style="display: inline-block; margin-top: 5px; color: #667eea; font-weight: 600;">
                                        <?php echo htmlspecialchars($row['Subject']); ?>
                                    </small>
                                    
                                    <!-- Display the Preferred Time -->
                                    <small style="display: block; margin-top: 3px; color: #666;">
                                        <?php echo htmlspecialchars($row['PreferredStudyTime']); ?>
                                    </small>
                                </div>
                                
                                <form action="send_request.php" method="POST" style="margin: 0;">
                                    <input type="hidden" name="receiver_id" value="<?php echo $row['StudentID']; ?>">
                                    <button type="submit" class="btn" style="padding: 8px 15px; font-size: 0.9em;">Request</button>
                                </form>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 30px; background: #f9f9f9; border-radius: 10px;">
                            <p style="color: #999; margin: 0;">No partners available for this subject right now.</p>
                        </div>
                    <?php endif; ?>
            </div>

            <!-- RIGHT COLUMN: CALENDAR -->
            <div class="calendar-sidebar">
                <div class="calendar-card">
                    <h3 class="sidebar-title">Study Schedule</h3>
                    <?php
                    $pairings_query = $conn->prepare("SELECT p.*, s.StudentName, s.Subject, s.PreferredStudyTime FROM Pairings p JOIN Students s ON (p.StudentID1 = s.StudentID OR p.StudentID2 = s.StudentID) WHERE (p.StudentID1 = ? OR p.StudentID2 = ?) AND s.StudentID != ? ORDER BY p.CreatedAt ASC");
                    $pairings_query->bind_param("iii", $user_id, $user_id, $user_id);
                    $pairings_query->execute();
                    $my_pairings = $pairings_query->get_result();

                    if($my_pairings->num_rows > 0):
                        while($pair = $my_pairings->fetch_assoc()): ?>
                            <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 10px; margin-bottom: 10px;">
                                <strong><?php echo htmlspecialchars($pair['StudentName']); ?></strong><br>
                                <small><?php echo htmlspecialchars($pair['Subject']); ?></small><br>
                                <small><?php echo htmlspecialchars($pair['PreferredStudyTime']); ?></small><br>
                                <a href="cancel_pairing.php?id=<?php echo $pair['PairingID']; ?>" style="color: #ef4444; font-size: 0.85em; text-decoration: none; font-weight: 600;">Cancel Session</a>
                            </div>
                        <?php endwhile;
                    else: ?>
                        <p style="color: #999; font-style: italic;">No confirmed meetings.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
    function updateSubjects(preserveSubject = '') {
        const majorSelect = document.getElementById('major');
        const subjectSelect = document.getElementById('subject');
        const selectedMajor = majorSelect.value;
        const subjectData = {
            'Computer Science': ['Calculus', 'Data Structures', 'Web Development', 'Computer Architecture', 'C++ Programming'],
            'Mathematics': ['Algebra 1', 'Algebra 2', 'Calculus 1', 'Calculus 2', 'Calculus 3', 'Linear Algebra'],
            'English': ['Literacy & Composition', 'Creative Writing', 'American Literature', 'Critical Thinking']
        };
        subjectSelect.innerHTML = '<option value="">All Subjects</option>';
        if (selectedMajor && subjectData[selectedMajor]) {
            subjectData[selectedMajor].forEach(function(course) {
                let option = document.createElement('option');
                option.value = course;
                option.textContent = course;
                if (course === preserveSubject) option.selected = true;
                subjectSelect.appendChild(option);
            });
        }
    }
    </script>
</body>
</html>