<?php
/**
 * DATABASE CONNECTION FILE
 * Handles MySQL connection and creates all required tables on first run
 * Tables: Students, StudyRequests, Pairings
 */

// Database credentials
$host = "localhost";    // Database server address
$user = "root";          // Database username
$pass = "";              // Database password (empty by default)
$dbname = "studybuddy";  // Database name

// Create connection to MySQL server. Try socket (localhost) first, then TCP (127.0.0.1).
try {
    $conn = @new mysqli($host, $user, $pass);
    if ($conn->connect_error) {
        // fallback: try TCP via 127.0.0.1
        $conn = @new mysqli('127.0.0.1', $user, $pass);
    }
} catch (mysqli_sql_exception $e) {
    // Try TCP fallback if exception thrown
    try { $conn = @new mysqli('127.0.0.1', $user, $pass); } catch (mysqli_sql_exception $e2) { die('DB connection failed: ' . $e2->getMessage()); }
}

// Final check
if (!isset($conn) || $conn->connect_error) {
    die("Connection failed: " . ($conn->connect_error ?? 'unknown'));
}

// Automatically create database if it doesn't exist
$conn->query("CREATE DATABASE IF NOT EXISTS $dbname");
// Select the database for use
$conn->select_db($dbname);

/**
 * STUDENTS TABLE
 * Stores user account information
 */
$conn->query("CREATE TABLE IF NOT EXISTS Students (
    StudentID INT AUTO_INCREMENT PRIMARY KEY,
    Username VARCHAR(50) UNIQUE NOT NULL,
    Password VARCHAR(255) NOT NULL,
    StudentName VARCHAR(100),
    Major VARCHAR(100),
    Subject VARCHAR(100),
    PreferredStudyTime VARCHAR(50),
    ContactInfo VARCHAR(100),
    Bio TEXT,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

/**
 * STUDY REQUESTS TABLE
 * Tracks all study partnership requests between students
 * Status: pending (awaiting response), accepted (partner found!), declined (rejected)
 */
$conn->query("CREATE TABLE IF NOT EXISTS StudyRequests (
    RequestID INT AUTO_INCREMENT PRIMARY KEY,
    RequesterID INT,
    ReceiverID INT,
    Status ENUM('pending', 'accepted', 'declined') DEFAULT 'pending',
    Message TEXT,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

/**
 * PAIRINGS TABLE
 * Stores confirmed study partnerships (created when a request is accepted)
 */
$conn->query("CREATE TABLE IF NOT EXISTS Pairings (
    PairingID INT AUTO_INCREMENT PRIMARY KEY,
    StudentID1 INT,
    StudentID2 INT,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

/**
 * MESSAGES TABLE
 * keeps track of the study buddy conversations
 */
$conn->query("CREATE TABLE IF NOT EXISTS Messages (
    MessageID INT AUTO_INCREMENT PRIMARY KEY,
    SenderID INT DEFAULT NULL,
    SenderName VARCHAR(100),
    ReceiverID INT DEFAULT NULL,
    ReceiverName VARCHAR(100),
    GroupID INT DEFAULT NULL,
    MessageText TEXT,
    SentAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

// Ensure SenderID/ReceiverID/GroupID columns exist for older installs (check first to avoid SQL errors)
$check = $conn->query("SHOW COLUMNS FROM Messages LIKE 'SenderID'");
if ($check && $check->num_rows == 0) {
    $conn->query("ALTER TABLE Messages ADD COLUMN SenderID INT DEFAULT NULL");
}
$check2 = $conn->query("SHOW COLUMNS FROM Messages LIKE 'ReceiverID'");
if ($check2 && $check2->num_rows == 0) {
    $conn->query("ALTER TABLE Messages ADD COLUMN ReceiverID INT DEFAULT NULL");
}
$check3 = $conn->query("SHOW COLUMNS FROM Messages LIKE 'GroupID'");
if ($check3 && $check3->num_rows == 0) {
    $conn->query("ALTER TABLE Messages ADD COLUMN GroupID INT DEFAULT NULL");
}

/**
 * GROUPS TABLE
 * Stores study groups for multi-student chat rooms
 */
$conn->query("CREATE TABLE IF NOT EXISTS Groups (
    GroupID INT AUTO_INCREMENT PRIMARY KEY,
    GroupName VARCHAR(100) NOT NULL,
    Subject VARCHAR(100),
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

/**
 * GROUP MEMBERS TABLE
 * Tracks students assigned to each study group
 */
$conn->query("CREATE TABLE IF NOT EXISTS GroupMembers (
    MemberID INT AUTO_INCREMENT PRIMARY KEY,
    GroupID INT,
    StudentID INT,
    JoinedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

/**
 * NOTIFICATIONS TABLE
 * Stores user-facing notifications for actions: requests, responses, cancellations, messages
 */
$conn->query("CREATE TABLE IF NOT EXISTS Notifications (
    NotificationID INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NOT NULL,
    ActorID INT DEFAULT NULL,
    Type VARCHAR(50) NOT NULL,
    ItemID INT DEFAULT NULL,
    Message TEXT,
    IsRead TINYINT(1) DEFAULT 0,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
?>