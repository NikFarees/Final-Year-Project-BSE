<?php
include '../../../include/ad_header.php';
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
      <h4 class="page-title">Profile</h4>
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
          <a href="#">My Profile</a>
        </li>
      </ul>
    </div>

    <!-- Inner page content -->
    <div class="page-category">
      
      <?php
      // Query to get admin information based on the logged-in user_id
      $query = "SELECT a.*, u.* 
                FROM administrators a 
                JOIN users u ON a.user_id = u.user_id 
                WHERE a.user_id = ?";
      
      $stmt = $conn->prepare($query);
      $stmt->bind_param("s", $user_id);
      $stmt->execute();
      $result = $stmt->get_result();
      
      if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();
      ?>
      
      <div class="row">
        <!-- Profile Card -->
        <div class="col-md-4">
          <div class="card card-profile">
            <div class="card-header" style="background-image: url('../../../assets/img/profile-bg.jpg')">
              <div class="profile-picture">
                <div class="avatar avatar-xl">
                  <img src="../../../assets/img/profile-placeholder.jpg" alt="Admin Profile" class="avatar-img rounded-circle">
                </div>
              </div>
            </div>
            <div class="card-body">
              <div class="user-profile text-center">
                <div class="name"><?php echo htmlspecialchars($admin['name']); ?></div>
                <div class="job"><?php echo htmlspecialchars($admin['administrator_id']); ?></div>
                <div class="desc">Administrator</div>
                <div class="social-media">
                  <a class="btn btn-info btn-sm" href="mailto:<?php echo htmlspecialchars($admin['email']); ?>">
                    <i class="fa fa-envelope"></i> Email
                  </a>
                  <?php if (!empty($admin['phone'])): ?>
                  <a class="btn btn-primary btn-sm" href="tel:<?php echo htmlspecialchars($admin['phone']); ?>">
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
              <div class="card-title">Admin Information</div>
              <div class="card-category">Personal details and information</div>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-6">
                  <table class="table table-hover">
                    <tbody>
                      <tr>
                        <th width="40%">Full Name</th>
                        <td><?php echo htmlspecialchars($admin['name']); ?></td>
                      </tr>
                      <tr>
                        <th>Admin ID</th>
                        <td><?php echo htmlspecialchars($admin['administrator_id']); ?></td>
                      </tr>
                      <tr>
                        <th>IC Number</th>
                        <td><?php echo htmlspecialchars($admin['ic']); ?></td>
                      </tr>
                      <tr>
                        <th>Username</th>
                        <td><?php echo htmlspecialchars($admin['username']); ?></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <div class="col-md-6">
                  <table class="table table-hover">
                    <tbody>
                      <tr>
                        <th width="40%">Email</th>
                        <td><?php echo htmlspecialchars($admin['email']); ?></td>
                      </tr>
                      <tr>
                        <th>Phone</th>
                        <td><?php echo !empty($admin['phone']) ? htmlspecialchars($admin['phone']) : 'Not provided'; ?></td>
                      </tr>
                      <tr>
                        <th>Address</th>
                        <td><?php echo !empty($admin['address']) ? htmlspecialchars($admin['address']) : 'Not provided'; ?></td>
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
                  <?php echo date('d M Y h:i A', strtotime($admin['created_at'])); ?></p>
                </div>
                <div class="col-md-6">
                  <p><strong>Last Updated:</strong><br>
                  <?php echo date('d M Y h:i A', strtotime($admin['updated_at'])); ?></p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <?php
        } else {
          echo '<div class="alert alert-danger">Admin information not found. Please contact administrator.</div>';
        }
        $stmt->close();
      ?>
      
    </div>
    
  </div>
</div>

<?php
include '../../../include/footer.html';
?>