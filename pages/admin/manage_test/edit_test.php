<?php
include '../../../include/ad_header.php';
include '../../../database/db_connection.php';

$test_session_id = $_GET['test_session_id'] ?? null;

if ($test_session_id) {

  // Fetch enrolled student names and licenses
  $enrolled_students_query = $conn->prepare("
      SELECT u.name AS student_name, l.license_name
      FROM student_test_sessions sts
      JOIN student_tests st ON sts.student_test_id = st.student_test_id
      JOIN student_licenses sl ON st.student_license_id = sl.student_license_id
      JOIN licenses l ON sl.license_id = l.license_id
      JOIN students s ON sl.student_id = s.student_id
      JOIN users u ON s.user_id = u.user_id
      WHERE sts.test_session_id = ?
  ");

  $enrolled_students_query->bind_param("s", $test_session_id);
  $enrolled_students_query->execute();
  $enrolled_students_result = $enrolled_students_query->get_result();

  $enrolled_students_display = [];
  while ($row = $enrolled_students_result->fetch_assoc()) {
    $enrolled_students_display[] = $row['student_name'] . ' (' . $row['license_name'] . ')';
  }
  $enrolled_count = count($enrolled_students_display);

  // Fetch test session details
  $test_session_query = $conn->prepare("
        SELECT 
            ts.test_session_id,
            ts.test_date,
            ts.start_time,
            ts.end_time,
            ts.capacity_students,
            ts.status,
            t.test_id,
            t.test_name,
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

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $test_date = $_POST['testDate'];
    $start_time = $_POST['startTime'];
    $end_time = $_POST['endTime'];
    $capacity = intval($_POST['capacity']);
    $instructor_id = $_POST['instructor'];
    $new_enrolled_students = $_POST['enrolledStudents'] ?? [];

    // Get number of already enrolled students
    $stmt = $conn->prepare("SELECT COUNT(*) AS enrolled_count FROM student_test_sessions WHERE test_session_id = ?");
    $stmt->bind_param("s", $test_session_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $current_enrolled_count = intval($row['enrolled_count']);

    $total_after_addition = $current_enrolled_count + count($new_enrolled_students);

    if ($capacity < $test_session['capacity_students']) {
      $error_message = "Capacity cannot be decreased. Current capacity is already in use.";
    } elseif ($total_after_addition > $capacity) {
      $error_message = "Total enrolled students exceed the new capacity.";
    } else {
      // Update test session
      $update_query = $conn->prepare("
              UPDATE test_sessions 
              SET test_date = ?, start_time = ?, end_time = ?, capacity_students = ?, instructor_id = ? 
              WHERE test_session_id = ?
          ");
      $update_query->bind_param("sssiss", $test_date, $start_time, $end_time, $capacity, $instructor_id, $test_session_id);

      if ($update_query->execute()) {
        foreach ($new_enrolled_students as $student_test_id) {
          // Avoid duplicate enrollments
          $check_query = $conn->prepare("SELECT * FROM student_test_sessions WHERE student_test_id = ? AND test_session_id = ?");
          $check_query->bind_param("ss", $student_test_id, $test_session_id);
          $check_query->execute();
          $check_result = $check_query->get_result();

          if ($check_result->num_rows === 0) {
            // 1. Update schedule_status
            $update_status_query = $conn->prepare("UPDATE student_tests SET schedule_status = 'Assigned' WHERE student_test_id = ?");
            $update_status_query->bind_param("s", $student_test_id);
            $update_status_query->execute();

            // 2. Get student_id numeric (last 3 digits)
            $student_id_result = $conn->query("
                SELECT s.student_id FROM students s
                JOIN student_licenses sl ON sl.student_id = s.student_id
                JOIN student_tests st ON st.student_license_id = sl.student_license_id
                WHERE st.student_test_id = '$student_test_id' LIMIT 1
            ");
            $student_id_row = $student_id_result->fetch_assoc();
            $student_id_numeric = substr($student_id_row['student_id'], -3);

            // 3. Get session increment
            $session_increment_result = $conn->query("
                SELECT MAX(CAST(SUBSTRING(student_test_session_id, 6, 3) AS UNSIGNED)) AS max_id 
                FROM student_test_sessions
            ");
            $session_increment_row = $session_increment_result->fetch_assoc();
            $session_increment = $session_increment_row['max_id'] ? $session_increment_row['max_id'] + 1 : 1;

            // 4. Get session count part (from test_session_id, e.g., TSES002240416 → 002)
            $session_count = (int)substr($test_session_id, 4, 3); // Assumes test_session_id format is TSES + 3-digit count + date

            // 5. Generate student_test_session_id
            $student_test_session_id = sprintf(
              'STSES%03d%03d%03d',
              (int)$session_increment,
              (int)$student_id_numeric,
              (int)$session_count
            );

            // 6. Insert new session record
            $enroll_query = $conn->prepare("INSERT INTO student_test_sessions (student_test_session_id, student_test_id, test_session_id) VALUES (?, ?, ?)");
            $enroll_query->bind_param("sss", $student_test_session_id, $student_test_id, $test_session_id);
            $enroll_query->execute();
          }
        }

        echo "<script>
                  document.addEventListener('DOMContentLoaded', function() {
                      Swal.fire({
                          title: 'Success',
                          text: 'Test session updated successfully!',
                          icon: 'success',
                          confirmButtonText: 'OK'
                      }).then(() => {
                          window.location.href = 'list_test.php';
                      });
                  });
              </script>";
      } else {
        $error_message = "Failed to update test session.";
      }
    }
  }
} else {
  $error_message = "Test session ID is missing.";
}

// Fetch instructors
$instructors_query = "SELECT i.instructor_id, u.name AS instructor_name FROM instructors i JOIN users u ON i.user_id = u.user_id";
$instructors_result = mysqli_query($conn, $instructors_query);
?>

<div class="container">
  <div class="page-inner">

    <!-- Breadcrumbs -->
    <div class="page-header">
      <h4 class="page-title">Edit Test</h4>
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
          <a href="#">Edit Test</a>
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
          <div class="card-header text-center">
            <h3 class="fw-bold mb-3">Edit Test Information</h3>
          </div>
          <div class="card-body">
            <form method="POST" action="">
              <div class="row mb-3">
                <!-- Test Type (Read-only) -->
                <div class="col-md-6">
                  <label for="testType">Test Type</label>
                  <input type="text" id="testType" name="testType" class="form-control" value="<?php echo htmlspecialchars($test_session['test_name']); ?>" readonly>
                </div>

                <!-- Test Date -->
                <div class="col-md-6">
                  <label for="testDate">Test Date</label>
                  <input type="date" id="testDate" name="testDate" class="form-control" value="<?php echo htmlspecialchars($test_session['test_date']); ?>" required>
                </div>
              </div>

              <div class="row mb-3">
                <!-- Start Time -->
                <div class="col-md-6">
                  <label for="startTime">Start Time</label>
                  <input type="time" id="startTime" name="startTime" class="form-control" value="<?php echo htmlspecialchars($test_session['start_time']); ?>" required>
                </div>

                <!-- End Time -->
                <div class="col-md-6">
                  <label for="endTime">End Time</label>
                  <input type="time" id="endTime" name="endTime" class="form-control" value="<?php echo htmlspecialchars($test_session['end_time']); ?>" required>
                </div>
              </div>

              <div class="row mb-3">
                <!-- Capacity -->
                <div class="col-md-6">
                  <label for="capacity">Capacity</label>
                  <input type="number" id="capacity" name="capacity" class="form-control" value="<?php echo htmlspecialchars($test_session['capacity_students']); ?>" min="<?php echo htmlspecialchars($test_session['capacity_students']); ?>" required>
                  <small class="form-text text-muted">Capacity cannot be decreased from the current value.</small>
                </div>

                <!-- Instructor -->
                <div class="col-md-6">
                  <label for="instructor">Instructor</label>
                  <select id="instructor" name="instructor" class="form-control" required>
                    <?php while ($row = mysqli_fetch_assoc($instructors_result)): ?>
                      <option value="<?php echo $row['instructor_id']; ?>" <?php echo $row['instructor_id'] === $test_session['instructor_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($row['instructor_name']); ?>
                      </option>
                    <?php endwhile; ?>
                  </select>
                </div>
              </div>

              <!-- Enrolled Students -->
              <div class="col-md-12 mb-4">
                <label>Already Enrolled Students (<?php echo $enrolled_count; ?>)</label>
                <?php if ($enrolled_count > 0): ?>
                  <ul class="list-group mt-2">
                    <?php foreach ($enrolled_students_display as $student_info): ?>
                      <li class="list-group-item"><?php echo htmlspecialchars($student_info); ?></li>
                    <?php endforeach; ?>
                  </ul>
                <?php else: ?>
                  <p class="text-muted mt-2">No students currently enrolled.</p>
                <?php endif; ?>
              </div>

              <!-- Eligible Students -->
              <div class="col-md-12">
                <label for="eligibleStudents">Eligible Students</label>
                <div id="studentList" class="list-group mt-2">
                  <p id="noStudentsMessage" class="text-muted">Loading eligible students...</p>
                </div>
              </div>


              <div class="text-center">
                <a href="list_test.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Changes</button>
              </div>
            </form>
          </div>
        </div>
      <?php endif; ?>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const studentList = document.getElementById('studentList');
    const noStudentsMessage = document.getElementById('noStudentsMessage');
    const capacityInput = document.getElementById('capacity');

    // PHP: Output already enrolled student IDs as JS array
    const alreadyEnrolled = <?php
                            $enrolledQuery = $conn->prepare("SELECT student_test_id FROM student_test_sessions WHERE test_session_id = ?");
                            $enrolledQuery->bind_param("s", $test_session_id);
                            $enrolledQuery->execute();
                            $result = $enrolledQuery->get_result();
                            $ids = [];
                            while ($row = $result->fetch_assoc()) {
                              $ids[] = $row['student_test_id'];
                            }
                            echo json_encode($ids);
                            ?>;

    let maxSelectable = parseInt(capacityInput.value) - alreadyEnrolled.length;

    // Fetch eligible students for the test
    fetch(`fetch_eligible_students.php?test_id=<?php echo $test_session['test_id']; ?>`)
      .then(response => response.json())
      .then(data => {
        studentList.innerHTML = '';
        if (data.length > 0) {
          data.forEach(student => {
            const studentItem = document.createElement('div');
            studentItem.classList.add('list-group-item', 'd-flex', 'align-items-center', 'justify-content-between');

            const isEnrolled = alreadyEnrolled.includes(student.student_test_id);

            studentItem.innerHTML = `
              <span>${student.student_name} (${student.license_name})</span>
              <input type="checkbox" 
                name="enrolledStudents[]" 
                value="${student.student_test_id}" 
                class="form-check-input select-student"
                ${isEnrolled ? 'checked disabled' : ''}>
            `;

            studentList.appendChild(studentItem);
          });
          noStudentsMessage.style.display = 'none';
        } else {
          noStudentsMessage.textContent = 'No eligible students for this test.';
        }

        setupLimitEnforcement();
      })
      .catch(() => {
        noStudentsMessage.textContent = 'Failed to load eligible students.';
      });

    // Setup logic to enforce selection limit
    function setupLimitEnforcement() {
      const studentCheckboxes = studentList.querySelectorAll('.select-student:not(:disabled)');

      studentCheckboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
          const checkedCount = studentList.querySelectorAll('.select-student:checked:not(:disabled)').length;
          if (checkedCount > maxSelectable) {
            alert('You can only select up to ' + maxSelectable + ' new students.');
            checkbox.checked = false;
          }
        });
      });

      capacityInput.addEventListener('input', function() {
        const newCapacity = parseInt(capacityInput.value);
        maxSelectable = newCapacity - alreadyEnrolled.length;
      });
    }
  });
</script>


<?php include '../../../include/footer.html'; ?>