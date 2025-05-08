<?php
include '../../../include/in_header.php';
include '../../../database/db_connection.php';

// Get the instructor ID
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo "Error: User not logged in.";
    exit;
}

$stmt = $conn->prepare("SELECT instructor_id FROM instructors WHERE user_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$instructor = $result->fetch_assoc();
$instructor_id = $instructor['instructor_id'] ?? null;

if (!$instructor_id) {
    echo "Error: Unauthorized access. User is not an instructor.";
    exit;
}

// Get student ID from URL parameter
$student_id = $_GET['id'] ?? null;

if (!$student_id) {
    echo "Error: No student ID provided.";
    exit;
}

// Check if the student is assigned to this instructor
$check_sql = "SELECT 1 
              FROM student_lessons sl
              JOIN student_licenses sl2 ON sl.student_license_id = sl2.student_license_id
              WHERE sl2.student_id = ? AND sl.instructor_id = ?
              LIMIT 1";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ss", $student_id, $instructor_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    echo "Error: You are not authorized to view this student's information.";
    exit;
}

// Get student's basic information
$student_sql = "SELECT s.student_id, s.dob, s.bank_number, s.bank_name, 
                u.name, u.email, u.phone, u.address, u.ic, u.created_at, u.user_id
                FROM students s
                JOIN users u ON s.user_id = u.user_id
                WHERE s.student_id = ?";
$student_stmt = $conn->prepare($student_sql);
$student_stmt->bind_param("s", $student_id);
$student_stmt->execute();
$student_result = $student_stmt->get_result();
$student = $student_result->fetch_assoc();

// Get student's licenses
$licenses_sql = "SELECT sl.student_license_id, 
                l.license_id, l.license_name, l.license_type, l.description,
                les.lesson_name, les.lesson_id
                FROM student_licenses sl
                JOIN licenses l ON sl.license_id = l.license_id
                JOIN lessons les ON sl.lesson_id = les.lesson_id
                WHERE sl.student_id = ?";
$licenses_stmt = $conn->prepare($licenses_sql);
$licenses_stmt->bind_param("s", $student_id);
$licenses_stmt->execute();
$licenses_result = $licenses_stmt->get_result();
$licenses = [];
while ($row = $licenses_result->fetch_assoc()) {
    $licenses[] = $row;
}

