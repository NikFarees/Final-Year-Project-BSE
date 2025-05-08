<?php
include '../../../include/ad_header.php';
include '../../../database/db_connection.php';

// Fetch student_id from URL parameter
if (!isset($_GET['id'])) {
    die("Student ID is not set.");
}
$student_id = $_GET['id'];

// Fetch student details
$studentDetailsQuery = "
    SELECT 
        s.student_id, 
        u.name 
    FROM 
        students AS s
    JOIN 
        users AS u ON s.user_id = u.user_id 
    WHERE 
        s.student_id = ?
";
$studentDetailsStmt = $conn->prepare($studentDetailsQuery);
$studentDetailsStmt->bind_param("s", $student_id);
$studentDetailsStmt->execute();
$studentDetailsResult = $studentDetailsStmt->get_result();
$student = $studentDetailsResult->fetch_assoc();

// Check if student details were found
if (!$student) {
    die("Student not found.");
}

// Get all active student licenses
$stmt = $conn->prepare("
    SELECT sl.student_license_id, sl.license_id, l.license_name, l.license_type, 
           les.lesson_name, les.lesson_id
    FROM student_licenses sl
    JOIN licenses l ON sl.license_id = l.license_id
    JOIN lessons les ON sl.lesson_id = les.lesson_id
    WHERE sl.student_id = ?
    ORDER BY sl.student_license_id ASC
");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$licenses = $result->fetch_all(MYSQLI_ASSOC);
$totalLicenses = count($licenses);

// Store all license data
$licenseData = [];

// If the student has at least one license
if ($totalLicenses > 0) {
    // Process each license
    foreach ($licenses as $index => $license) {
        $student_license_id = $license['student_license_id'];
        $currentData = [];
        $currentData['license'] = $license;
        
        // Get license status from issued_licenses table
        $stmt = $conn->prepare("
            SELECT status 
            FROM issued_licenses 
            WHERE student_license_id = ?
        ");
        $stmt->bind_param("s", $student_license_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $licenseStatusData = $result->fetch_assoc();
        $currentData['license_status'] = $licenseStatusData ? $licenseStatusData['status'] : 'Not Available';
        
        // Get lessons for this license
        $stmt = $conn->prepare("
            SELECT sl.*, u.name as instructor_name 
            FROM student_lessons sl
            LEFT JOIN instructors i ON sl.instructor_id = i.instructor_id
            LEFT JOIN users u ON i.user_id = u.user_id
            WHERE sl.student_license_id = ?
            ORDER BY 
                CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(sl.student_lesson_name, ' ', -1), '/', 1) AS UNSIGNED) ASC
        ");
         
        $stmt->bind_param("s", $student_license_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $currentData['lessons'] = $result->fetch_all(MYSQLI_ASSOC);

        // Get tests for this license - MODIFIED to group by test_id
        $stmt = $conn->prepare("
            SELECT st.*, t.test_name, t.test_id, sts.test_date, sts.start_time, sts.end_time, 
                   u.name as instructor_name,
                   stss.attendance_status
            FROM student_tests st
            JOIN tests t ON st.test_id = t.test_id
            LEFT JOIN student_test_sessions stss ON st.student_test_id = stss.student_test_id
            LEFT JOIN test_sessions sts ON stss.test_session_id = sts.test_session_id
            LEFT JOIN instructors i ON sts.instructor_id = i.instructor_id
            LEFT JOIN users u ON i.user_id = u.user_id
            WHERE st.student_license_id = ?
            ORDER BY t.test_id, st.student_test_id DESC
        ");
        $stmt->bind_param("s", $student_license_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Restructure tests to group them by test_id
        $groupedTests = [];
        while ($test = $result->fetch_assoc()) {
            if (!isset($groupedTests[$test['test_id']])) {
                $groupedTests[$test['test_id']] = [
                    'test_name' => $test['test_name'],
                    'attempts' => []
                ];
            }
            $groupedTests[$test['test_id']]['attempts'][] = $test;
        }
        $currentData['tests'] = $groupedTests;

        // Calculate progress dynamically
        // Define test types for progress tracking
        $test_types = ['Computer Test', 'QTI Test', 'Circuit Test', 'On-Road Test'];
        $test_completed = array_fill_keys($test_types, false);

        // Determine minimum required lessons based on lesson_id
        $minimum_required_lessons = 4; // Default
        $lesson_id = $license['lesson_id'];
        if ($lesson_id == 'LES01' || $lesson_id == 'LES02') {
            $minimum_required_lessons = 4;
        } else if ($lesson_id == 'LES03' || $lesson_id == 'LES04') {
            $minimum_required_lessons = 6;
        }

        // Determine total lessons based on lesson_id (4 for LES01/LES02, 8 for LES03/LES04)
        $total_lessons = 4; // Default
        if ($lesson_id == 'LES03' || $lesson_id == 'LES04') {
            $total_lessons = 8;
        }

        // Count all regular lessons (excluding extra classes)
        $regularLessonCount = 0;
        foreach ($currentData['lessons'] as $lesson) {
            if (strpos($lesson['student_lesson_name'], 'Extra Class') === false) {
                $regularLessonCount++;
            }
        }

        // Count completed lessons (only regular lessons, excluding extra classes)
        $completed_lessons = 0;
        foreach ($currentData['lessons'] as $lesson) {
            if ($lesson['status'] === 'Completed' && strpos($lesson['student_lesson_name'], 'Extra Class') === false) {
                $completed_lessons++;
            }
        }

        // Count attended lessons (only regular lessons, excluding extra classes)
        $attended_lessons = 0;
        foreach ($currentData['lessons'] as $lesson) {
            if ($lesson['attendance_status'] === 'Attend' && strpos($lesson['student_lesson_name'], 'Extra Class') === false) {
                $attended_lessons++;
            }
        }

        // Check if attendance requirement is met
        $attendance_requirement_met = ($attended_lessons >= $minimum_required_lessons);

        // Calculate lesson completion percentage (based on completed/total regular lessons)
        $lesson_completion_percentage = ($regularLessonCount > 0) ? 
            min(100, ($completed_lessons / $regularLessonCount) * 100) : 0;

        // Count completed tests
        $completed_tests = 0;
        foreach ($groupedTests as $test_id => $testData) {
            $latestAttempt = !empty($testData['attempts']) ? $testData['attempts'][0] : null;
            if ($latestAttempt) {
                foreach ($test_types as $test_type) {
                    if (strpos($testData['test_name'], $test_type) !== false && $latestAttempt['status'] === 'Passed') {
                        $test_completed[$test_type] = true;
                        $completed_tests++;
                        break;
                    }
                }
            }
        }

        // Total milestones = 4 tests + regular lessons
        $total_milestones = 4 + $regularLessonCount;

        // Completed milestones = passed tests + completed lessons
        $completed_milestones = $completed_tests + $completed_lessons;

        // Calculate progress percentage
        $progress_percentage = ($total_milestones > 0) ?
            round(($completed_milestones / $total_milestones) * 100, 2) : 0;

        // Store calculated values
        $currentData['test_completed'] = $test_completed;
        $currentData['total_lessons'] = $total_lessons;
        $currentData['completed_lessons'] = $completed_lessons;
        $currentData['attended_lessons'] = $attended_lessons;
        $currentData['attendance_requirement_met'] = $attendance_requirement_met;
        $currentData['completed_tests'] = $completed_tests;
        $currentData['total_milestones'] = $total_milestones;
        $currentData['completed_milestones'] = $completed_milestones;
        $currentData['progress_percentage'] = $progress_percentage;
        $currentData['minimum_required_lessons'] = $minimum_required_lessons;
        $currentData['lesson_completion_percentage'] = $lesson_completion_percentage;
        $currentData['regular_lesson_count'] = $regularLessonCount;
        
        // Add to license data array
        $licenseData[] = $currentData;
    }
}
?>

<div class="container">
    <div class="page-inner">
        <!-- Breadcrumbs -->
        <div class="page-header">
            <h4 class="page-title">Manage Schedule</h4>
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
                    <a href="/pages/admin/manage_schedule/list_users.php">User List</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Student Schedule</a>
                </li>
            </ul>
        </div>

        <!-- Inner page content -->
        <div class="page-category">
            <!-- Student Info Header -->
            <div class="row mb-3">
                <div class="col-md-12">
                    <div class="alert alert-info">
                        <h5 class="mb-0">
                            <i class="fas fa-user-graduate mr-2"></i>
                            Student: <strong><?php echo $student['student_id'] . ' - ' . $student['name']; ?></strong>
                        </h5>
                    </div>
                </div>
            </div>

            <?php if (!empty($licenseData)): ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title">Student Schedule</h4>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="progress-toggle-btn">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                            <div class="card-body" id="progress-card-body">
                                <?php if ($totalLicenses > 1): ?>
                                    <!-- License Selection Cards -->
                                    <div class="row mb-4">
                                        <?php foreach ($licenseData as $index => $data): ?>
                                            <div class="col-md-6">
                                                <div class="card card-stats card-round license-toggle-card <?php echo ($index === 0) ? 'active' : ''; ?>" data-target="license-container-<?php echo $index; ?>">
                                                    <div class="card-body">
                                                        <div class="row">
                                                            <div class="col-5">
                                                                <div class="icon-big text-center">
                                                                    <i class="fas fa-id-card text-primary"></i>
                                                                </div>
                                                            </div>
                                                            <div class="col-7 col-stats">
                                                                <div class="numbers">
                                                                    <p class="card-category"><?php echo $data['license']['license_type']; ?> License</p>
                                                                    <h4 class="card-title"><?php echo $data['license']['license_name']; ?></h4>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- License Content Containers -->
                                <?php foreach ($licenseData as $index => $data): ?>
                                    <div class="license-container" id="license-container-<?php echo $index; ?>" <?php echo ($index !== 0) ? 'style="display: none;"' : ''; ?>>
                                        <div class="card">
                                            <div class="card-header">
                                                <div class="card-title">
                                                    <?php echo $data['license']['license_type'] . ' - ' . $data['license']['license_name']; ?> License Progress
                                                </div>
                                                <div class="card-category">
                                                    Student's journey toward obtaining a driver's license
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <div class="progress-card mb-4">
                                                    <div class="d-flex justify-content-between mb-1">
                                                        <span class="text-muted">Overall Progress</span>
                                                        <span class="text-muted font-weight-bold"> <?php echo $data['progress_percentage']; ?>%</span>
                                                    </div>
                                                    <div class="progress mb-2" style="height: 7px;">
                                                        <div class="progress-bar bg-success" role="progressbar"
                                                            style="width: <?php echo $data['progress_percentage']; ?>%"
                                                            aria-valuenow="<?php echo $data['progress_percentage']; ?>"
                                                            aria-valuemin="0" aria-valuemax="100"
                                                            data-toggle="tooltip" data-placement="top"
                                                            title="" data-original-title="<?php echo $data['progress_percentage']; ?>%">
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Timeline -->
                                                <div class="timeline-container">
                                                    <div class="timeline">
                                                        <?php
                                                        // Get status class functions
                                                        $getStatusClass = function ($status) {
                                                            switch ($status) {
                                                                case 'Passed':
                                                                    return 'success';
                                                                case 'Failed':
                                                                    return 'danger';
                                                                case 'Pending':
                                                                    return 'warning';
                                                                case 'Ineligible':
                                                                case 'Unassigned':
                                                                    return 'secondary';
                                                                default:
                                                                    return 'info';
                                                            }
                                                        };

                                                        $getLessonStatusClass = function ($status) {
                                                            switch ($status) {
                                                                case 'Completed':
                                                                    return 'success';
                                                                case 'Pending':
                                                                    return 'warning';
                                                                case 'Ineligible':
                                                                case 'Unassigned':
                                                                    return 'secondary';
                                                                default:
                                                                    return 'info';
                                                            }
                                                        };

                                                        $getAttendanceClass = function ($status) {
                                                            switch ($status) {
                                                                case 'Attend':
                                                                    return 'success';
                                                                case 'Absent':
                                                                    return 'danger';
                                                                default:
                                                                    return 'secondary';
                                                            }
                                                        };

                                                        // Format test date
                                                        $formatDate = function ($date, $start_time, $end_time) {
                                                            if (!$date) return 'Not Scheduled';
                                                            $formatted_date = date('d M Y', strtotime($date));
                                                            $formatted_start = date('h:i A', strtotime($start_time));
                                                            $formatted_end = date('h:i A', strtotime($end_time));
                                                            return "$formatted_date, $formatted_start - $formatted_end";
                                                        };

                                                        // Function to display test info
                                                        $displayTestInfo = function ($test, $formatDate, $getStatusClass, $getAttendanceClass) {
                                                            $html = '';
                                                            $html .= '<p><span class="badge badge-' . $getStatusClass($test['status']) . '">' . $test['status'] . '</span>';
                                                            if (isset($test['score']) && $test['score'] !== null) {
                                                                $html .= ' <span class="badge badge-info ml-2">Score: ' . $test['score'] . '</span>';
                                                            }
                                                            $html .= '</p>';

                                                            $html .= '<p class="text-muted"><i class="far fa-calendar-alt"></i> ' .
                                                                $formatDate($test['test_date'] ?? null, $test['start_time'] ?? null, $test['end_time'] ?? null) . '</p>';

                                                            if (isset($test['attendance_status'])) {
                                                                $html .= '<p class="text-muted"><i class="fas fa-clipboard-check"></i> Attendance: ';
                                                                $html .= '<span class="badge badge-' . $getAttendanceClass($test['attendance_status']) . '">' .
                                                                    $test['attendance_status'] . '</span></p>';
                                                            }

                                                            if (isset($test['instructor_name']) && $test['instructor_name']) {
                                                                $html .= '<p class="text-muted"><i class="fas fa-user"></i> Instructor: ' . $test['instructor_name'] . '</p>';
                                                            }

                                                            if (isset($test['comment']) && !empty($test['comment'])) {
                                                                $html .= '<p class="text-muted"><i class="fas fa-comment"></i> Comment: ' . $test['comment'] . '</p>';
                                                            }

                                                            return $html;
                                                        };

                                                        // Display items in timeline with the correct sequence
                                                        $journey_sequence = [
                                                            'Computer Test',
                                                            'Driving Lessons',
                                                            'QTI Test',
                                                            'Circuit Test',
                                                            'On-Road Test',
                                                            'License Certificate'
                                                        ];

                                                        // Function to get the correct test by type from grouped tests
                                                        $getTestByType = function ($groupedTests, $testType) {
                                                            foreach ($groupedTests as $test_id => $testData) {
                                                                if (strpos($testData['test_name'], $testType) !== false) {
                                                                    return $testData;
                                                                }
                                                            }
                                                            return null;
                                                        };

                                                        // Loop through journey sequence to maintain order
                                                        foreach ($journey_sequence as $step) {
                                                            // Handle each step based on what it is
                                                            if ($step === 'Driving Lessons') {
                                                                // Insert the Driving Lessons section here
                                                                // Count total lessons
                                                                $lesson_parts = explode(' ', $data['license']['lesson_name']);
                                                                $total_lessons = intval($lesson_parts[0]);

                                                                // Group lessons for display
                                                                echo '<div class="timeline-item">';
                                                                echo '<div class="timeline-badge bg-info"><i class="fas fa-car"></i></div>';
                                                                echo '<div class="timeline-panel">';
                                                                echo '<div class="timeline-heading">';
                                                                echo '<h4 class="timeline-title">Driving Lessons</h4>';
                                                                echo '<p><small class="text-muted"><i class="fas fa-tag"></i> ' . $data['license']['lesson_name'] . '</small></p>';
                                                                echo '</div>';
                                                                echo '<div class="timeline-body">';

                                                                // Show lesson completion progress as a percentage
                                                                echo '<div class="progress-card mb-3">';
                                                                echo '<div class="d-flex justify-content-between mb-1">';
                                                                echo '<span class="text-muted">Lessons Completion</span>';
                                                                echo '<span class="text-muted font-weight-bold">' . round($data['lesson_completion_percentage'], 1) . '%</span>';
                                                                echo '</div>';
                                                                echo '<div class="progress mb-2" style="height: 5px;">';
                                                                echo '<div class="progress-bar bg-info" role="progressbar" style="width: ' . $data['lesson_completion_percentage'] . '%" aria-valuenow="' . $data['lesson_completion_percentage'] . '" aria-valuemin="0" aria-valuemax="100"></div>';
                                                                echo '</div>';

                                                                // Show attendance requirements status
                                                                echo '<div class="d-flex justify-content-between align-items-center">';
                                                                echo '<small class="text-muted">' . $data['completed_lessons'] . ' of ' . $data['regular_lesson_count'] . ' lessons completed</small>';

                                                                // Display attendance status with badge
                                                                if ($data['attendance_requirement_met']) {
                                                                    echo '<span class="badge badge-success">Attendance Requirement Met</span>';
                                                                } else {
                                                                    echo '<span class="badge badge-warning">Need ' . ($data['minimum_required_lessons'] - $data['attended_lessons']) . ' more attended lessons</span>';
                                                                }
                                                                echo '</div>';

                                                                // Show attendance details
                                                                echo '<small class="text-muted d-block mt-1">' . $data['attended_lessons'] . ' of ' . $data['minimum_required_lessons'] . ' minimum required attendances</small>';
                                                                echo '</div>';

                                                                // Display individual lessons in collapsible
                                                                echo '<div class="accordion">';
                                                                echo '<div class="card">';
                                                                echo '<div class="card-header collapsed" id="lessonHeading-' . $index . '" data-toggle="collapse" data-target="#lessonCollapse-' . $index . '" aria-expanded="false" aria-controls="lessonCollapse-' . $index . '" style="cursor: pointer;">';
                                                                echo '<h5 class="mb-0">View All Lessons <i class="fas fa-chevron-down float-right"></i></h5>';
                                                                echo '</div>';
                                                                echo '<div id="lessonCollapse-' . $index . '" class="collapse" aria-labelledby="lessonHeading-' . $index . '">';
                                                                echo '<div class="card-body p-0">';
                                                                echo '<div class="table-responsive">';
                                                                echo '<table class="table table-striped">';
                                                                echo '<thead><tr><th>Lesson</th><th>Date & Time</th><th>Status</th><th>Attendance</th><th>Instructor</th></tr></thead>';
                                                                echo '<tbody>';

                                                                // Before entering the loop, separate regular and extra lessons
                                                                $regularLessons = [];
                                                                $extraLessons = [];
                                                                $extraLessonsCount = 0;

                                                                foreach ($data['lessons'] as $lessonItem) {
                                                                    if (strpos($lessonItem['student_lesson_name'], 'Extra Class') !== false) {
                                                                        $extraLessons[] = $lessonItem;
                                                                        $extraLessonsCount++;
                                                                    } else {
                                                                        $regularLessons[] = $lessonItem;
                                                                    }
                                                                }

                                                                // Now display regular lessons first
                                                                $lesson_count = 1;
                                                                foreach ($regularLessons as $lesson) {
                                                                    echo '<tr>';
                                                                    echo '<td>Lesson ' . $lesson_count . '/' . $data['total_lessons'] . '</td>';
                                                                    echo '<td>' . $formatDate($lesson['date'], $lesson['start_time'], $lesson['end_time']) . '</td>';
                                                                    echo '<td><span class="badge badge-' . $getLessonStatusClass($lesson['status']) . '">' . $lesson['status'] . '</span></td>';
                                                                    echo '<td><span class="badge badge-' . $getAttendanceClass($lesson['attendance_status']) . '">' . $lesson['attendance_status'] . '</span></td>';
                                                                    echo '<td>' . ($lesson['instructor_name'] ?? 'Not Assigned') . '</td>';
                                                                    echo '</tr>';
                                                                    $lesson_count++;
                                                                }

                                                                // Then display extra lessons
                                                                foreach ($extraLessons as $lesson) {
                                                                    // Extract extra lesson number
                                                                    preg_match('/Extra Class (\d+)/', $lesson['student_lesson_name'], $matches);
                                                                    $extraLessonNumber = isset($matches[1]) ? $matches[1] : '?';

                                                                    echo '<tr>';
                                                                    echo '<td>Extra Lesson ' . $extraLessonNumber . '/' . $extraLessonsCount . '</td>';
                                                                    echo '<td>' . $formatDate($lesson['date'], $lesson['start_time'], $lesson['end_time']) . '</td>';
                                                                    echo '<td><span class="badge badge-' . $getLessonStatusClass($lesson['status']) . '">' . $lesson['status'] . '</span></td>';
                                                                    echo '<td><span class="badge badge-' . $getAttendanceClass($lesson['attendance_status']) . '">' . $lesson['attendance_status'] . '</span></td>';
                                                                    echo '<td>' . ($lesson['instructor_name'] ?? 'Not Assigned') . '</td>';
                                                                    echo '</tr>';
                                                                }

                                                                echo '</tbody></table>';
                                                                echo '</div></div></div></div></div>';
                                                                echo '</div></div></div>';
                                                            } else if ($step === 'License Certificate') {
                                                                // Get license status
                                                                $licenseStatus = $data['license_status'];
                                                                
                                                                // Display License Certificate with status-specific content
                                                                echo '<div class="timeline-item">';
                                                                echo '<div class="timeline-badge ';
                                                                
                                                                // Set badge color based on status
                                                                switch ($licenseStatus) {
                                                                    case 'Approved':
                                                                        echo 'bg-success';
                                                                        break;
                                                                    case 'Pending':
                                                                        echo 'bg-warning';
                                                                        break;
                                                                    case 'Issued':
                                                                        echo 'bg-primary';
                                                                        break;
                                                                    default:
                                                                        echo 'bg-secondary';
                                                                }
                                                                
                                                                echo '">';
                                                                echo '<i class="fas fa-certificate"></i>';
                                                                echo '</div>';
                                                                echo '<div class="timeline-panel">';
                                                                echo '<div class="timeline-heading">';
                                                                echo '<h4 class="timeline-title">License Certificate</h4>';
                                                                echo '<p><small class="text-muted"><i class="fas fa-tag"></i> Certification</small></p>';
                                                                echo '</div>';
                                                                echo '<div class="timeline-body">';
                                                                
                                                                // Set message based on license status
                                                                echo '<p><span class="badge badge-';
                                                                
                                                                switch ($licenseStatus) {
                                                                    case 'Approved':
                                                                        echo 'success">Ready for Collection</span></p>';
                                                                        echo '<p class="text-muted">License has been approved and is ready for collection at the school.</p>';
                                                                        break;
                                                                    case 'Pending':
                                                                        echo 'warning">Processing</span></p>';
                                                                        echo '<p class="text-muted">License is currently being processed by administrators. Waiting for approval.</p>';
                                                                        break;
                                                                    case 'Issued':
                                                                        echo 'primary">Collected</span></p>';
                                                                        echo '<p class="text-muted">Student has successfully collected their license.</p>';
                                                                        break;
                                                                    default:
                                                                        echo 'secondary">Not Available</span></p>';
                                                                        echo '<p class="text-muted">Student must complete all tests and lessons to receive license certificate.</p>';
                                                                }
                                                                
                                                                echo '</div>';
                                                                echo '</div>';
                                                                echo '</div>';
                                                            } else {
                                                                // This is a test type
                                                                $testData = $getTestByType($data['tests'], $step);
                                                                
                                                                if (!$testData) {
                                                                    // Skip if no test of this type
                                                                    continue;
                                                                }
                                                                
                                                                $attempts = $testData['attempts'];
                                                                $latestAttempt = !empty($attempts) ? $attempts[0] : null;
                                                                $testName = $testData['test_name'];
                                                                $hasMultipleAttempts = count($attempts) > 1;
                                                                $testStatus = $latestAttempt ? $latestAttempt['status'] : 'Not Scheduled';
                                                                
                                                                // Determine icon based on test type
                                                                $icon_class = 'fas fa-clipboard-check';
                                                                if (strpos($testName, 'Computer') !== false) {
                                                                    $icon_class = 'fas fa-desktop';
                                                                } else if (strpos($testName, 'QTI') !== false) {
                                                                    $icon_class = 'fas fa-book';
                                                                } else if (strpos($testName, 'Circuit') !== false) {
                                                                    $icon_class = 'fas fa-road';
                                                                } else if (strpos($testName, 'On-Road') !== false) {
                                                                    $icon_class = 'fas fa-car-side';
                                                                }
                                                        ?>
                                                                
                                                                <div class="timeline-item">
                                                                    <div class="timeline-badge bg-<?php echo $latestAttempt ? $getStatusClass($testStatus) : 'secondary'; ?>">
                                                                        <i class="<?php echo $icon_class; ?>"></i>
                                                                    </div>
                                                                    <div class="timeline-panel">
                                                                        <div class="timeline-heading">
                                                                            <h4 class="timeline-title"><?php echo $testName; ?></h4>
                                                                            <p>
                                                                                <small class="text-muted"><i class="fas fa-tag"></i> Test</small>
                                                                                <?php if ($hasMultipleAttempts): ?>
                                                                                    <span class="badge badge-info ml-2"><?php echo count($attempts); ?> attempts</span>
                                                                                <?php endif; ?>
                                                                            </p>
                                                                        </div>
                                                                        <div class="timeline-body">
                                                                            <?php if (!$latestAttempt): ?>
                                                                                <p><span class="badge badge-secondary">Not Scheduled</span></p>
                                                                            <?php elseif (!$hasMultipleAttempts): ?>
                                                                                <!-- Single attempt - show directly -->
                                                                                <?php echo $displayTestInfo($latestAttempt, $formatDate, $getStatusClass, $getAttendanceClass); ?>
                                                                            <?php else: ?>
                                                                                <!-- Multiple attempts - show latest and add dropdown -->
                                                                                <p><strong>Latest Attempt:</strong></p>
                                                                                <?php echo $displayTestInfo($latestAttempt, $formatDate, $getStatusClass, $getAttendanceClass); ?>
                                                                                
                                                                                <div class="accordion mt-2">
                                                                                    <div class="card">
                                                                                        <div class="card-header collapsed" id="testHistoryHeading-<?php echo $index; ?>-<?php echo str_replace(' ', '', $step); ?>" data-toggle="collapse" data-target="#testHistoryCollapse-<?php echo $index; ?>-<?php echo str_replace(' ', '', $step); ?>" aria-expanded="false" aria-controls="testHistoryCollapse-<?php echo $index; ?>-<?php echo str_replace(' ', '', $step); ?>" style="cursor: pointer;">
                                                                                            <h5 class="mb-0">View Test History <i class="fas fa-chevron-down float-right"></i></h5>
                                                                                        </div>
                                                                                        <div id="testHistoryCollapse-<?php echo $index; ?>-<?php echo str_replace(' ', '', $step); ?>" class="collapse" aria-labelledby="testHistoryHeading-<?php echo $index; ?>-<?php echo str_replace(' ', '', $step); ?>">
                                                                                            <div class="card-body p-0">
                                                                                                <div class="table-responsive">
                                                                                                    <table class="table table-striped">
                                                                                                        <thead>
                                                                                                            <tr>
                                                                                                                <th>Attempt</th>
                                                                                                                <th>Date & Time</th>
                                                                                                                <th>Status</th>
                                                                                                                <th>Score</th>
                                                                                                                <th>Attendance</th>
                                                                                                                <th>Instructor</th>
                                                                                                            </tr>
                                                                                                        </thead>
                                                                                                        <tbody>
                                                                                                            <?php foreach ($attempts as $idx => $attempt): ?>
                                                                                                                <tr>
                                                                                                                    <td>Attempt <?php echo count($attempts) - $idx; ?></td>
                                                                                                                    <td><?php echo $formatDate($attempt['test_date'] ?? null, $attempt['start_time'] ?? null, $attempt['end_time'] ?? null); ?></td>
                                                                                                                    <td>
                                                                                                                        <span class="badge badge-<?php echo $getStatusClass($attempt['status']); ?>">
                                                                                                                            <?php echo $attempt['status']; ?>
                                                                                                                        </span>
                                                                                                                    </td>
                                                                                                                    <td><?php echo isset($attempt['score']) ? $attempt['score'] : 'N/A'; ?></td>
                                                                                                                    <td>
                                                                                                                        <?php if (isset($attempt['attendance_status'])): ?>
                                                                                                                        <span class="badge badge-<?php echo $getAttendanceClass($attempt['attendance_status']); ?>">
                                                                                                                            <?php echo $attempt['attendance_status']; ?>
                                                                                                                        </span>
                                                                                                                        <?php else: ?>
                                                                                                                        N/A
                                                                                                                        <?php endif; ?>
                                                                                                                    </td>
                                                                                                                    <td><?php echo $attempt['instructor_name'] ?? 'Not Assigned'; ?></td>
                                                                                                                </tr>
                                                                                                            <?php endforeach; ?>
                                                                                                        </tbody>
                                                                                                    </table>
                                                                                                </div>
                                                                                                <?php if (isset($attempts[0]['comment']) && !empty($attempts[0]['comment'])): ?>
                                                                                                <div class="p-3">
                                                                                                    <h6>Latest Comment:</h6>
                                                                                                    <div class="alert alert-light"><?php echo $attempts[0]['comment']; ?></div>
                                                                                                </div>
                                                                                                <?php endif; ?>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            <?php
                                                            }
                                                        }
                                                        ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="text-center py-5">
                                    <i class="fas fa-exclamation-circle fa-5x text-muted mb-3"></i>
                                    <h3>No License Enrolled</h3>
                                    <p>This student hasn't enrolled in any driving license program yet.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
include '../../../include/footer.html';
?>

<!-- Custom CSS for timeline -->
<style>
    .timeline {
        position: relative;
        padding: 20px 0 20px;
        list-style: none;
    }

    .timeline:before {
        content: " ";
        position: absolute;
        top: 0;
        bottom: 0;
        left: 50px;
        width: 3px;
        margin-left: -1.5px;
        background-color: #e9ecef;
    }

    .timeline-item {
        position: relative;
        margin-bottom: 20px;
    }

    .timeline-item:after,
    .timeline-item:before {
        content: " ";
        display: table;
    }

    .timeline-item:after {
        clear: both;
    }

    .timeline-badge {
        position: absolute;
        top: 16px;
        left: 50px;
        width: 50px;
        height: 50px;
        margin-left: -25px;
        z-index: 2;
        border-radius: 50%;
        text-align: center;
        font-size: 1.4em;
        line-height: 50px;
        color: #fff;
    }

    .timeline-panel {
        position: relative;
        width: calc(100% - 90px);
        float: right;
        padding: 15px;
        border: 1px solid #d4d4d4;
        border-radius: 5px;
        -webkit-box-shadow: 0 1px 6px rgba(0, 0, 0, 0.05);
        box-shadow: 0 1px 6px rgba(0, 0, 0, 0.05);
    }

    .timeline-panel:before {
        position: absolute;
        top: 26px;
        left: -15px;
        display: inline-block;
        border-top: 15px solid transparent;
        border-right: 15px solid #ccc;
        border-left: 0 solid #ccc;
        border-bottom: 15px solid transparent;
        content: " ";
    }

    .timeline-panel:after {
        position: absolute;
        top: 27px;
        left: -14px;
        display: inline-block;
        border-top: 14px solid transparent;
        border-right: 14px solid #fff;
        border-left: 0 solid #fff;
        border-bottom: 14px solid transparent;
        content: " ";
    }

    /* License Selector Cards */
    .license-toggle-card {
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .license-toggle-card.active {
        border-bottom: 3px solid #1572E8;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    .license-toggle-card:hover:not(.active) {
        transform: translateY(-5px);
    }

    .license-container {
        transition: all 0.3s ease;
    }
</style>

<script>
    $(document).ready(function() {
        // License toggle functionality
        $('.license-toggle-card').on('click', function() {
            // Remove active class from all cards
            $('.license-toggle-card').removeClass('active');
            
            // Add active class to clicked card
            $(this).addClass('active');
            
            // Hide all license containers
            $('.license-container').hide();
            
            // Show the license container corresponding to the clicked card
            $('#' + $(this).data('target')).show();
        });
        
        // Minimize button functionality
        $('#progress-toggle-btn').click(function() {
            var cardBody = $('#progress-card-body');
            
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
        
        // Lesson collapsible functionality 
        $('[id^="lessonHeading-"]').on('click', function() {
            var target = $(this).attr('data-target');
            $(target).collapse('toggle');
            
            // Toggle chevron icon
            $(this).find('i.fas').toggleClass('fa-chevron-down fa-chevron-up');
        });
        
        // Test history collapsible functionality
        $('[id^="testHistoryHeading-"]').on('click', function() {
            var target = $(this).attr('data-target');
            $(target).collapse('toggle');
            
            // Toggle chevron icon
            $(this).find('i.fas').toggleClass('fa-chevron-down fa-chevron-up');
        });
        
        // Initialize tooltips
        $('[data-toggle="tooltip"]').tooltip();
    });
</script>