<?php
include '../../../include/in_header.php';
include '../../../database/db_connection.php'; // Include the database connection file

// Check if student ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "Error: No student ID provided.";
    exit;
}

$student_id = $_GET['id'];

// Get the current user's ID from the session
$user_id = $_SESSION['user_id'];

// Get the instructor_id from the user_id
$sql_instructor = "SELECT instructor_id FROM instructors WHERE user_id = ?";
$stmt_instructor = $conn->prepare($sql_instructor);
$stmt_instructor->bind_param("s", $user_id);
$stmt_instructor->execute();
$result_instructor = $stmt_instructor->get_result();

if ($result_instructor->num_rows > 0) {
    $instructor_row = $result_instructor->fetch_assoc();
    $instructor_id = $instructor_row['instructor_id'];
} else {
    // Handle the case where the user is not an instructor
    echo "Error: Unauthorized access. User is not an instructor.";
    exit;
}

// Get student basic information
$sql_student = "SELECT s.student_id, u.name, u.email, u.phone, s.dob, u.address
                FROM students s 
                JOIN users u ON s.user_id = u.user_id
                WHERE s.student_id = ?";
                
$stmt_student = $conn->prepare($sql_student);
$stmt_student->bind_param("s", $student_id);
$stmt_student->execute();
$result_student = $stmt_student->get_result();

if ($result_student->num_rows == 0) {
    echo "Error: Student not found.";
    exit;
}

$student = $result_student->fetch_assoc();

// Get licenses enrolled by this student under this instructor
$sql_licenses = "SELECT DISTINCT sl.student_license_id, l.license_id, l.license_name, l.license_type, sl.progress
                FROM student_licenses sl
                JOIN licenses l ON sl.license_id = l.license_id
                JOIN specialities sp ON l.license_id = sp.license_id
                JOIN student_lessons sls ON sl.student_license_id = sls.student_license_id
                WHERE sl.student_id = ? AND sls.instructor_id = ? AND sp.instructor_id = ?
                GROUP BY sl.student_license_id, l.license_id, l.license_name, l.license_type, sl.progress";

$stmt_licenses = $conn->prepare($sql_licenses);
$stmt_licenses->bind_param("sss", $student_id, $instructor_id, $instructor_id);
$stmt_licenses->execute();
$result_licenses = $stmt_licenses->get_result();

?>

