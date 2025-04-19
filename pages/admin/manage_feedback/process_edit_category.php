<?php
include '../../../include/ad_header.php';
include '../../../database/db_connection.php';

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate input
    $category_id = trim($_POST['category_id']);
    $category_name = trim($_POST['category_name']);
    $category_description = isset($_POST['category_description']) ? trim($_POST['category_description']) : null;
    
    if (empty($category_id) || empty($category_name)) {
        $_SESSION['error_message'] = "Category ID and name are required.";
        header("Location: manage_feedback_categories.php");
        exit();
    }
    
    // Prepare and execute the query
    $query = "UPDATE feedback_categories SET feedback_name = ?, description = ? WHERE feedback_category_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $category_name, $category_description, $category_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Category updated successfully.";
    } else {
        $_SESSION['error_message'] = "Error updating category: " . $conn->error;
    }
    
    $stmt->close();
} else {
    $_SESSION['error_message'] = "Invalid request method.";
}

// Redirect back to categories page
header("Location: manage_feedback_categories.php");
exit();
?>