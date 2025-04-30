<?php
include '../../../include/in_header.php';
include '../../../database/db_connection.php';

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    $error_message = "User is not logged in.";
} else {
    // Step 1: Get instructor_id from the current user
    $stmt = $conn->prepare("SELECT instructor_id FROM instructors WHERE user_id = ?");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $instructor = $result->fetch_assoc();

    if (!$instructor) {
        $error_message = "Instructor record not found.";
    } else {
        $instructor_id = $instructor['instructor_id'];

        // Set the correct timezone
        date_default_timezone_set('Asia/Kuala_Lumpur');
        $today = date('Y-m-d');
        $current_time = date('H:i:s');
        $current_datetime = date('Y-m-d H:i:s');

        // AUTO-UPDATE: Change status from Pending to Completed for lessons that have passed
        $update_stmt = $conn->prepare("
            UPDATE student_lessons 
            SET status = 'Completed' 
            WHERE instructor_id = ? 
            AND status = 'Pending' 
            AND schedule_status = 'Assigned'
            AND (
                (date < ?) OR 
                (date = ? AND end_time < ?)
            )
        ");
        $update_stmt->bind_param("ssss", $instructor_id, $today, $today, $current_time);
        $update_stmt->execute();

        // Get attendance statistics
        $stats_query = "
            SELECT 
                COUNT(*) AS total_lessons,
                COUNT(*) AS completed_lessons, /* Since we're only counting completed lessons */
                0 AS pending_lessons, /* Set to zero since we're only counting completed lessons */
                SUM(CASE WHEN attendance_status = 'Attend' THEN 1 ELSE 0 END) AS attended_lessons,
                SUM(CASE WHEN attendance_status = 'Absent' THEN 1 ELSE 0 END) AS absent_lessons
            FROM student_lessons
            WHERE instructor_id = ?
            AND status = 'Completed' /* Only count completed lessons */
            AND schedule_status = 'Assigned'
        ";

        $stats_stmt = $conn->prepare($stats_query);
        $stats_stmt->bind_param("s", $instructor_id);
        $stats_stmt->execute();
        $stats_result = $stats_stmt->get_result();
        $attendance_stats = $stats_result->fetch_assoc();

        // Get monthly attendance statistics for chart
        $monthly_stats_query = "
            SELECT 
                DATE_FORMAT(date, '%Y-%m') AS month,
                COUNT(*) AS total_lessons,
                SUM(CASE WHEN attendance_status = 'Attend' THEN 1 ELSE 0 END) AS attended_lessons,
                SUM(CASE WHEN attendance_status = 'Absent' THEN 1 ELSE 0 END) AS absent_lessons
            FROM student_lessons
            WHERE instructor_id = ?
            AND status = 'Completed'
            AND schedule_status = 'Assigned'
            AND date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(date, '%Y-%m')
            ORDER BY month ASC
        ";

        $monthly_stats_stmt = $conn->prepare($monthly_stats_query);
        $monthly_stats_stmt->bind_param("s", $instructor_id);
        $monthly_stats_stmt->execute();
        $monthly_stats_result = $monthly_stats_stmt->get_result();
        $monthly_stats = $monthly_stats_result->fetch_all(MYSQLI_ASSOC);

        // Get license-wise attendance statistics
        $license_stats_query = "
            SELECT 
                l.license_name,
                COUNT(*) AS total_lessons,
                SUM(CASE WHEN sl.attendance_status = 'Attend' THEN 1 ELSE 0 END) AS attended_lessons,
                SUM(CASE WHEN sl.attendance_status = 'Absent' THEN 1 ELSE 0 END) AS absent_lessons
            FROM student_lessons sl
            INNER JOIN student_licenses slc ON sl.student_license_id = slc.student_license_id
            INNER JOIN licenses l ON slc.license_id = l.license_id
            WHERE sl.instructor_id = ?
            AND sl.status = 'Completed'
            AND sl.schedule_status = 'Assigned'
            GROUP BY l.license_name
            ORDER BY total_lessons DESC
        ";

        $license_stats_stmt = $conn->prepare($license_stats_query);
        $license_stats_stmt->bind_param("s", $instructor_id);
        $license_stats_stmt->execute();
        $license_stats_result = $license_stats_stmt->get_result();
        $license_stats = $license_stats_result->fetch_all(MYSQLI_ASSOC);

        // Load all data with prepared statements for the existing tables
        $data_queries = [
            // Today's lessons
            [
                "query" => "
                    SELECT 
                        sl.student_lesson_id, 
                        sl.student_lesson_name, 
                        sl.date, 
                        sl.start_time, 
                        sl.end_time, 
                        sl.status, 
                        sl.schedule_status,
                        sl.attendance_status,
                        l.lesson_name, 
                        lic.license_name,
                        u.name AS student_name,
                        u.phone AS student_phone,
                        s.student_id
                    FROM student_lessons sl
                    INNER JOIN student_licenses slc ON sl.student_license_id = slc.student_license_id
                    INNER JOIN lessons l ON slc.lesson_id = l.lesson_id
                    INNER JOIN licenses lic ON slc.license_id = lic.license_id
                    INNER JOIN students s ON slc.student_id = s.student_id
                    INNER JOIN users u ON s.user_id = u.user_id
                    WHERE sl.instructor_id = ?
                        AND sl.status = 'Pending'
                        AND sl.schedule_status = 'Assigned'
                        AND sl.date IS NOT NULL
                        AND sl.start_time IS NOT NULL
                        AND sl.end_time IS NOT NULL
                        AND sl.date = ?
                    ORDER BY sl.start_time ASC, sl.student_lesson_name ASC
                ",
                "params" => ["ss", $instructor_id, $today],
                "result" => "today_lessons"
            ],

            // Upcoming lessons
            [
                "query" => "
                    SELECT 
                        slc.student_license_id,
                        u.name AS student_name, 
                        lic.license_name, 
                        COUNT(*) AS upcoming_lessons,
                        (
                            SELECT COUNT(*) 
                            FROM student_lessons 
                            WHERE student_license_id = slc.student_license_id
                        ) AS total_lessons,
                        MIN(sl.date) AS next_lesson_date
                    FROM student_lessons sl
                    INNER JOIN student_licenses slc ON sl.student_license_id = slc.student_license_id
                    INNER JOIN lessons l ON slc.lesson_id = l.lesson_id
                    INNER JOIN licenses lic ON slc.license_id = lic.license_id
                    INNER JOIN students s ON slc.student_id = s.student_id
                    INNER JOIN users u ON s.user_id = u.user_id
                    WHERE sl.instructor_id = ?
                        AND sl.status = 'Pending'
                        AND sl.schedule_status = 'Assigned'
                        AND sl.date IS NOT NULL
                        AND sl.start_time IS NOT NULL
                        AND sl.end_time IS NOT NULL
                        AND sl.date > ?
                    GROUP BY slc.student_license_id, u.name, lic.license_name
                    ORDER BY next_lesson_date ASC
                ",
                "params" => ["ss", $instructor_id, $today],
                "result" => "upcoming_lessons_grouped"
            ],

            // Completed lessons
            [
                "query" => "
                    SELECT 
                        slc.student_license_id,
                        u.name AS student_name, 
                        lic.license_name, 
                        COUNT(*) AS completed_lessons,
                        SUM(CASE WHEN sl.attendance_status = 'Attend' THEN 1 ELSE 0 END) AS attended_count,
                        SUM(CASE WHEN sl.attendance_status = 'Absent' THEN 1 ELSE 0 END) AS absent_count,
                        (
                            SELECT COUNT(*) 
                            FROM student_lessons 
                            WHERE student_license_id = slc.student_license_id
                        ) AS total_lessons,
                        MAX(sl.date) AS last_lesson_date
                    FROM student_lessons sl
                    INNER JOIN student_licenses slc ON sl.student_license_id = slc.student_license_id
                    INNER JOIN lessons l ON slc.lesson_id = l.lesson_id
                    INNER JOIN licenses lic ON slc.license_id = lic.license_id
                    INNER JOIN students s ON slc.student_id = s.student_id
                    INNER JOIN users u ON s.user_id = u.user_id
                    WHERE sl.instructor_id = ?
                        AND sl.status = 'Completed'
                        AND sl.schedule_status = 'Assigned'
                    GROUP BY slc.student_license_id, u.name, lic.license_name
                    ORDER BY last_lesson_date DESC
                ",
                "params" => ["s", $instructor_id],
                "result" => "completed_lessons_grouped"
            ]
        ];

        // Execute all queries
        foreach ($data_queries as $query_data) {
            $stmt = $conn->prepare($query_data["query"]);
            $stmt->bind_param(...$query_data["params"]);
            $stmt->execute();
            $result = $stmt->get_result();
            ${$query_data["result"]} = $result->fetch_all(MYSQLI_ASSOC);
        }
    }
}
?>

<div class="container">
    <div class="page-inner">
        <!-- Breadcrumbs -->
        <!-- Breadcrumbs -->
        <div class="page-header">
            <h4 class="page-title">Manage Lesson</h4>
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
                    <a href="#">Lesson Overview</a>
                </li>
            </ul>
        </div>

        <!-- Inner page content -->
        <div class="page-category">
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <?php echo $error_message; ?>
                </div>
            <?php else: ?>
                <!-- NEW: Attendance Statistics Dashboard -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div class="card-title">Attendance Statistics Dashboard</div>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="toggle-stats-btn">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                            <div class="card-body" id="stats-card-content">
                                <!-- Overall Statistics -->
                                <div class="row mb-4">
                                    <div class="col-md-3">
                                        <div class="card card-stats card-round">
                                            <div class="card-body">
                                                <div class="row align-items-center">
                                                    <div class="col-icon">
                                                        <div class="icon-big text-center icon-primary bubble-shadow-small">
                                                            <i class="fas fa-calendar-check"></i>
                                                        </div>
                                                    </div>
                                                    <div class="col col-stats ml-3 ml-sm-0">
                                                        <div class="numbers">
                                                            <p class="card-category">Total Lessons</p>
                                                            <h4 class="card-title"><?php echo isset($attendance_stats['total_lessons']) ? $attendance_stats['total_lessons'] : 0; ?></h4>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card card-stats card-round">
                                            <div class="card-body">
                                                <div class="row align-items-center">
                                                    <div class="col-icon">
                                                        <div class="icon-big text-center icon-success bubble-shadow-small">
                                                            <i class="fas fa-user-check"></i>
                                                        </div>
                                                    </div>
                                                    <div class="col col-stats ml-3 ml-sm-0">
                                                        <div class="numbers">
                                                            <p class="card-category">Attended</p>
                                                            <h4 class="card-title"><?php echo isset($attendance_stats['attended_lessons']) ? $attendance_stats['attended_lessons'] : 0; ?></h4>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card card-stats card-round">
                                            <div class="card-body">
                                                <div class="row align-items-center">
                                                    <div class="col-icon">
                                                        <div class="icon-big text-center icon-danger bubble-shadow-small">
                                                            <i class="fas fa-user-times"></i>
                                                        </div>
                                                    </div>
                                                    <div class="col col-stats ml-3 ml-sm-0">
                                                        <div class="numbers">
                                                            <p class="card-category">Absent</p>
                                                            <h4 class="card-title"><?php echo isset($attendance_stats['absent_lessons']) ? $attendance_stats['absent_lessons'] : 0; ?></h4>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card card-stats card-round">
                                            <div class="card-body">
                                                <div class="row align-items-center">
                                                    <div class="col-icon">
                                                        <div class="icon-big text-center icon-warning bubble-shadow-small">
                                                            <i class="fas fa-percent"></i>
                                                        </div>
                                                    </div>
                                                    <div class="col col-stats ml-3 ml-sm-0">
                                                        <div class="numbers">
                                                            <p class="card-category">Attendance Rate</p>
                                                            <h4 class="card-title">
                                                                <?php
                                                                $attended = isset($attendance_stats['attended_lessons']) ? $attendance_stats['attended_lessons'] : 0;
                                                                $absent = isset($attendance_stats['absent_lessons']) ? $attendance_stats['absent_lessons'] : 0;
                                                                $completed = $attended + $absent;
                                                                echo $completed > 0 ? round(($attended / $completed) * 100) . '%' : '0%';
                                                                ?>
                                                            </h4>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Charts -->
                                <div class="row">
                                    <!-- Monthly Attendance Chart -->
                                    <div class="col-md-8">
                                        <div class="card">
                                            <div class="card-header">
                                                <div class="card-title">Monthly Attendance Trends</div>
                                            </div>
                                            <div class="card-body">
                                                <div class="chart-container">
                                                    <canvas id="monthlyAttendanceChart"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- License Type Attendance Chart -->
                                    <div class="col-md-4">
                                        <div class="card">
                                            <div class="card-header">
                                                <div class="card-title">Attendance by License Type</div>
                                            </div>
                                            <div class="card-body">
                                                <div class="chart-container">
                                                    <canvas id="licenseAttendanceChart"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Schedule Overview Card -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div class="card-title">Lesson List</div>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="toggle-card-btn">
                                    <i class="fas fa-minus"></i> <!-- Initially a minus icon -->
                                </button>
                            </div>
                            <div class="card-body" id="card-body-content">
                                <!-- Summary Cards -->
                                <div class="row mb-4">
                                    <?php
                                    $cardData = [
                                        [
                                            'target' => 'today-table',
                                            'icon' => 'fas fa-calendar-alt text-warning',
                                            'title' => 'Today\'s Lessons',
                                            'count' => count($today_lessons),
                                            'active' => true
                                        ],
                                        [
                                            'target' => 'upcoming-table',
                                            'icon' => 'fas fa-clock text-primary',
                                            'title' => 'Upcoming Lessons',
                                            'count' => count($upcoming_lessons_grouped),
                                            'active' => false
                                        ],
                                        [
                                            'target' => 'past-table',
                                            'icon' => 'fas fa-calendar-check text-success',
                                            'title' => 'Completed Lessons',
                                            'count' => count($completed_lessons_grouped),
                                            'active' => false
                                        ]
                                    ];

                                    foreach ($cardData as $card):
                                    ?>
                                        <div class="col-md-4">
                                            <div class="card card-stats card-round toggle-card <?= $card['active'] ? 'active' : '' ?>" data-target="<?= $card['target'] ?>">
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-5">
                                                            <div class="icon-big text-center">
                                                                <i class="<?= $card['icon'] ?>"></i>
                                                            </div>
                                                        </div>
                                                        <div class="col-7 col-stats">
                                                            <div class="numbers">
                                                                <p class="card-category"><?= $card['title'] ?></p>
                                                                <h4 class="card-title"><?= $card['count'] ?></h4>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Today's Lessons Table -->
                                <div class="table-container" id="today-table">
                                    <h4 class="mt-4 mb-3"><i class="fas fa-calendar-alt text-warning"></i> Today's Lessons (<?php echo date('d M Y'); ?>)</h4>
                                    <div class="table-responsive">
                                        <table id="today-lessons-table" class="table table-striped lessons-table">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Lesson</th>
                                                    <th>License</th>
                                                    <th>Time</th>
                                                    <th>Student</th>
                                                    <th>Contact</th>
                                                    <th>Attendance</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (count($today_lessons) > 0): ?>
                                                    <?php $counter = 1; ?>
                                                    <?php foreach ($today_lessons as $lesson): ?>
                                                        <tr class="table">
                                                            <td><?php echo $counter++; ?></td>
                                                            <td><?php echo htmlspecialchars($lesson['student_lesson_name'] ?? ''); ?></td>
                                                            <td><?php echo htmlspecialchars($lesson['license_name'] ?? ''); ?></td>
                                                            <td><?php echo !empty($lesson['start_time']) && !empty($lesson['end_time']) ? date('h:i A', strtotime($lesson['start_time'])) . ' - ' . date('h:i A', strtotime($lesson['end_time'])) : ''; ?></td>
                                                            <td><?php echo htmlspecialchars($lesson['student_name'] ?? ''); ?></td>
                                                            <td><?php echo htmlspecialchars($lesson['student_phone'] ?? 'N/A'); ?></td>
                                                            <td>
                                                                <span class="badge <?php echo $lesson['attendance_status'] === 'Attend' ? 'badge-success' : 'badge-danger'; ?>">
                                                                    <?php echo htmlspecialchars($lesson['attendance_status']); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <div class="btn-group">
                                                                    <button class="btn btn-warning btn-sm mark-attendance"
                                                                        data-session-id="<?php echo htmlspecialchars($lesson['student_lesson_id']); ?>"
                                                                        data-current-status="<?php echo htmlspecialchars($lesson['attendance_status']); ?>">
                                                                        <i class="fas fa-user-check"></i> Mark Attendance
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="8" class="text-center">No lessons scheduled for today.</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Upcoming Lessons Table - grouped by student -->
                                <div class="table-container" id="upcoming-table" style="display: none;">
                                    <h4 class="mt-4 mb-3"><i class="fas fa-clock text-primary"></i> Upcoming Lessons</h4>
                                    <div class="table-responsive">
                                        <table id="upcoming-lessons-table" class="table table-striped lessons-table">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Student Name</th>
                                                    <th>License</th>
                                                    <th>Next Lesson</th>
                                                    <th>Upcoming Lessons</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (count($upcoming_lessons_grouped) > 0): ?>
                                                    <?php $counter = 1; ?>
                                                    <?php foreach ($upcoming_lessons_grouped as $lesson): ?>
                                                        <tr>
                                                            <td><?php echo $counter++; ?></td>
                                                            <td><?php echo htmlspecialchars($lesson['student_name'] ?? ''); ?></td>
                                                            <td><?php echo htmlspecialchars($lesson['license_name'] ?? ''); ?></td>
                                                            <td><?php echo !empty($lesson['next_lesson_date']) ? date('d M Y', strtotime($lesson['next_lesson_date'])) : ''; ?></td>
                                                            <td><?php echo $lesson['upcoming_lessons']; ?> / <?php echo $lesson['total_lessons']; ?></td>
                                                            <td>
                                                                <a href="lesson_detail.php?student_license_id=<?php echo $lesson['student_license_id']; ?>&type=upcoming" class="btn btn-sm btn-primary">
                                                                    <i class="fas fa-eye"></i> View
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="6" class="text-center">No upcoming lessons found.</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Past Lessons Table - grouped by student -->
                                <div class="table-container" id="past-table" style="display: none;">
                                    <h4 class="mt-4 mb-3"><i class="fas fa-calendar-check text-success"></i> Completed Lessons</h4>
                                    <div class="table-responsive">
                                        <table id="past-lessons-table" class="table table-striped lessons-table">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Student Name</th>
                                                    <th>License</th>
                                                    <th>Last Lesson</th>
                                                    <th>Completed Lessons</th>
                                                    <th>Attendance Summary</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (count($completed_lessons_grouped) > 0): ?>
                                                    <?php $counter = 1; ?>
                                                    <?php foreach ($completed_lessons_grouped as $lesson): ?>
                                                        <tr>
                                                            <td><?php echo $counter++; ?></td>
                                                            <td><?php echo htmlspecialchars($lesson['student_name'] ?? ''); ?></td>
                                                            <td><?php echo htmlspecialchars($lesson['license_name'] ?? ''); ?></td>
                                                            <td><?php echo !empty($lesson['last_lesson_date']) ? date('d M Y', strtotime($lesson['last_lesson_date'])) : ''; ?></td>
                                                            <td><?php echo $lesson['completed_lessons']; ?> / <?php echo $lesson['total_lessons']; ?></td>
                                                            <td>
                                                                <span class="badge badge-success">Attended: <?php echo $lesson['attended_count']; ?></span>
                                                                <span class="badge badge-danger">Absent: <?php echo $lesson['absent_count']; ?></span>
                                                            </td>
                                                            <td>
                                                                <a href="lesson_detail.php?student_license_id=<?php echo $lesson['student_license_id']; ?>&type=past" class="btn btn-sm btn-primary">
                                                                    <i class="fas fa-eye"></i> View
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="7" class="text-center">No completed lessons found.</td>
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
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../../include/footer.html'; ?>

<!-- Include SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    $(document).ready(function() {
        // Consolidated DataTable initialization with common options
        $('.lessons-table').each(function() {
            $(this).DataTable();
        });
    });

    $(document).ready(function() {
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

        // Toggle stats card content visibility
        $('#toggle-stats-btn').click(function() {
            var statsContent = $('#stats-card-content');

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
        // Card tab functionality
        $('.toggle-card').on('click', function() {
            $('.toggle-card').removeClass('active');
            $(this).addClass('active');

            // Hide all tables and show the selected one
            $('.table-container').hide();
            $('#' + $(this).data('target')).show();
        });
    });

    $(document).ready(function() {
        // Enhanced hover effect
        $('.toggle-card').hover(
            function() {
                if (!$(this).hasClass('active')) {
                    $(this).addClass('shadow-sm');
                }
            },
            function() {
                $(this).removeClass('shadow-sm');
            }
        );

        // Initialize Charts
        initializeCharts();
    });

    // Initialize Charts function
    function initializeCharts() {
        // Monthly Attendance Chart
        const monthlyCtx = document.getElementById('monthlyAttendanceChart').getContext('2d');

        // Prepare data for monthly chart
        let monthlyData = <?php echo json_encode($monthly_stats); ?>;
        let months, attendedCounts, absentCounts;

        // Check if monthly data is empty
        if (monthlyData.length === 0) {
            // Create 6 months of empty data
            const currentDate = new Date();
            monthlyData = [];
            months = [];
            attendedCounts = [];
            absentCounts = [];

            for (let i = 5; i >= 0; i--) {
                const date = new Date(currentDate);
                date.setMonth(currentDate.getMonth() - i);
                const monthYear = date.toLocaleString('default', {
                    month: 'short',
                    year: '2-digit'
                });

                months.push(monthYear);
                attendedCounts.push(0);
                absentCounts.push(0);
            }
        } else {
            months = monthlyData.map(item => {
                const [year, month] = item.month.split('-');
                const date = new Date(year, month - 1);
                return date.toLocaleString('default', {
                    month: 'short',
                    year: '2-digit'
                });
            });
            attendedCounts = monthlyData.map(item => item.attended_lessons);
            absentCounts = monthlyData.map(item => item.absent_lessons);
        }

        // Custom message plugin for empty data
        const noMonthlyDataPlugin = {
            id: 'noMonthlyData',
            afterDraw(chart) {
                const datasets = chart.data.datasets;
                const allZero = datasets.every(dataset =>
                    dataset.data.every(value => value === 0)
                );

                if (allZero) {
                    const ctx = chart.ctx;
                    const width = chart.width;
                    const height = chart.height;

                    ctx.save();
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.font = '16px Arial';
                    ctx.fillStyle = '#666';
                    ctx.fillText('No attendance data for the last 6 months', width / 2, height / 2);
                    ctx.restore();
                }
            }
        };

        new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: months,
                datasets: [{
                        label: 'Attended',
                        data: attendedCounts,
                        backgroundColor: 'rgba(40, 167, 69, 0.6)',
                        borderColor: 'rgba(40, 167, 69, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Absent',
                        data: absentCounts,
                        backgroundColor: 'rgba(220, 53, 69, 0.6)',
                        borderColor: 'rgba(220, 53, 69, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Monthly Attendance Trends (Last 6 Months)',
                        font: {
                            size: 16
                        }
                    },
                    legend: {
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            afterBody: function(context) {
                                const index = context[0].dataIndex;
                                const total = attendedCounts[index] + absentCounts[index];
                                const attendanceRate = total > 0 ? Math.round((attendedCounts[index] / total) * 100) : 0;
                                return `Attendance Rate: ${attendanceRate}%`;
                            }
                        }
                    }
                }
            },
            plugins: [noMonthlyDataPlugin]
        });

        // License Type Attendance Chart
        const licenseCtx = document.getElementById('licenseAttendanceChart').getContext('2d');

        // Prepare data for license chart
        let licenseData = <?php echo json_encode($license_stats); ?>;
        let licenseNames, licenseAttendance;

        // Check if license data is empty
        if (licenseData.length === 0) {
            // Add fallback data
            licenseData = [{
                license_name: "No Data Available",
                total_lessons: 0,
                attended_lessons: 0,
                absent_lessons: 0
            }];
            licenseNames = ["No Data Available"];
            licenseAttendance = [0];
        } else {
            licenseNames = licenseData.map(item => item.license_name);
            licenseAttendance = licenseData.map(item => {
                const total = parseInt(item.total_lessons);
                const attended = parseInt(item.attended_lessons);
                return total > 0 ? Math.round((attended / total) * 100) : 0;
            });
        }

        // Colors for pie chart
        const backgroundColors = [
            'rgba(54, 162, 235, 0.7)',
            'rgba(255, 99, 132, 0.7)',
            'rgba(255, 206, 86, 0.7)',
            'rgba(75, 192, 192, 0.7)',
            'rgba(153, 102, 255, 0.7)',
            'rgba(255, 159, 64, 0.7)',
            'rgba(199, 199, 199, 0.7)'
        ];

        // Custom message plugin for empty meaningful data
        const noDataPlugin = {
            id: 'noData',
            afterDraw(chart) {
                if (licenseData[0].license_name === "No Data Available") {
                    const ctx = chart.ctx;
                    const width = chart.width;
                    const height = chart.height;

                    chart.clear();

                    ctx.save();
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.font = '16px Arial';
                    ctx.fillStyle = '#666';
                    ctx.fillText('No attendance data available yet', width / 2, height / 2);
                    ctx.restore();
                }
            }
        };

        new Chart(licenseCtx, {
            type: 'pie',
            data: {
                labels: licenseNames,
                datasets: [{
                    data: licenseAttendance,
                    backgroundColor: backgroundColors.slice(0, licenseNames.length)
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Attendance Rate by License Type (%)',
                        font: {
                            size: 16
                        }
                    },
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 15
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                return `${label}: ${value}%`;
                            },
                            afterLabel: function(context) {
                                const index = context.dataIndex;
                                const license = licenseData[index];
                                return license.license_name !== "No Data Available" ?
                                    `${license.attended_lessons} attended out of ${license.total_lessons} lessons` : '';
                            }
                        }
                    }
                }
            },
            plugins: [noDataPlugin]
        });
    }

    // Use event delegation for attendance marking
    $(document).on('click', '.mark-attendance', function() {
        const sessionId = $(this).data('session-id');
        const currentStatus = $(this).data('current-status');

        // Show custom confirmation popup
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
            let newStatus = currentStatus; // Default to current

            if (result.isConfirmed) {
                newStatus = 'Attend';
            } else if (result.isDenied) {
                newStatus = 'Absent';
            }

            // Only proceed if status changed
            if (newStatus !== currentStatus) {
                updateAttendanceStatus(sessionId, newStatus);
            }
        });
    });

    // Separate function for attendance update
    function updateAttendanceStatus(sessionId, newStatus) {
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
                    Swal.fire('Success', 'Attendance updated successfully!', 'success')
                        .then(() => location.reload());
                } else {
                    Swal.fire('Error', 'Failed to update attendance.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'An error occurred while updating attendance.', 'error');
            });
    }
</script>

<style>
    /* Consolidated CSS with better organization */
    .toggle-card {
        cursor: pointer;
    }

    .toggle-card.active {
        border-bottom: 3px solid #1572E8;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    .toggle-card:hover:not(.active) {
        transform: translateY(-5px);
    }

    .table-container,
    .card-body {
        transition: all 0.3s ease;
    }

    /* Chart containers */
    .chart-container {
        position: relative;
        height: 300px;
        width: 100%;
    }

    /* Stat card styling */
    .card-stats .numbers .card-category {
        margin-top: 5px;
        font-size: 14px;
    }

    .card-stats .icon-big {
        font-size: 3em;
        min-height: 64px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .bubble-shadow-small {
        position: relative;
        display: inline-block;
        border-radius: 50%;
        width: 60px;
        height: 60px;
    }

    /* Responsive improvements */
    @media (max-width: 768px) {
        .badge {
            display: block;
            margin-bottom: 3px;
        }

        .chart-container {
            height: 250px;
        }
    }

    @media (max-width: 576px) {
        .chart-container {
            height: 200px;
        }
    }
</style>