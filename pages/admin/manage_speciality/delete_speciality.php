<?php
include '../../../include/ad_header.php';
include '../../../database/db_connection.php'; // Include the database connection file

// Initialize variables
$instructor_id = isset($_GET['instructor_id']) ? $conn->real_escape_string($_GET['instructor_id']) : '';
$license_id = isset($_GET['license_id']) ? $conn->real_escape_string($_GET['license_id']) : '';
$errorMessage = '';
$successMessage = '';

// Handle the deletion logic
if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes' && !empty($instructor_id) && !empty($license_id)) {
    $sql_delete = "DELETE FROM specialities WHERE instructor_id = ? AND license_id = ?";
    $stmt_delete = $conn->prepare($sql_delete);

    if ($stmt_delete) {
        $stmt_delete->bind_param("ss", $instructor_id, $license_id);
        if ($stmt_delete->execute()) {
            $successMessage = "Instructor ID: $instructor_id has been successfully removed from License ID: $license_id.";
        } else {
            $errorMessage = "Error occurred while deleting the specialty: " . $stmt_delete->error;
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
                window.location.href = 'list_speciality.php?id=<?php echo $license_id; ?>';
            });
        <?php elseif (!empty($errorMessage)): ?>
            Swal.fire({
                title: "Error",
                text: "<?php echo $errorMessage; ?>",
                icon: "error",
                confirmButtonText: "OK"
            }).then(() => {
                window.location.href = 'list_speciality.php?id=<?php echo $license_id; ?>';
            });
        <?php else: ?>
            Swal.fire({
                title: "Are you sure?",
                text: "You are about to delete Instructor ID: <?php echo htmlspecialchars($instructor_id); ?> from License ID: <?php echo htmlspecialchars($license_id); ?>.",
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
                    window.location.href = "?instructor_id=<?php echo htmlspecialchars($instructor_id); ?>&license_id=<?php echo htmlspecialchars($license_id); ?>&confirm=yes";
                } else {
                    window.location.href = "list_speciality.php?id=<?php echo htmlspecialchars($license_id); ?>";
                }
            });
        <?php endif; ?>
    });
</script>

<?php include '../../../include/footer.html'; ?>