<?php
include '../../../include/st_header.php';
include '../../../database/db_connection.php';

// Validate session user ID
if (!isset($_SESSION['user_id'])) {
    echo "<div class='alert alert-danger text-center'>Session expired. Please log in again.</div>";
    exit;
}

// Validate POST data
$requiredFields = ['student_license_id', 'lessons_needed', 'lesson_fee', 'payment_option'];
foreach ($requiredFields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        echo "<div class='alert alert-danger text-center'>Missing required information. Please try again.</div>";
        exit;
    }
}

$student_license_id = $_POST['student_license_id'];
$lessons_needed = intval($_POST['lessons_needed']);
$lesson_fee = floatval($_POST['lesson_fee']);
$payment_option = $_POST['payment_option'];
$total_fee = $lessons_needed * $lesson_fee;

// Get current user
$user_id = $_SESSION['user_id'];
$student_sql = "SELECT student_id FROM students WHERE user_id = ?";
$student_stmt = $conn->prepare($student_sql);
$student_stmt->bind_param("s", $user_id);
$student_stmt->execute();
$student_result = $student_stmt->get_result();

if ($student_result->num_rows === 0) {
    echo "<div class='alert alert-danger text-center'>Student not found. 
          <a href='list_lesson.php' class='btn btn-primary mt-3'>Back to Lesson List</a></div>";
    exit;
}

$student = $student_result->fetch_assoc();

