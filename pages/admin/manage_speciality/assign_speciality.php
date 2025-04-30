<?php
include '../../../include/ad_header.php';
include '../../../database/db_connection.php'; // Include the database connection file

// Include SweetAlert2
echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";

// Initialize error messages
$errors = [];
$successMessage = "";

// Get license ID from query parameter
$license_id = isset($_GET['license_id']) ? $_GET['license_id'] : '';

// Fetch license details
$sql = "SELECT * FROM licenses WHERE license_id = '$license_id'";
$license_result = $conn->query($sql);

if ($license_result->num_rows == 0) {
    echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'License not found!',
                confirmButtonText: 'Back to Manage Specialty'
            }).then(() => {
                window.location.href = 'speciality.php';
            });
          </script>";
    exit;
}

$license = $license_result->fetch_assoc();

// Fetch instructors for the form, excluding those already assigned to this specialty
$instructors = $conn->query("SELECT i.instructor_id, u.name 
                             FROM users u 
                             JOIN instructors i ON u.user_id = i.user_id 
                             WHERE i.instructor_id NOT IN (SELECT instructor_id FROM specialities WHERE license_id = '$license_id')");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $instructor_id = isset($_POST['instructor_id']) ? $_POST['instructor_id'] : '';

    // Validate inputs
    if (empty($instructor_id)) $errors[] = "Instructor is required.";

    // If no errors, proceed with database insertion
    if (empty($errors)) {
        // Generate the speciality_id
        $instructor_part = substr($instructor_id, 2, 3);
        $license_part = substr($license_id, 3);

        // Get the last inserted speciality_id for incrementing
        $sql = "SELECT speciality_id FROM specialities ORDER BY speciality_id DESC LIMIT 1";
        $result = $conn->query($sql);
        $last_speciality_id = $result->fetch_assoc()['speciality_id'];

        // Increment the specialty ID value
        if ($last_speciality_id) {
            $last_num = (int) substr($last_speciality_id, 3, 3);
            $new_speciality_num = str_pad($last_num + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $new_speciality_num = '001';
        }

        $speciality_id = "SPE" . $new_speciality_num . $instructor_part . $license_part;

        // Insert new specialty into the specialties table
        $sql = "INSERT INTO specialities (speciality_id, instructor_id, license_id) 
                VALUES ('$speciality_id', '$instructor_id', '$license_id')";
        if ($conn->query($sql) === TRUE) {
            echo "<script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Specialty assigned successfully!',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.href = 'list_speciality.php?id=$license_id';
                    });
                  </script>";
            exit;
        } else {
            $errors[] = "Error: " . $conn->error;
        }
    }
}

// Display validation errors using SweetAlert2
if (!empty($errors)) {
    $error_messages = implode("<br>", $errors);
    echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                html: '$error_messages',
                confirmButtonText: 'OK'
            });
          </script>";
}
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
                    <a href="#">Assign Speciality</a>
                </li>
            </ul>
        </div>

        <div class="page-category">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="fw-bold mb-3 text-center">Assign Specialty</h3>
                        <p class="text-muted text-center">Assign specialty to an instructor for <?php echo $license['license_name']; ?></p>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="form-group" style="width: 50%; margin: 0 auto;">
                                <label for="instructor_id" style="display: block; text-align: center;">Select an Instructor:</label>
                                <select class="form-control" id="instructor_id" name="instructor_id" required>
                                    <option value="">--Select an Instructor--</option>
                                    <?php while ($instructor = $instructors->fetch_assoc()): ?>
                                        <option value="<?php echo $instructor['instructor_id']; ?>"><?php echo $instructor['name']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary mt-3 d-block mx-auto">Assign Specialty</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../../include/footer.html'; ?>