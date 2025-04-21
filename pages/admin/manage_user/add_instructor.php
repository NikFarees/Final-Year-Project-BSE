<?php
include '../../../include/ad_header.php';
include '../../../database/db_connection.php';

date_default_timezone_set('Asia/Kuala_Lumpur');
$currentDay = date('d');
$currentMonth = date('m');
$currentYear = date('y');
$errors = [];
$successMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $role_id = 'instructor';
    $ic = trim($_POST['ic']);
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirmPassword = trim($_POST['confirm_password']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);

    if (empty($ic)) $errors[] = "IC is required.";
    if (empty($name)) $errors[] = "Name is required.";
    if (empty($username)) $errors[] = "Username is required.";
    if (empty($password)) $errors[] = "Password is required.";
    if ($password !== $confirmPassword) $errors[] = "Passwords do not match.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format.";

    if (empty($errors)) {
        $conn->begin_transaction();
        try {
            $user_count_query = $conn->query("SELECT MAX(CAST(SUBSTRING(user_id, 3, 3) AS UNSIGNED)) AS max_id FROM users");
            $user_count = ($user_count_query->fetch_assoc()['max_id'] ?? 0) + 1;
            $formatted_user_id = sprintf('US%03d%s%s%s', $user_count, $currentDay, $currentMonth, $currentYear);

            $result = $conn->query("SELECT instructor_id FROM instructors ORDER BY instructor_id DESC LIMIT 1");
            $nextNumber = ($result->num_rows > 0) ? ((int)substr($result->fetch_assoc()['instructor_id'], 2, 3) + 1) : 1;
            $instructor_id = sprintf("IT%03d%03d", $nextNumber, $user_count);

            $password_hashed = hash('sha256', $password);

            $stmt = $conn->prepare("INSERT INTO users (user_id, role_id, ic, name, username, password, email, address, phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssss", $formatted_user_id, $role_id, $ic, $name, $username, $password_hashed, $email, $address, $phone);
            $stmt->execute();

            $stmt = $conn->prepare("INSERT INTO instructors (instructor_id, user_id) VALUES (?, ?)");
            $stmt->bind_param("ss", $instructor_id, $formatted_user_id);
            $stmt->execute();

            $conn->commit();
            $successMessage = "New instructor added successfully.";
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Error: " . $e->getMessage();
        }
    }
}
?>

<div class="container">
    <div class="page-inner">
        <!-- Breadcrumbs -->
        <div class="page-header">
            <h4 class="page-title">Manage User</h4>
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
                    <a href="/pages/admin/manage_user/list_users.php">User List</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Add Instructor</a>
                </li>
            </ul>
        </div>
        
        <div class="page-category">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header text-center">
                        <h3 class="fw-bold mb-3">Add Instructor</h3>
                        <p class="text-muted text-center">Fill the details of the instructor</p>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="row mb-3">
                                <div class="col-md-6"><label for="name">Name</label><input type="text" class="form-control" name="name" required></div>
                                <div class="col-md-6"><label for="username">Username</label><input type="text" class="form-control" name="username" required></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6"><label for="email">Email</label><input type="email" class="form-control" name="email" required></div>
                                <div class="col-md-6"><label for="ic">IC</label><input type="text" class="form-control" name="ic" required></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="password">Password</label>
                                    <input type="password" class="form-control" name="password" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="confirm_password">Confirm Password</label>
                                    <input type="password" class="form-control" name="confirm_password" required>
                                </div>
                            </div>
                            <div class="form-group mb-3"><label for="address">Address</label><textarea class="form-control" name="address" required></textarea></div>
                            <div class="form-group mb-3"><label for="phone">Phone</label><input type="text" class="form-control" name="phone" required></div>
                            <button type="submit" class="btn btn-primary mt-3 d-block mx-auto">Add Instructor</button>
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
                icon: "error"
            });
        <?php endif; ?>

        <?php if (!empty($successMessage)): ?>
            Swal.fire({
                title: "Success!",
                text: "<?php echo $successMessage; ?>",
                icon: "success",
                confirmButtonText: "OK"
            }).then(() => {
                window.location.href = 'list_users.php';
            });
        <?php endif; ?>
    });
</script>

<?php include '../../../include/footer.html'; ?>