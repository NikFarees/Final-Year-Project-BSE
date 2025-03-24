<?php
include '../../../include/ad_header.php';
include '../../../database/db_connection.php'; // Include the database connection file

// Initialize error messages
$errors = [];
$successMessage = "";

// Get license ID from query parameter safely
$license_id = isset($_GET['id']) ? $conn->real_escape_string($_GET['id']) : '';

// Fetch current license data
$sql = "SELECT * FROM licenses WHERE license_id = '$license_id'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    echo "<div class='alert alert-danger text-center'>License not found. <a href='list_license.php' class='btn btn-primary btn-round mt-3'>Back to Manage License</a></div>";
    exit;
}

$license = $result->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $license_name = trim($_POST['license_name']);
    $license_type = trim($_POST['license_type']);
    $description = trim($_POST['description']);
    $license_fee = trim($_POST['license_fee']);

    // Validate inputs
    if (empty($license_name)) $errors[] = "License name is required.";
    if (empty($license_fee)) $errors[] = "License fee is required.";

    // If no errors, proceed with database update
    if (empty($errors)) {
        // Update license data in the licenses table
        $sql = "UPDATE licenses SET 
                    license_name = '$license_name', 
                    license_type = '$license_type', 
                    description = '$description', 
                    license_fee = '$license_fee' 
                WHERE license_id = '$license_id'";

        if ($conn->query($sql) === TRUE) {
            $successMessage = "License details updated successfully.";
        } else {
            $errors[] = "Error updating license: " . $conn->error;
        }
    }
}
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
                    <a href="/pages/admin/manage_license/list_license.php">License List</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Edit License</a>
                </li>
            </ul>
        </div>

        <!-- Inner page content -->
        <div class="page-category">

            <div class="card">
                <div class="card-header">
                    <h3 class="fw-bold mb-3 text-center">Edit License</h3>
                    <p class="text-muted text-center">Update the details of the license</p>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($successMessage)): ?>
                        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                        <script>
                            Swal.fire({
                                title: "Success",
                                text: "<?php echo $successMessage; ?>",
                                icon: "success",
                                confirmButtonText: "OK"
                            }).then(() => {
                                window.location.href = 'list_license.php';
                            });
                        </script>
                    <?php else: ?>
                        <form method="POST" action="">
                            <!-- License Name and Type -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="license_name">License Name</label>
                                    <input type="text" class="form-control" id="license_name" name="license_name" value="<?php echo htmlspecialchars($license['license_name']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="license_type">License Type</label>
                                    <input type="text" class="form-control" id="license_type" name="license_type" value="<?php echo htmlspecialchars($license['license_type']); ?>">
                                </div>
                            </div>

                            <!-- description -->
                            <div class="form-group mb-3">
                                <label for="description">description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($license['description']); ?></textarea>
                            </div>

                            <!-- License Fee -->
                            <div class="form-group mb-3">
                                <label for="license_fee">License Fee (RM)</label>
                                <input type="text" class="form-control" id="license_fee" name="license_fee" value="<?php echo htmlspecialchars($license['license_fee']); ?>" required>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" class="btn btn-primary mt-3 d-block mx-auto">Update License</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </div>
</div>

<?php
include '../../../include/footer.html';
?>