// For each license, get lessons, tests and attendance information
$student_details = [];
foreach ($licenses as $license) {
    $student_license_id = $license['student_license_id'];

    // Get lessons
    $lessons_sql = "SELECT sl.student_lesson_id, sl.student_lesson_name, sl.date, 
                   sl.start_time, sl.end_time, sl.status, sl.attendance_status, sl.schedule_status,
                   i.instructor_id, u.name as instructor_name
                   FROM student_lessons sl
                   LEFT JOIN instructors i ON sl.instructor_id = i.instructor_id
                   LEFT JOIN users u ON i.user_id = u.user_id
                   WHERE sl.student_license_id = ?
                   ORDER BY sl.date, sl.start_time";
    $lessons_stmt = $conn->prepare($lessons_sql);
    $lessons_stmt->bind_param("s", $student_license_id);
    $lessons_stmt->execute();
    $lessons_result = $lessons_stmt->get_result();
    $lessons = [];
    while ($row = $lessons_result->fetch_assoc()) {
        $lessons[] = $row;
    }

    // Get tests
    $tests_sql = "SELECT st.student_test_id, st.status, st.schedule_status, st.score, st.comment,
                    t.test_id, t.test_name,
                    sts.student_test_session_id, sts.attendance_status,
                    ts.test_date, ts.start_time, ts.end_time, ts.status as session_status
                    FROM student_tests st
                    JOIN tests t ON st.test_id = t.test_id
                    LEFT JOIN student_test_sessions sts ON st.student_test_id = sts.student_test_id
                    LEFT JOIN test_sessions ts ON sts.test_session_id = ts.test_session_id
                    WHERE st.student_license_id = ?
                    ORDER BY ts.test_date IS NULL, ts.test_date ASC, ts.start_time ASC";
    $tests_stmt = $conn->prepare($tests_sql);
    $tests_stmt->bind_param("s", $student_license_id);
    $tests_stmt->execute();
    $tests_result = $tests_stmt->get_result();
    $tests = [];
    while ($row = $tests_result->fetch_assoc()) {
        $tests[] = $row;
    }

    // Calculate progress
    $completed_lessons = 0;
    $total_lessons = count($lessons);
    foreach ($lessons as $lesson) {
        if ($lesson['status'] === 'Completed') {
            $completed_lessons++;
        }
    }

    $completed_tests = 0;
    $total_tests = count($tests);
    foreach ($tests as $test) {
        if ($test['status'] === 'Passed') {
            $completed_tests++;
        }
    }

    // Calculate overall progress
    $lesson_progress = $total_lessons > 0 ? ($completed_lessons / $total_lessons) * 100 : 0;
    $test_progress = $total_tests > 0 ? ($completed_tests / $total_tests) * 100 : 0;
    $overall_progress = $total_lessons > 0 && $total_tests > 0 ?
        ($lesson_progress * 0.7 + $test_progress * 0.3) : ($total_lessons > 0 ? $lesson_progress : ($total_tests > 0 ? $test_progress : 0));

    // Check if license is issued
    $license_issued_sql = "SELECT status FROM issued_licenses WHERE student_license_id = ? LIMIT 1";
    $license_issued_stmt = $conn->prepare($license_issued_sql);
    $license_issued_stmt->bind_param("s", $student_license_id);
    $license_issued_stmt->execute();
    $license_issued_result = $license_issued_stmt->get_result();
    $license_issued = $license_issued_result->num_rows > 0 ? $license_issued_result->fetch_assoc()['status'] : 'Not Issued';

    // Add all collected data to student_details array
    $student_details[] = [
        'license' => $license,
        'lessons' => $lessons,
        'tests' => $tests,
        'completed_lessons' => $completed_lessons,
        'total_lessons' => $total_lessons,
        'completed_tests' => $completed_tests,
        'total_tests' => $total_tests,
        'lesson_progress' => $lesson_progress,
        'test_progress' => $test_progress,
        'overall_progress' => $overall_progress,
        'license_issued' => $license_issued
    ];
}
?>

