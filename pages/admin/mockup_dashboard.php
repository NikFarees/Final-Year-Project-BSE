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

// NEW: Count licenses by type
$license_count_query = "
    SELECT 
        l.license_type,
        COUNT(sl.student_license_id) as count
    FROM 
        licenses l
    LEFT JOIN 
        student_licenses sl ON l.license_id = sl.license_id
    GROUP BY 
        l.license_type
";
$license_count_result = $conn->query($license_count_query);
$license_data = [];
while ($row = $license_count_result->fetch_assoc()) {
  $license_data[$row['license_type']] = $row['count'];
}

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
        AND YEAR(payment_datetime) = YEAR(CURRENT_DATE)
    GROUP BY 
        YEAR(payment_datetime), MONTH(payment_datetime)
    ORDER BY 
        YEAR(payment_datetime), MONTH(payment_datetime)
";
$revenue_result = $conn->query($revenue_query);

// Initialize revenue data array with zeros for all months
$months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$revenue_data = array_fill(0, 12, 0);
$revenue_labels = [];

// Fill in actual revenue data
$current_year_revenue = 0;
$last_month_revenue = 0;
$current_month_revenue = 0;
$current_month = date('n'); // Current month (1-12)

while ($row = $revenue_result->fetch_assoc()) {
  $month_index = (int)$row['month'] - 1; // Convert to 0-based index
  $revenue_data[$month_index] = (float)$row['monthly_revenue'];

  // Calculate current year total
  $current_year_revenue += (float)$row['monthly_revenue'];

  // Get current month revenue
  if ((int)$row['month'] === $current_month) {
    $current_month_revenue = (float)$row['monthly_revenue'];
  }

  // Get last month revenue
  if ((int)$row['month'] === ($current_month - 1 ?: 12)) {
    $last_month_revenue = (float)$row['monthly_revenue'];
  }
}

// Calculate month-over-month percentage change
$percentage_change = 0;
if ($last_month_revenue > 0) {
  $percentage_change = (($current_month_revenue - $last_month_revenue) / $last_month_revenue) * 100;
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

// NEW: Get age distribution of students
$age_distribution_query = "
    SELECT 
        CASE 
            WHEN TIMESTAMPDIFF(YEAR, dob, CURDATE()) < 20 THEN 'Under 20'
            WHEN TIMESTAMPDIFF(YEAR, dob, CURDATE()) BETWEEN 20 AND 29 THEN '20-29'
            WHEN TIMESTAMPDIFF(YEAR, dob, CURDATE()) BETWEEN 30 AND 39 THEN '30-39'
            WHEN TIMESTAMPDIFF(YEAR, dob, CURDATE()) BETWEEN 40 AND 49 THEN '40-49'
            ELSE '50+' 
        END AS age_group,
        COUNT(*) as count
    FROM 
        students
    GROUP BY 
        age_group
    ORDER BY 
        CASE 
            WHEN age_group = 'Under 20' THEN 1
            WHEN age_group = '20-29' THEN 2
            WHEN age_group = '30-39' THEN 3
            WHEN age_group = '40-49' THEN 4
            ELSE 5
        END
";
$age_distribution_result = $conn->query($age_distribution_query);
$age_labels = [];
$age_data = [];
while ($row = $age_distribution_result->fetch_assoc()) {
  $age_labels[] = $row['age_group'];
  $age_data[] = $row['count'];
}

// NEW: Get instructor ratings
$instructor_ratings_query = "
    SELECT 
        u.name AS instructor_name,
        ROUND(AVG(CASE WHEN f.description LIKE '%rating:%' 
                 THEN SUBSTRING_INDEX(SUBSTRING_INDEX(f.description, 'rating:', -1), ' ', 1) 
                 ELSE NULL END), 1) AS avg_rating,
        COUNT(f.feedback_id) AS feedback_count
    FROM 
        feedback f
    JOIN 
        users u ON f.target_user_id = u.user_id
    JOIN 
        instructors i ON u.user_id = i.user_id
    WHERE 
        f.description LIKE '%rating:%'
    GROUP BY 
        u.name
    HAVING 
        avg_rating IS NOT NULL
    ORDER BY 
        avg_rating DESC
    LIMIT 5
";
$instructor_ratings_result = $conn->query($instructor_ratings_query);
$instructor_names = [];
$instructor_ratings = [];
$instructor_feedback_counts = [];
while ($row = $instructor_ratings_result->fetch_assoc()) {
  $instructor_names[] = $row['instructor_name'];
  $instructor_ratings[] = $row['avg_rating'];
  $instructor_feedback_counts[] = $row['feedback_count'];
}

// NEW: Get completion time statistics
$completion_time_query = "
    SELECT 
        l.license_type,
        ROUND(AVG(DATEDIFF(il.issued_date, (
            SELECT MIN(p.payment_datetime) 
            FROM payments p 
            WHERE p.student_license_id = sl.student_license_id
        ))), 1) AS avg_days_to_complete
    FROM 
        issued_licenses il
    JOIN 
        student_licenses sl ON il.student_license_id = sl.student_license_id
    JOIN 
        licenses l ON sl.license_id = l.license_id
    WHERE 
        il.status = 'Issued'
    GROUP BY 
        l.license_type
";
$completion_time_result = $conn->query($completion_time_query);
$completion_time_data = [];
while ($row = $completion_time_result->fetch_assoc()) {
  $completion_time_data[$row['license_type']] = $row['avg_days_to_complete'];
}

// NEW: Calculate growth rates
$growth_query = "
    SELECT 
        (SELECT COUNT(*) FROM students s
         JOIN users u ON s.user_id = u.user_id
         WHERE YEAR(u.created_at) = YEAR(CURRENT_DATE) AND MONTH(u.created_at) = MONTH(CURRENT_DATE)) AS current_month_students,
        (SELECT COUNT(*) FROM students s
         JOIN users u ON s.user_id = u.user_id
         WHERE YEAR(u.created_at) = YEAR(CURRENT_DATE - INTERVAL 1 MONTH) AND MONTH(u.created_at) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH)) AS last_month_students,
        (SELECT COUNT(*) FROM issued_licenses 
         WHERE YEAR(issued_date) = YEAR(CURRENT_DATE) AND MONTH(issued_date) = MONTH(CURRENT_DATE)) AS current_month_licenses,
        (SELECT COUNT(*) FROM issued_licenses 
         WHERE YEAR(issued_date) = YEAR(CURRENT_DATE - INTERVAL 1 MONTH) AND MONTH(issued_date) = MONTH(CURRENT_DATE - INTERVAL 1 MONTH)) AS last_month_licenses
