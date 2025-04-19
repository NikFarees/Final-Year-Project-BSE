<?php
ob_start(); // Start output buffering
include '../../../include/ad_header.php';
include '../../../database/db_connection.php'; // Include the database connection file

// Initialize variables
$errors = [];
$successMessage = "";

// Get feedback_id from the query string
if (!isset($_GET['feedback_id']) || empty($_GET['feedback_id'])) {
    die("Feedback ID is required.");
}
$feedback_id = $_GET['feedback_id'];

// Fetch feedback details
$feedback_query = "
    SELECT 
        f.feedback_id, 
        fc.feedback_name, 
        u.name AS sender_name, 
        tu.name AS target_name, 
        f.description, 
        f.status, 
        f.submitted_at 
    FROM feedback f
    JOIN feedback_categories fc ON f.feedback_category_id = fc.feedback_category_id
    JOIN users u ON f.user_id = u.user_id
    LEFT JOIN users tu ON f.target_user_id = tu.user_id
    WHERE f.feedback_id = ?
";
$stmt = $conn->prepare($feedback_query);
$stmt->bind_param("s", $feedback_id); // Treat as string
$stmt->execute();
$feedback_result = $stmt->get_result();
$feedback = $feedback_result->fetch_assoc();

if (!$feedback) {
    die("Feedback not found.");
}

// Fetch feedback reply
$reply_query = "
    SELECT 
        fr.reply_id, 
        fr.reply_text, 
        fr.reply_date, 
        u.name AS admin_name 
    FROM feedback_replies fr
    JOIN users u ON fr.admin_id = u.user_id
    WHERE fr.feedback_id = ?
";
$stmt = $conn->prepare($reply_query);
$stmt->bind_param("s", $feedback_id); // Treat as string
$stmt->execute();
$reply_result = $stmt->get_result();
$reply = $reply_result->fetch_assoc();

// Function to generate feedback_replies_id
function generateFeedbackReplyID($conn)
{
    $formattedDate = date("dmy"); // Format: dmy
    $likePattern = "FBRP%$formattedDate";

    $sql = "SELECT MAX(CAST(SUBSTRING(reply_id, 5, 3) AS UNSIGNED)) AS max_id 
            FROM feedback_replies 
            WHERE reply_id LIKE ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $likePattern);
    $stmt->execute();
    $result = $stmt->get_result();

    $maxID = 0;
    if ($result && $row = $result->fetch_assoc()) {
        $maxID = $row['max_id'] ?? 0;
    }

    $newID = $maxID + 1;
    return 'FBRP' . str_pad($newID, 3, '0', STR_PAD_LEFT) . $formattedDate;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $status = $_POST['status'];
    $reply_text = trim($_POST['reply_text']);
    $admin_id = $_SESSION['user_id']; // Admin ID as string

    // Update feedback status
    $update_status_query = "UPDATE feedback SET status = ? WHERE feedback_id = ?";
    $stmt = $conn->prepare($update_status_query);
    $stmt->bind_param("ss", $status, $feedback_id);
    if (!$stmt->execute()) {
        $errors[] = "Failed to update feedback status.";
    }

    // Update or insert reply
    if (empty($errors)) {
        if ($reply) {
            // Update existing reply
            $update_reply_query = "UPDATE feedback_replies SET reply_text = ?, reply_date = NOW() WHERE reply_id = ?";
            $stmt = $conn->prepare($update_reply_query);
            $stmt->bind_param("ss", $reply_text, $reply['reply_id']);
            if (!$stmt->execute()) {
                $errors[] = "Failed to update reply.";
            }
        } else {
            // Insert new reply
            $reply_id = generateFeedbackReplyID($conn);
            $insert_reply_query = "INSERT INTO feedback_replies (reply_id, feedback_id, admin_id, reply_text) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_reply_query);
            $stmt->bind_param("ssss", $reply_id, $feedback_id, $admin_id, $reply_text);
            if (!$stmt->execute()) {
                $errors[] = "Failed to add reply.";
            }
        }
    }

    if (empty($errors)) {
        $successMessage = "Feedback updated successfully.";
    }
}
?>

<!-- HTML Content -->
<div class="container">
    <div class="page-inner">
        <!-- Breadcrumbs -->
        <div class="page-header">
            <h4 class="page-title">Feedback Details</h4>
            <ul class="breadcrumbs">
                <li class="nav-home">
                    <a href="/pages/admin/dashboard.php"><i class="icon-home"></i></a>
                </li>
                <li class="separator"><i class="icon-arrow-right"></i></li>
                <li class="nav-item"><a href="/pages/admin/manage_feedback/list_feedback.php">Feedback List</a></li>
                <li class="separator"><i class="icon-arrow-right"></i></li>
                <li class="nav-item"><a href="#">View Feedback</a></li>
            </ul>
        </div>

        <div class="page-category">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="fw-bold mb-3 text-center">Feedback Details</h3>
                    </div>
                    <div class="card-body">
                        <p><strong>Category:</strong> <?= htmlspecialchars($feedback['feedback_name']) ?></p>
                        <p><strong>Sender:</strong> <?= htmlspecialchars($feedback['sender_name']) ?></p>
                        <p><strong>Target:</strong> <?= $feedback['target_name'] ? htmlspecialchars($feedback['target_name']) : 'General' ?></p>
                        <p><strong>Description:</strong> <?= nl2br(htmlspecialchars($feedback['description'])) ?></p>
                        <p><strong>Status:</strong> <?= ucfirst($feedback['status']) ?></p>
                        <p><strong>Submitted At:</strong> <?= date("d M Y, h:i A", strtotime($feedback['submitted_at'])) ?></p>

                        <form method="POST" action="">
                            <div class="form-group mb-3">
                                <label for="status">Change Status</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="pending" <?= $feedback['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="resolved" <?= $feedback['status'] == 'resolved' ? 'selected' : '' ?>>Resolved</option>
                                </select>
                            </div>
                            <div class="form-group mb-3">
                                <label for="reply_text">Reply</label>
                                <textarea class="form-control" id="reply_text" name="reply_text" rows="4"><?= $reply ? htmlspecialchars($reply['reply_text']) : '' ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary mt-3 d-block mx-auto">Update Feedback</button>
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
                window.location.href = '/pages/admin/manage_feedback/list_feedback.php'; // Redirect to list_feedback.php
            });
        <?php endif; ?>
    });
</script>

<?php
ob_end_flush(); // End output buffering
include '../../../include/footer.html';
?>