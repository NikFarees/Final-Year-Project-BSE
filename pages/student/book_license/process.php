<?php
include '../../../include/st_header.php';
include '../../../database/db_connection.php';

// Validate session user ID
if (!isset($_SESSION['user_id'])) {
    echo "<div class='alert alert-danger text-center'>Session expired. Please log in again.</div>";
    exit;
}

// Fetch and validate POST data
$requiredFields = ['license_id', 'license_fee', 'lesson_id', 'lesson_fee', 'payment_option', 'test_ids', 'test_fees'];
foreach ($requiredFields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        echo "<div class='alert alert-danger text-center'>Missing required information. Please try again.</div>";
        exit;
    }
}

$license_id = $_POST['license_id'];
$license_fee = floatval($_POST['license_fee']);
$lesson_id = $_POST['lesson_id'];
$lesson_fee = floatval($_POST['lesson_fee']);
$payment_option = $_POST['payment_option'];
$test_ids = $_POST['test_ids'];
$test_fees = array_map('floatval', $_POST['test_fees']);

// Calculate total fees
$total_fees = $license_fee + $lesson_fee + array_sum($test_fees);

// Fetch user details
$user_id = $_SESSION['user_id'];
$student_sql = "SELECT student_id FROM students WHERE user_id = ?";
$student_stmt = $conn->prepare($student_sql);
$student_stmt->bind_param("s", $user_id);
$student_stmt->execute();
$student_result = $student_stmt->get_result();

if ($student_result->num_rows === 0) {
    echo "<div class='alert alert-danger text-center'>Student record not found. <a href='list_license.php' class='btn btn-primary btn-round mt-3'>Back to License List</a></div>";
    exit;
}

$student = $student_result->fetch_assoc();
$student_id = $student['student_id'];

