<?php
include '../../../include/ad_header.php';
include '../../../database/db_connection.php';

// Get instructor ID from query parameter
$instructor_id = isset($_GET['id']) ? $_GET['id'] : '';

// Fetch instructor details
$sql = "SELECT i.instructor_id, u.user_id, u.ic, u.name, u.username, u.email, u.address, u.phone, 
            GROUP_CONCAT(CONCAT(l.license_name, ' (', l.license_type, ')') SEPARATOR ', ') AS specialties
        FROM instructors i
        JOIN users u ON i.user_id = u.user_id
        LEFT JOIN specialities s ON i.instructor_id = s.instructor_id
        LEFT JOIN licenses l ON s.license_id = l.license_id
        WHERE i.instructor_id = ?
        GROUP BY i.instructor_id, u.user_id, u.ic, u.name, u.username, u.email, u.address, u.phone";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $instructor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Instructor not found!',
                confirmButtonText: 'Back to Manage Instructor'
            }).then(() => {
                window.location.href = 'list_users.php';
            });
          </script>";
    exit;
}

$instructor = $result->fetch_assoc();
?>

<div class="container">
  <div class="page-inner">

    <!-- Breadcrumbs -->
    <div class="page-header">
      <h4 class="page-title">Instructor Detail</h4>
      <ul class="breadcrumbs">
        <li class="nav-home">
          <a href="/pages/admin/dashboard.php">
            <i class="icon-home"></i>
          </a>
        </li>
        <li class="separator">
          <i class="icon-arrow-right"></i>
        </li>
        <li class="nav-item">
          <a href="/pages/admin/manage_user/list_users.php">User List</a>
        </li>
        <li class="separator">
          <i class="icon-arrow-right"></i>
        </li>
        <li class="nav-item">
          <a href="#">Instructor Detail</a>
        </li>
      </ul>
    </div>

    <!-- Inner page content -->
    <div class="page-category">
      <div class="card">
        <div class="card-header">
          <h3 class="fw-bold mb-3">Instructor Detail</h3>
        </div>
        <div class="card-body">
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="instructor_id">Instructor ID</label>
              <input type="text" class="form-control" id="instructor_id" value="<?php echo htmlspecialchars($instructor['instructor_id']); ?>" readonly>
            </div>
            <div class="col-md-6">
              <label for="name">Name</label>
              <input type="text" class="form-control" id="name" value="<?php echo htmlspecialchars($instructor['name']); ?>" readonly>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="username">Username</label>
              <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($instructor['username']); ?>" readonly>
            </div>
            <div class="col-md-6">
              <label for="email">Email</label>
              <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($instructor['email']); ?>" readonly>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="ic">IC</label>
              <input type="text" class="form-control" id="ic" value="<?php echo htmlspecialchars($instructor['ic']); ?>" readonly>
            </div>
            <div class="col-md-6">
              <label for="phone">Phone</label>
              <input type="text" class="form-control" id="phone" value="<?php echo htmlspecialchars($instructor['phone']); ?>" readonly>
            </div>
          </div>
          <div class="form-group mb-3">
            <label for="address">Address</label>
            <textarea class="form-control" id="address" rows="3" readonly><?php echo htmlspecialchars($instructor['address']); ?></textarea>
          </div>
          <div class="form-group mb-3">
            <label for="specialties">Specialties</label>
            <input type="text" class="form-control" id="specialties" value="<?php echo htmlspecialchars($instructor['specialties']); ?>" readonly>
          </div>
        </div>
      </div>
    </div>
    
  </div>
</div>

<?php
include '../../../include/footer.html';
?>