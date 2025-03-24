<?php
session_start();

// Check if the user is logged in
if (isset($_SESSION['user_id'])) {
    // Read the user ID
    $user_id = $_SESSION['user_id'];

    // Destroy the session
    session_unset();
    session_destroy();

    // Redirect to login page
    header("Location: login_frontend.php");
    exit;
} else {
    // If no user is logged in, redirect to login page
    header("Location: login_frontend.php");
    exit;
}
?>