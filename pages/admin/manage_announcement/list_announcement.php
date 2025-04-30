<?php
include '../../../include/ad_header.php';
include '../../../database/db_connection.php';

// Fetch announcements from the database
$query = "
    SELECT 
        a.announcement_id,
        a.title,
        a.description,
        a.created_at,
        GROUP_CONCAT(DISTINCT r.role_id SEPARATOR ', ') AS role_audience,
        GROUP_CONCAT(DISTINCT u.name SEPARATOR ', ') AS user_audience
    FROM 
        announcements a
    LEFT JOIN 
        role_announcements ra ON a.announcement_id = ra.announcement_id
    LEFT JOIN 
        roles r ON ra.role_id = r.role_id
    LEFT JOIN 
        user_announcements ua ON a.announcement_id = ua.announcement_id
    LEFT JOIN 
        users u ON ua.user_id = u.user_id
    GROUP BY 
        a.announcement_id, a.title, a.description, a.created_at
";
$result = $conn->query($query);
$announcementCount = $result->num_rows;
?>

<div class="container">
    <div class="page-inner">

        <!-- Breadcrumbs -->
        <div class="page-header">
            <h4 class="page-title">Announcement</h4>
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
                    <a href="#">Announcement List</a>
                </li>
            </ul>
        </div>

        <!-- Inner page content -->
        <div class="page-category">

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Announcement List</h4>
                    <div class="d-flex align-items-center">
                        <a href="add_announcement.php" class="btn btn-primary mr-3">
                            Create Announcement
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="announcement-toggle-btn">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body" id="announcement-card-body">
                    <div class="table-responsive">
                        <table id="announcement-table" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th style="width: 5%;">#</th>
                                    <th style="width: 35%;">Detail</th>
                                    <th style="width: 25%;">Audience</th>
                                    <th style="width: 15%;">Date Time</th>
                                    <th style="width: 20%;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($announcementCount > 0) {
                                    $counter = 1;
                                    while ($row = $result->fetch_assoc()) {
                                        // Format date from 2025-04-23 06:13:49 to 23 Apr 2025 06:13:49
                                        $createdAt = date('d M Y H:i:s', strtotime($row['created_at']));

                                        echo "<tr>";
                                        echo "<td>" . $counter++ . "</td>";
                                        echo "<td><strong>" . htmlspecialchars($row['title']) . "</strong><br>" . htmlspecialchars($row['description']) . "</td>";
                                        echo "<td style='width: 10%;'>" . htmlspecialchars($row['role_audience']) . "<br>" . htmlspecialchars($row['user_audience']) . "</td>";
                                        echo "<td>" . $createdAt . "</td>";
                                        echo "<td>
                                                <a href='edit_announcement.php?id=" . $row['announcement_id'] . "' class='btn btn-sm btn-primary mr-1'>
                                                    <i class='fas fa-edit mr-1'></i> Edit
                                                </a>
                                                <a href='delete_announcement.php?id=" . $row['announcement_id'] . "' class='btn btn-sm btn-danger'>
                                                    <i class='fas fa-trash-alt mr-1'></i> Delete
                                                </a>
                                            </td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5' class='text-center'>No announcements found</td></tr>";
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

<?php
include '../../../include/footer.html';
?>

<script>
    $(document).ready(function() {
        $("#announcement-table").DataTable({});
    });

    $(document).ready(function() {
        // Toggle card content visibility
        $('#announcement-toggle-btn').click(function() {
            var cardBody = $('#announcement-card-body');
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
</script>

<style>
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
    #announcement-card-body {
        transition: none;
    }

    /* Table styling */
    .table-responsive {
        margin-bottom: 1rem;
    }

    /* Button styling */
    .btn-round {
        border-radius: 2rem;
    }
</style>