// Start transaction
$conn->begin_transaction();
try {
    date_default_timezone_set('Asia/Kuala_Lumpur');
    $date_format = date("dmy");

    // Get license_id, license_type and lesson_id from student_license_id
    $license_sql = "SELECT sl.lesson_id, l.license_id, l.license_type 
                    FROM student_licenses sl 
                    JOIN licenses l ON sl.license_id = l.license_id 
                    WHERE sl.student_license_id = ?";
    $license_stmt = $conn->prepare($license_sql);
    $license_stmt->bind_param("s", $student_license_id);
    $license_stmt->execute();
    $license_result = $license_stmt->get_result();

    if ($license_result->num_rows === 0) {
        throw new Exception("License record not found.");
    }

    $license_row = $license_result->fetch_assoc();
    $license_id = $license_row['license_id'];
    $license_type = $license_row['license_type'];
    $lesson_id = $license_row['lesson_id'];

    // Count to generate IDs
    $license_count_query = $conn->query("SELECT MAX(CAST(SUBSTRING(student_license_id, 6, 3) AS UNSIGNED)) AS max_id FROM student_licenses");
    $license_count_row = $license_count_query->fetch_assoc();
    $license_count = $license_count_row['max_id'] ?: 1;

    // Get previous instructor_id for this student_license_id
    $instructor_sql = "SELECT instructor_id FROM student_lessons 
                            WHERE student_license_id = ? AND instructor_id IS NOT NULL 
                            ORDER BY created_at DESC LIMIT 1";
    $instructor_stmt = $conn->prepare($instructor_sql);
    $instructor_stmt->bind_param("s", $student_license_id);
    $instructor_stmt->execute();
    $instructor_result = $instructor_stmt->get_result();
    $instructor_id = null;
    if ($instructor_result->num_rows > 0) {
        $instructor_row = $instructor_result->fetch_assoc();
        $instructor_id = $instructor_row['instructor_id'];
    }

    // Get the count of EXTRA classes only for this license
    $extra_class_count_sql = "SELECT COUNT(*) as extra_count FROM student_lessons 
                             WHERE student_license_id = ? AND student_lesson_name LIKE '%Extra Class%'";
    $extra_class_count_stmt = $conn->prepare($extra_class_count_sql);
    $extra_class_count_stmt->bind_param("s", $student_license_id);
    $extra_class_count_stmt->execute();
    $extra_class_count_result = $extra_class_count_stmt->get_result();
    $extra_class_count_row = $extra_class_count_result->fetch_assoc();
    $extra_class_count = $extra_class_count_row['extra_count'];

    // Prepare insert statement with instructor_id and status = 'Pending'
    $lesson_stmt = $conn->prepare("INSERT INTO student_lessons 
                                           (student_lesson_id, student_license_id, instructor_id, student_lesson_name, status) 
                                           VALUES (?, ?, ?, ?, 'Pending')");

    // Create the specified number of lessons (based on lessons_needed)
    for ($i = 1; $i <= $lessons_needed; $i++) {
        $lesson_count_query = $conn->query("SELECT MAX(CAST(SUBSTRING(student_lesson_id, 10, 3) AS UNSIGNED)) AS max_id 
                     FROM student_lessons 
                     WHERE SUBSTRING(student_lesson_id, 6, 3) = LPAD('$license_count', 3, '0')");
        $lesson_count_row = $lesson_count_query->fetch_assoc();
        $lesson_count = $lesson_count_row['max_id'] ? $lesson_count_row['max_id'] + 1 : 1;
        $student_lesson_id = sprintf('STLES%03d%03d', $license_count, $lesson_count);
        
        // Name format: "License B Extra Class n" where n starts from (existing extra classes + 1)
        $student_lesson_name = sprintf('License %s Extra Class %d', $license_type, $extra_class_count + $i);

        $lesson_stmt->bind_param("ssss", $student_lesson_id, $student_license_id, $instructor_id, $student_lesson_name);
        $lesson_stmt->execute();
    }

    // Generate payment_id
    $payment_count_query = $conn->query("SELECT MAX(CAST(SUBSTRING(payment_id, 4, 3) AS UNSIGNED)) AS max_id FROM payments");
    $payment_count_row = $payment_count_query->fetch_assoc();
    $payment_count = $payment_count_row['max_id'] ? $payment_count_row['max_id'] + 1 : 1;
    $payment_id = sprintf('PAY%03d%s', $payment_count, $date_format);

    $payment_type = 'Extra Lesson';
    $payment_status = 'Completed';

    // Insert payment
    $insert_payment_sql = "INSERT INTO payments (payment_id, student_license_id, payment_type, payment_method, total_amount, payment_status) 
                           VALUES (?, ?, ?, ?, ?, ?)";
    $insert_payment_stmt = $conn->prepare($insert_payment_sql);
    $insert_payment_stmt->bind_param("ssssds", $payment_id, $student_license_id, $payment_type, $payment_option, $total_fee, $payment_status);
    $insert_payment_stmt->execute();

    // Generate and insert payment_detail
    $payment_detail_count_query = $conn->query("SELECT MAX(CAST(SUBSTRING(payment_detail_id, 8, 3) AS UNSIGNED)) AS max_id FROM payment_details");
    $payment_detail_count_row = $payment_detail_count_query->fetch_assoc();
    $payment_detail_count = $payment_detail_count_row['max_id'] ? $payment_detail_count_row['max_id'] + 1 : 1;
    $payment_detail_id = sprintf('PDET%03d%03d', $payment_count, $payment_detail_count);

    $insert_detail_sql = "INSERT INTO payment_details (payment_detail_id, payment_id, item_type, item_id, amount) 
                          VALUES (?, ?, 'Lesson', ?, ?)";
    $insert_detail_stmt = $conn->prepare($insert_detail_sql);
    $insert_detail_stmt->bind_param("sssd", $payment_detail_id, $payment_id, $lesson_id, $total_fee);
    $insert_detail_stmt->execute();

    // Commit
    $conn->commit();
    $_SESSION['last_payment_id'] = $payment_id;
?>

    <div class="container">
        <div class="page-inner">
            <div class="page-category">
                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6">
                        <div class="card shadow-lg">
                            <div class="card-header text-center">
                                <h3>Processing Payment</h3>
                                <p class="text-muted">Please do not refresh this page.</p>
                            </div>
                            <div class="card-body text-center">
                                <div class="my-5">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                    <p class="mt-3">Your <?php echo $lessons_needed; ?> extra lesson(s) booking is being processed...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        setTimeout(function() {
            window.location.href = 'add_lesson_receipt.php?payment_id=<?php echo $payment_id; ?>';
        }, 2000);
    </script>

<?php
} catch (Exception $e) {
    $conn->rollback();
    echo "<div class='alert alert-danger text-center'><h4>Error</h4><p>" . htmlspecialchars($e->getMessage()) . "</p>
          <a href='list_lesson.php' class='btn btn-primary mt-3'>Return to Lesson List</a></div>";
}

include '../../../include/footer.html';
?>