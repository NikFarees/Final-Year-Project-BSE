<?php
include '../../include/ad_header.php';
include '../../database/db_connection.php'; // Include the database connection file

// Fetch the top 3 latest notifications
$notification_query = "
    SELECT 
        a.title, 
        a.description, 
        a.created_at, 
        u.name AS created_by 
    FROM 
        announcements AS a
    LEFT JOIN 
        users AS u ON a.created_by = u.user_id
    ORDER BY 
        a.created_at DESC 
    LIMIT 3
";
$notification_result = $conn->query($notification_query);

// Fetch the number of students
$student_count_query = "SELECT COUNT(*) AS student_count FROM students";
$student_count_result = $conn->query($student_count_query);
$student_count_row = $student_count_result->fetch_assoc();
$student_count = $student_count_row['student_count'];

// Fetch the number of instructors
$instructor_count_query = "SELECT COUNT(*) AS instructor_count FROM instructors";
$instructor_count_result = $conn->query($instructor_count_query);
$instructor_count_row = $instructor_count_result->fetch_assoc();
$instructor_count = $instructor_count_row['instructor_count'];

// Get today's date for session queries
$today = date('Y-m-d');

// Query for today's lessons
$lesson_query = "
    SELECT 
        sl.start_time, 
        sl.end_time, 
        u_student.name AS student_name, 
        u_instructor.name AS instructor_name, 
        sl.student_lesson_name AS event_name,
        'Lesson' AS event_type,
        sl.status,
        CASE
            WHEN sl.status = 'Completed' THEN 'badge-success'
            WHEN CURRENT_TIME() BETWEEN sl.start_time AND sl.end_time THEN 'badge-info'
            ELSE 'badge-warning'
        END AS badge_class
    FROM 
        student_lessons sl
    JOIN 
        student_licenses slic ON sl.student_license_id = slic.student_license_id
    JOIN 
        students s ON slic.student_id = s.student_id
    JOIN 
        users u_student ON s.user_id = u_student.user_id
    LEFT JOIN 
        instructors i ON sl.instructor_id = i.instructor_id
    LEFT JOIN 
        users u_instructor ON i.user_id = u_instructor.user_id
    WHERE 
        sl.date = ?
    ORDER BY 
        sl.start_time";

$lesson_stmt = $conn->prepare($lesson_query);
$lesson_stmt->bind_param("s", $today);
$lesson_stmt->execute();
$lesson_result = $lesson_stmt->get_result();

// Query for today's tests
$test_query = "
    SELECT 
        ts.start_time, 
        ts.end_time, 
        u_student.name AS student_name, 
        u_instructor.name AS instructor_name,
        t.test_name AS event_name,
        'Test' AS event_type,
        ts.status,
        CASE
            WHEN ts.status = 'Completed' THEN 'badge-success'
            WHEN CURRENT_TIME() BETWEEN ts.start_time AND ts.end_time THEN 'badge-info'
            ELSE 'badge-warning'
        END AS badge_class
    FROM 
        test_sessions ts
    JOIN 
        student_test_sessions sts ON ts.test_session_id = sts.test_session_id
    JOIN 
        student_tests st ON sts.student_test_id = st.student_test_id
    JOIN 
        student_licenses slic ON st.student_license_id = slic.student_license_id
    JOIN 
        students s ON slic.student_id = s.student_id
    JOIN 
        users u_student ON s.user_id = u_student.user_id
    JOIN 
        tests t ON ts.test_id = t.test_id
    LEFT JOIN 
        instructors i ON ts.instructor_id = i.instructor_id
    LEFT JOIN 
        users u_instructor ON i.user_id = u_instructor.user_id
    WHERE 
        ts.test_date = ?
    ORDER BY 
        ts.start_time";

$test_stmt = $conn->prepare($test_query);
$test_stmt->bind_param("s", $today);
$test_stmt->execute();
$test_result = $test_stmt->get_result();

// Combine and sort session results
$all_sessions = array();
while ($lesson = $lesson_result->fetch_assoc()) {
  $all_sessions[] = $lesson;
}
while ($test = $test_result->fetch_assoc()) {
  $all_sessions[] = $test;
}
usort($all_sessions, function ($a, $b) {
  return strtotime($a['start_time']) - strtotime($b['start_time']);
});

