<?php
include '../../../include/st_header.php';
include '../../../database/db_connection.php';

$currentUserId = $_SESSION['user_id']; // Assuming the user ID is stored in the session

// Fetch payments for the current student
$paymentsQuery = "
    SELECT 
        p.payment_id,
        p.student_license_id,
        p.payment_type,
        p.payment_method,
        p.total_amount,
        p.payment_datetime,
        p.payment_status
    FROM 
        payments AS p
    JOIN 
        student_licenses AS sl ON p.student_license_id = sl.student_license_id
    JOIN 
        students AS s ON sl.student_id = s.student_id
    WHERE 
        s.user_id = ?
";
$paymentsStmt = $conn->prepare($paymentsQuery);
$paymentsStmt->bind_param("s", $currentUserId);
$paymentsStmt->execute();
$paymentsResult = $paymentsStmt->get_result();

// Fetch refund requests for the current student
$refundRequestsQuery = "
    SELECT 
        rr.refund_request_id,
        rr.payment_id,
        rr.request_datetime,
        rr.reason,
        rr.status
    FROM 
        refund_requests AS rr
    JOIN 
        payments AS p ON rr.payment_id = p.payment_id
    JOIN 
        student_licenses AS sl ON p.student_license_id = sl.student_license_id
    JOIN 
        students AS s ON sl.student_id = s.student_id
    WHERE 
        s.user_id = ?
";
$refundRequestsStmt = $conn->prepare($refundRequestsQuery);
$refundRequestsStmt->bind_param("s", $currentUserId);
$refundRequestsStmt->execute();
$refundRequestsResult = $refundRequestsStmt->get_result();
?>

<div class="container">
    <div class="page-inner">

        <!-- Breadcrumbs -->
        <div class="page-header">
            <h4 class="page-title">My Payment</h4>
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
                    <a href="#">Payment & Refund Request List</a>
                </li>
            </ul>
        </div>

        <!-- Inner page content -->
        <div class="page-category">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title">Payment & Refund Request List</h4>
                        <div class="ms-md-auto py-2 py-md-0">
                            <a href="#" class="btn btn-primary btn-round">Request Refund (X)</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-tabs nav-line nav-color-secondary" id="line-tab" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="line-payments-tab" data-bs-toggle="pill" href="#line-payments" role="tab" aria-controls="line-payments" aria-selected="true">Payments</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="line-refunds-tab" data-bs-toggle="pill" href="#line-refunds" role="tab" aria-controls="line-refunds" aria-selected="false">Refund Requests</a>
                            </li>
                        </ul>
                        <div class="tab-content mt-3 mb-3" id="line-tabContent">

                            <!-- Payments -->
                            <div class="tab-pane fade show active" id="line-payments" role="tabpanel" aria-labelledby="line-payments-tab">
                                <div class="table-responsive">
                                    <table id="payments-table" class="table table-bordered table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Payment Type</th>
                                                <th>Payment Method</th>
                                                <th>Total Amount</th>
                                                <th>Payment Date</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            if ($paymentsResult->num_rows > 0) {
                                                $counter = 1;
                                                while ($row = $paymentsResult->fetch_assoc()) {
                                                    echo "<tr class='clickable-row' data-href='view_payment.php?id=" . $row['payment_id'] . "'>";
                                                    echo "<td>" . $counter++ . "</td>";
                                                    echo "<td>" . $row['payment_type'] . "</td>";
                                                    echo "<td>" . $row['payment_method'] . "</td>";
                                                    echo "<td>" . $row['total_amount'] . "</td>";
                                                    echo "<td>" . $row['payment_datetime'] . "</td>";
                                                    echo "<td>" . $row['payment_status'] . "</td>";
                                                    echo "</tr>";
                                                }
                                            } else {
                                                echo "<tr><td colspan='6'>No payments found.</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Refund Requests -->
                            <div class="tab-pane fade" id="line-refunds" role="tabpanel" aria-labelledby="line-refunds-tab">
                                <div class="table-responsive">
                                    <table id="refunds-table" class="table table-bordered table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Payment ID</th>
                                                <th>Request Date</th>
                                                <th>Reason</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            if ($refundRequestsResult->num_rows > 0) {
                                                $counter = 1;
                                                while ($row = $refundRequestsResult->fetch_assoc()) {
                                                    echo "<tr class='clickable-row' data-href='view_refund.php?id=" . $row['refund_request_id'] . "'>";
                                                    echo "<td>" . $counter++ . "</td>";
                                                    echo "<td>" . $row['payment_id'] . "</td>";
                                                    echo "<td>" . $row['request_datetime'] . "</td>";
                                                    echo "<td>" . $row['reason'] . "</td>";
                                                    echo "<td>" . $row['status'] . "</td>";
                                                    echo "</tr>";
                                                }
                                            } else {
                                                echo "<tr><td colspan='5'>No refund requests found.</td></tr>";
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
    </div>
</div>

<?php
include '../../../include/footer.html';
?>

<script>
    $(document).ready(function() {
        $("#payments-table").DataTable({});

        // Make entire row clickable
        $(".clickable-row").click(function() {
            window.location = $(this).data("href");
        });
    });

    $(document).ready(function() {
        $("#refunds-table").DataTable({});
    });
</script>

<style>
    .clickable-row {
        cursor: pointer;
    }
</style>