<div class="container">
    <div class="page-inner">
        <!-- Breadcrumbs -->
        <div class="page-header">
            <h4 class="page-title">Manage Student</h4>
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
                    <a href="/pages/instructor/manage_student/list_student.php">Overview Student Information</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Student Details</a>
                </li>
            </ul>
        </div>

        <!-- Student Information -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">Student Details</h4>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="info-toggle-btn">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                    <div class="card-body" id="info-card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card shadow-sm">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="avatar avatar-xl mr-3">
                                                <span class="avatar-initial rounded-circle bg-primary"><?php echo substr($student['name'], 0, 1); ?></span>
                                            </div>
                                            <div>
                                                <h4 class="mb-0"><?php echo htmlspecialchars($student['name']); ?></h4>
                                                <span class="text-muted">Student ID: <?php echo htmlspecialchars($student['student_id']); ?></span>
                                                <div class="mt-2">
                                                    <?php if (!empty($student['phone'])): ?>
                                                        <a class="btn btn-primary btn-sm" href="tel:<?php echo htmlspecialchars($student['phone']); ?>">
                                                            <i class="fa fa-phone"></i> Call
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <tbody>
                                                    <tr>
                                                        <td><strong>IC Number</strong></td>
                                                        <td><?php echo htmlspecialchars($student['ic']); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Date of Birth</strong></td>
                                                        <td><?php echo date('d M Y', strtotime($student['dob'])); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Email</strong></td>
                                                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Phone</strong></td>
                                                        <td><?php echo htmlspecialchars($student['phone']); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Address</strong></td>
                                                        <td><?php echo htmlspecialchars($student['address']); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Registered On</strong></td>
                                                        <td><?php echo date('d M Y', strtotime($student['created_at'])); ?></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card shadow-sm mb-3">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="mb-0"><i class="fas fa-id-card"></i> License Overview</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if (count($licenses) > 0): ?>
                                            <?php foreach ($licenses as $license): ?>
                                                <div class="mb-3">
                                                    <h6><?php echo htmlspecialchars($license['license_name']); ?> (<?php echo htmlspecialchars($license['license_type']); ?>)</h6>
                                                    <div class="d-flex justify-content-between mb-1">
                                                        <small>Lesson Type: <strong><?php echo htmlspecialchars($license['lesson_name']); ?></strong></small>
                                                    </div>
                                                    <?php
                                                    // Find the corresponding student detail for this license
                                                    $detail = null;
                                                    foreach ($student_details as $sd) {
                                                        if ($sd['license']['student_license_id'] === $license['student_license_id']) {
                                                            $detail = $sd;
                                                            break;
                                                        }
                                                    }

                                                    if ($detail):
                                                        $progress_color = $detail['overall_progress'] < 30 ? 'danger' : ($detail['overall_progress'] < 70 ? 'warning' : 'success');
                                                    ?>
                                                        <div class="progress" style="height: 20px;">
                                                            <div class="progress-bar bg-<?php echo $progress_color; ?>" role="progressbar"
                                                                style="width: <?php echo $detail['overall_progress']; ?>%"
                                                                aria-valuenow="<?php echo $detail['overall_progress']; ?>" aria-valuemin="0" aria-valuemax="100">
                                                                <?php echo round($detail['overall_progress']); ?>%
                                                            </div>
                                                        </div>
                                                        <div class="d-flex justify-content-between mt-2">
                                                            <small>Lessons: <strong><?php echo $detail['completed_lessons']; ?>/<?php echo $detail['total_lessons']; ?></strong></small>
                                                            <small>Tests: <strong><?php echo $detail['completed_tests']; ?>/<?php echo $detail['total_tests']; ?></strong></small>
                                                            <small>Status:
                                                                <span class="badge badge-<?php
                                                                                            echo $detail['license_issued'] === 'Issued' ? 'success' : ($detail['license_issued'] === 'Pending' ? 'warning' : 'secondary');
                                                                                            ?>">
                                                                    <?php echo $detail['license_issued']; ?>
                                                                </span>
                                                            </small>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <p class="text-muted">No licenses registered.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- License Details Tabs -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">License Details</h4>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="details-toggle-btn">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                    <div class="card-body" id="details-card-body">
                        <!-- Nav-liner tabs -->
                        <div class="nav-line-container">
                            <ul class="nav nav-tabs nav-line nav-color-secondary" id="licenseTabs" role="tablist">
                                <?php foreach ($student_details as $index => $detail): ?>
                                    <li class="nav-item">
                                        <a class="nav-link <?php echo $index === 0 ? 'active' : ''; ?>"
                                            id="license-<?php echo $index; ?>-tab"
                                            data-toggle="tab"
                                            href="#license-<?php echo $index; ?>"
                                            role="tab"
                                            aria-controls="license-<?php echo $index; ?>"
                                            aria-selected="<?php echo $index === 0 ? 'true' : 'false'; ?>">
                                            <?php echo htmlspecialchars($detail['license']['license_name']); ?> (<?php echo htmlspecialchars($detail['license']['license_type']); ?>)
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>

                        <div class="tab-content mt-3" id="licenseTabsContent">
                            <?php foreach ($student_details as $index => $detail): ?>
                                <div class="tab-pane fade <?php echo $index === 0 ? 'show active' : ''; ?>"
                                    id="license-<?php echo $index; ?>"
                                    role="tabpanel"
                                    aria-labelledby="license-<?php echo $index; ?>-tab">

                                    <!-- Lessons Section -->
                                    <div class="card mb-3">
                                        <div class="card-header bg-primary text-white">
                                            <h5 class="mb-0"><i class="fas fa-book"></i> Lessons (<?php echo $detail['completed_lessons']; ?>/<?php echo $detail['total_lessons']; ?> Completed)</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table id="lesson-table-<?php echo $index; ?>" class="table table-bordered  table-sm">
                                                    <thead>
                                                        <tr>
                                                            <th>#</th>
                                                            <th>Lesson Name</th>
                                                            <th>Date</th>
                                                            <th>Time</th>
                                                            <th>Status</th>
                                                            <th>Attendance</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php if (count($detail['lessons']) > 0): ?>
                                                            <?php foreach ($detail['lessons'] as $lessonIndex => $lesson): ?>
                                                                <tr class="<?php
                                                                            echo $lesson['status'] === 'Completed' ? 'table-success' : ($lesson['status'] === 'Pending' ? 'table-warning' : 'table-light');
                                                                            ?>">
                                                                    <td><?php echo $lessonIndex + 1; ?></td>
                                                                    <td><?php echo htmlspecialchars($lesson['student_lesson_name']); ?></td>
                                                                    <td><?php echo $lesson['date'] ? date('d M Y', strtotime($lesson['date'])) : 'Not Scheduled'; ?></td>
                                                                    <td>
                                                                        <?php
                                                                        if ($lesson['start_time'] && $lesson['end_time']) {
                                                                            echo date('h:i A', strtotime($lesson['start_time'])) . ' - ' .
                                                                                date('h:i A', strtotime($lesson['end_time']));
                                                                        } else {
                                                                            echo 'Not Scheduled';
                                                                        }
                                                                        ?>
                                                                    </td>
                                                                    <td>
                                                                        <span class="badge badge-<?php
                                                                                                    echo $lesson['status'] === 'Completed' ? 'success' : ($lesson['status'] === 'Pending' ? 'warning' : 'secondary');
                                                                                                    ?>">
                                                                            <?php echo htmlspecialchars($lesson['status']); ?>
                                                                        </span>
                                                                    </td>
                                                                    <td>
                                                                        <span class="badge badge-<?php
                                                                                                    echo $lesson['attendance_status'] === 'Attend' ? 'success' : 'danger';
                                                                                                    ?>">
                                                                            <?php echo htmlspecialchars($lesson['attendance_status']); ?>
                                                                        </span>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <tr>
                                                                <td colspan="7" class="text-center">No lessons scheduled yet.</td>
                                                            </tr>
                                                        <?php endif; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Tests Section -->
                                    <div class="card">
                                        <div class="card-header bg-info text-white">
                                            <h5 class="mb-0"><i class="fas fa-clipboard-check"></i> Tests (<?php echo $detail['completed_tests']; ?>/<?php echo $detail['total_tests']; ?> Passed)</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table id="test-table-<?php echo $index; ?>" class="table table-bordered  table-sm">
                                                    <thead>
                                                        <tr>
                                                            <th>#</th>
                                                            <th>Test Name</th>
                                                            <th>Date</th>
                                                            <th>Time</th>
                                                            <th>Status</th>
                                                            <th>Score</th>
                                                            <th>Attendance</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php if (count($detail['tests']) > 0): ?>
                                                            <?php foreach ($detail['tests'] as $testIndex => $test): ?>
                                                                <tr class="<?php
                                                                            echo $test['status'] === 'Passed' ? 'table-success' : ($test['status'] === 'Failed' ? 'table-danger' : ($test['status'] === 'Pending' ? 'table-warning' : 'table-light'));
                                                                            ?>">
                                                                    <td><?php echo $testIndex + 1; ?></td>
                                                                    <td><?php echo htmlspecialchars($test['test_name']); ?></td>
                                                                    <td><?php echo $test['test_date'] ? date('d M Y', strtotime($test['test_date'])) : 'Not Scheduled'; ?></td>
                                                                    <td>
                                                                        <?php
                                                                        if ($test['start_time'] && $test['end_time']) {
                                                                            echo date('h:i A', strtotime($test['start_time'])) . ' - ' .
                                                                                date('h:i A', strtotime($test['end_time']));
                                                                        } else {
                                                                            echo 'Not Scheduled';
                                                                        }
                                                                        ?>
                                                                    </td>
                                                                    <td>
                                                                        <span class="badge badge-<?php
                                                                                                    echo $test['status'] === 'Passed' ? 'success' : ($test['status'] === 'Failed' ? 'danger' : ($test['status'] === 'Pending' ? 'warning' : 'secondary'));
                                                                                                    ?>">
                                                                            <?php echo htmlspecialchars($test['status']); ?>
                                                                        </span>
                                                                    </td>
                                                                    <td>
                                                                        <?php
                                                                        if ($test['score'] !== null) {
                                                                            echo $test['score'] . '/50 (' . round(($test['score'] / 50) * 100) . '%)';
                                                                        } else {
                                                                            echo 'Not graded';
                                                                        }
                                                                        ?>
                                                                    </td>
                                                                    <td>
                                                                        <?php if ($test['attendance_status']): ?>
                                                                            <span class="badge badge-<?php
                                                                                                        echo $test['attendance_status'] === 'Attend' ? 'success' : 'danger';
                                                                                                        ?>">
                                                                                <?php echo htmlspecialchars($test['attendance_status']); ?>
                                                                            </span>
                                                                        <?php else: ?>
                                                                            <span class="badge badge-secondary">Not Available</span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <tr>
                                                                <td colspan="7" class="text-center">No tests scheduled yet.</td>
                                                            </tr>
                                                        <?php endif; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
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
        // Initialize tables on the active tab first
        initializeTabTables(0);

        // Initialize tables when tab is clicked
        $('#licenseTabs a').on('shown.bs.tab', function(e) {
            // Get the newly activated tab index from the href attribute
            var tabId = $(e.target).attr('href');
            var tabIndex = tabId.split('-')[1];

            // Initialize tables for this tab
            initializeTabTables(tabIndex);
        });

        // Function to initialize tables for a specific tab
        function initializeTabTables(tabIndex) {
            // Check if DataTable is already initialized
            if (!$.fn.DataTable.isDataTable('#lesson-table-' + tabIndex)) {
                $('#lesson-table-' + tabIndex).DataTable({
                    "paging": true,
                    "searching": true,
                    "ordering": true
                });
            }

            if (!$.fn.DataTable.isDataTable('#test-table-' + tabIndex)) {
                $('#test-table-' + tabIndex).DataTable({
                    "paging": true,
                    "searching": true,
                    "ordering": true
                });
            }
        }

        // Toggle information card visibility
        $('#info-toggle-btn').click(function() {
            var infoContent = $('#info-card-body');
            infoContent.slideToggle(300);

            // Toggle the icon
            var icon = $(this).find('i');
            if (icon.hasClass('fa-minus')) {
                icon.removeClass('fa-minus').addClass('fa-plus');
            } else {
                icon.removeClass('fa-plus').addClass('fa-minus');
            }
        });

        // Toggle details card visibility
        $('#details-toggle-btn').click(function() {
            var detailsContent = $('#details-card-body');
            detailsContent.slideToggle(300);

            // Toggle the icon
            var icon = $(this).find('i');
            if (icon.hasClass('fa-minus')) {
                icon.removeClass('fa-minus').addClass('fa-plus');
            } else {
                icon.removeClass('fa-plus').addClass('fa-minus');
            }
        });

        // Make sure the nav tabs are working properly
        $('#licenseTabs a').click(function(e) {
            e.preventDefault();
            $(this).tab('show');
        });
    });
</script>

<style>
    /* Enhanced Avatar Styling */
    .avatar-xl {
        width: 60px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .avatar-initial {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        font-weight: 600;
        color: #fff;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        background-image: linear-gradient(135deg, #1572e8 0%, #4286f4 100%);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        border: 2px solid rgba(255, 255, 255, 0.6);
        transition: all 0.3s ease;
    }

    .avatar-initial:hover {
        transform: scale(1.05);
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
    }

    /* Add this right before the closing </head> tag */
</style>