";
$growth_result = $conn->query($growth_query);
$growth_data = $growth_result->fetch_assoc();

$student_growth_rate = 0;
if ($growth_data['last_month_students'] > 0) {
  $student_growth_rate = (($growth_data['current_month_students'] - $growth_data['last_month_students']) / $growth_data['last_month_students']) * 100;
}

$license_growth_rate = 0;
if ($growth_data['last_month_licenses'] > 0) {
  $license_growth_rate = (($growth_data['current_month_licenses'] - $growth_data['last_month_licenses']) / $growth_data['last_month_licenses']) * 100;
}

// NEW: Get payment method statistics
$payment_methods_query = "
    SELECT 
        payment_method,
        COUNT(*) AS count,
        SUM(total_amount) AS total
    FROM 
        payments
    WHERE 
        payment_status = 'Completed'
        AND YEAR(payment_datetime) = YEAR(CURRENT_DATE)
    GROUP BY 
        payment_method
";
$payment_methods_result = $conn->query($payment_methods_query);
$payment_method_labels = [];
$payment_method_data = [];
$payment_method_totals = [];
while ($row = $payment_methods_result->fetch_assoc()) {
  $payment_method_labels[] = $row['payment_method'];
  $payment_method_data[] = $row['count'];
  $payment_method_totals[] = $row['total'];
}

// NEW: Get test scheduling efficiency
$test_scheduling_query = "
    SELECT 
        t.test_name,
        COUNT(ts.test_session_id) AS total_sessions,
        SUM(ts.capacity_students) AS total_capacity,
        COUNT(sts.student_test_session_id) AS total_students,
        ROUND((COUNT(sts.student_test_session_id) * 100.0) / SUM(ts.capacity_students), 1) AS utilization_rate
    FROM 
        test_sessions ts
    JOIN 
        tests t ON ts.test_id = t.test_id
    LEFT JOIN 
        student_test_sessions sts ON ts.test_session_id = sts.test_session_id
    WHERE 
        ts.status = 'Completed'
        AND YEAR(ts.test_date) = YEAR(CURRENT_DATE)
    GROUP BY 
        t.test_name
