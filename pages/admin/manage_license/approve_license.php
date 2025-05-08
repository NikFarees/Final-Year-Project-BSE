<?php
include '../../../include/ad_header.php';
include '../../../database/db_connection.php'; // Include the database connection file

// Initialize variables
$license_id = isset($_POST['license_id']) ? $conn->real_escape_string($_POST['license_id']) : 
              (isset($_GET['license_id']) ? $conn->real_escape_string($_GET['license_id']) : '');
$errorMessage = '';
$successMessage = '';

// Handle the approval logic
if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes' && !empty($license_id)) {
    // Update license status to 'Approved'
    $update_query = "UPDATE issued_licenses SET status = 'Approved' WHERE issued_license_id = ?";
    
    $stmt = $conn->prepare($update_query);
    
    if ($stmt) {
        $stmt->bind_param("s", $license_id);
        
        if ($stmt->execute()) {
            // Success
            $successMessage = "License ID: $license_id has been successfully approved. The student can collect it next week.";
            $_SESSION['success_message'] = $successMessage;
        } else {
            // Error
            $errorMessage = "Error occurred while approving the license: " . $stmt->error;
        }
        
        $stmt->close();
    } else {
        $errorMessage = "Error preparing SQL statement: " . $conn->error;
    }
} 
// Handle AJAX request
else if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($license_id)) {
    // Just return JSON for AJAX response without confirmation or processing
    echo json_encode(['success' => true, 'redirect' => "approve_license.php?license_id=$license_id"]);
    exit;
}
?>

<div class="container">
    <div class="page-inner">
        <!-- Content will be displayed via JavaScript -->
    </div>
</div>

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
                window.location.href = 'list_license.php';
            });
        <?php elseif (!empty($errorMessage)): ?>
            Swal.fire({
                title: "Error",
                text: "<?php echo $errorMessage; ?>",
                icon: "error",
                confirmButtonText: "OK"
            }).then(() => {
                window.location.href = 'list_license.php';
            });
        <?php elseif (!empty($license_id)): ?>
            Swal.fire({
                title: "Are you sure?",
                text: "Are you ready to approve this student license? Make sure the license is prepared for the student to collect next week.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, approve it!",
                cancelButtonText: "Cancel",
                confirmButtonColor: '#31ce36',
                cancelButtonColor: '#f25961'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "?license_id=<?php echo htmlspecialchars($license_id); ?>&confirm=yes";
                } else {
                    window.location.href = "list_license.php";
                }
            });
        <?php else: ?>
            window.location.href = "list_license.php";
        <?php endif; ?>
    });
</script>

<?php include '../../../include/footer.html'; ?>