// Begin transaction
$conn->begin_transaction();
try {
    // Set timezone and date format
    date_default_timezone_set('Asia/Kuala_Lumpur');
    $date_format = date("dmy");

    // Generate student_license_id
    $license_count_query = $conn->query("SELECT MAX(CAST(SUBSTRING(student_license_id, 6, 3) AS UNSIGNED)) AS max_id FROM student_licenses");
    $license_count_row = $license_count_query->fetch_assoc();
    $license_count = $license_count_row['max_id'] ? $license_count_row['max_id'] + 1 : 1;
    $student_license_id = sprintf('STLIC%03d%s', $license_count, $date_format);

    // Insert into student_licenses
    $insert_license_sql = "INSERT INTO student_licenses (student_license_id, lesson_id, student_id, license_id) VALUES (?, ?, ?, ?)";
    $insert_license_stmt = $conn->prepare($insert_license_sql);
    $insert_license_stmt->bind_param("ssss", $student_license_id, $lesson_id, $student_id, $license_id);
    $insert_license_stmt->execute();

    // Generate issued_license_id and insert into issued_licenses
    $issued_license_count_query = $conn->query("SELECT MAX(CAST(SUBSTRING(issued_license_id, 6, 2) AS UNSIGNED)) AS max_id FROM issued_licenses");
    $issued_license_count_row = $issued_license_count_query->fetch_assoc();
    $issued_license_count = $issued_license_count_row['max_id'] ? $issued_license_count_row['max_id'] + 1 : 1;
    $issued_license_id = sprintf('ISLIC%02d%s', $issued_license_count, $date_format);

    // Insert into issued_licenses with default Not Available status
    $insert_issued_license_sql = "INSERT INTO issued_licenses (issued_license_id, student_license_id, status) VALUES (?, ?, 'Not Available')";
    $insert_issued_license_stmt = $conn->prepare($insert_issued_license_sql);
    $insert_issued_license_stmt->bind_param("ss", $issued_license_id, $student_license_id);
    $insert_issued_license_stmt->execute();

    // Fetch license type
    $license_type_query = $conn->query("SELECT license_type FROM licenses WHERE license_id = '$license_id'");
    $license_type_row = $license_type_query->fetch_assoc();
    $license_type = $license_type_row['license_type'];

    // Generate and insert student_lessons
    $num_lessons = in_array($lesson_id, ['LES01', 'LES02']) ? 4 : (in_array($lesson_id, ['LES03', 'LES04']) ? 8 : 0);
    if ($num_lessons > 0) {
        $lesson_stmt = $conn->prepare("INSERT INTO student_lessons (student_lesson_id, student_license_id, student_lesson_name, status) VALUES (?, ?, ?, 'ineligible')");
        for ($i = 1; $i <= $num_lessons; $i++) {
            $lesson_count_query = $conn->query("SELECT MAX(CAST(SUBSTRING(student_lesson_id, 10, 3) AS UNSIGNED)) AS max_id FROM student_lessons WHERE SUBSTRING(student_lesson_id, 6, 3) = LPAD('$license_count', 3, '0')");
            $lesson_count_row = $lesson_count_query->fetch_assoc();
            $lesson_count = $lesson_count_row['max_id'] ? $lesson_count_row['max_id'] + 1 : 1;
            $student_lesson_id = sprintf('STLES%03d%03d', $license_count, $lesson_count);
            $student_lesson_name = sprintf('License %s Class %d', $license_type, $i);
            $lesson_stmt->bind_param("sss", $student_lesson_id, $student_license_id, $student_lesson_name);
            $lesson_stmt->execute();
        }
    }

    // Generate student_test_id and insert tests
    foreach ($test_ids as $index => $test_id) {
        $test_count_query = $conn->query("SELECT MAX(CAST(SUBSTRING(student_test_id, 6, 3) AS UNSIGNED)) AS max_id FROM student_tests");
        $test_count_row = $test_count_query->fetch_assoc();
        $test_count = $test_count_row['max_id'] ? $test_count_row['max_id'] + 1 : 1;
        $student_test_id = sprintf('STTES%03d%s', $test_count, $date_format);

        // Determine the status based on the test_id
        $status = ($test_id === 'TES01') ? 'Pending' : 'ineligible';

        // Insert the test with the appropriate status
        $insert_test_sql = "INSERT INTO student_tests (student_test_id, student_license_id, test_id, status) VALUES (?, ?, ?, ?)";
        $insert_test_stmt = $conn->prepare($insert_test_sql);
        $insert_test_stmt->bind_param("ssss", $student_test_id, $student_license_id, $test_id, $status);
        $insert_test_stmt->execute();
    }

    // Generate and insert payment
    $payment_count_query = $conn->query("SELECT MAX(CAST(SUBSTRING(payment_id, 4, 3) AS UNSIGNED)) AS max_id FROM payments");
    $payment_count_row = $payment_count_query->fetch_assoc();
    $payment_count = $payment_count_row['max_id'] ? $payment_count_row['max_id'] + 1 : 1;
    $payment_id = sprintf('PAY%03d%s', $payment_count, $date_format);

    $payment_type = 'Registration'; // Assuming this is the registration payment
    $payment_status = 'Completed';
    $insert_payment_sql = "INSERT INTO payments (payment_id, student_license_id, payment_type, payment_method, total_amount, payment_status) 
                       VALUES (?, ?, ?, ?, ?, ?)";
    $insert_payment_stmt = $conn->prepare($insert_payment_sql);
    $insert_payment_stmt->bind_param("ssssds", $payment_id, $student_license_id, $payment_type, $payment_option, $total_fees, $payment_status);
    $insert_payment_stmt->execute();

    // Generate and insert payment details
    $payment_detail_count_query = $conn->query("SELECT MAX(CAST(SUBSTRING(payment_detail_id, 8, 3) AS UNSIGNED)) AS max_id FROM payment_details");
    $payment_detail_count_row = $payment_detail_count_query->fetch_assoc();
    $payment_detail_count = $payment_detail_count_row['max_id'] ? $payment_detail_count_row['max_id'] + 1 : 1;

    // Insert license payment detail
    $payment_detail_id_license = sprintf('PDET%03d%03d', $payment_count, $payment_detail_count++);
    $insert_payment_detail_sql = "INSERT INTO payment_details (payment_detail_id, payment_id, item_type, item_id, amount) 
                               VALUES (?, ?, 'License', ?, ?)";
    $insert_payment_detail_stmt = $conn->prepare($insert_payment_detail_sql);
    $insert_payment_detail_stmt->bind_param("sssd", $payment_detail_id_license, $payment_id, $license_id, $license_fee);
    $insert_payment_detail_stmt->execute();

    $payment_detail_id_lesson = sprintf('PDET%03d%03d', $payment_count, $payment_detail_count++);
    $insert_payment_detail_sql = "INSERT INTO payment_details (payment_detail_id, payment_id, item_type, item_id, amount) 
                               VALUES (?, ?, 'Lesson', ?, ?)";
    $insert_payment_detail_stmt = $conn->prepare($insert_payment_detail_sql);
    $insert_payment_detail_stmt->bind_param("sssd", $payment_detail_id_lesson, $payment_id, $lesson_id, $lesson_fee);
    $insert_payment_detail_stmt->execute();

    foreach ($test_ids as $index => $test_id) {
        $payment_detail_id_test = sprintf('PDET%03d%03d', $payment_count, $payment_detail_count++);
        $test_fee = $test_fees[$index];
        $insert_payment_detail_sql = "INSERT INTO payment_details (payment_detail_id, payment_id, item_type, item_id, amount) 
                                       VALUES (?, ?, 'Test', ?, ?)";
        $insert_payment_detail_stmt = $conn->prepare($insert_payment_detail_sql);
        $insert_payment_detail_stmt->bind_param("sssd", $payment_detail_id_test, $payment_id, $test_id, $test_fee);
        $insert_payment_detail_stmt->execute();
    }

    // Commit transaction
    $conn->commit();

    // Store payment ID for potential use
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
                                    <p class="mt-3">Your license booking is being processed...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Redirect to receipt page after 2 seconds
        setTimeout(function() {
            window.location.href = 'receipt.php?payment_id=<?php echo $payment_id; ?>';
        }, 2000);
    </script>

<?php
} catch (Exception $e) {
    $conn->rollback();
    echo "<div class='container'><div class='page-inner'><div class='page-category'>
          <div class='alert alert-danger text-center'>
            <h4><i class='fas fa-exclamation-triangle'></i> Error</h4>
            <p>" . htmlspecialchars($e->getMessage()) . "</p>
            <a href='list_license.php' class='btn btn-primary mt-3'>Return to License List</a>
          </div>
          </div></div></div>";
}

include '../../../include/footer.html';
?>