<?php
include '../../../include/ad_header.php';
include '../../../database/db_connection.php'; // Include the database connection file

// Initialize variables
$test_session_id = isset($_GET['test_session_id']) ? $conn->real_escape_string($_GET['test_session_id']) : '';
$errorMessage = '';
$successMessage = '';

// Handle the deletion logic
if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes' && !empty($test_session_id)) {
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // First, update any student_tests records to 'Unassigned' status if they were related to this test session
        $sql_update_tests = "UPDATE student_tests st 
                            JOIN student_test_sessions sts ON st.student_test_id = sts.student_test_id
                            SET st.schedule_status = 'Unassigned'
                            WHERE sts.test_session_id = ?";
        
        $stmt_update = $conn->prepare($sql_update_tests);
        
        if ($stmt_update) {
            $stmt_update->bind_param("s", $test_session_id);
            $stmt_update->execute();
        }
        
        // Now delete the test session (student_test_sessions will be deleted via CASCADE)
        $sql_delete = "DELETE FROM test_sessions WHERE test_session_id = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        
        if ($stmt_delete) {
            $stmt_delete->bind_param("s", $test_session_id);
            
            if ($stmt_delete->execute()) {
                // Commit the transaction
                $conn->commit();
                $successMessage = "Test session ID: $test_session_id has been successfully deleted.";
            } else {
                throw new Exception("Error occurred while deleting the test session: " . $stmt_delete->error);
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
                window.location.href = 'list_test.php';
            });
        <?php elseif (!empty($errorMessage)): ?>
            Swal.fire({
                title: "Error",
                text: "<?php echo $errorMessage; ?>",
                icon: "error",
                confirmButtonText: "OK"
            }).then(() => {
                window.location.href = 'list_test.php';
            });
        <?php else: ?>
            Swal.fire({
                title: "Are you sure?",
                text: "You are about to delete Test Session ID: <?php echo htmlspecialchars($test_session_id); ?>. This will also remove all student enrollments for this session.",
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
                    window.location.href = "?test_session_id=<?php echo htmlspecialchars($test_session_id); ?>&confirm=yes";
                } else {
                    window.location.href = "list_test.php";
                }
            });
        <?php endif; ?>
    });
</script>

<?php include '../../../include/footer.html'; ?>