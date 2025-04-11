<?php
include '../../../include/ad_header.php';
include '../../../database/db_connection.php';

// Fetch licenses and their corresponding specialties
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
$licenses_result = $conn->query($query);
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
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Specialities</h4>
                    <!-- Add button to assign new speciality -->
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs nav-line nav-color-secondary" id="speciality-tab" role="tablist">
                        <?php
                        $active = true;
                        while ($license = $licenses_result->fetch_assoc()): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $active ? 'active' : ''; ?>" id="tab-<?php echo $license['license_id']; ?>" data-bs-toggle="pill" href="#content-<?php echo $license['license_id']; ?>" role="tab" aria-controls="content-<?php echo $license['license_id']; ?>" aria-selected="<?php echo $active ? 'true' : 'false'; ?>">
                                    <?php echo htmlspecialchars($license['license_name']); ?> (<?php echo htmlspecialchars($license['license_type']); ?>)
                                </a>
                            </li>
                        <?php
                        $active = false;
                        endwhile; ?>
                    </ul>
                    <div class="tab-content mt-3" id="speciality-tabContent">
                        <?php
                        $licenses_result->data_seek(0); // Reset result pointer
                        $active = true;
                        while ($license = $licenses_result->fetch_assoc()):
                            // Fetch instructors for the current license
                            $license_id = $license['license_id'];
                            $instructors_query = "
                                SELECT s.speciality_id, u.name AS instructor_name, s.instructor_id
                                FROM specialities s
                                JOIN instructors i ON s.instructor_id = i.instructor_id
                                JOIN users u ON i.user_id = u.user_id
                                WHERE s.license_id = '$license_id'
                            ";
                            $instructors_result = $conn->query($instructors_query);
                        ?>
                            <div class="tab-pane fade <?php echo $active ? 'show active' : ''; ?>" id="content-<?php echo $license['license_id']; ?>" role="tabpanel" aria-labelledby="tab-<?php echo $license['license_id']; ?>">
                                <div class="card mt-4">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h4 class="card-title"><?php echo htmlspecialchars($license['license_name']); ?> (<?php echo htmlspecialchars($license['license_type']); ?>)</h4>
                                        <div class="ms-md-auto py-2 py-md-0">
                                            <a href="../manage_speciality/assign_speciality.php?license_id=<?php echo htmlspecialchars($license_id); ?>" class="btn btn-primary btn-round">Assign Speciality</a>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <?php if ($instructors_result->num_rows > 0): ?>
                                            <table class="table table-striped" id="basic-datatables-<?php echo $license['license_id']; ?>">
                                                <thead>
                                                    <tr>
                                                        <th>Speciality ID</th>
                                                        <th>Instructor Name</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php while ($speciality = $instructors_result->fetch_assoc()): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($speciality['speciality_id']); ?></td>
                                                            <td><?php echo htmlspecialchars($speciality['instructor_name']); ?></td>
                                                            <td>
                                                                <a href="delete_speciality.php?instructor_id=<?php echo htmlspecialchars($speciality['instructor_id']); ?>&license_id=<?php echo htmlspecialchars($license_id); ?>" class="text-danger">Delete</a>
                                                            </td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        <?php else: ?>
                                            <p>No instructors assigned to this specialty.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php
                        $active = false;
                        endwhile; ?>
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
        // Initialize DataTables for each specialty table
        <?php
        $licenses_result->data_seek(0); // Reset result pointer
        while ($license = $licenses_result->fetch_assoc()): ?>
            $("#basic-datatables-<?php echo $license['license_id']; ?>").DataTable({});
        <?php endwhile; ?>
    });
</script>