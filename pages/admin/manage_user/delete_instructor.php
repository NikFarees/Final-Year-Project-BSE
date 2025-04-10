<?php
include '../../../include/ad_header.php';
include '../../../database/db_connection.php';

// Initialize variables
$instructor_id = isset($_GET['id']) ? $conn->real_escape_string($_GET['id']) : '';
$errorMessage = '';
$successMessage = '';

// Handle deletion logic
if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes' && !empty($instructor_id)) {
    // Get the user_id associated with the instructor
    $sql = "SELECT user_id FROM instructors WHERE instructor_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $instructor_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $user_id = $row['user_id'];

        // Start a transaction
        $conn->begin_transaction();
        try {
            // Delete from instructors table
            $sql = "DELETE FROM instructors WHERE instructor_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $instructor_id);
            $stmt->execute();

            // Delete from users table
            $sql = "DELETE FROM users WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $user_id);
            $stmt->execute();

            // Commit transaction
            $conn->commit();
            $successMessage = "Instructor ID: $instructor_id has been successfully deleted.";
        } catch (Exception $e) {
            $conn->rollback();
            $errorMessage = "Error occurred while deleting the instructor: " . $e->getMessage();
        }
    } else {
        $errorMessage = "Instructor not found.";
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
                window.location.href = 'list_users.php';
            });
        <?php elseif (!empty($errorMessage)): ?>
            Swal.fire({
                title: "Error",
                text: "<?php echo $errorMessage; ?>",
                icon: "error",
                confirmButtonText: "OK"
            }).then(() => {
                window.location.href = 'list_users.php';
            });
        <?php else: ?>
            Swal.fire({
                title: "Are you sure?",
                text: "You are about to delete Instructor ID: <?php echo htmlspecialchars($instructor_id); ?>.",
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
                    window.location.href = "?id=<?php echo htmlspecialchars($instructor_id); ?>&confirm=yes";
                } else {
                    window.location.href = "list_users.php";
                }
            });
        <?php endif; ?>
    });
</script>

<?php include '../../../include/footer.html'; ?>