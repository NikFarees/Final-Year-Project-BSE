<?php
include '../../../include/st_header.php';
include '../../../database/db_connection.php';

// Check if required POST data exists
if (!isset($_POST['student_license_id']) || !isset($_POST['lessons_needed'])) {
    echo "<script>alert('Missing required information'); window.location.href='list_lesson.php';</script>";
    exit;
}

$student_license_id = $_POST['student_license_id'];
$lessons_needed = (int)$_POST['lessons_needed'];
$user_id = $_SESSION['user_id'] ?? null;

// Verify ownership
$verify_sql = "SELECT sl.student_license_id, s.student_id, sl.license_id, l.license_name, l.license_type, les.lesson_id, les.lesson_name, les.lesson_fee
               FROM student_licenses sl
               JOIN students s ON sl.student_id = s.student_id
               JOIN licenses l ON sl.license_id = l.license_id
               JOIN lessons les ON sl.lesson_id = les.lesson_id
               WHERE sl.student_license_id = ? AND s.user_id = ?";
$stmt = $conn->prepare($verify_sql);
$stmt->bind_param("ss", $student_license_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<script>alert('You are not authorized to add lessons for this license'); window.location.href='list_lesson.php';</script>";
    exit;
}

$data = $result->fetch_assoc();
// Fixed price for additional lessons is RM50
$additional_lesson_fee = 50;
$total_fee = $lessons_needed * $additional_lesson_fee;
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title">My Lesson</h4>
            <ul class="breadcrumbs">
                <li class="nav-home"><a href="/pages/student/dashboard.php"><i class="icon-home"></i></a></li>
                <li class="separator"><i class="icon-arrow-right"></i></li>
                <li class="nav-item"><a href="/pages/student/manage_lesson/list_lesson.php">Lesson List</a></li>
                <li class="separator"><i class="icon-arrow-right"></i></li>
                <li class="nav-item"><a href="#">Confirm Add Lesson</a></li>
            </ul>
        </div>

        <div class="page-category">
            <div class="card">
                <div class="card-header text-center">
                    <h2>Confirm Lesson Addition</h2>
                    <p class="text-muted">Please confirm the lesson details and payment</p>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-4">
                        <i class="fas fa-info-circle"></i> You are about to add <strong><?php echo $lessons_needed; ?></strong> lesson(s). A lesson fee applies.
                    </div>

                    <form method="POST" action="process_add_lesson.php">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="license">License</label>
                                <input type="text" class="form-control" id="license" 
                                       value="<?php echo $data['license_name'] . ' (' . $data['license_type'] . ')'; ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label for="lesson_name">Lesson Name</label>
                                <input type="text" class="form-control" id="lesson_name" 
                                       value="<?php echo $data['lesson_name']; ?>" readonly>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="lesson_fee">Additional Lesson Fee (Each)</label>
                                <input type="text" class="form-control" id="lesson_fee" 
                                       value="RM <?php echo number_format($additional_lesson_fee, 2); ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label for="total_fee">Total Fee</label>
                                <input type="text" class="form-control" id="total_fee" 
                                       value="RM <?php echo number_format($total_fee, 2); ?>" readonly>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="payment_option">Payment Option</label>
                            <select class="form-control" id="payment_option" name="payment_option" required>
                                <option value="" disabled selected>Select Payment Option</option>
                                <option value="FPX (Bank Transfer)">FPX (Bank Transfer)</option>
                                <option value="Debit Card">Debit Card</option>
                            </select>
                        </div>

                        <!-- Hidden fields -->
                        <input type="hidden" name="student_license_id" value="<?php echo $student_license_id; ?>">
                        <input type="hidden" name="lessons_needed" value="<?php echo $lessons_needed; ?>">
                        <input type="hidden" name="lesson_fee" value="<?php echo $additional_lesson_fee; ?>">
                        <input type="hidden" name="total_fee" value="<?php echo $total_fee; ?>">

                        <!-- Buttons -->
                        <div class="text-center mt-4">
                            <a href="list_lesson.php" class="btn btn-danger mr-2">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-success">
                                Confirm & Pay
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header"><h4>Lesson Info</h4></div>
                <div class="card-body">
                    <p>When adding lessons:</p>
                    <ul>
                        <li>You will be charged RM50 for each additional lesson</li>
                        <li>Lessons will be scheduled by admin</li>
                        <li>You will be notified once sessions are assigned</li>
                        <li>Unattended lessons may result in rescheduling or penalties</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Form validation -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelector('form').addEventListener('submit', function(e) {
            const paymentOption = document.getElementById('payment_option').value;
            if (!paymentOption) {
                e.preventDefault();
                alert('Please select a payment option');
            }
        });
    });
</script>

<?php include '../../../include/footer.html'; ?>