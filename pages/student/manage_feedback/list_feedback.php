<?php
include '../../../include/st_header.php';
include '../../../database/db_connection.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch the student_id based on the user_id
$student_query = "SELECT student_id FROM students WHERE user_id = ?";
$stmt = $conn->prepare($student_query);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$student_result = $stmt->get_result();

if ($student_result->num_rows == 0) {
    echo '<div class="alert alert-danger">Student information not found.</div>';
    include '../../../include/footer.html';
    exit;
}

$student_row = $student_result->fetch_assoc();
$student_id = $student_row['student_id'];

// Fetch pending feedback
$pending_query = "
    SELECT 
        f.feedback_id, 
        fc.feedback_name, 
        f.description, 
        f.submitted_at,
        tu.name AS target_name
    FROM feedback f
    JOIN feedback_categories fc ON f.feedback_category_id = fc.feedback_category_id
    LEFT JOIN users tu ON f.target_user_id = tu.user_id
    WHERE f.user_id = ? AND f.status = 'pending'
    ORDER BY f.submitted_at DESC
";
$stmt = $conn->prepare($pending_query);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$pending_feedback = $stmt->get_result();
$pendingCount = $pending_feedback->num_rows;

// Fetch resolved feedback
$resolved_query = "
    SELECT 
        f.feedback_id, 
        fc.feedback_name, 
        f.submitted_at,
        tu.name AS target_name,
        fr.reply_text,
        fr.reply_date,
        u2.name AS admin_name
    FROM feedback f
    JOIN feedback_categories fc ON f.feedback_category_id = fc.feedback_category_id
    LEFT JOIN users tu ON f.target_user_id = tu.user_id
    LEFT JOIN feedback_replies fr ON f.feedback_id = fr.feedback_id
    LEFT JOIN users u2 ON fr.admin_id = u2.user_id
    WHERE f.user_id = ? AND f.status = 'resolved'
    ORDER BY f.submitted_at DESC
";
$stmt = $conn->prepare($resolved_query);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$resolved_feedback = $stmt->get_result();
$resolvedCount = $resolved_feedback->num_rows;
?>

<div class="container">
    <div class="page-inner">

        <!-- Breadcrumbs -->
        <div class="page-header">
            <h4 class="page-title">My Feedback</h4>
            <ul class="breadcrumbs">
                <li class="nav-home">
                    <a href="/pages/student/dashboard.php"><i class="icon-home"></i></a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Feedback List</a>
                </li>
            </ul>
        </div>

        <!-- Page Content -->
        <div class="page-category">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title">Feedback List</h4>
                        <div class="d-flex align-items-center">
                            <a href="add_feedback.php" class="btn btn-primary mr-3">Add Feedback</a>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="feedback-toggle-btn">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body" id="feedback-card-body">
                        <!-- Toggle Cards -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card card-stats card-round toggle-card active" data-target="pending-container">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-5">
                                                <div class="icon-big text-center">
                                                    <i class="fas fa-clock text-warning"></i>
                                                </div>
                                            </div>
                                            <div class="col-7 col-stats">
                                                <div class="numbers">
                                                    <p class="card-category">Pending Feedback</p>
                                                    <h4 class="card-title"><?php echo $pendingCount; ?></h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card card-stats card-round toggle-card" data-target="resolved-container">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-5">
                                                <div class="icon-big text-center">
                                                    <i class="fas fa-check-circle text-success"></i>
                                                </div>
                                            </div>
                                            <div class="col-7 col-stats">
                                                <div class="numbers">
                                                    <p class="card-category">Resolved Feedback</p>
                                                    <h4 class="card-title"><?php echo $resolvedCount; ?></h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pending Feedback Container -->
                        <div class="table-container" id="pending-container">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="mb-0"><i class="fas fa-clock text-warning"></i> Pending Feedback</h4>
                            </div>
                            <div class="table-responsive">
                                <table id="pending-table" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Category</th>
                                            <th>Description</th>
                                            <th>Target</th>
                                            <th>Submitted</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($pendingCount > 0): ?>
                                            <?php 
                                            mysqli_data_seek($pending_feedback, 0); // Reset pointer
                                            $counter = 1; 
                                            ?>
                                            <?php while ($row = $pending_feedback->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?= $counter++ ?></td>
                                                    <td><?= htmlspecialchars($row['feedback_name']) ?></td>
                                                    <td><?= nl2br(htmlspecialchars($row['description'])) ?></td>
                                                    <td><?= $row['target_name'] ? htmlspecialchars($row['target_name']) : '-' ?></td>
                                                    <td><?= date("d M Y, H:i", strtotime($row['submitted_at'])) ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center">No pending feedback found.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Resolved Feedback Container -->
                        <div class="table-container" id="resolved-container" style="display: none;">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="mb-0"><i class="fas fa-check-circle text-success"></i> Resolved Feedback</h4>
                            </div>
                            <div class="table-responsive">
                                <table id="resolved-table" class="table table-bordered table-striped ">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Category</th>
                                            <th>Target</th>
                                            <th>Submitted</th>
                                            <th>Reply</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($resolvedCount > 0): ?>
                                            <?php 
                                            mysqli_data_seek($resolved_feedback, 0); // Reset pointer
                                            $counter = 1; 
                                            ?>
                                            <?php while ($row = $resolved_feedback->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?= $counter++ ?></td>
                                                    <td><?= htmlspecialchars($row['feedback_name']) ?></td>
                                                    <td><?= $row['target_name'] ? htmlspecialchars($row['target_name']) : '-' ?></td>
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
                                                <td colspan="5" class="text-center">No resolved feedback found.</td>
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

<?php include '../../../include/footer.html'; ?>

<script>
    $(document).ready(function() {
        $("#pending-table").DataTable({});
    });

    $(document).ready(function() {
        $("#resolved-table").DataTable({});
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
        // Toggle feedback card content visibility
        $('#feedback-toggle-btn').click(function() {
            var cardBody = $('#feedback-card-body');

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

    .clickable-row {
        cursor: pointer;
    }

    #feedback-card-body {
        transition: none;
    }

    /* Fix for header interaction issues */
    .navbar .nav-link, .navbar .dropdown-item {
        z-index: 1000;
        position: relative;
    }

    /* Add some margin to the Add Feedback button */
    .mr-3 {
        margin-right: 1rem;
    }

    .card-header {
        padding: 0.75rem 1.25rem;
    }
</style>