// Get monthly revenue data for the chart
$revenue_query = "
    SELECT 
        MONTH(payment_datetime) AS month,
        YEAR(payment_datetime) AS year,
        SUM(total_amount) AS monthly_revenue
    FROM 
        payments
    WHERE 
        payment_status = 'Completed'
        AND (
            (YEAR(payment_datetime) = YEAR(CURRENT_DATE))
            OR 
            (YEAR(payment_datetime) = YEAR(CURRENT_DATE) - 1 AND MONTH(payment_datetime) = 12 AND MONTH(CURRENT_DATE) = 1)
        )
    GROUP BY 
        YEAR(payment_datetime), MONTH(payment_datetime)
    ORDER BY 
        YEAR(payment_datetime), MONTH(payment_datetime)
";
$revenue_result = $conn->query($revenue_query);

// Initialize revenue data array with zeros for all months
$months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$revenue_data = array_fill(0, 12, 0);
$revenue_by_month = array(); // New array to store revenue by month number
$current_month = date('n'); // Current month (1-12)

// Fill in actual revenue data
$current_year_revenue = 0;

while ($row = $revenue_result->fetch_assoc()) {
  $month_index = (int)$row['month'] - 1; // Convert to 0-based index
  $month_num = (int)$row['month']; // Month as number (1-12)
  $revenue_data[$month_index] = (float)$row['monthly_revenue'];

  // Store revenue by month number for easy access
  $revenue_by_month[$month_num] = (float)$row['monthly_revenue'];

  // Calculate current year total
  $current_year_revenue += (float)$row['monthly_revenue'];
}

// Get current month revenue (default to 0 if not found)
$current_month_revenue = $revenue_by_month[$current_month] ?? 0;

// Get last month revenue (handle January case by using December of previous year)
$last_month = $current_month - 1;
if ($last_month == 0) $last_month = 12;
$last_month_revenue = $revenue_by_month[$last_month] ?? 0;

// Calculate month-over-month percentage change
$percentage_change = 0;
if ($last_month_revenue > 0) {
  $percentage_change = (($current_month_revenue - $last_month_revenue) / $last_month_revenue) * 100;
} else if ($current_month_revenue > 0 && $last_month_revenue == 0) {
  // If last month was 0 but this month has revenue, show 100% increase
  $percentage_change = 100;
} else if ($current_month_revenue == 0 && $last_month_revenue > 0) {
  // If current month is 0 but last month had revenue, show 100% decrease
  $percentage_change = -100;
}

// Get student progress metrics
// 1. Computer Test Pass Rate
$computer_pass_query = "
    SELECT 
        COUNT(*) AS total,
        SUM(CASE WHEN st.status = 'Passed' THEN 1 ELSE 0 END) AS passed
    FROM 
        student_tests st
    JOIN 
        tests t ON st.test_id = t.test_id
    JOIN 
        student_test_sessions sts ON st.student_test_id = sts.student_test_id
    JOIN 
        test_sessions ts ON sts.test_session_id = ts.test_session_id
    WHERE 
        t.test_name LIKE '%Computer%'
        AND ts.status = 'Completed'
";
$computer_result = $conn->query($computer_pass_query);
$computer_data = $computer_result->fetch_assoc();
$computer_pass_rate = 0;
if ($computer_data['total'] > 0) {
  $computer_pass_rate = round(($computer_data['passed'] / $computer_data['total']) * 100);
}

// 2. QTI Test Pass Rate
$qti_pass_query = "
    SELECT 
        COUNT(*) AS total,
        SUM(CASE WHEN st.status = 'Passed' THEN 1 ELSE 0 END) AS passed
    FROM 
        student_tests st
    JOIN 
        tests t ON st.test_id = t.test_id
    JOIN 
        student_test_sessions sts ON st.student_test_id = sts.student_test_id
    JOIN 
        test_sessions ts ON sts.test_session_id = ts.test_session_id
    WHERE 
        t.test_name LIKE '%QTI%'
        AND ts.status = 'Completed'
";
$qti_result = $conn->query($qti_pass_query);
$qti_data = $qti_result->fetch_assoc();
$qti_pass_rate = 0;
if ($qti_data['total'] > 0) {
  $qti_pass_rate = round(($qti_data['passed'] / $qti_data['total']) * 100);
}

// 3. Circuit Test Pass Rate
$circuit_pass_query = "
    SELECT 
        COUNT(*) AS total,
        SUM(CASE WHEN st.status = 'Passed' THEN 1 ELSE 0 END) AS passed
    FROM 
        student_tests st
    JOIN 
        tests t ON st.test_id = t.test_id
    JOIN 
        student_test_sessions sts ON st.student_test_id = sts.student_test_id
    JOIN 
        test_sessions ts ON sts.test_session_id = ts.test_session_id
    WHERE 
        t.test_name LIKE '%Circuit%'
        AND ts.status = 'Completed'
