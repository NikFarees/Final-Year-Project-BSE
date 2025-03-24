<?php
include '../../../include/st_header.php';
include '../../../database/db_connection.php'; // Include the database connection file

// Get the license ID from the query parameter
$license_id = $_GET['id'];

// Fetch license details from the database
$sql = "SELECT * FROM licenses WHERE license_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $license_id);
$stmt->execute();
$license_result = $stmt->get_result();

if ($license_result->num_rows == 0) {
    echo "<div class='alert alert-danger text-center'>License not found. <a href='list_license.php' class='btn btn-primary btn-round mt-3'>Back to License List</a></div>";
    exit;
}

$license = $license_result->fetch_assoc();

// Fetch lesson types from the database
$lesson_sql = "SELECT * FROM lessons";
$lesson_result = $conn->query($lesson_sql);

// Fetch student ID from the users table using the user ID stored in the session
$user_id = $_SESSION['user_id'];
$student_sql = "SELECT student_id FROM students WHERE user_id = ?";
$student_stmt = $conn->prepare($student_sql);
$student_stmt->bind_param("s", $user_id);
$student_stmt->execute();
$student_result = $student_stmt->get_result();

if ($student_result->num_rows == 0) {
    echo "<div class='alert alert-danger text-center'>Student not found. <a href='list_license.php' class='btn btn-primary btn-round mt-3'>Back to License List</a></div>";
    exit;
}

$student = $student_result->fetch_assoc();
$student_id = $student['student_id'];

// Initialize error messages
$errors = [];
$successMessage = "";
?>

<div class="container">
    <div class="page-inner">

        <!-- Breadcrumbs -->
        <div class="page-header">
            <h4 class="page-title">Dashboard</h4>
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
                    <a href="/pages/student/book_license/list_license.php">License List</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Book License</a>
                </li>
            </ul>
        </div>

        <!-- Inner page content -->
        <div class="page-category">

            <div class="row justify-content-center">
                <div class="col-md-12">
                    <div class="card card-post card-round">
                        <div class="card-header text-center">
                            <h3 class="fw-bold mb-3 text-center">Book License</h3>
                            <p class="text-muted text-center">Select Lesson Type</p>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger">
                                    <ul>
                                        <?php foreach ($errors as $error): ?>
                                            <li><?php echo $error; ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            <form method="POST" action="confirm_booking.php">
                                <div class="form-group">
                                    <label for="license_name">License Name</label>
                                    <input type="text" class="form-control" id="license_name" name="license_name" value="<?php echo $license['license_name']; ?>" readonly>
                                    <input type="hidden" name="license_id" value="<?php echo $license_id; ?>" /> <!-- License ID -->
                                </div>
                                <div class="form-group">
                                    <label for="lesson_id">Lesson Type</label>
                                    <select class="form-control" id="lesson_id" name="lesson_id" required onchange="calculateFees()">
                                        <option value="">Select Lesson Type</option>
                                        <?php while ($lesson = $lesson_result->fetch_assoc()): ?>
                                            <option value="<?php echo $lesson['lesson_id']; ?>" data-fee="<?php echo $lesson['lesson_fee']; ?>">
                                                <?php echo $lesson['lesson_name']; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="total_fees">Total Fees (RM)</label>
                                    <input type="text" class="form-control" id="total_fees" name="total_fees" readonly>
                                </div>
                                <button type="submit" class="btn btn-primary mt-3 d-block mx-auto">Proceed to Confirmation</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>

<script>
    // JavaScript to calculate total fees dynamically
    function calculateFees() {
        const lessonSelect = document.getElementById('lesson_id');
        const selectedOption = lessonSelect.options[lessonSelect.selectedIndex];
        const lessonFee = parseFloat(selectedOption.getAttribute('data-fee')) || 0;

        // Update the total fee input field with the lesson fee
        document.getElementById('total_fees').value = lessonFee.toFixed(2);
    }
</script>

<?php
include '../../../include/footer.html';
?>