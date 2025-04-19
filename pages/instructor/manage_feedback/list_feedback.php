<?php
include '../../../include/in_header.php';
include '../../../database/db_connection.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch pending feedback created by the instructor
$pending_query = "
    SELECT 
        f.feedback_id, 
        fc.feedback_name, 
        f.description, 
        f.submitted_at
    FROM feedback f
    JOIN feedback_categories fc ON f.feedback_category_id = fc.feedback_category_id
    WHERE f.user_id = ? AND f.status = 'pending'
    ORDER BY f.submitted_at DESC
";
$stmt = $conn->prepare($pending_query);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$pending_feedback = $stmt->get_result();

// Fetch resolved feedback created by the instructor
$resolved_query = "
    SELECT 
        f.feedback_id, 
        fc.feedback_name, 
        f.submitted_at,
        fr.reply_text,
        fr.reply_date
    FROM feedback f
    JOIN feedback_categories fc ON f.feedback_category_id = fc.feedback_category_id
    LEFT JOIN feedback_replies fr ON f.feedback_id = fr.feedback_id
    WHERE f.user_id = ? AND f.status = 'resolved'
    ORDER BY f.submitted_at DESC
";
$stmt = $conn->prepare($resolved_query);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$resolved_feedback = $stmt->get_result();
?>

<div class="container">
    <div class="page-inner">

        <!-- Breadcrumbs -->
        <div class="page-header">
            <h4 class="page-title">My Feedback</h4>
            <ul class="breadcrumbs">
                <li class="nav-home">
                    <a href="/pages/instructor/dashboard.php"><i class="icon-home"></i></a>
                </li>
                <li class="separator"><i class="icon-arrow-right"></i></li>
                <li class="nav-item"><a href="#">Feedback List</a></li>
            </ul>
        </div>

        <!-- Page Content -->
        <div class="page-category">
            <div class="col-md-12">
                <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title">Feedback List</h4>
                        <a href="add_feedback.php" class="btn btn-primary btn-round">Add Feedback</a>
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
                            <!-- Pending Feedback Tab -->
                            <div class="tab-pane fade show active" id="pending" role="tabpanel">
                                <div class="table-responsive">
                                    <table id="pending-table" class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>Feedback ID</th>
                                                <th>Category</th>
                                                <th>Description</th>
                                                <th>Submitted</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($pending_feedback->num_rows > 0): ?>
                                                <?php while ($row = $pending_feedback->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($row['feedback_id']) ?></td>
                                                        <td><?= htmlspecialchars($row['feedback_name']) ?></td>
                                                        <td><?= nl2br(htmlspecialchars($row['description'])) ?></td>
                                                        <td><?= date("d M Y, H:i", strtotime($row['submitted_at'])) ?></td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="4" class="text-center">No pending feedback found.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Resolved Feedback Tab -->
                            <div class="tab-pane fade" id="resolved" role="tabpanel">
                                <div class="table-responsive">
                                    <table id="resolved-table" class="table table-bordered table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>Feedback ID</th>
                                                <th>Category</th>
                                                <th>Submitted</th>
                                                <th>Reply</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($resolved_feedback->num_rows > 0): ?>
                                                <?php while ($row = $resolved_feedback->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($row['feedback_id']) ?></td>
                                                        <td><?= htmlspecialchars($row['feedback_name']) ?></td>
                                                        <td><?= date("d M Y, H:i", strtotime($row['submitted_at'])) ?></td>
                                                        <td>
                                                            <?php if (!empty($row['reply_text'])): ?>
                                                                <?= nl2br(htmlspecialchars($row['reply_text'])) ?><br>
                                                                <small><em><?= date("d M Y, H:i", strtotime($row['reply_date'])) ?></em></small>
                                                            <?php else: ?>
                                                                <em>No reply yet</em>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="4" class="text-center">No resolved feedback found.</td>
                                                </tr>
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
    $(document).ready(function() {
        $('#pending-table').DataTable({});
    });

    $(document).ready(function() {
        $('#resolved-table').DataTable({});
    });
</script>

<style>
    .clickable-row {
        cursor: pointer;
    }
</style>