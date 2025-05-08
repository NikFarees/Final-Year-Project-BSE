<?php
include '../../../include/ad_header.php';
include '../../../database/db_connection.php'; // Include the database connection file

// Initialize variables
$license_id = isset($_POST['id']) ? $conn->real_escape_string($_POST['id']) : 
              (isset($_GET['id']) ? $conn->real_escape_string($_GET['id']) : '');
$errorMessage = '';
$successMessage = '';

// Handle the approval logic
if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes' && !empty($license_id)) {
    // Current date and time for issue date
    $current_date = date('Y-m-d');
    $current_time = date('H:i:s');
    
    // Update license status to 'Issued' with timestamp
    $update_query = "UPDATE issued_licenses 
                    SET status = 'Issued', 
                        issued_date = ?, 
                        issued_time = ? 
                    WHERE issued_license_id = ?";
    
    $stmt = $conn->prepare($update_query);
    
    if ($stmt) {
        $stmt->bind_param("sss", $current_date, $current_time, $license_id);
        
        if ($stmt->execute()) {
            // Success
            $successMessage = "License ID: $license_id has been successfully marked as collected and issued to the student.";
            $_SESSION['success_message'] = $successMessage;
        } else {
            // Error
            $errorMessage = "Error occurred while marking the license as collected: " . $stmt->error;
        }
        
        $stmt->close();
    } else {
        $errorMessage = "Error preparing SQL statement: " . $conn->error;
    }
} 
// Handle AJAX request
else if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($license_id)) {
    // Just return JSON for AJAX response without confirmation or processing
    echo json_encode(['success' => true, 'redirect' => "issued_license.php?id=$license_id"]);
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
                title: "Confirm License Collection",
                text: "Are you sure the student has already collected their license? This will mark the license as issued and record the current date and time.",
                icon: "question",
                showCancelButton: true,
                confirmButtonText: "Yes, it's been collected!",
                cancelButtonText: "Cancel",
                confirmButtonColor: '#1572E8',
                cancelButtonColor: '#f25961'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "?id=<?php echo htmlspecialchars($license_id); ?>&confirm=yes";
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