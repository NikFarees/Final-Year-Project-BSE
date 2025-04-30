<?php
include '../../../include/in_header.php';
include '../../../database/db_connection.php';

$user_id = $_SESSION['user_id'] ?? null;

date_default_timezone_set('Asia/Kuala_Lumpur');
$currentDateTime = date('Y-m-d H:i:s');
$currentDate = date('Y-m-d');

if ($user_id) {
  // Get instructor ID
  $stmt = $conn->prepare("SELECT instructor_id FROM instructors WHERE user_id = ?");
  $stmt->bind_param("s", $user_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $instructor = $result->fetch_assoc();
  $instructor_id = $instructor['instructor_id'] ?? null;

  if ($instructor_id) {
    // Test Results Overview - Get pass/fail rates and average scores by test type
    $overview_sql = "
      SELECT 
          t.test_name,
          COUNT(st.student_test_id) AS total_tests,
          SUM(CASE WHEN st.status = 'Passed' THEN 1 ELSE 0 END) AS passed_count,
          SUM(CASE WHEN st.status = 'Failed' THEN 1 ELSE 0 END) AS failed_count,
          ROUND((AVG(st.score) / 50) * 100, 2) AS average_score
      FROM 
          test_sessions ts
      JOIN 
          tests t ON ts.test_id = t.test_id
      JOIN 
          student_test_sessions sts ON ts.test_session_id = sts.test_session_id
      JOIN 
          student_tests st ON sts.student_test_id = st.student_test_id
      WHERE 
          ts.instructor_id = ? AND 
          st.status IN ('Passed', 'Failed')
      GROUP BY 
          t.test_name
    ";
    $overview_stmt = $conn->prepare($overview_sql);
    $overview_stmt->bind_param("s", $instructor_id);
    $overview_stmt->execute();
    $overview_result = $overview_stmt->get_result();

    // Top Performing Students
    $top_students_sql = "
      SELECT 
          u.name AS student_name,
          t.test_name,
          l.license_name,
          st.score AS raw_score,
          ROUND((MAX(st.score) / 50) * 100, 2) AS best_score
      FROM 
          test_sessions ts
      JOIN 
          tests t ON ts.test_id = t.test_id
      JOIN 
          student_test_sessions sts ON ts.test_session_id = sts.test_session_id
      JOIN 
          student_tests st ON sts.student_test_id = st.student_test_id
      JOIN 
          student_licenses sl ON st.student_license_id = sl.student_license_id
      JOIN 
          licenses l ON sl.license_id = l.license_id
      JOIN 
          students s ON sl.student_id = s.student_id
      JOIN 
          users u ON s.user_id = u.user_id
      WHERE 
          ts.instructor_id = ? AND 
          st.status = 'Passed'
      GROUP BY 
          u.name, t.test_name, l.license_name
      ORDER BY 
          best_score DESC
      LIMIT 3
    ";
    $top_students_stmt = $conn->prepare($top_students_sql);
    $top_students_stmt->bind_param("s", $instructor_id);
    $top_students_stmt->execute();
    $top_students_result = $top_students_stmt->get_result();

    // Students Needing Help
    $struggling_students_sql = "
      SELECT 
          u.name AS student_name,
          t.test_name,
          l.license_name,
          st.score AS raw_score,
          ROUND((st.score / 50) * 100, 2) AS score_percentage,
          st.status
      FROM 
          test_sessions ts
      JOIN 
          tests t ON ts.test_id = t.test_id
      JOIN 
          student_test_sessions sts ON ts.test_session_id = sts.test_session_id
      JOIN 
          student_tests st ON sts.student_test_id = st.student_test_id
      JOIN 
          student_licenses sl ON st.student_license_id = sl.student_license_id
      JOIN 
          licenses l ON sl.license_id = l.license_id
      JOIN 
          students s ON sl.student_id = s.student_id
      JOIN 
          users u ON s.user_id = u.user_id
      WHERE 
          ts.instructor_id = ? AND 
          (st.status = 'Failed' OR st.score < 25)
      ORDER BY 
          st.score ASC
      LIMIT 3
    ";
    $struggling_students_stmt = $conn->prepare($struggling_students_sql);
    $struggling_students_stmt->bind_param("s", $instructor_id);
    $struggling_students_stmt->execute();
    $struggling_students_result = $struggling_students_stmt->get_result();

    // AUTO-UPDATE: Change status from Scheduled to Completed for test sessions that have passed
    $update_stmt = $conn->prepare("
      UPDATE test_sessions
      SET status = 'Completed'
      WHERE instructor_id = ? 
      AND status = 'Scheduled'
      AND STR_TO_DATE(CONCAT(test_date, ' ', end_time), '%Y-%m-%d %H:%i:%s') < ?
    ");
    $update_stmt->bind_param("ss", $instructor_id, $currentDateTime);
    $update_stmt->execute();
    $updated_rows = $update_stmt->affected_rows; // Added to capture affected rows

    // Today's Tests
    $today_sql = "
      SELECT 
          ts.*, t.test_name,
          (SELECT COUNT(*) FROM student_test_sessions sts WHERE sts.test_session_id = ts.test_session_id) AS enrolled_count
      FROM 
          test_sessions ts
      JOIN 
          tests t ON ts.test_id = t.test_id
      WHERE 
          ts.instructor_id = ? AND 
          ts.test_date = ? AND
          STR_TO_DATE(CONCAT(ts.test_date, ' ', ts.end_time), '%Y-%m-%d %H:%i:%s') > ?
      ORDER BY 
          ts.start_time ASC
    ";
    $today_stmt = $conn->prepare($today_sql);
    $today_stmt->bind_param("sss", $instructor_id, $currentDate, $currentDateTime);
    $today_stmt->execute();
    $today_result = $today_stmt->get_result();
    $today_count = $today_result->num_rows;

    // Upcoming Tests (excluding today's tests)
    $upcoming_sql = "
      SELECT 
          ts.*, t.test_name,
          (SELECT COUNT(*) FROM student_test_sessions sts WHERE sts.test_session_id = ts.test_session_id) AS enrolled_count
      FROM 
          test_sessions ts
      JOIN 
          tests t ON ts.test_id = t.test_id
      WHERE 
          ts.instructor_id = ? AND 
          STR_TO_DATE(CONCAT(ts.test_date, ' ', ts.end_time), '%Y-%m-%d %H:%i:%s') > ? AND
          ts.test_date > ?
      ORDER BY 
          ts.test_date ASC, ts.start_time ASC
    ";
    $upcoming_stmt = $conn->prepare($upcoming_sql);
    $upcoming_stmt->bind_param("sss", $instructor_id, $currentDateTime, $currentDate);
    $upcoming_stmt->execute();
    $upcoming_result = $upcoming_stmt->get_result();
    $upcoming_count = $upcoming_result->num_rows;

    // Past Tests
    $past_sql = "
      SELECT 
          ts.*, t.test_name,
          (SELECT COUNT(*) FROM student_test_sessions sts WHERE sts.test_session_id = ts.test_session_id) AS enrolled_count,
          (SELECT COUNT(*) FROM student_test_sessions sts 
            JOIN student_tests st ON sts.student_test_id = st.student_test_id 
            WHERE sts.test_session_id = ts.test_session_id AND st.status = 'Passed') AS passed_count,
          (SELECT COUNT(*) FROM student_test_sessions sts 
            JOIN student_tests st ON sts.student_test_id = st.student_test_id 
            WHERE sts.test_session_id = ts.test_session_id AND st.status = 'Failed') AS failed_count
      FROM 
          test_sessions ts
      JOIN 
          tests t ON ts.test_id = t.test_id
      WHERE 
          ts.instructor_id = ? AND 
          STR_TO_DATE(CONCAT(ts.test_date, ' ', ts.end_time), '%Y-%m-%d %H:%i:%s') <= ?
      ORDER BY 
          ts.test_date DESC, ts.start_time DESC
    ";
    $past_stmt = $conn->prepare($past_sql);
    $past_stmt->bind_param("ss", $instructor_id, $currentDateTime);
    $past_stmt->execute();
    $past_result = $past_stmt->get_result();
    $past_count = $past_result->num_rows;
  } else {
    $error_message = "Instructor ID not found.";
  }
} else {
  $error_message = "User ID not found in session.";
}
?>

<div class="container">
  <div class="page-inner">
    <div class="page-header">
      <h4 class="page-title">Manage Test</h4>
      <ul class="breadcrumbs">
        <li class="nav-home">
          <a href="/pages/instructor/dashboard.php">
            <i class="icon-home"></i>
          </a>
        </li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="#">Test Overview</a></li>
      </ul>
    </div>

    <div class="page-category">
      <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
      <?php else: ?>
        <!-- Auto-update notification -->
        <?php if (isset($updated_rows) && $updated_rows > 0): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?php echo $updated_rows; ?> test session(s) have been automatically marked as completed.
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>
        
        <!-- Test Statistics -->
        <div class="card mb-4">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Test Results Statistics</h3>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="stats-toggle-btn">
              <i class="fas fa-minus"></i>
            </button>
          </div>

          <div class="card-body" id="stats-card-body">
            <div class="row">
              <!-- Test Results Overview -->
              <div class="col-md-4">
                <div class="card shadow-sm mb-3">
                  <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Tests Overview</h5>
                  </div>
                  <div class="card-body">
                    <?php if ($overview_result->num_rows > 0): ?>
                      <?php while ($row = $overview_result->fetch_assoc()): ?>
                        <div class="mb-3">
                          <h6><?= htmlspecialchars($row['test_name']) ?></h6>
                          <div class="d-flex justify-content-between mb-1">
                            <small>Average Score: <strong><?= $row['average_score'] ?>%</strong></small>
                            <small>Total Tests: <strong><?= $row['total_tests'] ?></strong></small>
                          </div>
                          <div class="progress" style="height: 20px;">
                            <?php
                            $pass_percentage = $row['total_tests'] > 0 ? ($row['passed_count'] / $row['total_tests']) * 100 : 0;
                            $fail_percentage = $row['total_tests'] > 0 ? ($row['failed_count'] / $row['total_tests']) * 100 : 0;
                            ?>
                            <div class="progress-bar bg-success" role="progressbar" style="width: <?= $pass_percentage ?>%">
                              <?= $row['passed_count'] ?> Pass
                            </div>
                            <div class="progress-bar bg-danger" role="progressbar" style="width: <?= $fail_percentage ?>%">
                              <?= $row['failed_count'] ?> Fail
                            </div>
                          </div>
                        </div>
                      <?php endwhile; ?>
                    <?php else: ?>
                      <p>No test results available yet.</p>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <!-- Top Performing Students -->
              <div class="col-md-4">
                <div class="card shadow-sm mb-3">
                  <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-trophy"></i> Top 3 Performing Students</h5>
                  </div>
                  <div class="card-body">
                    <?php if ($top_students_result->num_rows > 0): ?>
                      <table class="table-responsive table-sm">
                        <thead>
                          <tr>
                            <th>Student</th>
                            <th>License</th>
                            <th>Test</th>
                            <th>Score</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php while ($row = $top_students_result->fetch_assoc()): ?>
                            <tr>
                              <td><?= htmlspecialchars($row['student_name']) ?></td>
                              <td><?= htmlspecialchars($row['license_name']) ?></td>
                              <td><?= htmlspecialchars($row['test_name']) ?></td>
                              <td>
                                <span class="badge badge-success">
                                  <?= $row['best_score'] ?>% (<?= $row['raw_score'] ?>/50)
                                </span>
                              </td>
                            </tr>
                          <?php endwhile; ?>
                        </tbody>
                      </table>
                    <?php else: ?>
                      <p>No top students data available yet.</p>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <!-- Students Needing Help -->
              <div class="col-md-4">
                <div class="card shadow-sm mb-3">
                  <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Top 3 Students Needing Help</h5>
                  </div>
                  <div class="card-body">
                    <?php if ($struggling_students_result->num_rows > 0): ?>
                      <table class="table-responsive table-sm">
                        <thead>
                          <tr>
                            <th>Student</th>
                            <th>License</th>
                            <th>Test</th>
                            <th>Score</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php while ($row = $struggling_students_result->fetch_assoc()): ?>
                            <tr>
                              <td><?= htmlspecialchars($row['student_name']) ?></td>
                              <td><?= htmlspecialchars($row['license_name']) ?></td>
                              <td><?= htmlspecialchars($row['test_name']) ?></td>
                              <td>
                                <span class="badge badge-<?= $row['status'] == 'Failed' ? 'danger' : 'warning' ?>">
                                  <?= $row['score_percentage'] ?>% (<?= $row['raw_score'] ?>/50)
                                </span>
                              </td>
                            </tr>
                          <?php endwhile; ?>
                        </tbody>
                      </table>
                    <?php else: ?>
                      <p>No students currently need help.</p>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Original Test List Card -->
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Test List</h3>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="toggle-card-btn">
              <i class="fas fa-minus"></i> <!-- Initially a minus icon -->
            </button>
          </div>

          <div class="card-body" id="card-body-content">
            <div class="row mb-4">
              <div class="col-md-4">
                <div class="card card-stats card-round toggle-card active" data-target="today-table">
                  <div class="card-body">
                    <div class="row">
                      <div class="col-5">
                        <div class="icon-big text-center">
                          <i class="fas fa-calendar-alt text-warning"></i>
                        </div>
                      </div>
                      <div class="col-7 col-stats">
                        <div class="numbers">
                          <p class="card-category">Today's Tests</p>
                          <h4 class="card-title"><?php echo $today_count; ?></h4>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-md-4">
                <div class="card card-stats card-round toggle-card" data-target="upcoming-table">
                  <div class="card-body">
                    <div class="row">
                      <div class="col-5">
                        <div class="icon-big text-center">
                          <i class="fas fa-clock text-primary"></i>
                        </div>
                      </div>
                      <div class="col-7 col-stats">
                        <div class="numbers">
                          <p class="card-category">Upcoming Tests</p>
                          <h4 class="card-title"><?php echo $upcoming_count; ?></h4>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-md-4">
                <div class="card card-stats card-round toggle-card" data-target="past-table">
                  <div class="card-body">
                    <div class="row">
                      <div class="col-5">
                        <div class="icon-big text-center">
                          <i class="fas fa-calendar-check text-success"></i>
                        </div>
                      </div>
                      <div class="col-7 col-stats">
                        <div class="numbers">
                          <p class="card-category">Completed Tests</p>
                          <h4 class="card-title"><?php echo $past_count; ?></h4>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Today's Tests Table -->
            <div class="table-container" id="today-table">
              <h4 class="mt-4 mb-3"><i class="fas fa-calendar-alt text-warning"></i> Today's Tests (<?php echo date('d M Y'); ?>)</h4>
              <div class="table-responsive">
                <table id="today-tests-table" class="table table-bordered table-striped ">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Name</th>
                      <th>Time</th> <!-- Combined column -->
                      <th>Capacity</th>
                      <th>Enrolled</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if ($today_result->num_rows > 0): ?>
                      <?php $counter = 1; ?>
                      <?php while ($row = $today_result->fetch_assoc()): ?>
                        <tr>
                          <td><?= $counter++ ?></td>
                          <td><?= htmlspecialchars($row['test_name']) ?></td>
                          <td><?= date('h:i A', strtotime($row['start_time'])) . ' - ' . date('h:i A', strtotime($row['end_time'])) ?></td> <!-- Combined time -->
                          <td><?= htmlspecialchars($row['capacity_students']) ?></td>
                          <td><?= htmlspecialchars($row['enrolled_count']) ?></td>
                          <td>
                            <a href="view_test.php?test_session_id=<?php echo $row['test_session_id']; ?>&session_type=today" class="btn btn-sm btn-primary">
                              <i class="fas fa-eye"></i> View
                            </a>
                          </td>
                        </tr>
                      <?php endwhile; ?>
                    <?php else: ?>
                      <tr>
                        <td colspan="6">No tests scheduled for today.</td> <!-- Updated colspan -->
                      </tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Upcoming Tests Table -->
            <div class="table-container" id="upcoming-table" style="display: none;">
              <h4 class="mt-4 mb-3"><i class="fas fa-clock text-primary"></i> Upcoming Tests</h4>
              <div class="table-responsive">
                <table id="upcoming-tests-table" class="table table-bordered table-striped ">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Name</th>
                      <th>Date</th>
                      <th>Time</th> <!-- Combined column -->
                      <th>Capacity</th>
                      <th>Enrolled</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if ($upcoming_result->num_rows > 0): ?>
                      <?php $counter = 1; ?>
                      <?php while ($row = $upcoming_result->fetch_assoc()): ?>
                        <tr>
                          <td><?= $counter++ ?></td>
                          <td><?= htmlspecialchars($row['test_name']) ?></td>
                          <td><?= date('d M Y', strtotime($row['test_date'])) ?></td>
                          <td><?= date('h:i A', strtotime($row['start_time'])) . ' - ' . date('h:i A', strtotime($row['end_time'])) ?></td> <!-- Combined time -->
                          <td><?= htmlspecialchars($row['capacity_students']) ?></td>
                          <td><?= htmlspecialchars($row['enrolled_count']) ?></td>
                          <td>
                            <a href="view_test.php?test_session_id=<?php echo $row['test_session_id']; ?>&session_type=upcoming" class="btn btn-sm btn-primary">
                              <i class="fas fa-eye"></i> View
                            </a>
                          </td>
                        </tr>
                      <?php endwhile; ?>
                    <?php else: ?>
                      <tr>
                        <td colspan="7">No upcoming tests found.</td> <!-- Updated colspan -->
                      </tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Past Tests Table -->
            <div class="table-container" id="past-table" style="display: none;">
              <h4 class="mt-4 mb-3"><i class="fas fa-calendar-check text-success"></i> Completed Tests</h4>
              <div class="table-responsive">
                <table id="past-tests-table" class="table table-bordered table-striped ">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Name</th>
                      <th>Date</th>
                      <th>Time</th>
                      <th>Capacity</th>
                      <th>Enrolled</th>
                      <th>Results Summary</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if ($past_result->num_rows > 0): ?>
                      <?php $counter = 1; ?>
                      <?php while ($row = $past_result->fetch_assoc()): ?>
                        <tr>
                          <td><?= $counter++ ?></td>
                          <td><?= htmlspecialchars($row['test_name']) ?></td>
                          <td><?= date('d M Y', strtotime($row['test_date'])) ?></td>
                          <td><?= date('h:i A', strtotime($row['start_time'])) . ' - ' . date('h:i A', strtotime($row['end_time'])) ?></td>
                          <td><?= htmlspecialchars($row['capacity_students']) ?></td>
                          <td><?= htmlspecialchars($row['enrolled_count']) ?></td>
                          <td>
                            <span class="badge badge-success">Passed: <?= htmlspecialchars($row['passed_count']) ?></span>
                            <span class="badge badge-danger">Failed: <?= htmlspecialchars($row['failed_count']) ?></span>
                          </td>
                          <td>
                            <a href="view_test.php?test_session_id=<?php echo $row['test_session_id']; ?>&session_type=past" class="btn btn-sm btn-primary">
                              <i class="fas fa-eye"></i> View
                            </a>
                          </td>
                        </tr>
                      <?php endwhile; ?>
                    <?php else: ?>
                      <tr>
                        <td colspan="8">No past tests found.</td> <!-- Updated colspan -->
                      </tr>
                    <?php endif; ?>
                  </tbody>
                </table>
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
    $("#today-tests-table").DataTable({});
  });

  $(document).ready(function() {
    $("#upcoming-tests-table").DataTable({});
  });

  $(document).ready(function() {
    $("#past-tests-table").DataTable({});
  });

  $(document).ready(function() {
    // Auto-dismiss alert after 5 seconds
    setTimeout(function() {
      $('.alert-dismissible').alert('close');
    }, 5000);
    
    // Toggle card content visibility
    $('#toggle-card-btn').click(function() {
      var cardBody = $('#card-body-content');

      // Remove transition property entirely
      cardBody.css('transition', 'none');

      // Use jQuery's slideToggle with a specified duration
      cardBody.slideToggle(300);

      // Toggle the icon
      var icon = $(this).find('i');
      if (icon.hasClass('fa-minus')) {
        icon.removeClass('fa-minus').addClass('fa-plus');
      } else {
        icon.removeClass('fa-plus').addClass('fa-minus');
      }
    });
  });

  $(document).ready(function() {
    // Toggle stats card content visibility
    $('#stats-toggle-btn').click(function() {
      var statsContent = $('#stats-card-body');

      // Remove transition property entirely
      statsContent.css('transition', 'none');

      // Use jQuery's slideToggle with a specified duration
      statsContent.slideToggle(300);

      // Toggle the icon
      var icon = $(this).find('i');
      if (icon.hasClass('fa-minus')) {
        icon.removeClass('fa-minus').addClass('fa-plus');
      } else {
        icon.removeClass('fa-plus').addClass('fa-minus');
      }
    });
  });

  $(document).ready(function() {
    // Add click event for the toggle cards
    $('.toggle-card').on('click', function() {
      // Remove active class from all cards
      $('.toggle-card').removeClass('active');

      // Add active class to clicked card
      $(this).addClass('active');

      // Hide all tables
      $('.table-container').hide();

      // Show the table corresponding to the clicked card
      $('#' + $(this).data('target')).show();
    });
  });

  $(document).ready(function() {
    // Add some visual feedback when hovering over cards
    $('.toggle-card').hover(
      function() {
        if (!$(this).hasClass('active')) {
          $(this).css('cursor', 'pointer');
          $(this).addClass('shadow-sm');
        }
      },
      function() {
        $(this).removeClass('shadow-sm');
      }
    );
  });
</script>

<style>
  .clickable-row {
    cursor: pointer;
  }

  .toggle-card {
    transition: all 0.3s ease;
    cursor: pointer;
  }

  .toggle-card.active {
    border-bottom: 3px solid #1572E8;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
  }

  .toggle-card:hover:not(.active) {
    transform: translateY(-5px);
  }

  .table-container {
    transition: all 0.3s ease;
  }

  /* Removed hover transforms for statistics cards */
  .card.shadow-sm {
    transition: none;
  }

  .progress {
    border-radius: 5px;
    overflow: hidden;
  }

  .progress-bar {
    text-align: center;
    font-weight: bold;
    font-size: 0.85rem;
  }

  .card-header h5 {
    font-size: 1rem;
    font-weight: 600;
  }

  .table-sm td,
  .table-sm th {
    padding: 0.5rem;
    font-size: 0.9rem;
  }

  #stats-card-body {
    transition: none;
  }
</style>