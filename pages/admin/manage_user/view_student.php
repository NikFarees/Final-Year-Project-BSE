<?php
include '../../../include/ad_header.php';
include '../../../database/db_connection.php';

// Get student ID from query parameter
$student_id = isset($_GET['id']) ? $_GET['id'] : '';

// Fetch student details
$sql = "SELECT s.student_id, u.user_id, u.ic, u.name, u.username, u.email, u.address, u.phone, s.bank_number, s.bank_name,
            GROUP_CONCAT(CONCAT(l.license_name, ' (', l.license_type, ')') SEPARATOR ', ') AS licenses_enrolled
        FROM students s
        JOIN users u ON s.user_id = u.user_id
        LEFT JOIN student_licenses sl ON s.student_id = sl.student_id
        LEFT JOIN licenses l ON sl.license_id = l.license_id
        WHERE s.student_id = ?
        GROUP BY s.student_id, u.user_id, u.ic, u.name, u.username, u.email, u.address, u.phone, s.bank_number, s.bank_name";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Student not found!',
                confirmButtonText: 'Back to Manage Student'
            }).then(() => {
                window.location.href = 'list_users.php';
            });
          </script>";
    exit;
}

$student = $result->fetch_assoc();
?>

<div class="container">
  <div class="page-inner">

    <!-- Breadcrumbs -->
    <div class="page-header">
      <h4 class="page-title">Manage User</h4>
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
          <a href="#">Student Detail</a>
        </li>
      </ul>
    </div>

    <!-- Inner page content -->
    <div class="page-category">
      <div class="card">
        <div class="card-header">
          <h3 class="fw-bold mb-3">Student Detail</h3>
        </div>
        <div class="card-body">
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="student_id">Student ID</label>
              <input type="text" class="form-control" id="student_id" value="<?php echo htmlspecialchars($student['student_id']); ?>" readonly>
            </div>
            <div class="col-md-6">
              <label for="name">Name</label>
              <input type="text" class="form-control" id="name" value="<?php echo htmlspecialchars($student['name']); ?>" readonly>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="username">Username</label>
              <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($student['username']); ?>" readonly>
            </div>
            <div class="col-md-6">
              <label for="email">Email</label>
              <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($student['email']); ?>" readonly>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="ic">IC</label>
              <input type="text" class="form-control" id="ic" value="<?php echo htmlspecialchars($student['ic']); ?>" readonly>
            </div>
            <div class="col-md-6">
              <label for="phone">Phone</label>
              <input type="text" class="form-control" id="phone" value="<?php echo htmlspecialchars($student['phone']); ?>" readonly>
            </div>
          </div>
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="bank_number">Bank Number</label>
              <input type="text" class="form-control" id="bank_number" value="<?php echo htmlspecialchars($student['bank_number']); ?>" readonly>
            </div>
            <div class="col-md-6">
              <label for="bank_name">Bank Name</label>
              <input type="text" class="form-control" id="bank_name" value="<?php echo htmlspecialchars($student['bank_name']); ?>" readonly>
            </div>
          </div>
          <div class="form-group mb-3">
            <label for="address">Address</label>
            <textarea class="form-control" id="address" rows="3" readonly><?php echo htmlspecialchars($student['address']); ?></textarea>
          </div>
          <div class="form-group mb-3">
            <label for="licenses_enrolled">Licenses Enrolled</label>
            <input type="text" class="form-control" id="licenses_enrolled" value="<?php echo htmlspecialchars($student['licenses_enrolled']); ?>" readonly>
          </div>
        </div>
      </div>
    </div>
    
  </div>
</div>

<?php
include '../../../include/footer.html';
?>