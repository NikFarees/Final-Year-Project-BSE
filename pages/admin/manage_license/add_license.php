<?php
include '../../../include/ad_header.php';
include '../../../database/db_connection.php'; // Include the database connection file

// Initialize error messages and success message
$errors = [];
$successMessage = "";

// Function to generate license_id using the max existing ID
function generateLicenseID($conn)
{
    $sql = "SELECT MAX(CAST(SUBSTRING(license_id, 4) AS UNSIGNED)) AS max_id FROM licenses";
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        $maxID = $row['max_id'] ?? 0; // Get the maximum ID or 0 if none exists
        $newID = $maxID + 1;
        return 'LIC' . str_pad($newID, 2, '0', STR_PAD_LEFT); // Format as LICxx
    }
    return 'LIC01'; // Default if query fails
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $license_name = trim($_POST['license_name']);
    $license_type = trim($_POST['license_type']);
    $description = trim($_POST['description']);
    $license_fee = trim($_POST['license_fee']);

    // Validate inputs
    if (empty($license_name)) $errors[] = "License name is required.";
    if (empty($license_fee)) $errors[] = "License fee is required.";
    if (!is_numeric($license_fee)) $errors[] = "License fee must be a valid number.";

    // If no errors, proceed with database insertion
    if (empty($errors)) {
        $license_id = generateLicenseID($conn); // Generate license_id
        $sql = "INSERT INTO licenses (license_id, license_name, license_type, description, license_fee) 
                VALUES ('$license_id', '$license_name', '$license_type', '$description', '$license_fee')";

        if ($conn->query($sql) === TRUE) {
            $successMessage = "New license added successfully with ID: $license_id.";
        } else {
            $errors[] = "Error: " . $sql . "<br>" . $conn->error;
        }
    }
}
?>

<div class="container">
    <div class="page-inner">
        <!-- Breadcrumbs -->
        <div class="page-header">
            <h4 class="page-title">Manage Instructor</h4>
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
                    <a href="#">Add License</a>
                </li>
            </ul>
        </div>

        <div class="page-category">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="fw-bold mb-3 text-center">Add License</h3>
                        <p class="text-muted text-center">Fill the details of the license</p>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="license_name">License Name</label>
                                    <input type="text" class="form-control" id="license_name" name="license_name" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="license_type">License Type</label>
                                    <input type="text" class="form-control" id="license_type" name="license_type">
                                </div>
                            </div>
                            <div class="form-group mb-3">
                                <label for="description">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            </div>
                            <div class="form-group mb-3">
                                <label for="license_fee">License Fee (RM)</label>
                                <input type="text" class="form-control" id="license_fee" name="license_fee" required>
                            </div>
                            <button type="submit" class="btn btn-primary mt-3 d-block mx-auto">Add License</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    <?php if (!empty($errors)): ?>
        Swal.fire({
            title: "Error!",
            html: "<?php echo implode('<br>', $errors); ?>",
            icon: "error",
        });
    <?php endif; ?>

    <?php if (!empty($successMessage)): ?>
        Swal.fire({
            title: "Success!",
            text: "<?php echo $successMessage; ?>",
            icon: "success",
            confirmButtonText: "OK"
        }).then(() => {
            window.location.href = 'list_license.php';
        });
    <?php endif; ?>
});
</script>

<?php include '../../../include/footer.html'; ?>
