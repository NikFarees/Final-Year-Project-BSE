<?php
include '../../../include/st_header.php';
include '../../../database/db_connection.php';

// Fetch form data
$license_id = $_POST['license_id'];
$lesson_id = $_POST['lesson_id'];

// Fetch license and lesson details from the database
$license_sql = "SELECT * FROM licenses WHERE license_id = ?";
$stmt = $conn->prepare($license_sql);
$stmt->bind_param("s", $license_id);
$stmt->execute();
$license_result = $stmt->get_result();
$license = $license_result->fetch_assoc();
$license_fee = $license['license_fee'];

$lesson_sql = "SELECT * FROM lessons WHERE lesson_id = ?";
$stmt = $conn->prepare($lesson_sql);
$stmt->bind_param("s", $lesson_id);
$stmt->execute();
$lesson_result = $stmt->get_result();
$lesson = $lesson_result->fetch_assoc();
$lesson_fee = $lesson['lesson_fee'];

// Fetch test details from the database
$test_sql = "SELECT * FROM tests";
$test_result = $conn->query($test_sql);
$test_fees = 0;
$tests = [];
while ($test = $test_result->fetch_assoc()) {
    $test_fees += $test['test_fee'];
    $tests[] = $test;
}

// Calculate total fees
$total_fees = $license_fee + $lesson_fee + $test_fees;
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
                    <a href="/pages/student/book_license/book_license.php?id=<?php echo urlencode($license_id); ?>">Book License</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Confirm Booking</a>
                </li>
            </ul>
        </div>

        <!-- Inner page content -->
        <div class="page-category">

            <div class="col-md-12">
                <div class="card">
                    <div class="card-header text-center">
                        <h2>Confirm Booking</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="process.php">

                            <!-- Row 1: License Name and License Fee -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="license_name">License Name</label>
                                    <input type="text" class="form-control" id="license_name" name="license_name" value="<?php echo $license['license_name']; ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label for="license_fee">License Fee</label>
                                    <input type="text" class="form-control" id="license_fee" value="RM <?php echo number_format($license_fee, 2); ?>" readonly>
                                </div>
                            </div>

                            <!-- Row 2: Lesson Type and Lesson Fee -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="lesson_name">Lesson Type</label>
                                    <input type="text" class="form-control" id="lesson_name" value="<?php echo $lesson['lesson_name']; ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label for="lesson_fee">Lesson Fee</label>
                                    <input type="text" class="form-control" id="lesson_fee" value="RM <?php echo number_format($lesson_fee, 2); ?>" readonly>
                                </div>
                            </div>

                            <!-- Row 3: Test Fees -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="test_name">Test Name</label>
                                </div>
                                <div class="col-md-6">
                                    <label for="test_fee">Test Fee</label>
                                </div>
                            </div>
                            <?php foreach ($tests as $test): ?>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" id="test_name_<?php echo $test['test_id']; ?>" value="<?php echo $test['test_name']; ?>" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" id="test_fee_<?php echo $test['test_id']; ?>" value="RM <?php echo number_format($test['test_fee'], 2); ?>" readonly>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <!-- Row 4: Total Fee -->
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="total_fees">Total Fees</label>
                                    <input type="text" class="form-control" id="total_fees" value="RM <?php echo number_format($total_fees, 2); ?>" readonly>
                                </div>
                            </div>

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

                            <br>
                            <!-- Pass license details -->
                            <input type="hidden" name="license_id" value="<?php echo $license_id; ?>">
                            <input type="hidden" name="license_fee" value="<?php echo $license_fee; ?>">

                            <!-- Pass lesson details -->
                            <input type="hidden" name="lesson_id" value="<?php echo $lesson_id; ?>">
                            <input type="hidden" name="lesson_fee" value="<?php echo $lesson_fee; ?>">

                            <!-- Pass test details -->
                            <?php foreach ($tests as $test): ?>
                                <input type="hidden" name="test_ids[]" value="<?php echo $test['test_id']; ?>">
                                <input type="hidden" name="test_fees[]" value="<?php echo $test['test_fee']; ?>">
                            <?php endforeach; ?>

                            <!-- Pass payment option -->
                            <input type="hidden" id="payment_option_hidden" name="payment_option" value="">

                            <!-- Submit and cancel buttons -->
                            <div class="text-center">
                                <a href="list_license.php" class="btn btn-danger">Cancel</a>
                                <button type="submit" class="btn btn-success">Confirm Booking</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>

<!-- Add this JavaScript to update the input field with selected dropdown value -->
<script>
    // Update the hidden input field when a payment option is selected
    document.getElementById('payment_option').addEventListener('change', function() {
        document.getElementById('payment_option_hidden').value = this.value;
    });

    // Prevent form submission if no payment option is selected
    document.querySelector('form').addEventListener('submit', function(e) {
        if (!document.getElementById('payment_option_hidden').value) {
            alert("Please select a payment option.");
            e.preventDefault(); // Stop form submission
        }
    });
</script>

<?php
include '../../../include/footer.html';
?>