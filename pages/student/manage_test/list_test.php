<?php
include '../../../include/st_header.php';
include '../../../database/db_connection.php';

$user_id = $_SESSION['user_id'] ?? null;

date_default_timezone_set('Asia/Kuala_Lumpur');
$currentDateTime = date('Y-m-d H:i:s');
$today = date('Y-m-d');

if ($user_id) {
  // Get student ID
  $stmt = $conn->prepare("SELECT student_id FROM students WHERE user_id = ?");
  $stmt->bind_param("s", $user_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $student = $result->fetch_assoc();
  $student_id = $student['student_id'] ?? null;

  if ($student_id) {
    // Auto-update test sessions status from Scheduled to Completed for tests that have ended
    $update_sql = "
      UPDATE test_sessions ts
      SET ts.status = 'Completed'
      WHERE ts.status = 'Scheduled'
      AND STR_TO_DATE(CONCAT(ts.test_date, ' ', ts.end_time), '%Y-%m-%d %H:%i:%s') < ?
      AND ts.test_session_id IN (
        SELECT sts.test_session_id
        FROM student_test_sessions sts
        JOIN student_tests st ON sts.student_test_id = st.student_test_id
        WHERE st.student_license_id IN (
          SELECT student_license_id FROM student_licenses WHERE student_id = ?
        )
      )
    ";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ss", $currentDateTime, $student_id);
    $update_stmt->execute();
    $updated_tests = $update_stmt->affected_rows;

    // Today's Tests - Modified to remove status column
    $today_sql = "
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
        AND ts.test_date = ?
        AND STR_TO_DATE(CONCAT(ts.test_date, ' ', ts.end_time), '%Y-%m-%d %H:%i:%s') > ?
      ORDER BY 
        ts.start_time ASC
    ";
    $today_stmt = $conn->prepare($today_sql);
    $today_stmt->bind_param("sss", $student_id, $today, $currentDateTime);
    $today_stmt->execute();
    $today_result = $today_stmt->get_result();
    $today_count = $today_result->num_rows;

    // Upcoming Tests (excluding today's tests) - Modified to remove status column
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
        AND ts.test_date > ?
      ORDER BY 
        ts.test_date ASC, ts.start_time ASC
    ";
    $upcoming_stmt = $conn->prepare($upcoming_sql);
    $upcoming_stmt->bind_param("sss", $student_id, $currentDateTime, $today);
    $upcoming_stmt->execute();
    $upcoming_result = $upcoming_stmt->get_result();
    $upcoming_count = $upcoming_result->num_rows;

    // Past Tests - Modified to include student_tests.status for results
    $past_sql = "
      SELECT 
        ts.*, t.test_name, st.status as test_status, st.score
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
    $past_count = $past_result->num_rows;
  }
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
      <?php if (isset($updated_tests) && $updated_tests > 0): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
          <?= $updated_tests ?> test(s) have been automatically marked as completed.
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      <?php endif; ?>

      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div class="card-title">Test List</div>
          <button type="button" class="btn btn-sm btn-outline-secondary" id="test-toggle-btn">
            <i class="fas fa-minus"></i>
          </button>
        </div>
        <div class="card-body" id="test-card-body">
          <!-- Toggle Cards -->
          <div class="row mb-4">
            <div class="col-md-4">
              <div class="card card-stats card-round toggle-card active" data-target="today-tests-container">
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
              <div class="card card-stats card-round toggle-card" data-target="upcoming-tests-container">
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
              <div class="card card-stats card-round toggle-card" data-target="past-tests-container">
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

          <!-- Today's Tests -->
          <div class="table-container" id="today-tests-container">
            <h4 class="mt-4 mb-3"><i class="fas fa-calendar-alt text-warning"></i> Today's Tests (<?php echo date('d M Y'); ?>)</h4>
            <div class="table-responsive">
              <table id="today-tests-table" class="table table-bordered table-striped">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Time</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php $counter = 1; ?>
                  <?php if ($today_count > 0): ?>
                    <?php while ($row = $today_result->fetch_assoc()): ?>
                      <tr>
                        <td><?= $counter++ ?></td>
                        <td><?= htmlspecialchars($row['test_name']) ?></td>
                        <td><?= date('h:i A', strtotime($row['start_time'])) ?> - <?= date('h:i A', strtotime($row['end_time'])) ?></td>
                        <td>
                          <a href="view_test.php?test_session_id=<?= $row['test_session_id'] ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-eye"></i> View
                          </a>
                        </td>
                      </tr>
                    <?php endwhile; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="4">No tests scheduled for today.</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Upcoming Tests -->
          <div class="table-container" id="upcoming-tests-container" style="display: none;">
            <h4 class="mt-4 mb-3"><i class="fas fa-clock text-primary"></i> Upcoming Tests</h4>
            <div class="table-responsive">
              <table id="upcoming-tests-table" class="table table-bordered table-striped">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php $counter = 1; ?>
                  <?php if ($upcoming_count > 0): ?>
                    <?php while ($row = $upcoming_result->fetch_assoc()): ?>
                      <tr>
                        <td><?= $counter++ ?></td>
                        <td><?= htmlspecialchars($row['test_name']) ?></td>
                        <td><?= date('d M Y', strtotime($row['test_date'])) ?></td>
                        <td><?= date('h:i A', strtotime($row['start_time'])) ?> - <?= date('h:i A', strtotime($row['end_time'])) ?></td>
                        <td>
                          <a href="view_test.php?test_session_id=<?= $row['test_session_id'] ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-eye"></i> View
                          </a>
                        </td>
                      </tr>
                    <?php endwhile; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="5">No upcoming tests found.</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Past Tests -->
          <div class="table-container" id="past-tests-container" style="display: none;">
            <h4 class="mt-4 mb-3"><i class="fas fa-calendar-check text-success"></i> Completed Tests</h4>
            <div class="table-responsive">
              <table id="past-tests-table" class="table table-bordered table-striped">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Result</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php $counter = 1; ?>
                  <?php if ($past_count > 0): ?>
                    <?php while ($row = $past_result->fetch_assoc()): ?>
                      <tr>
                        <td><?= $counter++ ?></td>
                        <td><?= htmlspecialchars($row['test_name']) ?></td>
                        <td><?= date('d M Y', strtotime($row['test_date'])) ?></td>
                        <td><?= date('h:i A', strtotime($row['start_time'])) ?> - <?= date('h:i A', strtotime($row['end_time'])) ?></td>
                        <td>
                          <?php if ($row['test_status'] == 'Passed'): ?>
                            <span class="badge badge-success">
                              Passed <?= !empty($row['score']) ? '(' . $row['score'] . ')' : '' ?>
                            </span>
                          <?php elseif ($row['test_status'] == 'Failed'): ?>
                            <span class="badge badge-danger">
                              Failed <?= !empty($row['score']) ? '(' . $row['score'] . ')' : '' ?>
                            </span>
                          <?php elseif ($row['test_status'] == 'Pending'): ?>
                            <span class="badge badge-warning">Pending</span>
                          <?php else: ?>
                            <span class="badge badge-secondary">Not Graded</span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <a href="view_test.php?test_session_id=<?= $row['test_session_id'] ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-eye"></i> View
                          </a>
                        </td>
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
    // Add visual feedback when hovering over cards
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

  $(document).ready(function() {
    // Auto-dismiss alert after 5 seconds
    setTimeout(function() {
      $('.alert-dismissible').alert('close');
    }, 5000);

    // Toggle test card content visibility
    $('#test-toggle-btn').click(function() {
      var cardBody = $('#test-card-body');

      // Remove transition property to avoid conflicts
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
</script>

<style>
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

  .card-header {
    padding: 0.75rem 1.25rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .card-title {
    margin-bottom: 0;
  }

  #test-card-body {
    transition: none;
  }

  /* Fix for header interaction issues */
  .navbar .nav-link,
  .navbar .dropdown-item {
    z-index: 1000;
    position: relative;
  }

  /* Badge styling */
  .badge-success {
    background-color: #31ce36;
  }

  .badge-danger {
    background-color: #f25961;
  }

  .badge-warning {
    background-color: #ffad46;
  }

  .badge-secondary {
    background-color: #6c757d;
  }
  
  /* Add margin to retest button */
  .ml-1 {
    margin-left: 0.25rem;
  }
</style>