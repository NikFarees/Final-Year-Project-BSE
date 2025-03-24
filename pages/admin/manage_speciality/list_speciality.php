<?php
include '../../../include/ad_header.php';
include '../../../database/db_connection.php'; // Include the database connection file

// Fetch licenses and their corresponding specialties from the database
$query = "
    SELECT 
        l.license_id, 
        l.license_name, 
        l.license_type, 
        COUNT(s.speciality_id) AS total_instructors
    FROM licenses l
    LEFT JOIN specialities s ON l.license_id = s.license_id
    GROUP BY l.license_id, l.license_name, l.license_type
";
$result = $conn->query($query);
?>

<div class="container">
    <div class="page-inner">

        <!-- Breadcrumbs -->
        <div class="page-header">
            <h4 class="page-title">Manage Speciality</h4>
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
                    <a href="#">Speciality List</a>
                </li>
            </ul>
        </div>

        <!-- Inner page content -->
        <div class="page-category">

            <div class="row">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <div class="col-md-6">
                            <div class="card card-secondary">
                                <div class="card-body skew-shadow">
                                    <h1><?php echo htmlspecialchars($row['license_name']); ?> (<?php echo htmlspecialchars($row['license_type']); ?>)</h1>
                                    <h5 class="op-8">Total Instructors</h5>
                                    <div class="pull-right">
                                        <h3 class="fw-bold op-8"><?php echo (int)$row['total_instructors']; ?></h3>
                                    </div>
                                    <div class="mt-3">
                                        <a href="../manage_speciality/view_speciality.php?id=<?php echo urlencode($row['license_id']); ?>" class="btn btn-sm detail-view-btn"><b>Detail View</b></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No specialties found.</p>
                <?php endif; ?>
            </div>

        </div>

    </div>
</div>

<?php
include '../../../include/footer.html';
?>

<style>
    .detail-view-btn {
        background-color: #FFF;
        border-color: #FFF;
        font-size: 0.9em; /* Increase font size */
    }
    .detail-view-btn:hover {
        background-color: #D3D3D3; /* Change hover color to gray */
        border-color: #D3D3D3;
        /* Change font color to black */
        color: #000;
    }
</style>