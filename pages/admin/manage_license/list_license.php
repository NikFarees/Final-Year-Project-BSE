<?php
include '../../../include/ad_header.php';
include '../../../database/db_connection.php'; // Include the database connection file

// Fetch licenses from the database
$sql = "SELECT * FROM licenses";
$result = $conn->query($sql);
?>

<div class="container">
    <div class="page-inner">

        <!-- Breadcrumbs -->
        <div class="page-header">
            <h4 class="page-title">Manage License</h4>
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
                    <a href="#">License List</a>
                </li>
            </ul>
        </div>

        <!-- Inner page content -->
        <div class="page-category">


            <!-- Card Structure with Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title">License List</h4>
                    <div class="ms-md-auto py-2 py-md-0">
                        <a href="add_license.php" class="btn btn-primary btn-round">Add License</a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Table Section -->
                    <div class="table-responsive">
                        <table id="basic-datatables" class="display table table-striped">
                            <thead>
                                <tr>
                                    <th>License ID</th>
                                    <th>License Name</th>
                                    <th>License Type</th>
                                    <th>Description</th>
                                    <th>License Fee</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . $row['license_id'] . "</td>";
                                        echo "<td>" . $row['license_name'] . "</td>";
                                        echo "<td>" . $row['license_type'] . "</td>";
                                        echo "<td>" . ($row['description'] ? $row['description'] : "") . "</td>";
                                        echo "<td>" . $row['license_fee'] . "</td>";
                                        echo "<td>";
                                        echo "<a href='edit_license.php?id=" . $row['license_id'] . "' class='text-dark me-3'>Edit</a>";
                                        echo "<a href='delete_license.php?id=" . $row['license_id'] . "' class='text-danger'>Delete</a>";
                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='6'>No licenses found</td></tr>";
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
        $("#basic-datatables").DataTable({});
    });
</script>