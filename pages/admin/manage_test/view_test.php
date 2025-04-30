<?php
include '../../../include/ad_header.php';
include '../../../database/db_connection.php';

// Get the test_session_id from the query parameter
$test_session_id = $_GET['test_session_id'] ?? null;

if ($test_session_id) {
  // Fetch test session details
  $test_session_query = $conn->prepare("
        SELECT 
            ts.test_session_id, 
            ts.test_date, 
            ts.start_time, 
            ts.end_time, 
            ts.capacity_students, 
            ts.status, 
            t.test_name, 
            t.test_id,
            i.instructor_id,
            u.name AS instructor_name
        FROM 
            test_sessions ts
        JOIN 
            tests t ON ts.test_id = t.test_id
        LEFT JOIN 
            instructors i ON ts.instructor_id = i.instructor_id
        LEFT JOIN 
            users u ON i.user_id = u.user_id
        WHERE 
            ts.test_session_id = ?
    ");
  $test_session_query->bind_param("s", $test_session_id);
  $test_session_query->execute();
  $test_session_result = $test_session_query->get_result();
  $test_session = $test_session_result->fetch_assoc();

  $student_list_query = $conn->prepare("
      SELECT 
          sts.student_test_session_id,
          st.student_test_id,
          s.student_id,
          u.name AS student_name,
          lic.license_name,
          sts.attendance_status,
          st.score,
          st.comment,
          st.status AS test_status
      FROM 
          student_test_sessions sts
      JOIN 
          student_tests st ON sts.student_test_id = st.student_test_id
      JOIN 
          student_licenses sl ON st.student_license_id = sl.student_license_id
      JOIN 
          licenses lic ON sl.license_id = lic.license_id
      JOIN 
          students s ON sl.student_id = s.student_id
      JOIN 
          users u ON s.user_id = u.user_id
      WHERE 
          sts.test_session_id = ?
  ");
  $student_list_query->bind_param("s", $test_session_id);
  $student_list_query->execute();
  $student_list_result = $student_list_query->get_result();

  // Count statistics
  $total_students = $student_list_result->num_rows;
  $passed = 0;
  $failed = 0;
  $pending = 0;
  $attended = 0;
  $absent = 0;

  // Store results in an array to use after counting stats
  $students = [];
  while ($student = $student_list_result->fetch_assoc()) {
    $students[] = $student;
    
    // Count statistics
    if ($student['test_status'] === 'Passed') $passed++;
    elseif ($student['test_status'] === 'Failed') $failed++;
    elseif ($student['test_status'] === 'Pending') $pending++;
    
    if ($student['attendance_status'] === 'Attend') $attended++;
    else $absent++;
  }
} else {
  $error_message = "Test session ID is missing.";
}
?>

<div class="container">
  <div class="page-inner">

    <!-- Breadcrumbs -->
    <div class="page-header">
      <h4 class="page-title">Manage Test</h4>
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
          <a href="/pages/admin/manage_test/list_test.php">Test List</a>
        </li>
        <li class="separator">
          <i class="icon-arrow-right"></i>
        </li>
        <li class="nav-item">
          <a href="#">View Test Details</a>
        </li>
      </ul>
    </div>

    <!-- Inner page content -->
    <div class="page-category">
      <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger">
          <?php echo $error_message; ?>
        </div>
      <?php else: ?>        
        <!-- Test Session Details Card -->
        <div class="card mb-4">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Test Session Details</h3>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="toggle-details-btn">
              <i class="fas fa-minus"></i>
            </button>
          </div>
          <div class="card-body" id="details-card-body">
            <div class="row">
              <!-- Test Information -->
              <div class="col-md-4">
                <h5>Test Information</h5>
                <p><strong>Test ID:</strong> <?php echo htmlspecialchars($test_session['test_id']); ?></p>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($test_session['test_name']); ?></p>
                <p><strong>Date:</strong> <?php echo date('d M Y', strtotime($test_session['test_date'])); ?></p>
                <p><strong>Time:</strong> <?php echo date('h:i A', strtotime($test_session['start_time'])); ?> - <?php echo date('h:i A', strtotime($test_session['end_time'])); ?></p>
              </div>

              <!-- Status Information -->
              <div class="col-md-4">
                <h5>Status Information</h5>
                <p><strong>Status:</strong> <span class="badge badge-info"><?php echo htmlspecialchars($test_session['status']); ?></span></p>
                <p><strong>Capacity:</strong> <?php echo htmlspecialchars($test_session['capacity_students']); ?></p>
                <p><strong>Enrolled:</strong> <?php echo $total_students; ?></p>
                <p><strong>Instructor:</strong> <?php echo htmlspecialchars($test_session['instructor_name'] ?? 'Not Assigned'); ?></p>
              </div>
              
              <!-- Statistics -->
              <div class="col-md-4">
                <h5>Test Statistics</h5>
                <p><strong>Attendance:</strong> <?php echo $attended; ?> attended, <?php echo $absent; ?> absent</p>
                <p><strong>Results:</strong> 
                  <span class="badge badge-success"><?php echo $passed; ?> passed</span>
                  <span class="badge badge-danger"><?php echo $failed; ?> failed</span>
                  <span class="badge badge-warning"><?php echo $pending; ?> pending</span>
                </p>
                <p><strong>Pass Rate:</strong> <?php echo $total_students > 0 ? round(($passed / $total_students) * 100) : 0; ?>%</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Student List Card -->
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Student Results</h3>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="toggle-student-list-btn">
              <i class="fas fa-minus"></i>
            </button>
          </div>
          <div class="card-body" id="student-list-card-body">
            <div class="table-responsive">
              <table id="student-list-table" class="table table-bordered table-striped">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>License</th>
                    <th>Attendance</th>
                    <th>Score</th>
                    <th>Comment</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php $counter = 1; ?>
                  <?php foreach ($students as $student): ?>
                    <tr>
                      <td><?php echo $counter++; ?></td>
                      <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                      <td><?php echo htmlspecialchars($student['license_name']); ?></td>
                      <td>
                        <span class="badge <?php echo $student['attendance_status'] === 'Attend' ? 'badge-success' : 'badge-danger'; ?>">
                          <?php echo htmlspecialchars($student['attendance_status'] ?? 'Absent'); ?>
                        </span>
                      </td>
                      <td><?php echo $student['score'] !== null ? htmlspecialchars($student['score']) . '/50' : '-'; ?></td>
                      <td><?php echo htmlspecialchars($student['comment'] ?? '-'); ?></td>
                      <td>
                        <span class="badge 
                        <?php
                            echo $student['test_status'] === 'Passed' ? 'badge-success' : ($student['test_status'] === 'Failed' ? 'badge-danger' : ($student['test_status'] === 'Pending' ? 'badge-warning' : 'badge-secondary'));
                        ?>">
                          <?php echo htmlspecialchars($student['test_status']); ?>
                        </span>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php
include '../../../include/footer.html';
?>

<script>
  $(document).ready(function() {
    // Initialize DataTable
    $("#student-list-table").DataTable({
      "responsive": true,
      "pageLength": 10
    });
    
    // Toggle functionality for test details card
    $('#toggle-details-btn').click(function() {
      var cardBody = $('#details-card-body');
      cardBody.slideToggle(); 
      
      // Toggle the icon
      var icon = $(this).find('i');
      if (icon.hasClass('fa-minus')) {
        icon.removeClass('fa-minus').addClass('fa-plus');
      } else {
        icon.removeClass('fa-plus').addClass('fa-minus');
      }
    });
    
    // Toggle functionality for student list card
    $('#toggle-student-list-btn').click(function() {
      var cardBody = $('#student-list-card-body');
      cardBody.slideToggle(); 
      
      // Toggle the icon
      var icon = $(this).find('i');
      if (icon.hasClass('fa-minus')) {
        icon.removeClass('fa-minus').addClass('fa-plus');
      } else {
        icon.removeClass('fa-plus').addClass('fa-minus');
      }
    });
  });
</script>

<!-- Include SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
  .card {
    margin-bottom: 20px;
    box-shadow: 0 0.75rem 1.5rem rgba(18, 38, 63, 0.03);
  }
  
  .card-header {
    padding: 0.75rem 1.25rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  
  .card-title {
    margin-bottom: 0;
  }
  
  .badge {
    padding: 0.4em 0.6em;
    font-size: 85%;
  }
  
  .mr-1 {
    margin-right: 0.25rem;
  }
  
  .mb-3 {
    margin-bottom: 1rem;
  }
  
  .mb-4 {
    margin-bottom: 1.5rem;
  }
</style>