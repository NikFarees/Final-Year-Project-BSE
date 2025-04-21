<?php
include '../../../include/in_header.php';
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

  // Fetch student list for the test session
  $student_list_query = $conn->prepare("
        SELECT 
            sts.student_test_session_id,
            st.student_test_id,
            s.student_id,
            u.name AS student_name,
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
            students s ON sl.student_id = s.student_id
        JOIN 
            users u ON s.user_id = u.user_id
        WHERE 
            sts.test_session_id = ?
    ");
  $student_list_query->bind_param("s", $test_session_id);
  $student_list_query->execute();
  $student_list_result = $student_list_query->get_result();
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
          <a href="/pages/instructor/dashboard.php">
            <i class="icon-home"></i>
          </a>
        </li>
        <li class="separator">
          <i class="icon-arrow-right"></i>
        </li>
        <li class="nav-item">
          <a href="/pages/instructor/manage_test/list_test.php">Test List</a>
        </li>
        <li class="separator">
          <i class="icon-arrow-right"></i>
        </li>
        <li class="nav-item">
          <a href="#">Test Session Details</a>
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
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Test Session Details</h3>
          </div>
          <div class="card-body">
            <div class="row">
              <!-- Test Information -->
              <div class="col-md-6">
                <h5>Test Information</h5>
                <p><strong>Test ID:</strong> <?php echo htmlspecialchars($test_session['test_id']); ?></p>
                <p><strong>Type:</strong> <?php echo htmlspecialchars($test_session['test_name']); ?></p>
                <p><strong>Date:</strong> <?php echo htmlspecialchars($test_session['test_date']); ?></p>
                <p><strong>Time:</strong> <?php echo htmlspecialchars($test_session['start_time']); ?> - <?php echo htmlspecialchars($test_session['end_time']); ?></p>
              </div>

              <!-- Status Information -->
              <div class="col-md-6">
                <h5>Status Information</h5>
                <p><strong>Status:</strong> <?php echo htmlspecialchars($test_session['status']); ?></p>
                <p><strong>Capacity:</strong> <?php echo htmlspecialchars($test_session['capacity_students']); ?></p>
                <p><strong>Enrolled:</strong> <?php echo $student_list_result->num_rows; ?></p>
                <p><strong>Instructor:</strong> <?php echo htmlspecialchars($test_session['instructor_name']); ?> (YOU)</p>
              </div>
            </div>

            <!-- Student List -->
            <h5 class="mt-4">Student List</h5>
            <div class="table-responsive">
              <table class="table table-bordered table-striped">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Attendance</th>
                    <th>Score</th>
                    <th>Comment</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php $counter = 1; ?>
                  <?php while ($student = $student_list_result->fetch_assoc()): ?>
                    <tr>
                      <td><?php echo $counter++; ?></td> <!-- Display counter instead of student_id -->
                      <td><?php echo htmlspecialchars($student['student_name']); ?></td>
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
                      <td>
                        <button class="btn btn-warning btn-sm mark-attendance"
                          data-session-id="<?php echo htmlspecialchars($student['student_test_session_id']); ?>" 
                          data-current-status="<?php echo htmlspecialchars($student['attendance_status']); ?>">
                          Mark Attendance
                        </button>
                        <a href="edit_score.php?student_test_id=<?php echo htmlspecialchars($student['student_test_id']); ?>" class="btn btn-primary btn-sm">Edit Score</a>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>

  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Handle Mark Attendance button click
    document.querySelectorAll('.mark-attendance').forEach(function(button) {
      button.addEventListener('click', function() {
        const sessionId = this.getAttribute('data-session-id');
        const currentStatus = this.getAttribute('data-current-status');

        // Show custom confirmation popup with 3 buttons
        Swal.fire({
          title: 'Mark Attendance',
          text: `Current status: ${currentStatus}`,
          icon: 'question',
          showCancelButton: true,
          showDenyButton: true,
          confirmButtonText: 'Attend',
          denyButtonText: 'Absent',
          cancelButtonText: 'Cancel'
        }).then((result) => {
          let newStatus = currentStatus; // Default to current status

          if (result.isConfirmed) {
            newStatus = 'Attend'; // Mark as Attend
          } else if (result.isDenied) {
            newStatus = 'Absent'; // Mark as Absent
          }

          // Only proceed if the status has changed
          if (newStatus !== currentStatus) {
            // Update attendance status via AJAX
            fetch('update_attendance.php', {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                  session_id: sessionId,
                  attendance_status: newStatus
                })
              })
              .then(response => response.json())
              .then(data => {
                if (data.success) {
                  Swal.fire('Success', 'Attendance updated successfully!', 'success').then(() => {
                    location.reload(); // Reload the page to reflect changes
                  });
                } else {
                  Swal.fire('Error', 'Failed to update attendance.', 'error');
                }
              })
              .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'An error occurred while updating attendance.', 'error');
              });
          }
        });
      });
    });
  });
</script>

<!-- Include SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
include '../../../include/footer.html';
?>