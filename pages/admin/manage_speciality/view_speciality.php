<?php
include '../../../include/ad_header.php';
include '../../../database/db_connection.php';

// Get license ID from query parameter
$license_id = isset($_GET['id']) ? $conn->real_escape_string($_GET['id']) : '';

if (empty($license_id)) {
    echo "<div class='alert alert-danger text-center'>Invalid License ID. <a href='speciality.php' class='btn btn-primary btn-round mt-3'>Back to Manage Specialty</a></div>";
    exit;
}

// Fetch license details
$sql = "SELECT * FROM licenses WHERE license_id = '$license_id'";
$license_result = $conn->query($sql);

if ($license_result->num_rows == 0) {
    echo "<div class='alert alert-danger text-center'>License not found. <a href='speciality.php' class='btn btn-primary btn-round mt-3'>Back to Manage Specialty</a></div>";
    exit;
}

$license = $license_result->fetch_assoc();

// Fetch specialities and instructors assigned to this specialty
$sql = "
    SELECT s.speciality_id, u.name AS instructor_name, s.instructor_id
    FROM specialities s
    JOIN instructors i ON s.instructor_id = i.instructor_id
    JOIN users u ON i.user_id = u.user_id
    WHERE s.license_id = '$license_id'
";
$instructors_result = $conn->query($sql);
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
                    <a href="/pages/admin/manage_speciality/list_speciality.php">Speciality List</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">View Speciality</a>
                </li>
            </ul>
        </div>

        <!-- Inner page content -->
        <div class="page-category">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="card-title"><?php echo htmlspecialchars($license['license_name']); ?> (<?php echo htmlspecialchars($license['license_type']); ?>)</h4>
                            <div class="ms-md-auto py-2 py-md-0">
                                <a href="../manage_speciality/assign_speciality.php?license_id=<?php echo htmlspecialchars($license_id); ?>" class="btn btn-primary btn-round">Assign Speciality</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if ($instructors_result->num_rows > 0): ?>
                                <table class="table table-striped" id="basic-datatables">
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