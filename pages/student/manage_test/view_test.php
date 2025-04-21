<?php
include '../../../include/st_header.php';
include '../../../database/db_connection.php';

$test_data = null;
$error_message = null;

if (!isset($_GET['test_session_id']) || empty($_GET['test_session_id'])) {
    $error_message = 'No test session specified.';
} else {
    $test_session_id = $_GET['test_session_id'];
    $current_user_id = $_SESSION['user_id'];

    $student_query = "SELECT s.student_id FROM students s 
                      JOIN users u ON s.user_id = u.user_id 
                      WHERE u.user_id = ?";
    $stmt = $conn->prepare($student_query);
    $stmt->bind_param("s", $current_user_id);
    $stmt->execute();
    $student_result = $stmt->get_result();

    if ($student_result->num_rows == 0) {
        $error_message = 'Student information not found.';
    } else {
        $student_row = $student_result->fetch_assoc();
        $student_id = $student_row['student_id'];

        $query = "SELECT ts.*, t.test_name,
                  u.name AS instructor_name, 
                  sts.student_test_session_id, sts.attendance_status,
                  st.status AS test_status, st.score, st.comment, st.student_test_id,
                  sl.license_id, l.license_name, l.license_type
                  FROM test_sessions ts
                  JOIN tests t ON ts.test_id = t.test_id
                  LEFT JOIN instructors i ON ts.instructor_id = i.instructor_id
                  LEFT JOIN users u ON i.user_id = u.user_id
                  JOIN student_test_sessions sts ON ts.test_session_id = sts.test_session_id
                  JOIN student_tests st ON sts.student_test_id = st.student_test_id
                  JOIN student_licenses sl ON st.student_license_id = sl.student_license_id
                  JOIN licenses l ON sl.license_id = l.license_id
                  WHERE ts.test_session_id = ? 
                  AND EXISTS (
                      SELECT 1 FROM student_licenses sl2 
                      WHERE sl2.student_id = ? AND sl2.student_license_id = st.student_license_id
                  )";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $test_session_id, $student_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $test_data = $result->fetch_assoc();
        } else {
            $error_message = 'Test session details not found or you are not authorized to view this test.';
        }
    }
}
?>

<div class="container">
    <div class="page-inner">

        <!-- Breadcrumbs -->
        <div class="page-header">
            <h4 class="page-title">My Test</h4>
            <ul class="breadcrumbs">
                <li class="nav-home">
                    <a href="/pages/student/dashboard.php">
                        <i class="icon-home"></i>
                    </a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="/pages/student/manage_test/list_test.php">Test List</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Test Detail</a>
                </li>
            </ul>
        </div>

        <div class="page-category">
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php elseif ($test_data): ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title">Test Detail</h4>
                                <div class="ms-md-auto py-2 py-md-0">
                                    <?php if ($test_data['test_status'] == 'Pending'): ?>
                                        <a href="#" class="btn btn-info btn-round" onclick="printTestInfo('<?php echo $test_data['student_test_session_id']; ?>')">
                                            <i class="fa fa-print"></i> Print Test Information
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($test_data['test_status'] == 'Passed' || $test_data['test_status'] == 'Failed'): ?>
                                        <a href="#" class="btn btn-primary btn-round" onclick="printResult('<?php echo $test_data['student_test_id']; ?>')">
                                            <i class="fa fa-download"></i> Download Results
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="card-body">
                                <!-- Test Status -->
                                <div class="row mb-4">
                                    <div class="col-md-12">
                                        <?php if ($test_data['test_status'] == 'Passed'): ?>
                                            <div class="alert alert-success">
                                                <h4><i class="fa fa-check-circle"></i> Test Status: <?php echo $test_data['test_status']; ?></h4>
                                                <?php if (!empty($test_data['score'])): ?>
                                                    <p class="mb-0">Score: <?php echo $test_data['score']; ?></p>
                                                <?php endif; ?>
                                            </div>
                                        <?php elseif ($test_data['test_status'] == 'Failed'): ?>
                                            <div class="alert alert-danger">
                                                <h4><i class="fa fa-times-circle"></i> Test Status: <?php echo $test_data['test_status']; ?></h4>
                                                <?php if (!empty($test_data['score'])): ?>
                                                    <p class="mb-0">Score: <?php echo $test_data['score']; ?></p>
                                                <?php endif; ?>
                                            </div>
                                        <?php elseif ($test_data['test_status'] == 'Pending'): ?>
                                            <div class="alert alert-warning">
                                                <h4><i class="fa fa-clock"></i> Test Status: <?php echo $test_data['test_status']; ?></h4>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-secondary">
                                                <h4><i class="fa fa-info-circle"></i> Test Status: <?php echo $test_data['test_status']; ?></h4>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Test Session Info -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="font-weight-bold">Test Name</label>
                                            <p><?php echo $test_data['test_name']; ?></p>
                                        </div>
                                        <div class="form-group">
                                            <label class="font-weight-bold">License Type</label>
                                            <p>
                                                <?php echo $test_data['license_name']; ?>
                                                <span class="badge <?php echo $test_data['license_type'] == 'Auto' ? 'badge-primary' : 'badge-info'; ?>">
                                                    <?php echo $test_data['license_type']; ?>
                                                </span>
                                            </p>
                                        </div>
                                        <div class="form-group">
                                            <label class="font-weight-bold">Attendance Status</label>
                                            <?php if ($test_data['attendance_status'] == 'Attend'): ?>
                                                <p><span class="badge badge-success">Attended</span></p>
                                            <?php else: ?>
                                                <p><span class="badge badge-danger">Absent</span></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="font-weight-bold">Test Date</label>
                                            <p><?php echo date('d M Y', strtotime($test_data['test_date'])); ?></p>
                                        </div>
                                        <div class="form-group">
                                            <label class="font-weight-bold">Test Time</label>
                                            <p><?php echo date('h:i A', strtotime($test_data['start_time'])); ?> - <?php echo date('h:i A', strtotime($test_data['end_time'])); ?></p>
                                        </div>
                                        <div class="form-group">
                                            <label class="font-weight-bold">Instructor</label>
                                            <p><?php echo !empty($test_data['instructor_name']) ? $test_data['instructor_name'] : 'Not assigned yet'; ?></p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Instructor Comments -->
                                <?php if (!empty($test_data['comment'])): ?>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label class="font-weight-bold">Instructor Comments</label>
                                                <div class="p-3 bg-light rounded">
                                                    <?php echo nl2br($test_data['comment']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                    function printTestInfo(studentTestSessionId) {
                        window.open('/pages/student/tests/print_test_info.php?id=' + studentTestSessionId, '_blank');
                    }

                    function printResult(studentTestId) {
                        window.open('/pages/student/tests/print_result.php?id=' + studentTestId, '_blank');
                    }
                </script>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../../include/footer.html'; ?>
