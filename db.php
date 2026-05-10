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

// Create connection to MySQL server
$conn = new mysqli($host, $user, $pass);

// Check if connection failed
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
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
?>