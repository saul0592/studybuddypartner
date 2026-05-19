<?php
/**
 * MESSAGES PAGE
 * Supports multiple chat threads per user (one per partner)
 * Messages store both IDs and names for robustness
 */

session_start();
// Enable errors during debugging — remove or set to 0 in production
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db.php';

// Defensive check: if DB connection failed, show readable error instead of HTTP 500
if (!isset($conn) || ($conn instanceof mysqli && $conn->connect_error)) {
    $msg = isset($conn->connect_error) ? $conn->connect_error : 'Database connection not available';
    error_log("[message.php] DB connection error: " . $msg);
    echo "<h2>Database connection error</h2><pre>" . htmlspecialchars($msg) . "</pre>";
    exit();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];

// --- Build partner list: active pairings + historic message partners ---
$partners_ids = [];

// Active pairings
$pstmt = $conn->prepare("SELECT StudentID1, StudentID2 FROM Pairings WHERE StudentID1 = ? OR StudentID2 = ?");
$pstmt->bind_param("ii", $user_id, $user_id);
$pstmt->execute();
$pres = $pstmt->get_result();
while ($prow = $pres->fetch_assoc()) {
    $other = ($prow['StudentID1'] == $user_id) ? $prow['StudentID2'] : $prow['StudentID1'];
    if ($other && !in_array($other, $partners_ids)) $partners_ids[] = $other;
}

// Historical message partners
$mstmt = $conn->prepare(
    "SELECT DISTINCT SenderID AS pid FROM Messages WHERE ReceiverID = ? AND SenderID IS NOT NULL 
     UNION 
     SELECT DISTINCT ReceiverID AS pid FROM Messages WHERE SenderID = ? AND ReceiverID IS NOT NULL"
);
$mstmt->bind_param("ii", $user_id, $user_id);
$mstmt->execute();
$mres = $mstmt->get_result();
while ($mid = $mres->fetch_assoc()) {
    if ($mid['pid'] && !in_array($mid['pid'], $partners_ids)) $partners_ids[] = $mid['pid'];
}

// Prepare partner details (id + name)
$partners = [];
if (count($partners_ids) > 0) {
    foreach ($partners_ids as $pid) {
        $pn = $conn->prepare("SELECT StudentName FROM Students WHERE StudentID = ? LIMIT 1");
        $pn->bind_param("i", $pid);
        $pn->execute();
        $pnr = $pn->get_result()->fetch_assoc();
        if ($pnr) $partners[] = ['id' => $pid, 'name' => $pnr['StudentName']];
    }
}

// chat_with parameter selects which partner to open
$chat_with = isset($_GET['chat_with']) ? (int)$_GET['chat_with'] : null;
if (!$chat_with && count($partners) > 0) $chat_with = $partners[0]['id'];

$has_partner = false;
$partner_name = null;
if ($chat_with) {
    $pn = $conn->prepare("SELECT StudentName FROM Students WHERE StudentID = ? LIMIT 1");
    $pn->bind_param("i", $chat_with);
    $pn->execute();
    $pnr = $pn->get_result()->fetch_assoc();
    if ($pnr && $pnr['StudentName']) { $has_partner = true; $partner_name = $pnr['StudentName']; }
}

// --- Deletion (only sender can delete) ---
if (isset($_GET['del'])) {
    $message_id = (int)$_GET['del'];
    $delete = $conn->prepare("DELETE FROM Messages WHERE MessageID = ? AND (SenderID = ? OR SenderName = ?)");
    $delete->bind_param("iis", $message_id, $user_id, $user_name);
    $delete->execute();
    
    $back = 'message.php';
    if ($chat_with) $back .= '?chat_with=' . (int)$chat_with;
    header('Location: ' . $back);
    exit();
}

// --- Sending a new message ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && $has_partner) {
    $text = trim($_POST['message']);
    if ($text !== '') {
        $ins = $conn->prepare("INSERT INTO Messages (SenderID, SenderName, ReceiverID, ReceiverName, MessageText) VALUES (?, ?, ?, ?, ?)");
        $ins->bind_param("isiss", $user_id, $user_name, $chat_with, $partner_name, $text);
        $ins->execute();
        $message_id = $ins->insert_id;
        
        $conn->query("CREATE TABLE IF NOT EXISTS Notifications (
            NotifID INT AUTO_INCREMENT PRIMARY KEY,
            UserID INT,
            ActorID INT,
            Type VARCHAR(50),
            ItemID INT,
            Message TEXT,
            IsRead TINYINT(1) DEFAULT 0,
            CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        $notif = $conn->prepare("INSERT INTO Notifications (UserID, ActorID, Type, ItemID, Message) VALUES (?, ?, ?, ?, ?)");
        $type = 'new_message';
        $nmsg = 'You have a new message.';
        $notif->bind_param("iisis", $chat_with, $user_id, $type, $message_id, $nmsg);
        $notif->execute();
        
        $redir = 'message.php?chat_with=' . (int)$chat_with;
        header('Location: ' . $redir);
        exit();
    }
}

