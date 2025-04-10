<?php
include '../../../include/in_header.php';
include '../../../database/db_connection.php';

$currentUserId = $_SESSION['user_id']; // Assuming the user ID is stored in the session

$errors = [];
$successMessage = "";

// Fetch current instructor details
$query = "
    SELECT 
        u.name, 
        u.username, 
        u.email, 
        u.address, 
        u.phone 
    FROM 
        users AS u
    JOIN 
        instructors AS i ON u.user_id = i.user_id
    WHERE 
        u.user_id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $currentUserId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "<script>
            alert('User not found!');
            window.location.href = '../dashboard.php';
          </script>";
    exit;
}

$user = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $phone = $_POST['phone'];

    // Update user details
    $updateUserQuery = "
        UPDATE users 
        SET name = ?, username = ?, email = ?, address = ?, phone = ? 
        WHERE user_id = ?
    ";

    $userStmt = $conn->prepare($updateUserQuery);
    $userStmt->bind_param("ssssss", $name, $username, $email, $address, $phone, $currentUserId);

    if ($userStmt->execute()) {
        $successMessage = "Profile updated successfully!";
    } else {
        $errors[] = "Failed to update profile. Please try again.";
    }
}
?>

<div class="container">
  <div class="page-inner">

    <!-- Breadcrumbs -->
    <div class="page-header">
      <h4 class="page-title">Edit Profile</h4>
      <ul class="breadcrumbs">
        <li class="nav-home">
          <a href="/pages/instructor/dashboard.php">
            <i class="icon-home"></i>
          </a>
        </li>
        <li class="separator">
          <i class="icon-arrow-right"></i>
        </li>
        <li class="nav-item">
          <a href="/pages/instructor/manage_profile/view_profile.php">Profile</a>
        </li>
        <li class="separator">
          <i class="icon-arrow-right"></i>
        </li>
        <li class="nav-item">
          <a href="#">Edit Profile</a>
        </li>
      </ul>
    </div>

    <!-- Inner page content -->
    <div class="page-category">
      <div class="card">
        <div class="card-header">
          <h3 class="fw-bold">Edit Profile</h3>
        </div>
        <div class="card-body">
          <form method="POST" action="">
            <div class="row mb-3">
              <div class="col-md-6">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
              </div>
              <div class="col-md-6">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
              </div>
            </div>
            <div class="row mb-3">
              <div class="col-md-6">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
              </div>
              <div class="col-md-6">
                <label for="phone">Phone</label>
                <input type="text" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>">
              </div>
            </div>
            <div class="row mb-3">
              <div class="col-md-12">
                <label for="address">Address</label>
                <textarea id="address" name="address" class="form-control" rows="3"><?php echo htmlspecialchars($user['address']); ?></textarea>
              </div>
            </div>
            <button type="submit" class="btn btn-primary mt-3 d-block mx-auto">Update Profile</button>
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