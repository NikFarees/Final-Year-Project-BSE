<?php
// filepath: c:\Users\nikfa\Desktop\UniKL\BSE\SEM 6\Final Year Project 2\KMSE_Driveflow\pages\admin\manage_lesson\assign_instructor.php
include '../../../include/ad_header.php';
include '../../../database/db_connection.php'; // Include the database connection file

// Include SweetAlert2
echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";

// Initialize error messages
$errors = [];
$successMessage = "";

// Get student license ID from query parameter
$student_license_id = isset($_GET['student_license_id']) ? $conn->real_escape_string($_GET['student_license_id']) : '';

// Fetch lesson details
$sql = "
    SELECT 
        u.name AS student_name, 
        l.license_name AS license_name,
        sl.license_id AS license_id,
        le.lesson_name
    FROM 
        student_licenses AS sl
    JOIN 
        users AS u ON u.user_id = (
            SELECT s.user_id 
            FROM students AS s 
            WHERE s.student_id = sl.student_id
        )
    JOIN 
        licenses AS l ON sl.license_id = l.license_id
    JOIN 
        lessons AS le ON sl.lesson_id = le.lesson_id
    WHERE 
        sl.student_license_id = '$student_license_id';
";
$lesson_result = $conn->query($sql);

if ($lesson_result->num_rows == 0) {
    echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Lesson not found!',
                confirmButtonText: 'Back to Lesson List'
            }).then(() => {
                window.location.href = 'list_lesson.php';
            });
          </script>";
    exit;
}

$lesson = $lesson_result->fetch_assoc();

// Fetch instructors for the form, excluding those already assigned to this license
$license_id = $lesson['license_id'];
$instructors = $conn->query("
    SELECT i.instructor_id, u.name 
    FROM users u 
    JOIN instructors i ON u.user_id = i.user_id 
    JOIN specialities s ON i.instructor_id = s.instructor_id 
    WHERE s.license_id = '$license_id'
");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $instructor_id = isset($_POST['instructor_id']) ? $_POST['instructor_id'] : '';

    // Validate inputs
    if (empty($instructor_id)) $errors[] = "Instructor is required.";

    // If no errors, proceed with database update
    if (empty($errors)) {
        $sql = "
            UPDATE student_lessons
            SET instructor_id = '$instructor_id'
            WHERE student_license_id = '$student_license_id' AND schedule_status = 'Unassigned';
        ";
        if ($conn->query($sql) === TRUE) {
            echo "<script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Instructor assigned successfully!',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.href = 'list_lesson.php';
                    });
                  </script>";
            exit;
        } else {
            $errors[] = "Error: " . $conn->error;
        }
    }
}

// Display validation errors using SweetAlert2
if (!empty($errors)) {
    $error_messages = implode("<br>", $errors);
    echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                html: '$error_messages',
                confirmButtonText: 'OK'
            });
          </script>";
}
?>

<div class="container">
    <div class="page-inner">
        <!-- Breadcrumbs -->
        <div class="page-header">
            <h4 class="page-title">Manage Lesson</h4>
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
                    <a href="/pages/admin/manage_lesson/list_lesson.php">Lesson List</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Assign Instructor</a>
                </li>
            </ul>
        </div>

        <div class="page-category">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="fw-bold mb-3 text-center">Assign Instructor</h3>
                        <p class="text-muted text-center">
                            <strong>Student Name:</strong> <?php echo $lesson['student_name']; ?><br>
                            <strong>Lesson Name:</strong> <?php echo $lesson['lesson_name']; ?><br>
                            <strong>License Name:</strong> <?php echo $lesson['license_name']; ?>
                        </p>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="form-group" style="width: 50%; margin: 0 auto;">
                                <label for="instructor_id" style="display: block; text-align: center;">Select an Instructor:</label>
                                <select class="form-control" id="instructor_id" name="instructor_id" required>
                                    <option value="">--Select an Instructor--</option>
                                    <?php while ($instructor = $instructors->fetch_assoc()): ?>
                                        <option value="<?php echo $instructor['instructor_id']; ?>"><?php echo $instructor['name']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary mt-3 d-block mx-auto">Assign Instructor</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../../include/footer.html'; ?>