";
$test_scheduling_result = $conn->query($test_scheduling_query);
$test_labels = [];
$test_utilization = [];
while ($row = $test_scheduling_result->fetch_assoc()) {
  $test_labels[] = $row['test_name'];
  $test_utilization[] = $row['utilization_rate'];
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

      <!-- Stats Cards Row -->
      <div class="row">
        <div class="col-sm-6 col-md-3">
          <div class="card card-stats card-primary card-round">
            <div class="card-body">
              <div class="row">
                <div class="col-5">
                  <div class="icon-big text-center">
                    <i class="fas fa-users"></i>
                  </div>
                </div>
                <div class="col-7 col-stats">
                  <div class="numbers">
                    <p class="card-category">Students</p>
                    <h4 class="card-title"><?php echo $student_count; ?></h4>
                    <p class="card-category">
                      <span class="<?php echo $student_growth_rate >= 0 ? 'text-success' : 'text-danger'; ?>">
                        <i class="fa fa-<?php echo $student_growth_rate >= 0 ? 'arrow-up' : 'arrow-down'; ?>"></i>
                        <?php echo abs(round($student_growth_rate, 1)); ?>%
                      </span>
                      from last month
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-md-3">
          <div class="card card-stats card-info card-round">
            <div class="card-body">
              <div class="row">
                <div class="col-5">
                  <div class="icon-big text-center">
                    <i class="fas fa-user-tie"></i>
                  </div>
                </div>
                <div class="col-7 col-stats">
                  <div class="numbers">
                    <p class="card-category">Instructors</p>
                    <h4 class="card-title"><?php echo $instructor_count; ?></h4>
                    <p class="card-category">
                      <span class="text-success">
                        <i class="fa fa-check-circle"></i>
                        <?php echo number_format(count($instructor_ratings) > 0 ? array_sum($instructor_ratings) / count($instructor_ratings) : 0, 1); ?>/5
                      </span>
                      avg. rating
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-md-3">
          <div class="card card-stats card-success card-round">
            <div class="card-body">
              <div class="row">
                <div class="col-5">
                  <div class="icon-big text-center">
                    <i class="fas fa-id-card"></i>
                  </div>
                </div>
                <div class="col-7 col-stats">
                  <div class="numbers">
                    <p class="card-category">Licenses Issued</p>
                    <h4 class="card-title"><?php echo array_sum($license_data); ?></h4>
                    <p class="card-category">
                      <span class="<?php echo $license_growth_rate >= 0 ? 'text-success' : 'text-danger'; ?>">
                        <i class="fa fa-<?php echo $license_growth_rate >= 0 ? 'arrow-up' : 'arrow-down'; ?>"></i>
                        <?php echo abs(round($license_growth_rate, 1)); ?>%
                      </span>
                      from last month
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-md-3">
          <div class="card card-stats card-warning card-round">
            <div class="card-body">
              <div class="row">
                <div class="col-5">
                  <div class="icon-big text-center">
                    <i class="fas fa-calendar-check"></i>
                  </div>
                </div>
                <div class="col-7 col-stats">
                  <div class="numbers">
                    <p class="card-category">Sessions Today</p>
                    <h4 class="card-title">36</h4>
                    <p class="card-category">
                      <span class="text-success">
                        <i class="fa fa-check-circle"></i>
                        <?php echo $attendance_rate; ?>%
                      </span>
                      attendance rate
                    </p>
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

                    // Combine results from both queries
                    $all_sessions = array();

                    while ($lesson = $lesson_result->fetch_assoc()) {
                      $all_sessions[] = $lesson;
                    }

                    while ($test = $test_result->fetch_assoc()) {
                      $all_sessions[] = $test;
                    }

                    // Sort by start time
                    usort($all_sessions, function ($a, $b) {
                      return strtotime($a['start_time']) - strtotime($b['start_time']);
                    });

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
                        echo "<td>" . htmlspecialchars($session['event_type']) . "</td>";  // Separate column for type
                        echo "<td>" . htmlspecialchars($session['event_name']) . "</td>";  // Separate column for name
                        echo "<td><span class='badge " . $session['badge_class'] . "'>" . $status_text . "</span></td>";
                        echo "</tr>";
                      }
                    } else {
                      echo "<tr><td colspan='5' class='text-center'>No sessions scheduled for today</td></tr>";
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

      <!-- NEW: Additional Analytics -->
      <div class="row">
        <!-- Student Demographics -->
        <div class="col-md-6">
          <div class="card">
            <div class="card-header">
              <div class="card-title">Student Demographics</div>
            </div>
            <div class="card-body">
              <div class="chart-container">
                <canvas id="ageDistributionChart" style="width: 100%; height: 250px"></canvas>
              </div>
            </div>
          </div>
        </div>

        <!-- License Type Distribution -->
        <div class="col-md-6">
          <div class="card">
            <div class="card-header">
              <div class="card-title">License Distribution</div>
            </div>
            <div class="card-body">
              <div class="chart-container">
                <canvas id="licenseDistributionChart" style="width: 100%; height: 250px"></canvas>
              </div>
              <div class="mt-3">
                <div class="d-flex justify-content-between">
                  <span>Average Completion Time:</span>
                  <span class="fw-bold">
                    <?php
                    foreach ($completion_time_data as $type => $days) {
                      echo $type . ': ' . $days . ' days<br>';
                    }
                    ?>
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- NEW: Instructor Performance & Payment Analytics -->
      <div class="row">
        <!-- Top Instructor Ratings -->
        <div class="col-md-6">
          <div class="card">
            <div class="card-header">
              <div class="card-title">Top Instructor Ratings</div>
            </div>
            <div class="card-body">
              <div class="chart-container">
                <canvas id="instructorRatingsChart" style="width: 100%; height: 250px"></canvas>
              </div>
              <div class="mt-3">
                <table class="table table-sm">
                  <thead>
                    <tr>
                      <th>Instructor</th>
                      <th>Rating</th>
                      <th>Reviews</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    for ($i = 0; $i < count($instructor_names); $i++) {
                      echo "<tr>";
                      echo "<td>" . htmlspecialchars($instructor_names[$i]) . "</td>";
                      echo "<td>" . htmlspecialchars($instructor_ratings[$i]) . "/5</td>";
                      echo "<td>" . htmlspecialchars($instructor_feedback_counts[$i]) . "</td>";
                      echo "</tr>";
                    }
                    ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Payment Method Analysis -->
        <div class="col-md-6">
          <div class="card">
            <div class="card-header">
              <div class="card-title">Payment Methods</div>
            </div>
            <div class="card-body">
              <div class="chart-container">
                <canvas id="paymentMethodsChart" style="width: 100%; height: 250px"></canvas>
              </div>
              <div class="mt-3">
                <table class="table table-sm">
                  <thead>
                    <tr>
                      <th>Payment Method</th>
                      <th>Count</th>
                      <th>Total Revenue</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    for ($i = 0; $i < count($payment_method_labels); $i++) {
                      echo "<tr>";
                      echo "<td>" . htmlspecialchars($payment_method_labels[$i]) . "</td>";
                      echo "<td>" . htmlspecialchars($payment_method_data[$i]) . "</td>";
                      echo "<td>RM" . number_format($payment_method_totals[$i], 2) . "</td>";
                      echo "</tr>";
                    }
                    ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- NEW: Test Efficiency Analytics -->
      <div class="row">
        <!-- Test Session Utilization -->
        <div class="col-md-6">
          <div class="card">
            <div class="card-header">
              <div class="card-title">Test Session Utilization</div>
            </div>
            <div class="card-body">
              <div class="chart-container">
                <canvas id="testUtilizationChart" style="width: 100%; height: 250px"></canvas>
              </div>
              <div class="mt-3">
                <p class="card-category">
                  Shows the percentage of available test slots that were filled this year, indicating scheduling efficiency.
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- License Completion Timeline -->
        <div class="col-md-6">
          <div class="card">
            <div class="card-header">
              <div class="card-title">License Performance Metrics</div>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>Metric</th>
                      <th>Value</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td>Average Days to Complete License</td>
                      <td>
                        <?php
                        foreach ($completion_time_data as $type => $days) {
                          echo $type . ': <strong>' . $days . ' days</strong><br>';
                        }
                        ?>
                      </td>
                    </tr>
                    <tr>
                      <td>First-Time Pass Rate (On-Road)</td>
                      <td><strong><?php echo $onroad_pass_rate; ?>%</strong></td>
                    </tr>
                    <tr>
                      <td>First-Time Pass Rate (Theory)</td>
                      <td><strong><?php echo $computer_pass_rate; ?>%</strong></td>
                    </tr>
                    <tr>
                      <td>Student Attendance Rate</td>
                      <td><strong><?php echo $attendance_rate; ?>%</strong></td>
                    </tr>
                    <tr>
                      <td>License Growth Rate</td>
                      <td class="<?php echo $license_growth_rate >= 0 ? 'text-success' : 'text-danger'; ?>">
                        <strong><?php echo ($license_growth_rate >= 0 ? '+' : '') . number_format($license_growth_rate, 1) . '%'; ?></strong>
                      </td>
                    </tr>
                  </tbody>
                </table>
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

    // Age Distribution Chart
    var ageCtx = document.getElementById('ageDistributionChart').getContext('2d');
    var ageChart = new Chart(ageCtx, {
      type: 'pie',
      data: {
        labels: <?php echo json_encode($age_labels); ?>,
        datasets: [{
          data: <?php echo json_encode($age_data); ?>,
          backgroundColor: [
            'rgba(54, 162, 235, 0.7)',
            'rgba(255, 99, 132, 0.7)',
            'rgba(255, 206, 86, 0.7)',
            'rgba(75, 192, 192, 0.7)',
            'rgba(153, 102, 255, 0.7)'
          ],
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'right'
          },
          title: {
            display: true,
            text: 'Student Age Distribution'
          }
        }
      }
    });

    // License Distribution Chart
    var licenseCtx = document.getElementById('licenseDistributionChart').getContext('2d');
    var licenseData = {
      labels: <?php echo json_encode(array_keys($license_data)); ?>,
      datasets: [{
        label: 'Number of Licenses',
        data: <?php echo json_encode(array_values($license_data)); ?>,
        backgroundColor: [
          'rgba(54, 162, 235, 0.7)',
          'rgba(255, 99, 132, 0.7)'
        ],
        borderWidth: 1
      }]
    };
    var licenseChart = new Chart(licenseCtx, {
      type: 'bar',
      data: licenseData,
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false
          },
          title: {
            display: true,
            text: 'License Distribution by Type'
          }
        },
        scales: {
          y: {
            beginAtZero: true
          }
        }
      }
    });

    // Instructor Ratings Chart
    var instructorCtx = document.getElementById('instructorRatingsChart').getContext('2d');
    var instructorChart = new Chart(instructorCtx, {
      type: 'bar',
      data: {
        labels: <?php echo json_encode($instructor_names); ?>,
        datasets: [{
          label: 'Rating (out of 5)',
          data: <?php echo json_encode($instructor_ratings); ?>,
          backgroundColor: 'rgba(54, 162, 235, 0.7)',
          borderColor: 'rgba(54, 162, 235, 1)',
          borderWidth: 1
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
            beginAtZero: true,
            max: 5,
            ticks: {
              stepSize: 1
            }
          }
        }
      }
    });

    // Payment Methods Chart
    var paymentCtx = document.getElementById('paymentMethodsChart').getContext('2d');
    var paymentChart = new Chart(paymentCtx, {
      type: 'doughnut',
      data: {
        labels: <?php echo json_encode($payment_method_labels); ?>,
        datasets: [{
          data: <?php echo json_encode($payment_method_data); ?>,
          backgroundColor: [
            'rgba(54, 162, 235, 0.7)',
            'rgba(255, 99, 132, 0.7)',
            'rgba(255, 206, 86, 0.7)',
            'rgba(75, 192, 192, 0.7)',
            'rgba(153, 102, 255, 0.7)'
          ],
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'right'
          }
        }
      }
    });

    // Test Utilization Chart
    var testUtilCtx = document.getElementById('testUtilizationChart').getContext('2d');
    var testUtilChart = new Chart(testUtilCtx, {
      type: 'horizontalBar',
      data: {
        labels: <?php echo json_encode($test_labels); ?>,
        datasets: [{
          label: 'Utilization Rate (%)',
          data: <?php echo json_encode($test_utilization); ?>,
          backgroundColor: [
            'rgba(255, 99, 132, 0.7)',
            'rgba(54, 162, 235, 0.7)',
            'rgba(255, 206, 86, 0.7)',
            'rgba(75, 192, 192, 0.7)'
          ],
          borderWidth: 1
        }]
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false
          }
        },
        scales: {
          x: {
            beginAtZero: true,
            max: 100,
            title: {
              display: true,
              text: 'Utilization Rate (%)'
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