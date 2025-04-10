<?php
include '../../../include/st_header.php';
include '../../../database/db_connection.php';

$currentUserId = $_SESSION['user_id']; // Assuming the user ID is stored in the session

$errors = [];
$successMessage = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    // Fetch the current password hash from the database
    $query = "SELECT password FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $currentUserId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        $errors[] = "User not found.";
    } else {
        $user = $result->fetch_assoc();
        $currentPasswordHash = $user['password'];

        // Verify the current password
        if (!hash_equals(hash('sha256', $currentPassword), $currentPasswordHash)) {
            $errors[] = "Current password is incorrect.";
        } elseif ($newPassword !== $confirmPassword) {
            $errors[] = "New password and confirmation password do not match.";
        } else {
            // Update the password
            $newPasswordHash = hash('sha256', $newPassword);
            $updateQuery = "UPDATE users SET password = ? WHERE user_id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param("ss", $newPasswordHash, $currentUserId);

            if ($updateStmt->execute()) {
                $successMessage = "Password updated successfully!";
            } else {
                $errors[] = "Failed to update password. Please try again.";
            }
        }
    }
}
?>

<div class="container">
  <div class="page-inner">

    <!-- Breadcrumbs -->
    <div class="page-header">
      <h4 class="page-title">Edit Password</h4>
      <ul class="breadcrumbs">
        <li class="nav-home">
          <a href="/pages/student/dashboard.php">
            <i class="icon-home"></i>
          </a>
        </li>
        <li class="separator">
          <i class="icon-arrow-right"></i>
        </li>
        <li class="nav-item">
          <a href="/pages/student/manage_profile/view_profile.php">Profile</a>
        </li>
        <li class="separator">
          <i class="icon-arrow-right"></i>
        </li>
        <li class="nav-item">
          <a href="#">Edit Password</a>
        </li>
      </ul>
    </div>

    <!-- Inner page content -->
    <div class="page-category">
      <div class="card">
        <div class="card-header">
          <h3 class="fw-bold">Change Password</h3>
        </div>
        <div class="card-body">
          <form method="POST" action="">
            <div class="form-group mb-3">
              <label for="current_password">Current Password</label>
              <input type="password" id="current_password" name="current_password" class="form-control" required>
            </div>
            <div class="form-group mb-3">
              <label for="new_password">New Password</label>
              <input type="password" id="new_password" name="new_password" class="form-control" required>
            </div>
            <div class="form-group mb-3">
              <label for="confirm_password">Confirm New Password</label>
              <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary mt-3 d-block mx-auto">Update Password</button>
          </form>
        </div>
      </div>
    </div>
    
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (!empty($errors)): ?>
            Swal.fire({
                title: "Error!",
                html: "<?php echo implode('<br>', $errors); ?>",
                icon: "error"
            });
        <?php endif; ?>

        <?php if (!empty($successMessage)): ?>
            Swal.fire({
                title: "Success!",
                text: "<?php echo $successMessage; ?>",
                icon: "success",
                confirmButtonText: "OK"
            }).then(() => {
                window.location.href = 'view_profile.php';
            });
        <?php endif; ?>
    });
</script>

<?php
include '../../../include/footer.html';
?>