<?php
include '../../../include/in_header.php';
include '../../../database/db_connection.php';

// Get the student_test_id from the query parameter
$student_test_id = $_GET['student_test_id'] ?? null;

function updateFollowUpTestEligibility($conn, $student_license_id, $status) {
    // Set the appropriate status for follow-up tests based on TES02 result
    $followup_status = ($status === 'Passed') ? 'Pending' : 'Ineligible';
    
    // Update TES03 and TES04 status for this student's license
    $update_query = $conn->prepare("
        UPDATE student_tests 
        SET status = ?, schedule_status = 'Unassigned'
        WHERE student_license_id = ? 
        AND test_id IN ('TES03', 'TES04')
    ");
    
    if (!$update_query) {
        return false;
    }
    
    $update_query->bind_param("ss", $followup_status, $student_license_id);
    $update_query->execute();
    
    // Return number of rows affected
    return $update_query->affected_rows;
}

if ($student_test_id) {
  // Fetch student test details along with test_session_id
  $student_test_query = $conn->prepare("
        SELECT 
            st.student_test_id,
            st.student_license_id,
            s.student_id,
            u.name AS student_name,
            st.score,
            st.status,
            st.comment,
            t.test_id,
            t.test_name,
            ts.test_session_id
        FROM 
            student_tests st
        JOIN 
            student_licenses sl ON st.student_license_id = sl.student_license_id
        JOIN 
            students s ON sl.student_id = s.student_id
        JOIN 
            users u ON s.user_id = u.user_id
        JOIN 
            tests t ON st.test_id = t.test_id
        JOIN 
            test_sessions ts ON t.test_id = ts.test_id
        WHERE 
            st.student_test_id = ?
    ");
  $student_test_query->bind_param("s", $student_test_id);
  $student_test_query->execute();
  $student_test_result = $student_test_query->get_result();
  $student_test = $student_test_result->fetch_assoc();

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle form submission
    $new_score = $_POST['score'];
    $comments = $_POST['comments'];
    $status = $new_score >= 42 ? 'Passed' : 'Failed'; // Determine status based on score

    // Start transaction for data integrity
    $conn->begin_transaction();
    
    try {
        // Update the student test record
        $update_query = $conn->prepare("
                UPDATE student_tests 
                SET score = ?, status = ?, comment = ? 
                WHERE student_test_id = ?
            ");
        $update_query->bind_param("dsss", $new_score, $status, $comments, $student_test_id);

        if ($update_query->execute()) {
            // Update student_lessons status based on the student_tests status
            $lesson_status = ($status === 'Passed') ? 'Pending' : 'Ineligible';
            $update_lessons_query = $conn->prepare("
                    UPDATE student_lessons 
                    SET status = ? 
                    WHERE student_license_id = ?
                ");
            $update_lessons_query->bind_param("ss", $lesson_status, $student_test['student_license_id']);
            $update_lessons_query->execute();
            
            // If this is TES02, update follow-up tests eligibility (TES03 and TES04)
            $affected_rows = 0;
            if ($student_test['test_id'] === 'TES02') {
                $affected_rows = updateFollowUpTestEligibility(
                    $conn, 
                    $student_test['student_license_id'], 
                    $status
                );
            }
            
            // Commit all changes
            $conn->commit();
            
            $success_message = 'Score updated successfully!';
            if ($student_test['test_id'] === 'TES02') {
                $success_message .= ' Also updated eligibility for ' . $affected_rows . ' follow-up tests.';
            }
            
            echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            title: 'Success',
                            text: '$success_message',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            window.location.href = '/pages/instructor/manage_test/view_test.php?test_session_id=" . htmlspecialchars($student_test['test_session_id']) . "';
                        });
                    });
                </script>";
        }
    } catch (Exception $e) {
        // Roll back on error
        $conn->rollback();
        
        echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        title: 'Error',
                        text: 'Failed to update score: " . $e->getMessage() . "',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                });
            </script>";
    }
  }
} else {
  $error_message = "Student test ID is missing.";
}

?>