// --- Load messages for current chat (ID-based) ---
$messages = [];
if ($has_partner) {
    $mq = $conn->prepare(
        "SELECT * FROM Messages WHERE (SenderID = ? AND ReceiverID = ?) OR (SenderID = ? AND ReceiverID = ?) ORDER BY SentAt ASC"
    );
    $mq->bind_param("iiii", $user_id, $chat_with, $chat_with, $user_id);
    $mq->execute();
    $mres = $mq->get_result();
    while ($r = $mres->fetch_assoc()) $messages[] = $r;
    
    // Fallback name matching
    if (count($messages) == 0) {
        $mn = $conn->prepare(
            "SELECT * FROM Messages WHERE (SenderName = ? AND ReceiverName = ?) OR (SenderName = ? AND ReceiverName = ?) ORDER BY SentAt ASC"
        );
        $mn->bind_param("ssss", $user_name, $partner_name, $partner_name, $user_name);
        $mn->execute();
        $mnr = $mn->get_result();
        while ($r = $mnr->fetch_assoc()) $messages[] = $r;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Chats</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Estilos base para asegurar que la cuadrícula se mantenga estable */
        .chat-layout-grid { 
            display: grid !important; 
            grid-template-columns: 260px 1fr !important; 
            gap: 20px !important; 
            align-items: start !important;
            width: 100% !important;
        }
        .partner-list-item { 
            padding: 12px !important; 
            border-radius: 8px !important; 
            cursor: pointer !important; 
            transition: background 0.2s !important; 
            margin-bottom: 5px !important; 
        }
        .partner-list-item.active { 
            background: #eef2ff !important; 
            border-left: 4px solid #4738ec !important; 
        }
    </style>
</head>
<body>
<nav>
    <div><strong style="color:#4738ec;">Welcome, <?php echo htmlspecialchars($user_name); ?></strong></div>
    <div><a href="welcome.php">Main page</a></div>
</nav>

<div class="container">
    <h2>Chats</h2>
    <?php if (count($partners) == 0): ?>
        <div class="card"><p>No chats available yet. Pair with someone to start a conversation.</p></div>
    <?php else: ?>
        <div class="chat-layout-grid">
            <div>
                <div class="card" style="padding: 15px !important;">
                    <h4 style="margin-top:0; margin-bottom:12px; color:#374151;">Your Partners</h4>
                    <?php foreach ($partners as $p): ?>
                        <a href="message.php?chat_with=<?php echo $p['id']; ?>" style="text-decoration:none; color:inherit; display:block;">
                            <div class="partner-list-item <?php echo ($chat_with == $p['id'] ? 'active' : ''); ?>">
                                <strong>👤 <?php echo htmlspecialchars($p['name']); ?></strong>
                                <div style="font-size:12px; color:#666; margin-top:2px;">ID: <?php echo $p['id']; ?></div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div>
                <?php if (!$has_partner): ?>
                    <div class="card">Select a chat from the left to view messages.</div>
                <?php else: ?>
                    <div style="background: #ffffff !important; border: 1px solid #e5e7eb !important; border-radius: 12px !important; padding: 25px !important; display: flex !important; flex-direction: column !important; gap: 20px !important; width: 100% !important; box-sizing: border-box !important;">
                        
                        <h3 style="margin:0; color:#1f2937; font-family: inherit;">Conversation with <?php echo htmlspecialchars($partner_name); ?></h3>
                        
                        <div style="max-height: 50vh !important; overflow-y: auto !important; padding: 15px !important; display: flex !important; flex-direction: column !important; gap: 14px !important; background: #f9fafb !important; border: 1px solid #e5e7eb !important; border-radius: 8px !important; width: 100% !important; box-sizing: border-box !important;">
                            
                            <?php if (count($messages) == 0): ?>
                                <p style="color:#666; text-align: center; margin: 20px 0;">No messages yet. Send the first message.</p>
                            <?php else: foreach ($messages as $row): 
                                $isMe = (isset($row['SenderID']) && $row['SenderID'] == $user_id) || (isset($row['SenderName']) && $row['SenderName'] == $user_name);
                                
                                // Configuración dinámica de estilos según el emisor
                                $justify = $isMe ? 'flex-end' : 'flex-start';
                                $bg_color = $isMe ? '#e0e7ff' : '#f3f4f6'; // Azul claro para mí, gris para el compañero
                                $text_color = $isMe ? '#1e1b4b' : '#1f2937';
                                $radius = $isMe ? '16px 16px 4px 16px' : '16px 16px 16px 4px';
                                $label = $isMe ? 'You' : htmlspecialchars($row['SenderName']);
                            ?>
                                <div style="display: flex !important; width: 100% !important; justify-content: <?php echo $justify; ?> !important; box-sizing: border-box !important;">
                                    
                                    <div style="background-color: <?php echo $bg_color; ?> !important; color: <?php echo $text_color; ?> !important; border-radius: <?php echo $radius; ?> !important; max-width: 70% !important; min-width: 120px !important; padding: 12px 16px !important; font-size: 0.95em !important; line-height: 1.4 !important; box-shadow: 0 1px 2px rgba(0,0,0,0.08) !important; word-wrap: break-word !important; white-space: normal !important; display: block !important;">
                                        
                                        <div style="font-size: 0.78em !important; font-weight: 700 !important; margin-bottom: 4px !important; color: #4b5563 !important;"><?php echo $label; ?></div>
                                        
                                        <div style="width: 100% !important; font-family: inherit;"><?php echo htmlspecialchars($row['MessageText']); ?></div>
                                        
                                        <?php if ($isMe): ?>
                                            <div style="text-align: right !important; margin-top: 6px !important;">
                                                <a href="message.php?chat_with=<?php echo $chat_with; ?>&del=<?php echo $row['MessageID']; ?>" style="color: #ef4444 !important; font-size: 0.75em !important; text-decoration: none !important; font-weight: 600 !important;">Delete</a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                </div>
                            <?php endforeach; endif; ?>
                        </div>

                        <form method="post" style="margin: 0 !important; width: 100% !important;">
                            <textarea name="message" required placeholder="Type your message..." style="width:100% !important; height:80px !important; padding:12px !important; border-radius:6px !important; border:1px solid #ccc !important; resize:none !important; font-family:inherit !important; box-sizing: border-box !important; display: block !important;"></textarea>
                            <button type="submit" name="submit" class="btn" style="margin-top:10px !important; padding:10px 24px !important; width: auto !important; display: block !important;">Send Message</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
</body>
</html>