";
$circuit_result = $conn->query($circuit_pass_query);
$circuit_data = $circuit_result->fetch_assoc();
$circuit_pass_rate = 0;
if ($circuit_data['total'] > 0) {
  $circuit_pass_rate = round(($circuit_data['passed'] / $circuit_data['total']) * 100);
}

// 4. On-Road Test Pass Rate
$onroad_pass_query = "
    SELECT 
        COUNT(*) AS total,
        SUM(CASE WHEN st.status = 'Passed' THEN 1 ELSE 0 END) AS passed
    FROM 
        student_tests st
    JOIN 
        tests t ON st.test_id = t.test_id
    JOIN 
        student_test_sessions sts ON st.student_test_id = sts.student_test_id
    JOIN 
        test_sessions ts ON sts.test_session_id = ts.test_session_id
    WHERE 
        t.test_name LIKE '%On-Road%'
        AND ts.status = 'Completed'
";
$onroad_result = $conn->query($onroad_pass_query);
$onroad_data = $onroad_result->fetch_assoc();
$onroad_pass_rate = 0;
if ($onroad_data['total'] > 0) {
  $onroad_pass_rate = round(($onroad_data['passed'] / $onroad_data['total']) * 100);
}

// 5. Lesson Attendance Rate
$attendance_query = "
    SELECT 
        COUNT(*) AS total,
        SUM(CASE WHEN attendance_status = 'Attend' THEN 1 ELSE 0 END) AS attended
    FROM 
        student_lessons
    WHERE 
        status = 'Completed'
";
$attendance_result = $conn->query($attendance_query);
$attendance_data = $attendance_result->fetch_assoc();
$attendance_rate = 0;
if ($attendance_data['total'] > 0) {
  $attendance_rate = round(($attendance_data['attended'] / $attendance_data['total']) * 100);
}
?>

