<?php
include '../../../include/ad_header.php';
include '../../../database/db_connection.php';

$errors = [];
$successMessage = "";

// Function to generate unique feedback_category_id
function generateFeedbackCategoryID($conn) {
    $prefix = "FBCA";
    $maxTry = 5;
    $attempt = 0;

    do {
        // Get highest numeric suffix
        $sql = "SELECT MAX(CAST(SUBSTRING(feedback_category_id, 5) AS UNSIGNED)) AS max_id FROM feedback_categories";
        $result = $conn->query($sql);
        $maxID = 0;

        if ($result && $row = $result->fetch_assoc()) {
            $maxID = (int)$row['max_id'];
        }

        $newID = str_pad($maxID + 1, 2, '0', STR_PAD_LEFT);
        $generatedID = $prefix . $newID;

        // Double-check uniqueness in case of race condition
        $check = $conn->prepare("SELECT 1 FROM feedback_categories WHERE feedback_category_id = ?");
        $check->bind_param("s", $generatedID);
        $check->execute();
        $check->store_result();
        $exists = $check->num_rows > 0;
        $check->close();

        $attempt++;
    } while ($exists && $attempt < $maxTry);

    return $generatedID;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $feedback_name = trim($_POST['feedback_name']);
    $description = trim($_POST['description']);

    if (empty($feedback_name)) $errors[] = "Feedback category name is required.";

    if (empty($errors)) {
        $feedback_category_id = generateFeedbackCategoryID($conn);
        $stmt = $conn->prepare("INSERT INTO feedback_categories (feedback_category_id, feedback_name, description) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $feedback_category_id, $feedback_name, $description);

        if ($stmt->execute()) {
            $successMessage = "New feedback category added successfully with ID: $feedback_category_id.";
        } else {
            $errors[] = "Error inserting category: " . $stmt->error;
        }
        $stmt->close();
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
                    <a href="/pages/admin/dashboard.php"><i class="icon-home"></i></a>
                </li>
                <li class="separator"><i class="icon-arrow-right"></i></li>
                <li class="nav-item"><a href="/pages/admin/manage_feedback/list_feedback.php">Feedback List</a></li>
                <li class="separator"><i class="icon-arrow-right"></i></li>
                <li class="nav-item"><a href="#">Add Feedback Category</a></li>
            </ul>
        </div>

        <div class="page-category">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header text-center">
                        <h3 class="fw-bold mb-3">Add Feedback Category</h3>
                        <p class="text-muted">Fill the details of the feedback category</p>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="form-group mb-3">
                                <label for="feedback_name">Feedback Category Name</label>
                                <input type="text" class="form-control" id="feedback_name" name="feedback_name" required>
                            </div>
                            <div class="form-group mb-3">
                                <label for="description">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary mt-3 d-block mx-auto">Add Feedback Category</button>
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
            window.location.href = 'list_feedback.php';
        });
    <?php endif; ?>
});
</script>

<?php include '../../../include/footer.html'; ?>
