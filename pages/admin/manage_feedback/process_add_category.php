<?php
include '../../../include/ad_header.php';
include '../../../database/db_connection.php';

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate input
    $category_name = trim($_POST['category_name']);
    $category_description = isset($_POST['category_description']) ? trim($_POST['category_description']) : null;
    
    if (empty($category_name)) {
        $_SESSION['error_message'] = "Category name cannot be empty.";
        header("Location: manage_feedback_categories.php");
        exit();
    }
    
    // Generate a unique ID for the category
    $category_id = 'CAT' . date('YmdHis') . rand(100, 999);
    
    // Prepare and execute the query
    $query = "INSERT INTO feedback_categories (feedback_category_id, feedback_name, description, created_at) 
              VALUES (?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $category_id, $category_name, $category_description);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Category added successfully.";
    } else {
        $_SESSION['error_message'] = "Error adding category: " . $conn->error;
    }
    
    $stmt->close();
} else {
    $_SESSION['error_message'] = "Invalid request method.";
}

// Redirect back to categories page
header("Location: manage_feedback_categories.php");
exit();
?>