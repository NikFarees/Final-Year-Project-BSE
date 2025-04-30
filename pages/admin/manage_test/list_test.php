<?php
include '../../../include/ad_header.php';
include '../../../database/db_connection.php';

// Set the correct timezone
date_default_timezone_set('Asia/Kuala_Lumpur');
$currentDateTime = date('Y-m-d H:i:s');

// Update status of past tests to 'Completed'
$updateQuery = "
  UPDATE test_sessions 
  SET status = 'Completed' 
  WHERE status = 'Scheduled' 
    AND STR_TO_DATE(CONCAT(test_date, ' ', end_time), '%Y-%m-%d %H:%i:%s') <= ?
";

$stmt = mysqli_prepare($conn, $updateQuery);
mysqli_stmt_bind_param($stmt, 's', $currentDateTime);
mysqli_stmt_execute($stmt);
$updated_test_sessions = mysqli_stmt_affected_rows($stmt);
mysqli_stmt_close($stmt);

// AUTO-UPDATE: Check and update student test eligibility based on lesson attendance
function updateStudentTestEligibility($conn)
{
  // Initialize counter for updated records
  $updated_students = 0;

  // Process LES01 and LES02 (require exactly 4 completed lessons with all attended)
  $check_query_les12 = "
        SELECT 
            sl.student_license_id,
            sl.lesson_id,
            COUNT(sls.student_lesson_id) AS total_lessons,
            SUM(CASE WHEN sls.attendance_status = 'Attend' THEN 1 ELSE 0 END) AS attended_lessons
        FROM 
            student_licenses sl
        JOIN 
            student_lessons sls ON sl.student_license_id = sls.student_license_id
        JOIN 
            student_tests st ON sl.student_license_id = st.student_license_id
        WHERE 
            sl.lesson_id IN ('LES01', 'LES02')
            AND sls.status = 'Completed'
            AND sls.schedule_status = 'Assigned'
            AND st.test_id = 'TES02'
            AND st.status = 'Ineligible'
        GROUP BY 
            sl.student_license_id, sl.lesson_id
        HAVING 
            total_lessons = 4 
            AND attended_lessons = 4
    ";

  $result_les12 = mysqli_query($conn, $check_query_les12);

  if ($result_les12) {
    while ($row = mysqli_fetch_assoc($result_les12)) {
      $update_query = "
                UPDATE student_tests 
                SET status = 'Pending' 
                WHERE student_license_id = ? 
                AND test_id = 'TES02'
                AND status = 'Ineligible'
            ";

      $stmt = mysqli_prepare($conn, $update_query);
      mysqli_stmt_bind_param($stmt, 's', $row['student_license_id']);
      mysqli_stmt_execute($stmt);
      $updated_students += mysqli_stmt_affected_rows($stmt);
      mysqli_stmt_close($stmt);
    }
  }

  // Process LES03 and LES04 (require 8 completed lessons with at least 6 attended)
  $check_query_les34 = "
        SELECT 
            sl.student_license_id,
            sl.lesson_id,
            COUNT(sls.student_lesson_id) AS total_lessons,
            SUM(CASE WHEN sls.attendance_status = 'Attend' THEN 1 ELSE 0 END) AS attended_lessons
        FROM 
            student_licenses sl
        JOIN 
            student_lessons sls ON sl.student_license_id = sls.student_license_id
        JOIN 
            student_tests st ON sl.student_license_id = st.student_license_id
        WHERE 
            sl.lesson_id IN ('LES03', 'LES04')
            AND sls.status = 'Completed'
            AND sls.schedule_status = 'Assigned'
            AND st.test_id = 'TES02'
            AND st.status = 'Ineligible'
        GROUP BY 
            sl.student_license_id, sl.lesson_id
        HAVING 
            total_lessons = 8 
            AND attended_lessons >= 6
    ";

  $result_les34 = mysqli_query($conn, $check_query_les34);

  if ($result_les34) {
    while ($row = mysqli_fetch_assoc($result_les34)) {
      $update_query = "
                UPDATE student_tests 
                SET status = 'Pending' 
                WHERE student_license_id = ? 
                AND test_id = 'TES02'
                AND status = 'Ineligible'
            ";

      $stmt = mysqli_prepare($conn, $update_query);
      mysqli_stmt_bind_param($stmt, 's', $row['student_license_id']);
      mysqli_stmt_execute($stmt);
      $updated_students += mysqli_stmt_affected_rows($stmt);
      mysqli_stmt_close($stmt);
    }
  }

  return $updated_students;
}