<div class="container">
  <div class="page-inner">
    <!-- Breadcrumbs -->
    <div class="page-header">
      <h4 class="page-title">Edit Student Score</h4>
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
          <a href="/pages/instructor/manage_test/list_test.php">Test Overview</a>
        </li>
        <li class="separator">
          <i class="icon-arrow-right"></i>
        </li>
        <li class="nav-item">
          <a href="/pages/instructor/manage_test/view_test.php?test_session_id=<?php echo htmlspecialchars($student_test['test_session_id']); ?>">Test Session Details</a>
        </li>
        <li class="separator">
          <i class="icon-arrow-right"></i>
        </li>
        <li class="nav-item">
          <a href="#">Edit Score</a>
        </li>
      </ul>
    </div>

    <div class="page-category">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header text-center">
            <h3 class="fw-bold mb-3">Edit Student Score</h3>
            <p class="text-muted text-center">Update the score and comments for the selected student</p>
          </div>
          <div class="card-body">
            <?php if (!empty($error_message)): ?>
              <div class="alert alert-danger">
                <?php echo $error_message; ?>
              </div>
            <?php else: ?>
              <form method="POST" action="">
                <div class="row mb-3">
                  <!-- Student ID -->
                  <div class="col-md-6">
                    <label for="student_id">Student ID</label>
                    <input type="text" id="student_id" class="form-control" value="<?php echo htmlspecialchars($student_test['student_id']); ?>" readonly>
                  </div>

                  <!-- Student Name -->
                  <div class="col-md-6">
                    <label for="student_name">Student Name</label>
                    <input type="text" id="student_name" class="form-control" value="<?php echo htmlspecialchars($student_test['student_name']); ?>" readonly>
                  </div>
                </div>

                <div class="row mb-3">
                  <!-- Test Type -->
                  <div class="col-md-6">
                    <label for="test_type">Test Type</label>
                    <input type="text" id="test_type" class="form-control" value="<?php echo htmlspecialchars($student_test['test_name']); ?>" readonly>
                  </div>

                  <!-- Total Score -->
                  <div class="col-md-6">
                    <label for="score">Total Score (0/50)</label>
                    <input type="number" id="score" name="score" class="form-control" value="<?php echo htmlspecialchars($student_test['score']); ?>" min="0" max="50" required>
                  </div>
                </div>

                <div class="row mb-3">
                  <!-- Comments -->
                  <div class="col-md-12">
                    <label for="comments">Comments</label>
                    <textarea id="comments" name="comments" class="form-control"><?php echo htmlspecialchars($student_test['comment']); ?></textarea>
                  </div>
                </div>

                <div class="row mb-3">
                  <!-- Status -->
                  <div class="col-md-12">
                    <label for="status">Status</label>
                    <input type="text" id="status" class="form-control" value="<?php echo htmlspecialchars($student_test['status']); ?>" readonly>
                  </div>
                </div>

                <!-- Buttons -->
                <div class="text-center">
                  <a href="/pages/instructor/manage_test/view_test.php?test_session_id=<?php echo htmlspecialchars($student_test['test_session_id']); ?>" class="btn btn-secondary">Cancel</a>
                  <button type="submit" class="btn btn-primary">Save Score</button>
                </div>
              </form>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Include SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const scoreInput = document.getElementById('score');
    const statusInput = document.getElementById('status');

    // Add an event listener to the score input field
    scoreInput.addEventListener('input', function() {
      let score = parseFloat(scoreInput.value);

      // Ensure the score is within the valid range
      if (score > 50) {
        score = 50;
        scoreInput.value = score; // Set the value to the maximum allowed
      } else if (score < 0) {
        score = 0;
        scoreInput.value = score; // Set the value to the minimum allowed
      }

      // Update the status based on the score
      if (!isNaN(score)) {
        statusInput.value = score >= 42 ? 'Passed' : 'Failed';
      } else {
        statusInput.value = ''; // Clear the status if the input is invalid
      }
    });
  });
</script>

<?php
include '../../../include/footer.html';
?>