<?php
include '../../../include/in_header.php';
include '../../../database/db_connection.php'; // Include the database connection file

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

// Query to get all students assigned to this instructor with their licenses
$sql = "SELECT DISTINCT s.student_id, u.name, u.email, u.phone,
        GROUP_CONCAT(DISTINCT l.license_name ORDER BY l.license_name SEPARATOR ', ') as licenses
        FROM student_lessons sl
        JOIN student_licenses sl2 ON sl.student_license_id = sl2.student_license_id
        JOIN students s ON sl2.student_id = s.student_id
        JOIN users u ON s.user_id = u.user_id
        JOIN licenses l ON sl2.license_id = l.license_id
        JOIN specialities sp ON l.license_id = sp.license_id AND sp.instructor_id = sl.instructor_id
        WHERE sl.instructor_id = ?
        GROUP BY s.student_id, u.name, u.email, u.phone";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $instructor_id);
$stmt->execute();
$result = $stmt->get_result();

// Get the statistics for the cards
// 1. Total Students
$sql_total_students = "SELECT COUNT(DISTINCT s.student_id) as total_students
                      FROM student_lessons sl
                      JOIN student_licenses sl2 ON sl.student_license_id = sl2.student_license_id
                      JOIN students s ON sl2.student_id = s.student_id
                      JOIN specialities sp ON sl2.license_id = sp.license_id
                      WHERE sl.instructor_id = ? AND sp.instructor_id = ?";

$stmt_total_students = $conn->prepare($sql_total_students);
$stmt_total_students->bind_param("ss", $instructor_id, $instructor_id);
$stmt_total_students->execute();
$result_total_students = $stmt_total_students->get_result();
$total_students = $result_total_students->fetch_assoc()['total_students'];

// 2. Total Licenses
$sql_total_licenses = "SELECT COUNT(DISTINCT sl2.student_license_id) as total_licenses
                       FROM student_lessons sl
                       JOIN student_licenses sl2 ON sl.student_license_id = sl2.student_license_id
                       JOIN specialities sp ON sl2.license_id = sp.license_id
                       WHERE sl.instructor_id = ? AND sp.instructor_id = ?";

$stmt_total_licenses = $conn->prepare($sql_total_licenses);
$stmt_total_licenses->bind_param("ss", $instructor_id, $instructor_id);
$stmt_total_licenses->execute();
$result_total_licenses = $stmt_total_licenses->get_result();
$total_licenses = $result_total_licenses->fetch_assoc()['total_licenses'];

// 3. Total Lessons Assigned
$sql_total_lessons = "SELECT COUNT(*) as total_lessons
                      FROM student_lessons sl
                      JOIN student_licenses sl2 ON sl.student_license_id = sl2.student_license_id
                      JOIN specialities sp ON sl2.license_id = sp.license_id
                      WHERE sl.instructor_id = ? AND sp.instructor_id = ?";

$stmt_total_lessons = $conn->prepare($sql_total_lessons);
$stmt_total_lessons->bind_param("ss", $instructor_id, $instructor_id);
$stmt_total_lessons->execute();
$result_total_lessons = $stmt_total_lessons->get_result();
$total_lessons = $result_total_lessons->fetch_assoc()['total_lessons'];

// 4. Total Tests Scheduled
$sql_total_tests = "SELECT COUNT(DISTINCT sts.student_test_id) as total_tests
                    FROM student_test_sessions sts
                    JOIN test_sessions ts ON sts.test_session_id = ts.test_session_id
                    JOIN student_tests st ON sts.student_test_id = st.student_test_id
                    JOIN student_licenses sl ON st.student_license_id = sl.student_license_id
                    JOIN specialities sp ON sl.license_id = sp.license_id
                    WHERE ts.instructor_id = ? AND sp.instructor_id = ?";

$stmt_total_tests = $conn->prepare($sql_total_tests);
$stmt_total_tests->bind_param("ss", $instructor_id, $instructor_id);
$stmt_total_tests->execute();
$result_total_tests = $stmt_total_tests->get_result();
$total_tests = $result_total_tests->fetch_assoc()['total_tests'];
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
                    <a href="#">Student List</a>
                </li>
            </ul>
        </div>

        <!-- Inner page content -->
        <div class="page-category">

            <!-- Card Statistical Data -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title">Statistical Data</h4>
                </div>
                <div class="card-body">
                    <!-- Statistical Cards -->
                    <div class="row">
                        <!-- Total Students Card -->
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
                                                <h4 class="card-title"><?php echo $total_students; ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Licenses Card -->
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-info card-round">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-5">
                                            <div class="icon-big text-center">
                                                <i class="fas fa-id-card"></i>
                                            </div>
                                        </div>
                                        <div class="col-7 col-stats">
                                            <div class="numbers">
                                                <p class="card-category">Licenses</p>
                                                <h4 class="card-title"><?php echo $total_licenses; ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Lessons Card -->
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-success card-round">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-5">
                                            <div class="icon-big text-center">
                                                <i class="fas fa-book"></i>
                                            </div>
                                        </div>
                                        <div class="col-7 col-stats">
                                            <div class="numbers">
                                                <p class="card-category">Lessons</p>
                                                <h4 class="card-title"><?php echo $total_lessons; ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Tests Card -->
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-warning card-round">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-5">
                                            <div class="icon-big text-center">
                                                <i class="fas fa-clipboard-check"></i>
                                            </div>
                                        </div>
                                        <div class="col-7 col-stats">
                                            <div class="numbers">
                                                <p class="card-category">Tests</p>
                                                <h4 class="card-title"><?php echo $total_tests; ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Card Student List -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title">Student List</h4>
                </div>
                <div class="card-body">
                    <!-- Table Section -->
                    <div class="table-responsive">
                        <table id="basic-datatables" class="display table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Licenses Enrolled</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($result->num_rows > 0) {
                                    $counter = 1; // Initialize counter
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . $counter++ . "</td>";
                                        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['phone']) . "</td>";
                                        echo "<td>" . htmlspecialchars($row['licenses']) . "</td>";
                                        echo "<td>";
                                        echo "<a href='view_student.php?id=" . htmlspecialchars($row['student_id']) . "' class='btn btn-sm btn-primary'>View</a>";
                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='6'>No students assigned to you</td></tr>";
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

<?php
include '../../../include/footer.html';
?>

<script>
    $(document).ready(function() {
        $("#basic-datatables").DataTable({});
    });
</script>