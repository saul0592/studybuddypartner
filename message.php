<?php
//EDIT MESSAGE PAGE
//User can receive messages from their partner and send messages to their partner   
session_start(); // Get user session
include 'db.php'; //connects to database
//check if user is logged in, if not they go back to homepage 
if (!isset($_SESSION['user_id']))
{
    header("Location: login.php");
    exit();
}
//get the name of the user that's logged in
$user = $_SESSION['name'];
//checks if delete was requested
if (isset($_GET['del']))
{
    $id_to_delete = $_GET['del']; //delete specific message using its ID
    mysqli_query($conn, "DELETE FROM Messages WHERE MessageID = '$id_to_delete'"); //delete selected message from messages table
    header("Location: message.php");
    exit();
}
//check if the send button is clicked
if (isset($_POST['submit']))
{
    $to = $_POST['receiver']; //get the person receiving the message
    $msg = $_POST['message']; //get message to textarea
    
    //save the message into messages table
    mysqli_query($conn, "INSERT INTO Messages (SenderName, ReceiverName, MessageText) VALUES ('$user', '$to', '$msg')");
    
    //a temporary message pop up to show message is sent
    echo "<div class='success-message' style='text-align:center;'>Message sent!</div>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Messages</title>
    <!--connects css file-->
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!--navigation link to dashboard and welcome message-->
    <nav>
        <div><strong style= "color: #4738ec;">Welcome, <?php echo $user;?></strong></div>
        <div><a href="welcome.php">Main page</a></div>
    </nav>
    <!--message history section-->
    <div class="container" style="max-width: 800px; width: 95%;">
        <div class="card" style="display: block; width: 100%; padding: 25px;">
            <h3 style="color: #4738ec">Message History</h3>
            <div style="margin-top:20px;">
                <?php
                //get messages from logged in user
                $sql = "SELECT * FROM Messages WHERE ReceiverName = '$user' OR SenderName = '$user' ORDER BY SentAt DESC";
                $res = mysqli_query($conn, $sql);
                while ($row = mysqli_fetch_assoc($res))
                    {
                        if ($row['SenderName'] == $user){ //check if user sent message
                            $label = "TO: " . $row['ReceiverName']; //display who the receiver is
                            $bgColor = "#e0e7ff"; //light blue color to signify 'sent' message
                            $nameColor = "#5034da"; //text color
                        } else 
                        {
                            $label = "From: " . $row['SenderName']; //display who the message came from
                            $bgColor = "#b5b9be"; //light gray for received
                            $nameColor = "#4738ec";
                        }
                        //creates boxes for each message
                        echo "<div style='background: $bgColor; padding: 15px; border-radius: 8px; margin-bottom: 10px; border: 1px solid #ddd;'>";
                        //show the sender/receiver 
                        echo "<b style='color: $nameColor; font-weight: bold;'>$label</b><br>";
                        //displays the message
                        echo "<span>" . htmlspecialchars($row['MessageText']) . "</span>";
                        //delete link section
                        echo "<div style= 'text-align: right; margin-top: 10px;'>";
                        //delete button
                        echo"<a href= 'message.php?del=" . $row['MessageID'] . "'style=' color: #ff4d4d; font-size: 11px; text-decoration: none;'>[Delete]</a>";
                        echo "</div>";
                        echo "</div>";
                    }
                ?>
            </div>
        </div>
        <!--Send a new message -->
        <div class = "card" style= "margin-top: 20px; padding: 25px;">
            <h3 style="color: #4738ec">New message</h3>
            <form method= "post">
                <!--textbox for partners name-->
                <input type="text" name="receiver" placeholder="Send To(Your Partners name):" required style="width:100%; margin-bottom: 10px; padding: 10px;">
                <!--textbox for users message-->
                <textarea name="message" placeholder="Type your message here..." required style="width:100%; height:100px; padding: 10px;"></textarea>
                <!--Submission button-->
                <button type="submit" name="submit" class="btn" style="width:100%; margin-top:10px;">Send</button>
            </form>
        </div>
    </div>
</body>
</html>