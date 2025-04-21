<?php
include '../../../include/st_header.php';
include '../../../database/db_connection.php';

$user_id = $_SESSION['user_id'] ?? null;

date_default_timezone_set('Asia/Kuala_Lumpur');
$currentDateTime = date('Y-m-d H:i:s');

if ($user_id) {
  // Get student ID
  $stmt = $conn->prepare("SELECT student_id FROM students WHERE user_id = ?");
  $stmt->bind_param("s", $user_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $student = $result->fetch_assoc();
  $student_id = $student['student_id'] ?? null;

  if ($student_id) {
    // Upcoming Tests
    $upcoming_sql = "
      SELECT 
        ts.*, t.test_name
      FROM 
        student_tests st
      JOIN 
        student_test_sessions sts ON st.student_test_id = sts.student_test_id
      JOIN 
        test_sessions ts ON sts.test_session_id = ts.test_session_id
      JOIN 
        tests t ON ts.test_id = t.test_id
      WHERE 
        st.student_license_id IN (
          SELECT student_license_id FROM student_licenses WHERE student_id = ?
        )
        AND STR_TO_DATE(CONCAT(ts.test_date, ' ', ts.end_time), '%Y-%m-%d %H:%i:%s') > ?
      ORDER BY 
        ts.test_date ASC, ts.start_time ASC
    ";
    $upcoming_stmt = $conn->prepare($upcoming_sql);
    $upcoming_stmt->bind_param("ss", $student_id, $currentDateTime);
    $upcoming_stmt->execute();
    $upcoming_result = $upcoming_stmt->get_result();

    // Past Tests
    $past_sql = "
      SELECT 
        ts.*, t.test_name
      FROM 
        student_tests st
      JOIN 
        student_test_sessions sts ON st.student_test_id = sts.student_test_id
      JOIN 
        test_sessions ts ON sts.test_session_id = ts.test_session_id
      JOIN 
        tests t ON ts.test_id = t.test_id
      WHERE 
        st.student_license_id IN (
          SELECT student_license_id FROM student_licenses WHERE student_id = ?
        )
        AND STR_TO_DATE(CONCAT(ts.test_date, ' ', ts.end_time), '%Y-%m-%d %H:%i:%s') <= ?
      ORDER BY 
        ts.test_date DESC, ts.start_time DESC
    ";
    $past_stmt = $conn->prepare($past_sql);
    $past_stmt->bind_param("ss", $student_id, $currentDateTime);
    $past_stmt->execute();
    $past_result = $past_stmt->get_result();
  } else {
    $error_message = "Student ID not found.";
  }
} else {
  $error_message = "User ID not found in session.";
}
?>

<div class="container">
  <div class="page-inner">
    <div class="page-header">
      <h4 class="page-title">My Test</h4>
      <ul class="breadcrumbs">
        <li class="nav-home">
          <a href="/pages/student/dashboard.php">
            <i class="icon-home"></i>
          </a>
        </li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="#">Test List</a></li>
      </ul>
    </div>

    <div class="page-category">
      <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
      <?php else: ?>
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Test List</h3>
          </div>
          <div class="card-body">
            <ul class="nav nav-tabs nav-line nav-color-secondary" id="line-tab" role="tablist">
              <li class="nav-item">
                <a class="nav-link active" id="line-upcoming-tests-tab" data-bs-toggle="pill" href="#line-upcoming-tests" role="tab" aria-controls="line-upcoming-tests" aria-selected="true">Upcoming Tests</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" id="line-past-tests-tab" data-bs-toggle="pill" href="#line-past-tests" role="tab" aria-controls="line-past-tests" aria-selected="false">Past Tests</a>
              </li>
            </ul>

            <div class="tab-content mt-3" id="line-tabContent">
              <!-- Upcoming Tests -->
              <div class="tab-pane fade show active" id="line-upcoming-tests" role="tabpanel" aria-labelledby="line-upcoming-tests-tab">
                <div class="table-responsive">
                  <table id="upcoming-tests-table" class="table table-bordered table-striped table-hover">
                    <thead>
                      <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Date</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php $counter = 1; ?>
                      <?php if ($upcoming_result->num_rows > 0): ?>
                        <?php while ($row = $upcoming_result->fetch_assoc()): ?>
                          <tr class="clickable-row" data-href="view_test.php?test_session_id=<?= $row['test_session_id'] ?>">
                            <td><?= $counter++ ?></td>
                            <td><?= htmlspecialchars($row['test_name']) ?></td>
                            <td><?= htmlspecialchars($row['test_date']) ?></td>
                            <td><?= htmlspecialchars($row['start_time']) ?></td>
                            <td><?= htmlspecialchars($row['end_time']) ?></td>
                            <td><?= htmlspecialchars($row['status']) ?></td>
                          </tr>
                        <?php endwhile; ?>
                      <?php else: ?>
                        <tr>
                          <td colspan="6">No upcoming tests found.</td>
                        </tr>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- Past Tests -->
              <div class="tab-pane fade" id="line-past-tests" role="tabpanel" aria-labelledby="line-past-tests-tab">
                <div class="table-responsive">
                  <table id="past-tests-table" class="table table-bordered table-striped table-hover">
                    <thead>
                      <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Date</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php $counter = 1; ?>
                      <?php if ($past_result->num_rows > 0): ?>
                        <?php while ($row = $past_result->fetch_assoc()): ?>
                          <tr class="clickable-row" data-href="view_test.php?test_session_id=<?= $row['test_session_id'] ?>">
                            <td><?= $counter++ ?></td>
                            <td><?= htmlspecialchars($row['test_name']) ?></td>
                            <td><?= htmlspecialchars($row['test_date']) ?></td>
                            <td><?= htmlspecialchars($row['start_time']) ?></td>
                            <td><?= htmlspecialchars($row['end_time']) ?></td>
                            <td><?= htmlspecialchars($row['status']) ?></td>
                          </tr>
                        <?php endwhile; ?>
                      <?php else: ?>
                        <tr>
                          <td colspan="6">No past tests found.</td>
                        </tr>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include '../../../include/footer.html'; ?>

<script>
  $(document).ready(function() {
    $("#upcoming-tests-table").DataTable({});
    $("#past-tests-table").DataTable({});
  });

  $(document).ready(function() {
    $(".clickable-row").click(function() {
      window.location = $(this).data("href");
    });
  });
</script>

<style>
  .clickable-row {
    cursor: pointer;
  }
</style>
