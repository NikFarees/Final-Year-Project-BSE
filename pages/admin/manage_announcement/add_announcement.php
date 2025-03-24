<?php
ob_start(); // Start output buffering
include '../../../include/ad_header.php';
include '../../../database/db_connection.php';

// Assume $current_user_id is the ID of the currently logged-in user
$current_user_id = $_SESSION['user_id'];

// Initialize error messages and success message
$errors = [];
$successMessage = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $audience = $_POST['audience'];
    $specific_user = $_POST['specific_user'] ?? null;

    // Set timezone to match your laptop's timezone
    date_default_timezone_set('Asia/Kuala_Lumpur');

    // Generate announcement_id
    $date = date('dmy');
    $announcementCountQuery = "SELECT MAX(CAST(SUBSTRING(announcement_id, 2, 3) AS UNSIGNED)) AS max_id FROM announcements";
    $announcementCountResult = $conn->query($announcementCountQuery);
    $announcementCountRow = $announcementCountResult->fetch_assoc();
    $announcementCount = $announcementCountRow['max_id'] + 1;
    $announcement_id = sprintf('A%03d%s', $announcementCount, $date);

    // Insert announcement
    $insertAnnouncementQuery = "INSERT INTO announcements (announcement_id, title, description, created_by) VALUES (?, ?, ?, ?)";
    $insertAnnouncementStmt = $conn->prepare($insertAnnouncementQuery);
    $insertAnnouncementStmt->bind_param("ssss", $announcement_id, $title, $description, $current_user_id);
    
    if ($insertAnnouncementStmt->execute()) {
        // Handle audience
        if ($audience == 'all' || $audience == 'instructor' || $audience == 'student') {
            // Generate role_announcement_id
            $roleAnnouncementCountQuery = "SELECT MAX(CAST(SUBSTRING(role_announcement_id, 3, 3) AS UNSIGNED)) AS max_id FROM role_announcements";
            $roleAnnouncementCountResult = $conn->query($roleAnnouncementCountQuery);
            $roleAnnouncementCountRow = $roleAnnouncementCountResult->fetch_assoc();
            $roleAnnouncementCount = $roleAnnouncementCountRow['max_id'] + 1;

            if ($audience == 'all') {
                // Insert for both instructor and student roles
                $roles = ['instructor', 'student'];
                foreach ($roles as $role) {
                    $role_announcement_id = sprintf('RA%03d%s', $roleAnnouncementCount++, substr($announcement_id, 1, 3));
                    $insertRoleAnnouncementQuery = "INSERT INTO role_announcements (role_announcement_id, announcement_id, role_id) VALUES (?, ?, ?)";
                    $insertRoleAnnouncementStmt = $conn->prepare($insertRoleAnnouncementQuery);
                    $insertRoleAnnouncementStmt->bind_param("sss", $role_announcement_id, $announcement_id, $role);
                    $insertRoleAnnouncementStmt->execute();
                }
            } else {
                $role_id = $audience == 'instructor' ? 'instructor' : 'student';
                $role_announcement_id = sprintf('RA%03d%s', $roleAnnouncementCount, substr($announcement_id, 1, 3));
                $insertRoleAnnouncementQuery = "INSERT INTO role_announcements (role_announcement_id, announcement_id, role_id) VALUES (?, ?, ?)";
                $insertRoleAnnouncementStmt = $conn->prepare($insertRoleAnnouncementQuery);
                $insertRoleAnnouncementStmt->bind_param("sss", $role_announcement_id, $announcement_id, $role_id);
                $insertRoleAnnouncementStmt->execute();
            }
        } elseif ($audience == 'specific_instructor' || $audience == 'specific_student') {
            // Generate user_announcement_id
            $userAnnouncementCountQuery = "SELECT MAX(CAST(SUBSTRING(user_announcement_id, 3, 3) AS UNSIGNED)) AS max_id FROM user_announcements";
            $userAnnouncementCountResult = $conn->query($userAnnouncementCountQuery);
            $userAnnouncementCountRow = $userAnnouncementCountResult->fetch_assoc();
            $userAnnouncementCount = $userAnnouncementCountRow['max_id'] + 1;
            $user_announcement_id = sprintf('UA%03d%s', $userAnnouncementCount, substr($announcement_id, 1, 3));

            // Insert user announcement
            $insertUserAnnouncementQuery = "INSERT INTO user_announcements (user_announcement_id, user_id, announcement_id) VALUES (?, ?, ?)";
            $insertUserAnnouncementStmt = $conn->prepare($insertUserAnnouncementQuery);
            $insertUserAnnouncementStmt->bind_param("sss", $user_announcement_id, $specific_user, $announcement_id);
            $insertUserAnnouncementStmt->execute();
        }

        $successMessage = "Announcement added successfully.";
    } else {
        $errors[] = "Error: " . $insertAnnouncementStmt->error;
    }
}
ob_end_flush(); // Flush the output buffer
?>

<div class="container">
    <div class="page-inner">

        <!-- Breadcrumbs -->
        <div class="page-header">
            <h4 class="page-title">Dashboard</h4>
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
                    <a href="/pages/admin/manage_announcement/list_announcement.php">List Announcement</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Add Announcement</a>
                </li>
            </ul>
        </div>

        <!-- Inner page content -->
        <div class="page-category">

            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Add Announcement</h4>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="form-group">
                                    <label for="title">Title</label>
                                    <input type="text" class="form-control" id="title" name="title" required>
                                </div>
                                <div class="form-group">
                                    <label for="description">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="audience">Select Audience</label>
                                    <select class="form-control" id="audience" name="audience" required>
                                        <option value="all">All Users (Instructor and Student)</option>
                                        <option value="instructor">Instructor</option>
                                        <option value="student">Student</option>
                                        <option value="specific_instructor">Specific Instructor</option>
                                        <option value="specific_student">Specific Student</option>
                                    </select>
                                </div>
                                <div class="form-group" id="specific-user-group" style="display: none;">
                                    <label for="specific_user">Select Specific User</label>
                                    <select class="form-control" id="specific_user" name="specific_user">
                                        <!-- Options will be populated by JavaScript -->
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary mt-3 d-block mx-auto">Add Announcement</button>
                            </form>
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
            window.location.href = 'list_announcement.php';
        });
    <?php endif; ?>
});

document.getElementById('audience').addEventListener('change', function() {
    var specificUserGroup = document.getElementById('specific-user-group');
    var specificUserSelect = document.getElementById('specific_user');
    specificUserSelect.innerHTML = ''; // Clear previous options

    if (this.value === 'specific_instructor' || this.value === 'specific_student') {
        specificUserGroup.style.display = 'block';

        // Fetch users based on the selected audience
        var role = this.value === 'specific_instructor' ? 'instructor' : 'student';
        fetch('fetch_users.php?role=' + role)
            .then(response => response.json())
            .then(data => {
                data.forEach(user => {
                    var option = document.createElement('option');
                    option.value = user.user_id;
                    option.textContent = user.user_id + ' - ' + user.name;
                    specificUserSelect.appendChild(option);
                });
            });
    } else {
        specificUserGroup.style.display = 'none';
    }
});
</script>