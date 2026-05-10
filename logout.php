<?php
/**
 * LOGOUT FILE
 * Ends user session and redirects to home page
 */

session_start();    // Start session
session_destroy();  // Destroy session data
header("Location: index.php");  // Redirect to home
?>