// Run the eligibility update function
$updated_students = updateStudentTestEligibility($conn);

// Fetch Upcoming Tests
function fetchUpcomingTests($conn)
{
  $query = "
    SELECT 
        ts.test_session_id,
        t.test_name,
        ts.test_date,
        ts.start_time,
        ts.end_time,
        ts.capacity_students,
        ts.status,
        (SELECT COUNT(*) 
         FROM student_test_sessions sts 
         WHERE sts.test_session_id = ts.test_session_id) AS enrolled_count
    FROM 
        test_sessions AS ts
    JOIN 
        tests AS t ON ts.test_id = t.test_id
    WHERE 
        ts.status = 'Scheduled'
    ORDER BY 
        ts.test_date ASC, ts.start_time ASC
  ";
  return mysqli_query($conn, $query);
}

// Fetch Past Tests
function fetchPastTests($conn)
{
  $query = "
    SELECT 
        ts.test_session_id,
        t.test_name,
        ts.test_date,
        ts.start_time,
        ts.end_time,
        ts.capacity_students,
        ts.status,
        (SELECT COUNT(*) 
         FROM student_test_sessions sts 
         WHERE sts.test_session_id = ts.test_session_id) AS enrolled_count
    FROM 
        test_sessions AS ts
    JOIN 
        tests AS t ON ts.test_id = t.test_id
    WHERE 
        ts.status = 'Completed'
    ORDER BY 
        ts.test_date DESC, ts.start_time DESC
  ";
  return mysqli_query($conn, $query);
}

// Fetch Eligibility Data
function fetchEligibilityStudents($conn)
{
  $query = "
    SELECT 
        u.name AS student_name,
        t.test_name,
        l.license_name
    FROM 
        student_tests AS st
    JOIN 
        tests AS t ON st.test_id = t.test_id
    JOIN 
        student_licenses AS sl ON st.student_license_id = sl.student_license_id
    JOIN 
        licenses AS l ON sl.license_id = l.license_id
    JOIN 
        students AS s ON sl.student_id = s.student_id
    JOIN 
        users AS u ON s.user_id = u.user_id
    WHERE 
        st.status = 'Pending' AND st.schedule_status = 'Unassigned'
  ";
  return mysqli_query($conn, $query);
}

$upcomingTestsResult = fetchUpcomingTests($conn);
$pastTestsResult = fetchPastTests($conn);
$eligibilityStudentsResult = fetchEligibilityStudents($conn);

