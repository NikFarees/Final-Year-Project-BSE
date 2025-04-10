<?php
include '../../../include/in_header.php';
include '../../../database/db_connection.php';

if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: ../../../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

?>

<div class="container">
  <div class="page-inner">

    <!-- Breadcrumbs -->
    <div class="page-header">
      <h4 class="page-title">Instructor Profile</h4>
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
          <a href="#">Profile</a>
        </li>
      </ul>
    </div>

    <!-- Inner page content -->
    <div class="page-category">
      
      <?php
      // Query to get instructor information based on the logged-in user_id
      $query = "SELECT i.*, u.* 
                FROM instructors i 
                JOIN users u ON i.user_id = u.user_id 
                WHERE i.user_id = ?";
      
      $stmt = $conn->prepare($query);
      $stmt->bind_param("s", $user_id);
      $stmt->execute();
      $result = $stmt->get_result();
      
      if ($result->num_rows > 0) {
        $instructor = $result->fetch_assoc();
      ?>
      
      <div class="row">
        <!-- Profile Card -->
        <div class="col-md-4">
          <div class="card card-profile">
            <div class="card-header" style="background-image: url('../../../assets/img/profile-bg.jpg')">
              <div class="profile-picture">
                <div class="avatar avatar-xl">
                  <img src="../../../assets/img/profile-placeholder.jpg" alt="Instructor Profile" class="avatar-img rounded-circle">
                </div>
              </div>
            </div>
            <div class="card-body">
              <div class="user-profile text-center">
                <div class="name"><?php echo htmlspecialchars($instructor['name']); ?></div>
                <div class="job"><?php echo htmlspecialchars($instructor['instructor_id']); ?></div>
                <div class="desc">Instructor</div>
                <div class="social-media">
                  <a class="btn btn-info btn-sm" href="mailto:<?php echo htmlspecialchars($instructor['email']); ?>">
                    <i class="fa fa-envelope"></i> Email
                  </a>
                  <?php if (!empty($instructor['phone'])): ?>
                  <a class="btn btn-primary btn-sm" href="tel:<?php echo htmlspecialchars($instructor['phone']); ?>">
                    <i class="fa fa-phone"></i> Call
                  </a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Information Cards -->
        <div class="col-md-8">
          <div class="card">
            <div class="card-header">
              <div class="card-title">Instructor Information</div>
              <div class="card-category">Personal details and information</div>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-6">
                  <table class="table table-hover">
                    <tbody>
                      <tr>
                        <th width="40%">Full Name</th>
                        <td><?php echo htmlspecialchars($instructor['name']); ?></td>
                      </tr>
                      <tr>
                        <th>Instructor ID</th>
                        <td><?php echo htmlspecialchars($instructor['instructor_id']); ?></td>
                      </tr>
                      <tr>
                        <th>IC Number</th>
                        <td><?php echo htmlspecialchars($instructor['ic']); ?></td>
                      </tr>
                      <tr>
                        <th>Username</th>
                        <td><?php echo htmlspecialchars($instructor['username']); ?></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <div class="col-md-6">
                  <table class="table table-hover">
                    <tbody>
                      <tr>
                        <th width="40%">Email</th>
                        <td><?php echo htmlspecialchars($instructor['email']); ?></td>
                      </tr>
                      <tr>
                        <th>Phone</th>
                        <td><?php echo !empty($instructor['phone']) ? htmlspecialchars($instructor['phone']) : 'Not provided'; ?></td>
                      </tr>
                      <tr>
                        <th>Address</th>
                        <td><?php echo !empty($instructor['address']) ? htmlspecialchars($instructor['address']) : 'Not provided'; ?></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
            <div class="card-footer">
              <div class="d-flex justify-content-end">
                <a href="edit_profile.php" class="btn btn-primary btn-sm mr-2">
                  <i class="fa fa-edit"></i> Edit Profile
                </a>
                <a href="edit_password.php" class="btn btn-warning btn-sm">
                  <i class="fa fa-key"></i> Change Password
                </a>
              </div>
            </div>
          </div>
          
          <!-- Additional Information Card -->
          <div class="card">
            <div class="card-header">
              <div class="card-title">Activity Information</div>
              <div class="card-category">Account creation and update details</div>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-6">
                  <p><strong>Account Created:</strong><br>
                  <?php echo date('d M Y h:i A', strtotime($instructor['created_at'])); ?></p>
                </div>
                <div class="col-md-6">
                  <p><strong>Last Updated:</strong><br>
                  <?php echo date('d M Y h:i A', strtotime($instructor['updated_at'])); ?></p>
                </div>
              </div>
            </div>
          </div>

          <!-- Speciality Information Card -->
          <div class="card">
            <div class="card-header">
              <div class="card-title">Speciality Information</div>
              <div class="card-category">Licenses the instructor specializes in</div>
            </div>
            <div class="card-body">
              <?php
              // Query to get instructor's specialities
              $specialityQuery = "
                SELECT l.license_name, l.license_type, l.description 
                FROM specialities s
                JOIN licenses l ON s.license_id = l.license_id
                WHERE s.instructor_id = ?
              ";
              $specialityStmt = $conn->prepare($specialityQuery);
              $specialityStmt->bind_param("s", $instructor['instructor_id']);
              $specialityStmt->execute();
              $specialityResult = $specialityStmt->get_result();

              if ($specialityResult->num_rows > 0) {
                echo '<ul>';
                while ($speciality = $specialityResult->fetch_assoc()) {
                  echo '<li><strong>' . htmlspecialchars($speciality['license_name']) . '</strong> (' . htmlspecialchars($speciality['license_type']) . ')<br>';
                  echo '<small>' . htmlspecialchars($speciality['description']) . '</small></li>';
                }
                echo '</ul>';
              } else {
                echo '<p>No specialities found for this instructor.</p>';
              }
              $specialityStmt->close();
              ?>
            </div>
          </div>
        </div>
      </div>
      
      <?php
        } else {
          echo '<div class="alert alert-danger">Instructor information not found. Please contact administrator.</div>';
        }
        $stmt->close();
      ?>
      
    </div>
    
  </div>
</div>

<?php
include '../../../include/footer.html';
?>