<?php
include '../../../include/st_header.php';
include '../../../database/db_connection.php';

$errors = [];
$successMessage = "";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch feedback categories
$categories = $conn->query("SELECT * FROM feedback_categories");

// Fetch possible target users (instructors)
$instructors = $conn->query("
    SELECT u.user_id, u.name 
    FROM users u
    JOIN instructors i ON u.user_id = i.user_id
");

// Function to generate feedback_id
function generateFeedbackID($conn)
{
    $prefix = "FB";
    $date = date("dmy"); // e.g., 160424
    $basePattern = $prefix . '%' . $date;

    $feedback_id = "";

    do {
        // Get max 3-digit number for today
        $sql = "
            SELECT MAX(CAST(SUBSTRING(feedback_id, 3, 3) AS UNSIGNED)) AS max_id
            FROM feedback
            WHERE feedback_id LIKE '$basePattern'
        ";
        $result = $conn->query($sql);
        $maxID = 0;

        if ($result && $row = $result->fetch_assoc()) {
            $maxID = (int)$row['max_id'];
        }

        $newID = str_pad($maxID + 1, 3, '0', STR_PAD_LEFT);
        $feedback_id = $prefix . $newID . $date;

        // Ensure uniqueness even in edge cases
        $check = $conn->prepare("SELECT 1 FROM feedback WHERE feedback_id = ?");
        $check->bind_param("s", $feedback_id);
        $check->execute();
        $check->store_result();
        $exists = $check->num_rows > 0;
    } while ($exists); // Retry if already exists

    return $feedback_id;
}



if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $feedback_category_id = trim($_POST['feedback_category_id']);
    $description = trim($_POST['description']);
    $target_user_id = !empty($_POST['target_user_id']) ? $_POST['target_user_id'] : null; // Set to NULL if empty

    if (empty($feedback_category_id)) $errors[] = "Feedback category is required.";
    if (empty($description)) $errors[] = "Description cannot be empty.";

    if (empty($errors)) {
        $feedback_id = generateFeedbackID($conn); // Generate feedback_id
        $stmt = $conn->prepare("INSERT INTO feedback (feedback_id, feedback_category_id, user_id, target_user_id, description) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $feedback_id, $feedback_category_id, $user_id, $target_user_id, $description);
        if ($stmt->execute()) {
            $successMessage = "Feedback submitted successfully with ID: $feedback_id.";
        } else {
            $errors[] = "Error submitting feedback: " . $stmt->error;
        }
    }
}
?>

<div class="container">
    <div class="page-inner">
        <!-- Breadcrumbs -->
        <div class="page-header">
            <h4 class="page-title">My Feedback</h4>
            <ul class="breadcrumbs">
                <li class="nav-home">
                    <a href="/pages/student/dashboard.php">
                        <i class="icon-home"></i>
                    </a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="/pages/student/manage_feedback/list_feedback.php">Feedback List</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Add Feedback</a>
                </li>
            </ul>
        </div>

        <div class="page-category">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header text-center">
                        <h3 class="fw-bold mb-3">Add Feedback</h3>
                        <p class="text-muted">Fill in your feedback details</p>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="feedback_category_id">Feedback Category</label>
                                <select class="form-control" name="feedback_category_id" required>
                                    <option value="">-- Select Category --</option>
                                    <?php while ($cat = $categories->fetch_assoc()): ?>
                                        <option value="<?= $cat['feedback_category_id'] ?>"><?= htmlspecialchars($cat['feedback_name']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="target_user_id">Target Instructor (optional)</label>
                                <select class="form-control" name="target_user_id">
                                    <option value="">-- General Feedback --</option>
                                    <?php while ($inst = $instructors->fetch_assoc()): ?>
                                        <option value="<?= $inst['user_id'] ?>"><?= htmlspecialchars($inst['name']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="description">Feedback Description</label>
                                <textarea class="form-control" name="description" rows="4" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary d-block mx-auto">Submit Feedback</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
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