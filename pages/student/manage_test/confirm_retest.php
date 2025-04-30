<?php
include '../../../include/st_header.php';
include '../../../database/db_connection.php';

// Check if required parameters exist
if (!isset($_GET['test_id']) || !isset($_GET['student_license_id'])) {
    echo "<script>alert('Missing required information'); window.location.href='list_test.php';</script>";
    exit;
}

// Get parameters
$test_id = $_GET['test_id'];
$student_license_id = $_GET['student_license_id'];
$user_id = $_SESSION['user_id'] ?? null;

// Verify user owns this license
$verify_sql = "SELECT sl.student_license_id, s.student_id, sl.license_id, l.license_name, l.license_type 
               FROM student_licenses sl
               JOIN students s ON sl.student_id = s.student_id
               JOIN licenses l ON sl.license_id = l.license_id
               WHERE sl.student_license_id = ? AND s.user_id = ?";
$stmt = $conn->prepare($verify_sql);
$stmt->bind_param("ss", $student_license_id, $user_id);
$stmt->execute();
$license_result = $stmt->get_result();

if ($license_result->num_rows === 0) {
    echo "<script>alert('You are not authorized to schedule this retest'); window.location.href='list_test.php';</script>";
    exit;
}

$license = $license_result->fetch_assoc();

// Get test details
$test_sql = "SELECT * FROM tests WHERE test_id = ?";
$stmt = $conn->prepare($test_sql);
$stmt->bind_param("s", $test_id);
$stmt->execute();
$test_result = $stmt->get_result();

if ($test_result->num_rows === 0) {
    echo "<script>alert('Test not found'); window.location.href='list_test.php';</script>";
    exit;
}

$test = $test_result->fetch_assoc();
$test_fee = $test['test_fee'];
?>

<div class="container">
    <div class="page-inner">
        <!-- Breadcrumbs -->
        <div class="page-header">
            <h4 class="page-title">Confirm Retest</h4>
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
                    <a href="#">Confirm Retest</a>
                </li>
            </ul>
        </div>

        <!-- Inner page content -->
        <div class="page-category">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header text-center">
                            <h2>Confirm Retest Booking</h2>
                            <p class="text-muted">Please review the details below to confirm your retest</p>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info mb-4">
                                <i class="fas fa-info-circle"></i> You are scheduling a retest for a previously failed test. A retest fee applies.
                            </div>

                            <form method="POST" action="process_retest.php">
                                <!-- Test Details -->
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="license_details">License</label>
                                        <input type="text" class="form-control" id="license_details" 
                                            value="<?php echo $license['license_name'] . ' (' . $license['license_type'] . ')'; ?>" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="test_name">Test Name</label>
                                        <input type="text" class="form-control" id="test_name" name="test_name" 
                                            value="<?php echo $test['test_name']; ?>" readonly>
                                    </div>
                                </div>

                                <!-- Fee Details -->
                                <div class="row mb-4">
                                    <div class="col-md-12">
                                        <label for="test_fee">Retest Fee</label>
                                        <input type="text" class="form-control" id="test_fee" 
                                            value="RM <?php echo number_format($test_fee, 2); ?>" readonly>
                                    </div>
                                </div>

                                <hr>

                                <!-- Payment Option -->
                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <label for="payment_option">Payment Option</label>
                                        <select class="form-control" id="payment_option" name="payment_option" required>
                                            <option value="" disabled selected>Select Payment Option</option>
                                            <option value="FPX (Bank Transfer)">FPX (Bank Transfer)</option>
                                            <option value="Debit Card">Debit Card</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Hidden fields -->
                                <input type="hidden" name="test_id" value="<?php echo $test_id; ?>">
                                <input type="hidden" name="student_license_id" value="<?php echo $student_license_id; ?>">
                                <input type="hidden" name="test_fee" value="<?php echo $test_fee; ?>">

                                <!-- Submit and cancel buttons -->
                                <div class="text-center mt-4">
                                    <a href="list_test.php" class="btn btn-danger mr-2">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-check"></i> Confirm & Pay
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Additional information card -->
                    <div class="card mt-3">
                        <div class="card-header">
                            <h4>About Retests</h4>
                        </div>
                        <div class="card-body">
                            <p>When you schedule a retest:</p>
                            <ul>
                                <li>You will need to pay the test fee again</li>
                                <li>A new test session will be assigned to you</li>
                                <li>You should prepare adequately to ensure you pass this time</li>
                                <li>Your previous test results will remain in your records</li>
                                <li>If you pass the retest, your license progress will continue</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Form validation script -->
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

<?php
include '../../../include/footer.html';
?>