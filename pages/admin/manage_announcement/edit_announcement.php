<?php
include '../../../include/ad_header.php';
include '../../../database/db_connection.php'; // Include the database connection file
?>

<!-- Include SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
// Initialize error messages
$errors = [];
$successMessage = "";

// Get announcement ID from query parameter
$announcement_id = isset($_GET['id']) ? $_GET['id'] : '';

// Fetch current announcement data
$sql = "SELECT * FROM announcements WHERE announcement_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $announcement_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Announcement not found!',
                confirmButtonText: 'Back to Manage Announcements'
            }).then(() => {
                window.location.href = 'list_announcement.php';
            });
          </script>";
    exit;
}

$announcement = $result->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);

    // Validate inputs
    if (empty($title)) $errors[] = "Title is required.";
    if (empty($description)) $errors[] = "Description is required.";

    // If no errors, proceed with database update
    if (empty($errors)) {
        // Update announcement data in the announcements table
        $sql = "UPDATE announcements SET 
                    title = ?, 
                    description = ? 
                WHERE announcement_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "sss",
            $title,
            $description,
            $announcement_id
        );

        if ($stmt->execute()) {
            echo "<script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Announcement details updated successfully!',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.href = 'list_announcement.php';
                    });
                  </script>";
            exit;
        } else {
            $errors[] = "Error updating announcement: " . $stmt->error;
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
            <h4 class="page-title">Manage Announcements</h4>
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
                    <a href="/pages/admin/manage_announcement/list_announcement.php">Announcement List</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Edit Announcement</a>
                </li>
            </ul>
        </div>

        <div class="page-category">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="fw-bold mb-3 text-center">Edit Announcement</h3>
                        <p class="text-muted text-center">Update the details of the announcement</p>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="form-group mb-3">
                                <label for="title">Title</label>
                                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($announcement['title']); ?>" required>
                            </div>
                            <div class="form-group mb-3">
                                <label for="description">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="4" required><?php echo htmlspecialchars($announcement['description']); ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary mt-3 d-block mx-auto">Update Announcement</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../../include/footer.html'; ?>