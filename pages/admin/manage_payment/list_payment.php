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

// Count number of records in each category
$paymentsCount = mysqli_num_rows($paymentsResult);
$refundRequestsCount = mysqli_num_rows($refundRequestsResult);
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
          <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="card-title mb-0">Payment & Refund Request List</h4>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="payment-toggle-btn">
              <i class="fas fa-minus"></i>
            </button>
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
                          <p class="card-category">Payment List</p>
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

            <!-- Payment List -->
            <div class="table-container" id="payments-container">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0"><i class="fas fa-credit-card text-primary"></i> Payment List</h4>
              </div>
              <div class="table-responsive">
                <table id="payments-table" class="table table-bordered table-striped">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Student Name</th>
                      <th>Payment Type</th>
                      <th>Payment Method</th>
                      <th>Total Amount</th>
                      <th>Date Time</th>
                      <th>Status</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    if ($paymentsCount > 0) {
                      mysqli_data_seek($paymentsResult, 0); // Reset pointer
                      $counter = 1; // Initialize counter
                      while ($row = $paymentsResult->fetch_assoc()) {
                        // Format payment date from 2025-04-23 06:13:49 to 23 Apr 2025 06:13:49
                        $paymentDate = date('d M Y H:i:s', strtotime($row['payment_datetime']));
                        
                        echo "<tr>";
                        echo "<td>" . $counter++ . "</td>"; // Counter column
                        echo "<td>" . htmlspecialchars($row['student_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['payment_type']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['payment_method']) . "</td>";
                        echo "<td>RM " . htmlspecialchars($row['total_amount']) . "</td>";
                        echo "<td>" . $paymentDate . "</td>";
                        echo "<td>";
                        // Show status with appropriate badge
                        $statusClass = '';
                        switch($row['payment_status']) {
                          case 'Completed':
                            $statusClass = 'badge-success';
                            break;
                          case 'Pending':
                            $statusClass = 'badge-warning';
                            break;
                          case 'Failed':
                            $statusClass = 'badge-danger';
                            break;
                          case 'Refunded':
                            $statusClass = 'badge-info';
                            break;
                          default:
                            $statusClass = 'badge-secondary';
                        }
                        echo "<span class='badge {$statusClass}'>" . htmlspecialchars($row['payment_status']) . "</span>";
                        echo "</td>";
                        echo "<td>
                                <a href='view_payment.php?id=" . htmlspecialchars($row['payment_id']) . "' class='btn btn-sm btn-primary'>
                                  <i class='fas fa-eye mr-1'></i> View
                                </a>
                              </td>";
                        echo "</tr>";
                      }
                    } else {
                      echo "<tr><td colspan='8' class='text-center'>No payments found.</td></tr>";
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
                <table id="refunds-table" class="table table-bordered table-striped">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Payment ID</th>
                      <th>Student Name</th>
                      <th>Date Time</th>
                      <th>Reason</th>
                      <th>Status</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    if ($refundRequestsCount > 0) {
                      mysqli_data_seek($refundRequestsResult, 0); // Reset pointer
                      $counter = 1; // Initialize counter
                      while ($row = $refundRequestsResult->fetch_assoc()) {
                        // Format request date
                        $requestDate = date('d M Y H:i:s', strtotime($row['request_datetime']));
                        
                        echo "<tr>";
                        echo "<td>" . $counter++ . "</td>"; // Counter column
                        echo "<td>" . htmlspecialchars($row['payment_id']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['student_name']) . "</td>";
                        echo "<td>" . $requestDate . "</td>";
                        echo "<td>" . htmlspecialchars($row['reason']) . "</td>";
                        echo "<td>";
                        // Show status with appropriate badge
                        $statusClass = '';
                        switch($row['status']) {
                          case 'Approved':
                            $statusClass = 'badge-success';
                            break;
                          case 'Pending':
                            $statusClass = 'badge-warning';
                            break;
                          case 'Rejected':
                            $statusClass = 'badge-danger';
                            break;
                          default:
                            $statusClass = 'badge-secondary';
                        }
                        echo "<span class='badge {$statusClass}'>" . htmlspecialchars($row['status']) . "</span>";
                        echo "</td>";
                        echo "<td>
                                <a href='view_refund.php?id=" . htmlspecialchars($row['refund_request_id']) . "' class='btn btn-sm btn-warning'>
                                  <i class='fas fa-eye mr-1'></i> View
                                </a>
                              </td>";
                        echo "</tr>";
                      }
                    } else {
                      echo "<tr><td colspan='7' class='text-center'>No refund requests found.</td></tr>";
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
    // Toggle card content visibility
    $('#payment-toggle-btn').click(function() {
      var cardBody = $('#payment-card-body');
      cardBody.css('transition', 'none');
      cardBody.slideToggle(300);
      
      var icon = $(this).find('i');
      if (icon.hasClass('fa-minus')) {
        icon.removeClass('fa-minus').addClass('fa-plus');
      } else {
        icon.removeClass('fa-plus').addClass('fa-minus');
      }
    });
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

  /* Card header styling */
  .card-header {
    padding: 0.75rem 1.25rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  
  .card-title {
    margin-bottom: 0;
  }
  
  /* Badge styling */
  .badge {
    font-size: 85%;
    font-weight: 500;
    padding: 0.35em 0.6em;
    border-radius: 0.2rem;
  }
  
  /* Button spacing */
  .mr-1 {
    margin-right: 0.25rem;
  }
  
  /* Card body transition handling */
  #payment-card-body {
    transition: none;
  }
</style>