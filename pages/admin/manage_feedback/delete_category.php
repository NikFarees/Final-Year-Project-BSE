<?php
include '../../../include/ad_header.php';
include '../../../database/db_connection.php'; // Include the database connection file

// Initialize variables
$category_id = isset($_GET['id']) ? $conn->real_escape_string($_GET['id']) : '';
$errorMessage = '';
$successMessage = '';

// Handle the deletion logic
if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes' && !empty($category_id)) {
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Check if there are any feedback entries using this category
        $check_query = "SELECT COUNT(*) as count FROM feedback WHERE feedback_category_id = ?";
        $check_stmt = $conn->prepare($check_query);
        
        if ($check_stmt) {
            $check_stmt->bind_param("s", $category_id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['count'] > 0) {
                throw new Exception("Cannot delete this category because it is being used by " . $row['count'] . " feedback entries.");
            }
        }
        
        // Now delete the feedback category
        $sql_delete = "DELETE FROM feedback_categories WHERE feedback_category_id = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        
        if ($stmt_delete) {
            $stmt_delete->bind_param("s", $category_id);
            
            if ($stmt_delete->execute()) {
                // Commit the transaction
                $conn->commit();
                $successMessage = "Feedback category has been successfully deleted.";
            } else {
                throw new Exception("Error occurred while deleting the category: " . $stmt_delete->error);
            }
        } else {
            throw new Exception("Error preparing SQL statement: " . $conn->error);
        }
    } catch (Exception $e) {
        // Roll back the transaction on error
        $conn->rollback();
        $errorMessage = $e->getMessage();
    }
}
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        <?php if (!empty($successMessage)): ?>
            Swal.fire({
                title: "Success",
                text: "<?php echo $successMessage; ?>",
                icon: "success",
                confirmButtonText: "OK"
            }).then(() => {
                window.location.href = 'list_feedback.php';
            });
        <?php elseif (!empty($errorMessage)): ?>
            Swal.fire({
                title: "Error",
                text: "<?php echo $errorMessage; ?>",
                icon: "error",
                confirmButtonText: "OK"
            }).then(() => {
                window.location.href = 'list_feedback.php';
            });
        <?php else: ?>
            Swal.fire({
                title: "Are you sure?",
                text: "You are about to delete this feedback category. This action cannot be undone.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, delete it!",
                cancelButtonText: "Cancel",
                customClass: {
                    confirmButton: "btn btn-success",
                    cancelButton: "btn btn-danger"
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "?id=<?php echo htmlspecialchars($category_id); ?>&confirm=yes";
                } else {
                    window.location.href = "list_feedback.php";
                }
            });
        <?php endif; ?>
    });
</script>

<?php include '../../../include/footer.html'; ?>