<?php
include '../../../include/ad_header.php';
include '../../../database/db_connection.php';

// Get payment ID from query parameter
$payment_id = isset($_GET['id']) ? $_GET['id'] : '';

// Fetch payment details
$paymentQuery = "
    SELECT 
        p.payment_id,
        p.student_license_id,
        p.payment_type,
        p.payment_method,
        p.total_amount,
        p.payment_datetime,
        p.payment_status,
        u.name AS student_name
    FROM 
        payments AS p
    JOIN 
        student_licenses AS sl ON p.student_license_id = sl.student_license_id
    JOIN 
        students AS s ON sl.student_id = s.student_id
    JOIN 
        users AS u ON s.user_id = u.user_id
    WHERE 
        p.payment_id = ?
";
$stmt = $conn->prepare($paymentQuery);
$stmt->bind_param("s", $payment_id);
$stmt->execute();
$paymentResult = $stmt->get_result();

if ($paymentResult->num_rows == 0) {
    echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Payment not found!',
                confirmButtonText: 'Back to Manage Payment'
            }).then(() => {
                window.location.href = 'list_payment.php';
            });
          </script>";
    exit;
}

$payment = $paymentResult->fetch_assoc();

// Fetch payment details items
$paymentDetailsQuery = "
    SELECT 
        pd.payment_detail_id,
        pd.item_type,
        pd.item_id,
        pd.amount,
        CASE 
            WHEN pd.item_type = 'License' THEN l.license_name
            WHEN pd.item_type = 'Lesson' THEN le.lesson_name
            WHEN pd.item_type = 'Test' THEN t.test_name
        END AS item_name
    FROM 
        payment_details AS pd
    LEFT JOIN 
        licenses AS l ON pd.item_id = l.license_id
    LEFT JOIN 
        lessons AS le ON pd.item_id = le.lesson_id
    LEFT JOIN 
        tests AS t ON pd.item_id = t.test_id
    WHERE 
        pd.payment_id = ?
";
$stmt = $conn->prepare($paymentDetailsQuery);
$stmt->bind_param("s", $payment_id);
$stmt->execute();
$paymentDetailsResult = $stmt->get_result();
?>

<div class="container">
    <div class="page-inner">

        <!-- Breadcrumbs -->
        <div class="page-header">
            <h4 class="page-title">Payment Detail</h4>
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
                    <a href="/pages/admin/manage_payment/list_payment.php">Payment List</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Payment Detail</a>
                </li>
            </ul>
        </div>

        <!-- Inner page content -->
        <div class="page-category">
            <div class="card">
                <div class="card-header">
                    <h3 class="fw-bold mb-3">Payment Detail</h3>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="payment_id">Payment ID</label>
                            <input type="text" class="form-control" id="payment_id" value="<?php echo htmlspecialchars($payment['payment_id']); ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="student_name">Student Name</label>
                            <input type="text" class="form-control" id="student_name" value="<?php echo htmlspecialchars($payment['student_name']); ?>" readonly>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="payment_type">Payment Type</label>
                            <input type="text" class="form-control" id="payment_type" value="<?php echo htmlspecialchars($payment['payment_type']); ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="payment_method">Payment Method</label>
                            <input type="text" class="form-control" id="payment_method" value="<?php echo htmlspecialchars($payment['payment_method']); ?>" readonly>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="total_amount">Total Amount</label>
                            <input type="text" class="form-control" id="total_amount" value="<?php echo htmlspecialchars($payment['total_amount']); ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="payment_datetime">Payment Date</label>
                            <input type="text" class="form-control" id="payment_datetime" value="<?php echo htmlspecialchars($payment['payment_datetime']); ?>" readonly>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="payment_status">Payment Status</label>
                            <input type="text" class="form-control" id="payment_status" value="<?php echo htmlspecialchars($payment['payment_status']); ?>" readonly>
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label for="payment_details">Payment Details</label>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Item Type</th>
                                        <th>Item Name</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($paymentDetailsResult->num_rows > 0) {
                                        while ($detail = $paymentDetailsResult->fetch_assoc()) {
                                            echo "<tr>
                              <td>{$detail['item_type']}</td>
                              <td>{$detail['item_name']}</td>
                              <td>{$detail['amount']}</td>
                            </tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='3'>No payment details found.</td></tr>";
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
</div>

<?php
include '../../../include/footer.html';
?>