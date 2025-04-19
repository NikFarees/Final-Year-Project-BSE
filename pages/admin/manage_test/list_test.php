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
mysqli_stmt_close($stmt);

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

      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h4 class="card-title">Test List</h4>
          <div class="ms-md-auto py-2 py-md-0">
            <a href="add_test.php" class="btn btn-primary btn-round">Schedule New Test</a>
          </div>
        </div>
        <div class="card-body">
          <ul class="nav nav-tabs nav-line nav-color-secondary" id="line-tab" role="tablist">
            <li class="nav-item">
              <a class="nav-link active" id="line-upcoming-tests-tab" data-bs-toggle="pill" href="#line-upcoming-tests" role="tab" aria-controls="line-upcoming-tests" aria-selected="true">Upcoming Tests</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" id="line-past-tests-tab" data-bs-toggle="pill" href="#line-past-tests" role="tab" aria-controls="line-past-tests" aria-selected="false">Past Tests</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" id="line-eligibility-tab" data-bs-toggle="pill" href="#line-eligibility" role="tab" aria-controls="line-eligibility" aria-selected="false">Eligibility</a>
            </li>
          </ul>
          <div class="tab-content mt-3 mb-3" id="line-tabContent">

            <!-- Upcoming Tests -->
            <div class="tab-pane fade show active" id="line-upcoming-tests" role="tabpanel" aria-labelledby="line-upcoming-tests-tab">
              <div class="table-responsive">
                <table id="upcoming-tests-table" class="table table-bordered table-striped table-hover">
                  <thead>
                    <tr>
                      <th>Test Name</th>
                      <th>Date</th>
                      <th>Start Time</th>
                      <th>End Time</th>
                      <th>Capacity</th>
                      <th>Enrolled</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    if (mysqli_num_rows($upcomingTestsResult) > 0) {
                      while ($row = mysqli_fetch_assoc($upcomingTestsResult)) {
                        echo "<tr class='clickable-row' data-href='edit_test.php?test_session_id=" . htmlspecialchars($row['test_session_id']) . "'>
                              <td>" . htmlspecialchars($row['test_name']) . "</td>
                              <td>" . htmlspecialchars($row['test_date']) . "</td>
                              <td>" . htmlspecialchars($row['start_time']) . "</td>
                              <td>" . htmlspecialchars($row['end_time']) . "</td>
                              <td>" . htmlspecialchars($row['capacity_students']) . "</td>
                              <td>" . htmlspecialchars($row['enrolled_count']) . "</td>
                              <td>" . htmlspecialchars($row['status']) . "</td>
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
            <div class="tab-pane fade" id="line-past-tests" role="tabpanel" aria-labelledby="line-past-tests-tab">
              <div class="table-responsive">
                <table id="past-tests-table" class="table table-bordered table-striped table-hover">
                  <thead>
                    <tr>
                      <th>Test Name</th>
                      <th>Date</th>
                      <th>Start Time</th>
                      <th>End Time</th>
                      <th>Capacity</th>
                      <th>Enrolled</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    if (mysqli_num_rows($pastTestsResult) > 0) {
                      while ($row = mysqli_fetch_assoc($pastTestsResult)) {
                        echo "<tr>
                                <td>{$row['test_name']}</td>
                                <td>{$row['test_date']}</td>
                                <td>{$row['start_time']}</td>
                                <td>{$row['end_time']}</td>
                                <td>{$row['capacity_students']}</td>
                                <td>{$row['enrolled_count']}</td>
                                <td>{$row['status']}</td>
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
            <div class="tab-pane fade" id="line-eligibility" role="tabpanel" aria-labelledby="line-eligibility-tab">
              <div class="table-responsive">
                <table id="eligibility-table" class="table table-bordered table-striped">
                  <thead>
                    <tr>
                      <th>Student Name</th>
                      <th>Test Name</th>
                      <th>License Name</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    if (mysqli_num_rows($eligibilityStudentsResult) > 0) {
                      while ($row = mysqli_fetch_assoc($eligibilityStudentsResult)) {
                        echo "<tr>
                                <td>{$row['student_name']}</td>
                                <td>{$row['test_name']}</td>
                                <td>{$row['license_name']}</td>
                              </tr>";
                      }
                    } else {
                      echo "<tr><td colspan='3'>No eligible students found.</td></tr>";
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

  // Make table rows clickable
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