<div class="container">
    <div class="page-inner">

        <!-- Breadcrumbs -->
        <div class="page-header">
            <h4 class="page-title">Student Detail</h4>
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
                    <a href="/pages/instructor/manage_student/list_student.php">Student List</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Student Detail</a>
                </li>
            </ul>
        </div>

        <!-- Student Information Card -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Student Information</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Name</label>
                                    <p class="form-control-static"><?php echo htmlspecialchars($student['name']); ?></p>
                                </div>
                                <div class="form-group">
                                    <label>Email</label>
                                    <p class="form-control-static"><?php echo htmlspecialchars($student['email']); ?></p>
                                </div>
                                <div class="form-group">
                                    <label>Phone</label>
                                    <p class="form-control-static"><?php echo htmlspecialchars($student['phone']); ?></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Date of Birth</label>
                                    <p class="form-control-static"><?php echo htmlspecialchars(date('d F Y', strtotime($student['dob']))); ?></p>
                                </div>
                                <div class="form-group">
                                    <label>Address</label>
                                    <p class="form-control-static"><?php echo htmlspecialchars($student['address']); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Licenses and Progress -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Licenses & Progress</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($result_licenses->num_rows > 0): ?>
                            <div class="row">
                                <?php while ($license = $result_licenses->fetch_assoc()): ?>
                                    <div class="col-md-6 mb-4">
                                        <div class="card card-info">
                                            <div class="card-header">
                                                <h4 class="card-title"><?php echo htmlspecialchars($license['license_name']); ?> (<?php echo htmlspecialchars($license['license_type']); ?>)</h4>
                                            </div>
                                            <div class="card-body">
                                                <h5>Progress</h5>
                                                <div class="progress mb-3">
                                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $license['progress']; ?>%" aria-valuenow="<?php echo $license['progress']; ?>" aria-valuemin="0" aria-valuemax="100"><?php echo $license['progress']; ?>%</div>
                                                </div>
                                                
                                                <?php
                                                // Get lessons for this license
                                                $student_license_id = $license['student_license_id'];
                                                
                                                $sql_lessons = "SELECT sl.student_lesson_id, sl.student_lesson_name, sl.date, sl.start_time, sl.end_time, sl.status, sl.attendance_status
                                                              FROM student_lessons sl
                                                              WHERE sl.student_license_id = ? AND sl.instructor_id = ?
                                                              ORDER BY sl.date, sl.start_time";
                                                              
                                                $stmt_lessons = $conn->prepare($sql_lessons);
                                                $stmt_lessons->bind_param("ss", $student_license_id, $instructor_id);
                                                $stmt_lessons->execute();
                                                $result_lessons = $stmt_lessons->get_result();
                                                ?>
                                                
                                                <h5>Lessons</h5>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered">
                                                        <thead>
                                                            <tr>
                                                                <th>Lesson</th>
                                                                <th>Date</th>
                                                                <th>Time</th>
                                                                <th>Status</th>
                                                                <th>Attendance</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php if ($result_lessons->num_rows > 0): ?>
                                                                <?php while ($lesson = $result_lessons->fetch_assoc()): ?>
                                                                    <tr>
                                                                        <td><?php echo htmlspecialchars($lesson['student_lesson_name']); ?></td>
                                                                        <td><?php echo htmlspecialchars(date('d M Y', strtotime($lesson['date']))); ?></td>
                                                                        <td><?php echo htmlspecialchars(substr($lesson['start_time'], 0, 5) . ' - ' . substr($lesson['end_time'], 0, 5)); ?></td>
                                                                        <td>
                                                                            <?php if ($lesson['status'] == 'Completed'): ?>
                                                                                <span class="badge badge-success">Completed</span>
                                                                            <?php elseif ($lesson['status'] == 'Pending'): ?>
                                                                                <span class="badge badge-warning">Pending</span>
                                                                            <?php else: ?>
                                                                                <span class="badge badge-secondary">Ineligible</span>
                                                                            <?php endif; ?>
                                                                        </td>
                                                                        <td>
                                                                            <?php if ($lesson['attendance_status'] == 'Attend'): ?>
                                                                                <span class="badge badge-success">Attended</span>
                                                                            <?php else: ?>
                                                                                <span class="badge badge-danger">Absent</span>
                                                                            <?php endif; ?>
                                                                        </td>
                                                                    </tr>
                                                                <?php endwhile; ?>
                                                            <?php else: ?>
                                                                <tr>
                                                                    <td colspan="5" class="text-center">No lessons scheduled</td>
                                                                </tr>
                                                            <?php endif; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                                
                                                <?php
                                                // Get tests for this license
                                                $sql_tests = "SELECT st.student_test_id, t.test_name, st.status, st.score, 
                                                             ts.test_date, ts.start_time, ts.end_time, sts.attendance_status
                                                             FROM student_tests st
                                                             JOIN tests t ON st.test_id = t.test_id
                                                             LEFT JOIN student_test_sessions sts ON st.student_test_id = sts.student_test_id
                                                             LEFT JOIN test_sessions ts ON sts.test_session_id = ts.test_session_id
                                                             WHERE st.student_license_id = ? AND (ts.instructor_id = ? OR ts.instructor_id IS NULL)
                                                             ORDER BY ts.test_date, ts.start_time";
                                                             
                                                $stmt_tests = $conn->prepare($sql_tests);
                                                $stmt_tests->bind_param("ss", $student_license_id, $instructor_id);
                                                $stmt_tests->execute();
                                                $result_tests = $stmt_tests->get_result();
                                                ?>
                                                
                                                <h5 class="mt-4">Tests</h5>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered">
                                                        <thead>
                                                            <tr>
                                                                <th>Test Name</th>
                                                                <th>Date</th>
                                                                <th>Time</th>
                                                                <th>Status</th>
                                                                <th>Score</th>
                                                                <th>Attendance</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php if ($result_tests->num_rows > 0): ?>
                                                                <?php while ($test = $result_tests->fetch_assoc()): ?>
                                                                    <tr>
                                                                        <td><?php echo htmlspecialchars($test['test_name']); ?></td>
                                                                        <td>
                                                                            <?php echo !empty($test['test_date']) ? htmlspecialchars(date('d M Y', strtotime($test['test_date']))) : 'Not scheduled'; ?>
                                                                        </td>
                                                                        <td>
                                                                            <?php echo !empty($test['start_time']) ? htmlspecialchars(substr($test['start_time'], 0, 5) . ' - ' . substr($test['end_time'], 0, 5)) : 'N/A'; ?>
                                                                        </td>
                                                                        <td>
                                                                            <?php if ($test['status'] == 'Passed'): ?>
                                                                                <span class="badge badge-success">Passed</span>
                                                                            <?php elseif ($test['status'] == 'Failed'): ?>
                                                                                <span class="badge badge-danger">Failed</span>
                                                                            <?php elseif ($test['status'] == 'Pending'): ?>
                                                                                <span class="badge badge-warning">Pending</span>
                                                                            <?php else: ?>
                                                                                <span class="badge badge-secondary">Ineligible</span>
                                                                            <?php endif; ?>
                                                                        </td>
                                                                        <td>
                                                                            <?php echo !empty($test['score']) ? htmlspecialchars($test['score']) : 'N/A'; ?>
                                                                        </td>
                                                                        <td>
                                                                            <?php if ($test['attendance_status'] == 'Attend'): ?>
                                                                                <span class="badge badge-success">Attended</span>
                                                                            <?php elseif (!empty($test['test_date'])): ?>
                                                                                <span class="badge badge-danger">Absent</span>
                                                                            <?php else: ?>
                                                                                <span class="badge badge-secondary">N/A</span>
                                                                            <?php endif; ?>
                                                                        </td>
                                                                    </tr>
                                                                <?php endwhile; ?>
                                                            <?php else: ?>
                                                                <tr>
                                                                    <td colspan="6" class="text-center">No tests scheduled</td>
                                                                </tr>
                                                            <?php endif; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                                
                                                <!-- Attendance Statistics -->
                                                <?php
                                                $sql_attendance = "SELECT 
                                                                 COUNT(CASE WHEN sl.attendance_status = 'Attend' THEN 1 END) as attended,
                                                                 COUNT(*) as total
                                                                 FROM student_lessons sl
                                                                 WHERE sl.student_license_id = ? AND sl.instructor_id = ?
                                                                 AND sl.date <= CURRENT_DATE()";
                                                                 
                                                $stmt_attendance = $conn->prepare($sql_attendance);
                                                $stmt_attendance->bind_param("ss", $student_license_id, $instructor_id);
                                                $stmt_attendance->execute();
                                                $result_attendance = $stmt_attendance->get_result();
                                                $attendance = $result_attendance->fetch_assoc();
                                                
                                                $attendance_rate = ($attendance['total'] > 0) ? round(($attendance['attended'] / $attendance['total']) * 100) : 0;
                                                ?>
                                                
                                                <h5 class="mt-4">Attendance Statistics</h5>
                                                <div class="progress mb-3">
                                                    <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $attendance_rate; ?>%" 
                                                         aria-valuenow="<?php echo $attendance_rate; ?>" aria-valuemin="0" aria-valuemax="100">
                                                        <?php echo $attendance_rate; ?>%
                                                    </div>
                                                </div>
                                                <p><?php echo $attendance['attended']; ?> of <?php echo $attendance['total']; ?> lessons attended</p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                No licenses found for this student under your instruction.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../../include/footer.html'; ?>

<script>
    $(document).ready(function() {
        $('.table').DataTable({
            "pageLength": 5,
            "lengthMenu": [[5, 10, 25, -1], [5, 10, 25, "All"]]
        });
    });
</script>