<?php
include '../../../include/ad_header.php';
include '../../../database/db_connection.php'; // Include the database connection file

// Initialize variables
$announcement_id = isset($_GET['id']) ? $conn->real_escape_string($_GET['id']) : '';
$errorMessage = '';
$successMessage = '';

// Handle the deletion logic
if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes' && !empty($announcement_id)) {
    $sql_delete = "DELETE FROM announcements WHERE announcement_id = ?";
    $stmt_delete = $conn->prepare($sql_delete);

    if ($stmt_delete) {
        $stmt_delete->bind_param("s", $announcement_id);
        if ($stmt_delete->execute()) {
            $successMessage = "Announcement ID: $announcement_id has been successfully deleted.";
        } else {
            $errorMessage = "Error occurred while deleting the announcement: " . $stmt_delete->error;
        }
    } else {
        $errorMessage = "Error preparing SQL statement: " . $conn->error;
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
                window.location.href = 'list_announcement.php';
            });
        <?php elseif (!empty($errorMessage)): ?>
            Swal.fire({
                title: "Error",
                text: "<?php echo $errorMessage; ?>",
                icon: "error",
                confirmButtonText: "OK"
            }).then(() => {
                window.location.href = 'list_announcement.php';
            });
        <?php else: ?>
            Swal.fire({
                title: "Are you sure?",
                text: "You are about to delete Announcement ID: <?php echo htmlspecialchars($announcement_id); ?>.",
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
                    window.location.href = "?id=<?php echo htmlspecialchars($announcement_id); ?>&confirm=yes";
                } else {
                    window.location.href = "list_announcement.php";
                }
            });
        <?php endif; ?>
    });
</script>

<?php include '../../../include/footer.html'; ?>