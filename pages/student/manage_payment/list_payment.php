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
$paymentsCount = $paymentsResult->num_rows;

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
$refundRequestsCount = $refundRequestsResult->num_rows;
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
                        <div class="card-title">Payment & Refund Request List</div>
                        <div class="d-flex align-items-center">
                            <a href="#" class="btn btn-primary mr-3">Request Refund (X)</a>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="payment-toggle-btn">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body" id="payment-card-body">
                        <!-- Toggle Cards -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card card-stats card-round toggle-card active" data-target="payments-container">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-5">
                                                <div class="icon-big text-center">
                                                    <i class="fas fa-credit-card text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="col-7 col-stats">
                                                <div class="numbers">
                                                    <p class="card-category">Payments</p>
                                                    <h4 class="card-title"><?php echo $paymentsCount; ?></h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card card-stats card-round toggle-card" data-target="refunds-container">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-5">
                                                <div class="icon-big text-center">
                                                    <i class="fas fa-undo-alt text-warning"></i>
                                                </div>
                                            </div>
                                            <div class="col-7 col-stats">
                                                <div class="numbers">
                                                    <p class="card-category">Refund Requests</p>
                                                    <h4 class="card-title"><?php echo $refundRequestsCount; ?></h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payments -->
                        <div class="table-container" id="payments-container">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="mb-0"><i class="fas fa-credit-card text-primary"></i> Payments</h4>
                            </div>
                            <div class="table-responsive">
                                <table id="payments-table" class="table table-bordered table-striped ">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Payment Type</th>
                                            <th>Payment Method</th>
                                            <th>Total Amount</th>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if ($paymentsCount > 0) {
                                            mysqli_data_seek($paymentsResult, 0); // Reset pointer
                                            $counter = 1;
                                            while ($row = $paymentsResult->fetch_assoc()) {
                                                echo "<tr>";
                                                echo "<td>" . $counter++ . "</td>";
                                                echo "<td>" . $row['payment_type'] . "</td>";
                                                echo "<td>" . $row['payment_method'] . "</td>";
                                                echo "<td>" . $row['total_amount'] . "</td>";
                                                echo "<td>" . date("d M Y", strtotime($row['payment_datetime'])) . "</td>";
                                                echo "<td>" . date("H:i:s", strtotime($row['payment_datetime'])) . "</td>";
                                                echo "<td>" . $row['payment_status'] . "</td>";
                                                echo "<td>
                                                        <a href='view_payment.php?id=" . $row['payment_id'] . "' class='btn btn-primary btn-sm'>
                                                            <i class='fas fa-eye'></i> View
                                                        </a>
                                                      </td>";
                                                echo "</tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='8'>No payments found.</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Refund Requests -->
                        <div class="table-container" id="refunds-container" style="display: none;">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="mb-0"><i class="fas fa-undo-alt text-warning"></i> Refund Requests</h4>
                            </div>
                            <div class="table-responsive">
                                <table id="refunds-table" class="table table-bordered table-striped ">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Payment ID</th>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Reason</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if ($refundRequestsCount > 0) {
                                            mysqli_data_seek($refundRequestsResult, 0); // Reset pointer
                                            $counter = 1;
                                            while ($row = $refundRequestsResult->fetch_assoc()) {
                                                echo "<tr>";
                                                echo "<td>" . $counter++ . "</td>";
                                                echo "<td>" . $row['payment_id'] . "</td>";
                                                echo "<td>" . date("d M Y", strtotime($row['request_datetime'])) . "</td>";
                                                echo "<td>" . date("H:i:s", strtotime($row['request_datetime'])) . "</td>";
                                                echo "<td>" . $row['reason'] . "</td>";
                                                echo "<td>" . $row['status'] . "</td>";
                                                echo "<td>
                                                        <a href='view_refund.php?id=" . $row['refund_request_id'] . "' class='btn btn-primary btn-sm'>
                                                            <i class='fas fa-eye'></i> View
                                                        </a>
                                                      </td>";
                                                echo "</tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='7'>No refund requests found.</td></tr>";
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

<?php
include '../../../include/footer.html';
?>

<script>
    $(document).ready(function() {
        $("#payments-table").DataTable({});
    });

    $(document).ready(function() {
        $("#refunds-table").DataTable({});
    });

    $(document).ready(function() {
        // Add click event for the toggle cards
        $('.toggle-card').on('click', function() {
            // Remove active class from all cards
            $('.toggle-card').removeClass('active');

            // Add active class to clicked card
            $(this).addClass('active');

            // Hide all tables
            $('.table-container').hide();

            // Show the table corresponding to the clicked card
            $('#' + $(this).data('target')).show();
        });
    });

    $(document).ready(function() {        
        // Add visual feedback when hovering over cards
        $('.toggle-card').hover(
            function() {
                if (!$(this).hasClass('active')) {
                    $(this).css('cursor', 'pointer');
                    $(this).addClass('shadow-sm');
                }
            },
            function() {
                $(this).removeClass('shadow-sm');
            }
        );
    });

    $(document).ready(function() {
        // Toggle payment card content visibility
        $('#payment-toggle-btn').click(function() {
            var cardBody = $('#payment-card-body');

            // Remove transition property to avoid conflicts
            cardBody.css('transition', 'none');

            // Use jQuery's slideToggle with a specified duration
            cardBody.slideToggle(300);

            // Toggle the icon
            var icon = $(this).find('i');
            if (icon.hasClass('fa-minus')) {
                icon.removeClass('fa-minus').addClass('fa-plus');
            } else {
                icon.removeClass('fa-plus').addClass('fa-minus');
            }
        });
    });
</script>

<style>
    .toggle-card {
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .toggle-card.active {
        border-bottom: 3px solid #1572E8;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    .toggle-card:hover:not(.active) {
        transform: translateY(-5px);
    }

    .table-container {
        transition: all 0.3s ease;
    }

    #payment-card-body {
        transition: none;
    }

    /* Fix for header interaction issues */
    .navbar .nav-link, .navbar .dropdown-item {
        z-index: 1000;
        position: relative;
    }

    /* Add some margin to the Request Refund button */
    .mr-3 {
        margin-right: 1rem;
    }
    .card-header {
        padding: 0.75rem 1.25rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .card-title {
        margin-bottom: 0;
    }
</style>