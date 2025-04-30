<?php
include '../../../include/ad_header.php';
include '../../../database/db_connection.php'; // Include the database connection file

// Initialize error messages
$errors = [];
$successMessage = "";

// Get category ID from query parameter safely
$category_id = isset($_GET['id']) ? $conn->real_escape_string($_GET['id']) : '';

// Fetch current category data
$sql = "SELECT * FROM feedback_categories WHERE feedback_category_id = '$category_id'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    echo "<div class='alert alert-danger text-center'>Category not found. <a href='list_feedback.php' class='btn btn-primary btn-round mt-3'>Back to Feedback List</a></div>";
    exit;
}

$category = $result->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $feedback_name = trim($_POST['feedback_name']);
    $description = trim($_POST['description']);

    // Validate inputs
    if (empty($feedback_name)) $errors[] = "Category name is required.";

    // Check if name already exists (excluding current category)
    $check_sql = "SELECT * FROM feedback_categories WHERE feedback_name = '$feedback_name' AND feedback_category_id != '$category_id'";
    $check_result = $conn->query($check_sql);
    
    if ($check_result->num_rows > 0) {
        $errors[] = "A category with this name already exists.";
    }

    // If no errors, proceed with database update
    if (empty($errors)) {
        // Update category data in the feedback_categories table
        $sql = "UPDATE feedback_categories SET 
                    feedback_name = '$feedback_name', 
                    description = '$description'
                WHERE feedback_category_id = '$category_id'";

        if ($conn->query($sql) === TRUE) {
            $successMessage = "Category details updated successfully.";
        } else {
            $errors[] = "Error updating category: " . $conn->error;
        }
    }
}
?>

<div class="container">
    <div class="page-inner">

        <!-- Breadcrumbs -->
        <div class="page-header">
            <h4 class="page-title">Manage Feedback</h4>
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
                    <a href="/pages/admin/manage_feedback/list_feedback.php">Feedback List</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Edit Category</a>
                </li>
            </ul>
        </div>

        <!-- Inner page content -->
        <div class="page-category">

            <div class="card">
                <div class="card-header">
                    <h3 class="fw-bold mb-3 text-center">Edit Category</h3>
                    <p class="text-muted text-center">Update the details of the feedback category</p>
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
                                window.location.href = 'list_feedback.php';
                            });
                        </script>
                    <?php else: ?>
                        <form method="POST" action="">
                            <!-- Category Name -->
                            <div class="form-group mb-3">
                                <label for="feedback_name">Category Name</label>
                                <input type="text" class="form-control" id="feedback_name" name="feedback_name" value="<?php echo htmlspecialchars($category['feedback_name']); ?>" required>
                            </div>

                            <!-- Description -->
                            <div class="form-group mb-3">
                                <label for="description">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($category['description']); ?></textarea>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" class="btn btn-primary mt-3 d-block mx-auto">Update Category</button>
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