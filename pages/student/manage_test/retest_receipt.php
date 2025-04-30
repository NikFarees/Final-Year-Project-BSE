<?php
include '../../../include/st_header.php';
include '../../../database/db_connection.php';

// Validate session user ID
if (!isset($_SESSION['user_id'])) {
    echo "<div class='alert alert-danger text-center'>Session expired. Please log in again.</div>";
    exit;
}

// Check if payment_id exists in GET parameters or session
$payment_id = $_GET['payment_id'] ?? $_SESSION['last_payment_id'] ?? null;

if (!$payment_id) {
    echo "<div class='alert alert-danger text-center'>No payment information found.
          <a href='list_test.php' class='btn btn-primary btn-round mt-3'>Back to Test List</a></div>";
    exit;
}

// Get payment details
$payment_sql = "SELECT p.*, pd.item_id, pd.amount, t.test_name, 
                sl.license_id, l.license_name, l.license_type 
                FROM payments p
                JOIN payment_details pd ON p.payment_id = pd.payment_id
                JOIN tests t ON pd.item_id = t.test_id
                JOIN student_licenses sl ON p.student_license_id = sl.student_license_id
                JOIN licenses l ON sl.license_id = l.license_id
                WHERE p.payment_id = ?";
$payment_stmt = $conn->prepare($payment_sql);
$payment_stmt->bind_param("s", $payment_id);
$payment_stmt->execute();
$payment_result = $payment_stmt->get_result();

if ($payment_result->num_rows === 0) {
    echo "<div class='alert alert-danger text-center'>Payment record not found.
          <a href='list_test.php' class='btn btn-primary btn-round mt-3'>Back to Test List</a></div>";
    exit;
}

$payment_data = $payment_result->fetch_assoc();
$test_name = $payment_data['test_name'];
$test_fee = $payment_data['amount'];
$payment_method = $payment_data['payment_method'];
$payment_date = $payment_data['payment_datetime'];
$license_name = $payment_data['license_name'];
$license_type = $payment_data['license_type'];
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
        </div>

        <div class="page-category">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="card shadow-lg">
                        <div class="card-header bg-success text-white text-center">
                            <i class="fas fa-check-circle fa-3x my-3"></i>
                            <h3>Retest Booking Successful</h3>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <h5>Your retest for <?php echo htmlspecialchars($test_name); ?> has been successfully booked.</h5>
                                <p class="text-muted">Payment Reference: <?php echo $payment_id; ?></p>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Your retest payment has been completed. An administrator will schedule your test session soon and you will be notified.
                            </div>
                            
                            <div class="table-responsive mb-4">
                                <table class="table table-bordered">
                                    <tbody>
                                        <tr>
                                            <th>License</th>
                                            <td><?php echo htmlspecialchars($license_name . ' (' . $license_type . ')'); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Test</th>
                                            <td><?php echo htmlspecialchars($test_name); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Amount Paid</th>
                                            <td>RM <?php echo number_format($test_fee, 2); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Payment Method</th>
                                            <td><?php echo htmlspecialchars($payment_method); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Date</th>
                                            <td><?php echo date('d M Y, h:i A', strtotime($payment_date)); ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="text-center mt-4">
                                <a href="list_test.php" class="btn btn-primary mr-2">
                                    </i> Ok
                                </a>
                                <button onclick="window.print()" class="btn btn-secondary">
                                    <i class="fas fa-print"></i> Print Receipt
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .navbar, .sidebar, .footer, .breadcrumbs, .btn {
        display: none !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    .card-header {
        color: #000 !important;
        background-color: #fff !important;
    }
}
</style>

<?php
include '../../../include/footer.html';
?>