<?php
include '../../../include/in_header.php';
include '../../../database/db_connection.php';

// Get the current instructor's user_id from session
$user_id = $_SESSION['user_id'] ?? '';
$role_id = $_SESSION['role_id'] ?? '';

// Fetch announcements relevant to this instructor (targeted to their role or specifically to them)
$query = "
    SELECT 
        a.announcement_id,
        a.title,
        a.description,
        a.created_at,
        u.name AS created_by_name
    FROM 
        announcements a
    LEFT JOIN
        users u ON a.created_by = u.user_id
    WHERE 
        a.announcement_id IN (
            -- Announcements targeted to the instructor's role
            SELECT announcement_id FROM role_announcements 
            WHERE role_id = ?
            
            UNION
            
            -- Announcements targeted specifically to the instructor
            SELECT announcement_id FROM user_announcements
            WHERE user_id = ?
        )
    ORDER BY 
        a.created_at ASC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $role_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$announcementCount = $result->num_rows;
?>

<div class="container">
    <div class="page-inner">

        <!-- Breadcrumbs -->
        <div class="page-header">
            <h4 class="page-title">Announcements</h4>
            <ul class="breadcrumbs">
                <li class="nav-home">
                    <a href="/pages/instructor/dashboard.php">
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
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="announcement-toggle-btn">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
                <div class="card-body" id="announcement-card-body">
                    <div class="table-responsive">
                        <table id="announcement-table" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th style="width: 10%;">#</th>
                                    <th style="width: 50%;">Detail</th>
                                    <th style="width: 20%;">Created By</th>
                                    <th style="width: 20%;">Date & Time</th>
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
                                        echo "<td>" . htmlspecialchars($row['created_by_name'] ?? 'System') . "</td>";
                                        echo "<td>" . $createdAt . "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='4' class='text-center'>No announcements found</td></tr>";
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
        $("#announcement-table").DataTable();
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

    /* Table detail column formatting */
    td strong {
        font-size: 1.05em;
        color: #1a2035;
    }
</style>