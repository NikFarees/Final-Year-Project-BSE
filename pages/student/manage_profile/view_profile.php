<?php
include '../../../include/st_header.php';
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
          <a href="/pages/student/dashboard.php">
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
      // Query to get student information based on the logged-in user_id
      $query = "SELECT s.*, u.* 
                FROM students s 
                JOIN users u ON s.user_id = u.user_id 
                WHERE s.user_id = ?";
      
      $stmt = $conn->prepare($query);
      $stmt->bind_param("s", $user_id);
      $stmt->execute();
      $result = $stmt->get_result();
      
      if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
      ?>
      
      <div class="row">
        <!-- Profile Card -->
        <div class="col-md-4">
            <div class="card card-profile">
              <div class="card-header" style="background-image: url('../../../assets/img/profile-bg.jpg')">
                <div class="profile-picture">
                  <div class="avatar avatar-xl">
                    <span class="avatar-initial rounded-circle bg-primary"><?php echo substr($student['name'], 0, 1); ?></span>
                  </div>
                </div>
              </div>
              <div class="card-body">
                <div class="user-profile text-center">
                  <div class="name"><?php echo htmlspecialchars($student['name']); ?></div>
                  <div class="job"><?php echo htmlspecialchars($student['student_id']); ?></div>
                  <div class="desc">Student</div>
                </div>
              </div>
            </div>
          </div>
        
        <!-- Information Cards -->
        <div class="col-md-8">
          <div class="card">
            <div class="card-header">
              <div class="card-title">Student Information</div>
              <div class="card-category">Personal details and information</div>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-6">
                  <table class="table table-hover">
                    <tbody>
                      <tr>
                        <th width="40%">Full Name</th>
                        <td><?php echo htmlspecialchars($student['name']); ?></td>
                      </tr>
                      <tr>
                        <th>Student ID</th>
                        <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                      </tr>
                      <tr>
                        <th>IC Number</th>
                        <td><?php echo htmlspecialchars($student['ic']); ?></td>
                      </tr>
                      <tr>
                        <th>Date of Birth</th>
                        <td><?php echo date('d M Y', strtotime($student['dob'])); ?></td>
                      </tr>
                      <tr>
                        <th>Username</th>
                        <td><?php echo htmlspecialchars($student['username']); ?></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <div class="col-md-6">
                  <table class="table table-hover">
                    <tbody>
                      <tr>
                        <th width="40%">Email</th>
                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                      </tr>
                      <tr>
                        <th>Phone</th>
                        <td><?php echo !empty($student['phone']) ? htmlspecialchars($student['phone']) : 'Not provided'; ?></td>
                      </tr>
                      <tr>
                        <th>Address</th>
                        <td><?php echo !empty($student['address']) ? htmlspecialchars($student['address']) : 'Not provided'; ?></td>
                      </tr>
                      <tr>
                        <th>Bank Name</th>
                        <td><?php echo !empty($student['bank_name']) ? htmlspecialchars($student['bank_name']) : 'Not provided'; ?></td>
                      </tr>
                      <tr>
                        <th>Bank Account</th>
                        <td><?php echo !empty($student['bank_number']) ? htmlspecialchars($student['bank_number']) : 'Not provided'; ?></td>
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
                  <?php echo date('d M Y h:i A', strtotime($student['created_at'])); ?></p>
                </div>
                <div class="col-md-6">
                  <p><strong>Last Updated:</strong><br>
                  <?php echo date('d M Y h:i A', strtotime($student['updated_at'])); ?></p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <?php
        } else {
          echo '<div class="alert alert-danger">Student information not found. Please contact administrator.</div>';
        }
        $stmt->close();
      ?>
      
    </div>
    
  </div>
</div>

<?php
include '../../../include/footer.html';
?>

<style>
  .avatar-initial {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    font-weight: 600;
    color: #fff;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
    background-image: linear-gradient(135deg, #1572e8 0%, #4286f4 100%);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
    border: 2px solid rgba(255, 255, 255, 0.6);
    transition: all 0.3s ease;
  }
</style>