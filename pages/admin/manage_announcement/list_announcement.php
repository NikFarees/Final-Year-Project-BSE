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
?>

<div class="container">
    <div class="page-inner">

        <!-- Breadcrumbs -->
        <div class="page-header">
            <h4 class="page-title">Dashboard</h4>
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
                    <a href="#">List Announcement</a>
                </li>
            </ul>
        </div>

        <!-- Inner page content -->
        <div class="page-category">
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title">Announcements</h4>
                    <div class="ms-md-auto py-2 py-md-0">
                        <a href="add_announcement.php" class="btn btn-primary btn-round">Create Announcement</a>
                    </div>
                </div>
                <div class="card-body">
                    <table id="basic-datatables" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th style="width: 10%;">No</th>
                                <th style="width: 30%;">Detail</th>
                                <th style="width: 20%;">Audience</th>
                                <th style="width: 10%;">Date</th>
                                <th style="width: 10%;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result->num_rows > 0) {
                                $no = 1;
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . $no++ . "</td>";
                                    echo "<td><strong>" . htmlspecialchars($row['title']) . "</strong><br>" . htmlspecialchars($row['description']) . "</td>";
                                    echo "<td style='width: 10%;'>" . htmlspecialchars($row['role_audience']) . "<br>" . htmlspecialchars($row['user_audience']) . "</td>";
                                    echo "<td style='width: 15%;'>" . htmlspecialchars($row['created_at']) . "</td>";
                                    echo "<td>";
                                    echo "<a href='edit_announcement.php?id=" . $row['announcement_id'] . "' class='text-dark me-3'>Edit</a>";
                                    echo "<a href='delete_announcement.php?id=" . $row['announcement_id'] . "' class='text-danger'>Delete</a>";
                                    echo "</td>";
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

<?php
include '../../../include/footer.html';
?>

<script>
    $(document).ready(function() {
        $("#basic-datatables").DataTable({});
    });
</script>