<div class="container">
  <div class="page-inner">

    <!-- Breadcrumbs -->
    <div class="page-header">
      <h4 class="page-title">Dashboard</h4>
      <ul class="breadcrumbs">
        <li class="nav-home">
          <a href="#">
            <i class="icon-home"></i>
          </a>
        </li>
      </ul>
    </div>

    <!-- Inner page content -->
    <div class="page-category">
      <!-- Add this directly before the "Today's Sessions" row -->
      <div class="row">
        <!-- Total Students Card -->
        <div class="col-sm-6 col-md-3">
          <div class="card card-stats card-round">
            <div class="card-body">
              <div class="row align-items-center">
                <div class="col-icon">
                  <div class="icon-big text-center icon-primary bubble-shadow-small">
                    <i class="fas fa-users"></i>
                  </div>
                </div>
                <div class="col col-stats ml-3 ml-sm-0">
                  <div class="numbers">
                    <p class="card-category">Total Students</p>
                    <h4 class="card-title"><?php echo $student_count; ?></h4>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Total Instructors Card -->
        <div class="col-sm-6 col-md-3">
          <div class="card card-stats card-round">
            <div class="card-body">
              <div class="row align-items-center">
                <div class="col-icon">
                  <div class="icon-big text-center icon-info bubble-shadow-small">
                    <i class="fas fa-chalkboard-teacher"></i>
                  </div>
                </div>
                <div class="col col-stats ml-3 ml-sm-0">
                  <div class="numbers">
                    <p class="card-category">Total Instructors</p>
                    <h4 class="card-title"><?php echo $instructor_count; ?></h4>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Monthly Revenue Card -->
        <div class="col-sm-6 col-md-3">
          <div class="card card-stats card-round">
            <div class="card-body">
              <div class="row align-items-center">
                <div class="col-icon">
                  <div class="icon-big text-center icon-success bubble-shadow-small">
                    <i class="fas fa-dollar-sign"></i>
                  </div>
                </div>
                <div class="col col-stats ml-3 ml-sm-0">
                  <div class="numbers">
                    <p class="card-category">This Month Revenue</p>
                    <div class="d-flex align-items-center">
                      <h4 class="card-title mb-0">RM<?php echo number_format($current_month_revenue, 2); ?></h4>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Today's Sessions Card -->
        <?php
        // Count today's total sessions
        $total_sessions = count($all_sessions);

        // Count completed sessions
        $completed_sessions = 0;
        foreach ($all_sessions as $session) {
          if ($session['status'] === 'Completed') {
            $completed_sessions++;
          }
        }
        ?>
        <div class="col-sm-6 col-md-3">
          <div class="card card-stats card-round">
            <div class="card-body">
              <div class="row align-items-center">
                <div class="col-icon">
                  <div class="icon-big text-center icon-warning bubble-shadow-small">
                    <i class="fas fa-calendar"></i>
                  </div>
                </div>
                <div class="col col-stats ml-3 ml-sm-0">
                  <div class="numbers">
                    <p class="card-category">Today's Sessions</p>
                    <h4 class="card-title"><?php echo $completed_sessions; ?> / <?php echo $total_sessions; ?></h4>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <!-- Upcoming Sessions -->
        <div class="col-md-8">
          <div class="card">
            <div class="card-header">
              <div class="card-title">Today's Sessions</div>
              <div class="card-category"><?php echo date('F j, Y'); ?></div>
            </div>
            <div class="card-body pb-0">
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>Time</th>
                      <th>Student</th>
                      <th>Instructor</th>
                      <th>Type</th>
                      <th>Name</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    // Display results
                    if (count($all_sessions) > 0) {
                      foreach ($all_sessions as $session) {
                        $start_time = date('h:i A', strtotime($session['start_time']));
                        $end_time = date('h:i A', strtotime($session['end_time']));
                        $time_range = $start_time . ' - ' . $end_time;

                        $status_text = 'Upcoming';
                        if ($session['status'] === 'Completed') {
                          $status_text = 'Completed';
                        } elseif (
                          strtotime(date('H:i:s')) >= strtotime($session['start_time']) &&
                          strtotime(date('H:i:s')) <= strtotime($session['end_time'])
                        ) {
                          $status_text = 'In Progress';
                        }

                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($time_range) . "</td>";
                        echo "<td>" . htmlspecialchars($session['student_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($session['instructor_name'] ?? 'Not Assigned') . "</td>";
                        echo "<td>" . htmlspecialchars($session['event_type']) . "</td>";
                        echo "<td>" . htmlspecialchars($session['event_name']) . "</td>";
                        echo "<td><span class='badge " . $session['badge_class'] . "'>" . $status_text . "</span></td>";
                        echo "</tr>";
                      }
                    } else {
                      echo "<tr><td colspan='6' class='text-center'>No sessions scheduled for today</td></tr>";
                    }
                    ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Notification Card -->
        <div class="col-md-4">
          <div class="card">
            <div class="card-header">
              <div class="card-title">Recent Notifications</div>
            </div>
            <div class="card-body">
              <div class="notification-list">
                <?php
                if ($notification_result->num_rows > 0) {
                  while ($notification = $notification_result->fetch_assoc()) {
                    echo '<div class="notification-item">';
                    echo '<h6 class="notification-heading fw-bold mb-1">' . htmlspecialchars($notification['title']) . '</h6>';
                    echo '<div class="notification-time text-muted"><small>' . date('F j, Y, g:i a', strtotime($notification['created_at'])) . '</small></div>';
                    echo '<p class="notification-text">' . htmlspecialchars($notification['description']) . '</p>';
                    echo '</div>';
                    echo '<div class="separator-dashed"></div>';
                  }
                } else {
                  echo '<div class="notification-item">';
                  echo '<p class="notification-text">No announcements yet.</p>';
                  echo '</div>';
                }
                ?>
                <div class="d-flex justify-content-center mt-3">
                  <a href="/pages/admin/manage_announcement/list_announcement.php" class="btn btn-sm btn-primary">See More</a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Revenue & Student Progress -->
      <div class="row">
        <!-- Revenue Chart -->
        <div class="col-md-8">
          <div class="card">
            <div class="card-header">
              <div class="card-title">Monthly Revenue</div>
            </div>
            <div class="card-body">
              <div class="chart-container">
                <canvas id="revenueChart" style="width: 100%; height: 250px"></canvas>
              </div>
              <div class="mt-3 d-flex justify-content-between">
                <div>
                  <h6>Total This Month</h6>
                  <h4>RM<?php echo number_format($current_month_revenue, 2); ?></h4>
                </div>
                <div>
                  <h6>Compared to Last Month</h6>
                  <h4 class="<?php echo $percentage_change >= 0 ? 'text-success' : 'text-danger'; ?>">
                    <?php echo ($percentage_change >= 0 ? '+' : '') . number_format($percentage_change, 1) . '%'; ?>
                  </h4>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Student Progress -->
        <div class="col-md-4">
          <div class="card">
            <div class="card-header">
              <div class="card-title">Student Progress</div>
            </div>
            <div class="card-body">
              <div class="progress-card">
                <div class="d-flex justify-content-between mb-1">
                  <span class="text-muted">Computer Test Pass Rate</span>
                  <span class="text-muted fw-bold"><?php echo $computer_pass_rate; ?>%</span>
                </div>
                <div class="progress mb-3" style="height: 7px;">
                  <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $computer_pass_rate; ?>%" aria-valuenow="<?php echo $computer_pass_rate; ?>" aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip" data-placement="top" title="<?php echo $computer_pass_rate; ?>%"></div>
                </div>
              </div>
              <div class="progress-card">
                <div class="d-flex justify-content-between mb-1">
                  <span class="text-muted">QTI Test Pass Rate</span>
                  <span class="text-muted fw-bold"><?php echo $qti_pass_rate; ?>%</span>
                </div>
                <div class="progress mb-3" style="height: 7px;">
                  <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $qti_pass_rate; ?>%" aria-valuenow="<?php echo $qti_pass_rate; ?>" aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip" data-placement="top" title="<?php echo $qti_pass_rate; ?>%"></div>
                </div>
              </div>
              <div class="progress-card">
                <div class="d-flex justify-content-between mb-1">
                  <span class="text-muted">Circuit Test Pass Rate</span>
                  <span class="text-muted fw-bold"><?php echo $circuit_pass_rate; ?>%</span>
                </div>
                <div class="progress mb-3" style="height: 7px;">
                  <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $circuit_pass_rate; ?>%" aria-valuenow="<?php echo $circuit_pass_rate; ?>" aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip" data-placement="top" title="<?php echo $circuit_pass_rate; ?>%"></div>
                </div>
              </div>
              <div class="progress-card">
                <div class="d-flex justify-content-between mb-1">
                  <span class="text-muted">On-Road Test Pass Rate</span>
                  <span class="text-muted fw-bold"><?php echo $onroad_pass_rate; ?>%</span>
                </div>
                <div class="progress mb-3" style="height: 7px;">
                  <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo $onroad_pass_rate; ?>%" aria-valuenow="<?php echo $onroad_pass_rate; ?>" aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip" data-placement="top" title="<?php echo $onroad_pass_rate; ?>%"></div>
                </div>
              </div>
              <div class="progress-card">
                <div class="d-flex justify-content-between mb-1">
                  <span class="text-muted">Lesson Attendance Rate</span>
                  <span class="text-muted fw-bold"><?php echo $attendance_rate; ?>%</span>
                </div>
                <div class="progress mb-3" style="height: 7px;">
                  <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $attendance_rate; ?>%" aria-valuenow="<?php echo $attendance_rate; ?>" aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip" data-placement="top" title="<?php echo $attendance_rate; ?>%"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Access Buttons -->
      <div class="row">
        <div class="col-md-12">
          <div class="card">
            <div class="card-header">
              <div class="card-title">Quick Access</div>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-6 col-md-3 mb-3">
                  <a href="/pages/admin/manage_student/add_student.php" class="btn btn-primary btn-block btn-round">
                    <i class="fas fa-user-plus mr-2"></i> New Student
                  </a>
                </div>
                <div class="col-6 col-md-3 mb-3">
                  <a href="/pages/admin/manage_lesson/schedule_lesson.php" class="btn btn-info btn-block btn-round">
                    <i class="fas fa-calendar-plus mr-2"></i> Schedule Session
                  </a>
                </div>
                <div class="col-6 col-md-3 mb-3">
                  <a href="/pages/admin/manage_payment/process_payment.php" class="btn btn-success btn-block btn-round">
                    <i class="fas fa-dollar-sign mr-2"></i> Process Payment
                  </a>
                </div>
                <div class="col-6 col-md-3 mb-3">
                  <a href="/pages/admin/reports/generate_report.php" class="btn btn-warning btn-block btn-round">
                    <i class="fas fa-file-alt mr-2"></i> Generate Reports
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
  // Revenue Chart
  document.addEventListener("DOMContentLoaded", function() {
    var revenueCtx = document.getElementById('revenueChart').getContext('2d');
    var revenueChart = new Chart(revenueCtx, {
      type: 'line',
      data: {
        labels: <?php echo json_encode($months); ?>,
        datasets: [{
          label: 'Revenue',
          data: <?php echo json_encode($revenue_data); ?>,
          borderColor: '#1572E8',
          backgroundColor: 'rgba(21, 114, 232, 0.1)',
          borderWidth: 2,
          fill: true,
          tension: 0.4
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false
          }
        },
        scales: {
          y: {
            beginAtZero: false,
            grid: {
              drawBorder: false
            },
            ticks: {
              callback: function(value) {
                return 'RM' + value.toLocaleString();
              }
            }
          },
          x: {
            grid: {
              display: false
            }
          }
        }
      }
    });
  });
</script>

<?php
include '../../include/footer.html';
?>