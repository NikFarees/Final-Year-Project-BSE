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

// Fetch feedback categories
$categories_query = "SELECT * FROM feedback_categories ORDER BY feedback_name ASC";
$categories_result = $conn->query($categories_query);

// Count number of records in each category
$pendingCount = mysqli_num_rows($pending_feedback);
$resolvedCount = mysqli_num_rows($resolved_feedback);
$categoriesCount = mysqli_num_rows($categories_result);
?>

<div class="container">
    <div class="page-inner">

        <!-- Breadcrumbs -->
        <div class="page-header">
            <h4 class="page-title">Manage Feedback</h4>
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
                    <a href="#">Feedback List</a>
                </li>
            </ul>
        </div>

        <!-- Page Content -->
        <div class="page-category">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">Feedback List</h4>
                        <div class="d-flex">
                            <a href="add_feedback_category.php" class="btn btn-primary  mr-3">
                                Add Feedback Categories
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="feedback-toggle-btn">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body" id="feedback-card-body">
                        <!-- Toggle Cards -->
                        <div class="row mb-4">
                            <div class="col-md-4">
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

                            <div class="col-md-4">
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

                            <div class="col-md-4">
                                <div class="card card-stats card-round toggle-card" data-target="categories-container">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-5">
                                                <div class="icon-big text-center">
                                                    <i class="fas fa-list-alt text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="col-7 col-stats">
                                                <div class="numbers">
                                                    <p class="card-category">Categories</p>
                                                    <h4 class="card-title"><?php echo $categoriesCount; ?></h4>
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
                                <table id="pending-table" class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Category</th>
                                            <th>Sender</th>
                                            <th>Target</th>
                                            <th>Submitted</th>
                                            <th>Action</th>
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
                                                    <td><?= htmlspecialchars($row['sender_name']) ?></td>
                                                    <td><?= $row['target_name'] ? htmlspecialchars($row['target_name']) : 'General' ?></td>
                                                    <td><?= date('d M Y, h:i A', strtotime($row['submitted_at'])) ?></td>
                                                    <td>
                                                        <a href="view_feedback.php?feedback_id=<?= $row['feedback_id'] ?>" class="btn btn-sm btn-warning">
                                                            <i class="fas fa-eye mr-1"></i> View
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center">No pending feedback.</td>
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
                                <table id="resolved-table" class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Category</th>
                                            <th>Sender</th>
                                            <th>Target</th>
                                            <th>Submitted</th>
                                            <th>Action</th>
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
                                                    <td><?= htmlspecialchars($row['sender_name']) ?></td>
                                                    <td><?= $row['target_name'] ? htmlspecialchars($row['target_name']) : 'General' ?></td>
                                                    <td><?= date('d M Y, h:i A', strtotime($row['submitted_at'])) ?></td>
                                                    <td>
                                                        <a href="view_feedback.php?feedback_id=<?= $row['feedback_id'] ?>" class="btn btn-sm btn-success">
                                                            <i class="fas fa-eye mr-1"></i> View
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center">No resolved feedback.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Categories Container -->
                        <div class="table-container" id="categories-container" style="display: none;">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="mb-0"><i class="fas fa-list-alt text-primary"></i> Categories</h4>
                            </div>
                            <div class="table-responsive">
                                <table id="categories-table" class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Category Name</th>
                                            <th>Description</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($categoriesCount > 0): ?>
                                            <?php
                                            mysqli_data_seek($categories_result, 0); // Reset pointer
                                            $counter = 1;
                                            ?>
                                            <?php while ($row = $categories_result->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?= $counter++ ?></td>
                                                    <td><?= htmlspecialchars($row['feedback_name']) ?></td>
                                                    <td><?= htmlspecialchars($row['description']) ?></td>
                                                    <td>
                                                        <a href="edit_category.php?id=<?= $row['feedback_category_id'] ?>" class="btn btn-sm btn-primary">
                                                            <i class="fas fa-edit mr-1"></i> Edit
                                                        </a>
                                                        <a href="delete_category.php?id=<?= $row['feedback_category_id'] ?>" class="btn btn-sm btn-danger ml-2">
                                                            <i class="fas fa-trash mr-1"></i> Delete
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center">No categories found.</td>
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
        $('#pending-table').DataTable({});
    });

    $(document).ready(function() {
        $('#resolved-table').DataTable({});
    });

    $(document).ready(function() {
        $('#categories-table').DataTable({});
    });

    $(document).ready(function() {
        // Toggle card content visibility
        $('#feedback-toggle-btn').click(function() {
            var cardBody = $('#feedback-card-body');
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

    /* Button spacing */
    .mr-1 {
        margin-right: 0.25rem;
    }

    .mr-3 {
        margin-right: 1rem;
    }

    /* Card body transition handling */
    #feedback-card-body {
        transition: none;
    }
</style>