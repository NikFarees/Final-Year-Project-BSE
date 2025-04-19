<?php
include '../../../include/ad_header.php';
include '../../../database/db_connection.php';

// Fetch pending feedback
$pending_query = "
    SELECT f.*, fc.feedback_name, u.name AS sender_name, tu.name AS target_name
    FROM feedback f
    JOIN feedback_categories fc ON f.feedback_category_id = fc.feedback_category_id
    JOIN users u ON f.user_id = u.user_id
    LEFT JOIN users tu ON f.target_user_id = tu.user_id
    WHERE f.status = 'pending'
    ORDER BY f.submitted_at DESC
";
$pending_feedback = $conn->query($pending_query);

// Fetch resolved feedback
$resolved_query = str_replace("f.status = 'pending'", "f.status = 'resolved'", $pending_query);
$resolved_feedback = $conn->query($resolved_query);
?>

<div class="container">
  <div class="page-inner">

    <!-- Breadcrumbs -->
    <div class="page-header">
      <h4 class="page-title">Feedback</h4>
      <ul class="breadcrumbs">
        <li class="nav-home"><a href="/pages/admin/dashboard.php"><i class="icon-home"></i></a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="#">Feedback List</a></li>
      </ul>
    </div>

    <!-- Page Content -->
    <div class="page-category">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header">
            <h4 class="card-title">User Feedback</h4>
          </div>
          <div class="card-body">
            <ul class="nav nav-tabs nav-line nav-color-secondary" id="feedback-tab" role="tablist">
              <li class="nav-item">
                <a class="nav-link active" id="pending-tab" data-bs-toggle="pill" href="#pending" role="tab">Pending</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" id="resolved-tab" data-bs-toggle="pill" href="#resolved" role="tab">Resolved</a>
              </li>
            </ul>

            <div class="tab-content mt-3 mb-3" id="feedback-tabContent">
              <!-- Pending Tab -->
              <div class="tab-pane fade show active" id="pending" role="tabpanel">
                <div class="table-responsive">
                  <table id="pending-table" class="table table-striped table-bordered table-hover">
                    <thead>
                      <tr>
                        <th>ID</th>
                        <th>Category</th>
                        <th>Sender</th>
                        <th>Target</th>
                        <th>Submitted</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if ($pending_feedback->num_rows > 0): ?>
                        <?php while ($row = $pending_feedback->fetch_assoc()): ?>
                          <tr class="clickable-row" data-href="view_feedback.php?feedback_id=<?= $row['feedback_id'] ?>">
                            <td><?= $row['feedback_id'] ?></td>
                            <td><?= htmlspecialchars($row['feedback_name']) ?></td>
                            <td><?= htmlspecialchars($row['sender_name']) ?></td>
                            <td><?= $row['target_name'] ? htmlspecialchars($row['target_name']) : 'General' ?></td>
                            <td><?= date('d M Y, h:i A', strtotime($row['submitted_at'])) ?></td>
                          </tr>
                        <?php endwhile; ?>
                      <?php else: ?>
                        <tr><td colspan="5" class="text-center">No pending feedback.</td></tr>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- Resolved Tab -->
              <div class="tab-pane fade" id="resolved" role="tabpanel">
                <div class="table-responsive">
                  <table id="resolved-table" class="table table-striped table-bordered table-hover">
                    <thead>
                      <tr>
                        <th>ID</th>
                        <th>Category</th>
                        <th>Sender</th>
                        <th>Target</th>
                        <th>Submitted</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if ($resolved_feedback->num_rows > 0): ?>
                        <?php while ($row = $resolved_feedback->fetch_assoc()): ?>
                          <tr class="clickable-row" data-href="view_feedback.php?feedback_id=<?= $row['feedback_id'] ?>">
                            <td><?= $row['feedback_id'] ?></td>
                            <td><?= htmlspecialchars($row['feedback_name']) ?></td>
                            <td><?= htmlspecialchars($row['sender_name']) ?></td>
                            <td><?= $row['target_name'] ? htmlspecialchars($row['target_name']) : 'General' ?></td>
                            <td><?= date('d M Y, h:i A', strtotime($row['submitted_at'])) ?></td>
                          </tr>
                        <?php endwhile; ?>
                      <?php else: ?>
                        <tr><td colspan="5" class="text-center">No resolved feedback.</td></tr>
                      <?php endif; ?>
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

<?php include '../../../include/footer.html'; ?>

<script>
  $(document).ready(function () {
    $('#pending-table').DataTable({});
    $('#resolved-table').DataTable({});
  });

  $(document).ready(function () {
    $('.clickable-row').click(function () {
      window.location = $(this).data('href');
    });
  });
</script>

<style>
  .clickable-row {
    cursor: pointer;
  }
</style>
