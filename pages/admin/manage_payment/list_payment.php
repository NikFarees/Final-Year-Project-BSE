<?php
include '../../../include/ad_header.php';
include '../../../database/db_connection.php';

// Fetch payments
$paymentsQuery = "
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
";
$paymentsResult = $conn->query($paymentsQuery);

// Fetch refund requests
$refundRequestsQuery = "
    SELECT 
        rr.refund_request_id,
        rr.payment_id,
        rr.request_datetime,
        rr.reason,
        rr.status,
        p.total_amount,
        u.name AS student_name
    FROM 
        refund_requests AS rr
    JOIN 
        payments AS p ON rr.payment_id = p.payment_id
    JOIN 
        student_licenses AS sl ON p.student_license_id = sl.student_license_id
    JOIN 
        students AS s ON sl.student_id = s.student_id
    JOIN 
        users AS u ON s.user_id = u.user_id
";
$refundRequestsResult = $conn->query($refundRequestsQuery);
?>

<div class="container">
  <div class="page-inner">

    <!-- Breadcrumbs -->
    <div class="page-header">
      <h4 class="page-title">Manage Payment</h4>
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
          <a href="#">Payment & Refund Request List</a>
        </li>
      </ul>
    </div>

    <!-- Inner page content -->
    <div class="page-category">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header">
            <h4 class="card-title">Payment & Refund Request List</h4>
          </div>
          <div class="card-body">
            <ul class="nav nav-tabs nav-line nav-color-secondary" id="line-tab" role="tablist">
              <li class="nav-item">
                <a class="nav-link active" id="line-payments-tab" data-bs-toggle="pill" href="#line-payments" role="tab" aria-controls="line-payments" aria-selected="true">Payment List</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" id="line-refunds-tab" data-bs-toggle="pill" href="#line-refunds" role="tab" aria-controls="line-refunds" aria-selected="false">Refund Requests</a>
              </li>
            </ul>
            <div class="tab-content mt-3 mb-3" id="line-tabContent">

              <!-- Payment List -->
              <div class="tab-pane fade show active" id="line-payments" role="tabpanel" aria-labelledby="line-payments-tab">
                <div class="card mt-4">
                  <div class="card-body">
                    <div class="table-responsive">
                      <table id="payments-table" class="table table-bordered table-striped table-hover">
                        <thead>
                          <tr>
                            <th>#</th>
                            <th>Student Name</th>
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
                            $counter = 1; // Initialize counter
                            while ($row = $paymentsResult->fetch_assoc()) {
                              echo "<tr class='clickable-row' data-href='view_payment.php?id=" . htmlspecialchars($row['payment_id']) . "'>";
                              echo "<td>" . $counter++ . "</td>"; // Counter column
                              echo "<td>" . htmlspecialchars($row['student_name']) . "</td>";
                              echo "<td>" . htmlspecialchars($row['payment_type']) . "</td>";
                              echo "<td>" . htmlspecialchars($row['payment_method']) . "</td>";
                              echo "<td>" . htmlspecialchars($row['total_amount']) . "</td>";
                              echo "<td>" . htmlspecialchars($row['payment_datetime']) . "</td>";
                              echo "<td>" . htmlspecialchars($row['payment_status']) . "</td>";
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
                </div>
              </div>

              <!-- Refund Requests -->
              <div class="tab-pane fade" id="line-refunds" role="tabpanel" aria-labelledby="line-refunds-tab">
                <div class="card mt-4">
                  <div class="card-body">
                    <div class="table-responsive">
                      <table id="refunds-table" class="table table-bordered">
                        <thead>
                          <tr>
                            <th>#</th>
                            <th>Payment ID</th>
                            <th>Student Name</th>
                            <th>Request Date</th>
                            <th>Reason</th>
                            <th>Status</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php
                          if ($refundRequestsResult->num_rows > 0) {
                            $counter = 1; // Initialize counter
                            while ($row = $refundRequestsResult->fetch_assoc()) {
                              echo "<tr>";
                              echo "<td>" . $counter++ . "</td>"; // Counter column
                              echo "<td>" . htmlspecialchars($row['payment_id']) . "</td>";
                              echo "<td>" . htmlspecialchars($row['student_name']) . "</td>";
                              echo "<td>" . htmlspecialchars($row['request_datetime']) . "</td>";
                              echo "<td>" . htmlspecialchars($row['reason']) . "</td>";
                              echo "<td>" . htmlspecialchars($row['status']) . "</td>";
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

  // Make entire row clickable
  $(document).ready(function() {
    $(".clickable-row").click(function() {
      window.location = $(this).data("href");
    });
  });

</script>

<style>
  .clickable-row {
    cursor: pointer;
  }
</style>