// Count number of records in each category
$upcomingTestsCount = mysqli_num_rows($upcomingTestsResult);
$pastTestsCount = mysqli_num_rows($pastTestsResult);
$eligibilityStudentsCount = mysqli_num_rows($eligibilityStudentsResult);
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
          <a href="#">Test List</a>
        </li>
      </ul>
    </div>

    <!-- Inner page content -->
    <div class="page-category">
      <!-- Display notifications for automatic updates -->
      <?php if ($updated_test_sessions > 0 || $updated_students > 0): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
          <?php if ($updated_test_sessions > 0): ?>
            <p><i class="fas fa-calendar-check mr-2"></i> <?php echo $updated_test_sessions; ?> test session(s) have been automatically marked as completed.</p>
          <?php endif; ?>

          <?php if ($updated_students > 0): ?>
            <p><i class="fas fa-user-graduate mr-2"></i> <?php echo $updated_students; ?> student(s) have been automatically updated to eligible status for TES02.</p>
          <?php endif; ?>

          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      <?php endif; ?>

      <div class="col-md-12">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <div class="card-title">Test List</div>
            <div class="d-flex">
              <a href="add_test.php" class="btn btn-primary mr-3">
                Schedule New Test
              </a>
              <button type="button" class="btn btn-sm btn-outline-secondary" id="test-toggle-btn">
                <i class="fas fa-minus"></i>
              </button>
            </div>
          </div>
          <div class="card-body" id="test-card-body">
            <!-- Toggle Cards -->
            <div class="row mb-4">
              <div class="col-md-4">
                <div class="card card-stats card-round toggle-card active" data-target="upcoming-tests-container">
                  <div class="card-body">
                    <div class="row">
                      <div class="col-5">
                        <div class="icon-big text-center">
                          <i class="fas fa-calendar-alt text-primary"></i>
                        </div>
                      </div>
                      <div class="col-7 col-stats">
                        <div class="numbers">
                          <p class="card-category">Upcoming Tests</p>
                          <h4 class="card-title"><?php echo $upcomingTestsCount; ?></h4>
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
                          <i class="fas fa-history text-success"></i>
                        </div>
                      </div>
                      <div class="col-7 col-stats">
                        <div class="numbers">
                          <p class="card-category">Past Tests</p>
                          <h4 class="card-title"><?php echo $pastTestsCount; ?></h4>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-md-4">
                <div class="card card-stats card-round toggle-card" data-target="eligibility-container">
                  <div class="card-body">
                    <div class="row">
                      <div class="col-5">
                        <div class="icon-big text-center">
                          <i class="fas fa-user-check text-warning"></i>
                        </div>
                      </div>
                      <div class="col-7 col-stats">
                        <div class="numbers">
                          <p class="card-category">Eligibility</p>
                          <h4 class="card-title"><?php echo $eligibilityStudentsCount; ?></h4>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Upcoming Tests -->
            <div class="table-container" id="upcoming-tests-container">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0"><i class="fas fa-calendar-alt text-primary"></i> Upcoming Tests</h4>
              </div>
              <div class="table-responsive">
                <table id="upcoming-tests-table" class="table table-bordered table-striped">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Test Name</th>
                      <th>Date</th>
                      <th>Time</th>
                      <th>Capacity</th>
                      <th>Enrolled</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    if ($upcomingTestsCount > 0) {
                      mysqli_data_seek($upcomingTestsResult, 0); // Reset pointer
                      $counter = 1; // Initialize counter
                      while ($row = mysqli_fetch_assoc($upcomingTestsResult)) {
                        echo "<tr>
                  <td>" . $counter++ . "</td>
                  <td>" . htmlspecialchars($row['test_name']) . "</td>
                  <td>" . date('d M Y', strtotime($row['test_date'])) . "</td>
                  <td>" . date('h:i A', strtotime($row['start_time'])) . ' - ' . date('h:i A', strtotime($row['end_time'])) . "</td>
                  <td>" . htmlspecialchars($row['capacity_students']) . "</td>
                  <td>" . htmlspecialchars($row['enrolled_count']) . "</td>
                  <td>
                    <a href='edit_test.php?test_session_id=" . htmlspecialchars($row['test_session_id']) . "' class='btn btn-sm btn-primary'>
                      <i class='fas fa-edit mr-1'></i> Update
                    </a>
                    <a href='delete_test.php?test_session_id=" . htmlspecialchars($row['test_session_id']) . "' class='btn btn-sm btn-danger ml-2'>
                      <i class='fas fa-trash mr-1'></i> Delete
                    </a>
                  </td>
                </tr>";
                      }
                    } else {
                      echo "<tr><td colspan='7'>No upcoming tests found.</td></tr>";
                    }
                    ?>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Past Tests -->
            <div class="table-container" id="past-tests-container" style="display: none;">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0"><i class="fas fa-history text-success"></i> Past Tests</h4>
              </div>
              <div class="table-responsive">
                <table id="past-tests-table" class="table table-bordered table-striped">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Test Name</th>
                      <th>Date</th>
                      <th>Time</th>
                      <th>Capacity</th>
                      <th>Enrolled</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    if ($pastTestsCount > 0) {
                      mysqli_data_seek($pastTestsResult, 0); // Reset pointer
                      $counter = 1; // Initialize counter
                      while ($row = mysqli_fetch_assoc($pastTestsResult)) {
                        echo "<tr>
                  <td>" . $counter++ . "</td>
                  <td>" . htmlspecialchars($row['test_name']) . "</td>
                  <td>" . date('d M Y', strtotime($row['test_date'])) . "</td>
                  <td>" . date('h:i A', strtotime($row['start_time'])) . ' - ' . date('h:i A', strtotime($row['end_time'])) . "</td>
                  <td>" . htmlspecialchars($row['capacity_students']) . "</td>
                  <td>" . htmlspecialchars($row['enrolled_count']) . "</td>
                  <td>
                    <a href='view_test.php?test_session_id=" . htmlspecialchars($row['test_session_id']) . "' class='btn btn-sm btn-info'>
                      <i class='fas fa-eye mr-1'></i> View
                    </a>
                  </td>
                </tr>";
                      }
                    } else {
                      echo "<tr><td colspan='7'>No past tests found.</td></tr>";
                    }
                    ?>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Eligibility -->
            <div class="table-container" id="eligibility-container" style="display: none;">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0"><i class="fas fa-user-check text-warning"></i> Eligibility</h4>
              </div>
              <div class="table-responsive">
                <table id="eligibility-table" class="table table-bordered table-striped">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Student Name</th>
                      <th>Test Name</th>
                      <th>License Name</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    if ($eligibilityStudentsCount > 0) {
                      mysqli_data_seek($eligibilityStudentsResult, 0); // Reset pointer
                      $counter = 1; // Initialize counter
                      while ($row = mysqli_fetch_assoc($eligibilityStudentsResult)) {
                        echo "<tr>
                                <td>" . $counter++ . "</td>
                                <td>" . htmlspecialchars($row['student_name']) . "</td>
                                <td>" . htmlspecialchars($row['test_name']) . "</td>
                                <td>" . htmlspecialchars($row['license_name']) . "</td>
                              </tr>";
                      }
                    } else {
                      echo "<tr><td colspan='4'>No eligible students found.</td></tr>";
                    }
                    ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
include '../../../include/footer.html';
?>

<script>
  $(document).ready(function() {
    $("#upcoming-tests-table").DataTable({});
  });

  $(document).ready(function() {
    $("#past-tests-table").DataTable({});
  });

  $(document).ready(function() {
    $("#eligibility-table").DataTable({});
  });

  $(document).ready(function() {
    // Auto-dismiss alerts after 8 seconds
    setTimeout(function() {
      $('.alert-dismissible').alert('close');
    }, 8000);

    // Toggle card content visibility
    $('#test-toggle-btn').click(function() {
      var cardBody = $('#test-card-body');
      cardBody.css('transition', 'none');
      cardBody.slideToggle(300);

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

  /* Card header styling */
  .card-header {
    padding: 0.75rem 1.25rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .card-title {
    margin-bottom: 0;
  }

  /* Button spacing */
  .mr-3 {
    margin-right: 1rem;
  }

  /* Card body transition handling */
  #test-card-body {
    transition: none;
  }

  /* Alert styling */
  .alert p:last-child {
    margin-bottom: 0;
  }

  .alert i {
    margin-right: 5px;
  }
</style>