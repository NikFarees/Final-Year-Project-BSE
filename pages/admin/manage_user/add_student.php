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
    $role_id = 'student';
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
    if (!empty($ic) && !preg_match('/^\d{12}$/', $ic)) {
        $errors[] = "IC must be exactly 12 digits.";
    }

    // Extract and validate DOB from IC
    if (!empty($ic) && preg_match('/^\d{12}$/', $ic)) {
        $year = substr($ic, 0, 2);
        $month = substr($ic, 2, 2);
        $day = substr($ic, 4, 2);
        $current_year = date('Y');
        $year_prefix = ($year > substr($current_year, 2, 2)) ? '19' : '20';
        $dob = "$year_prefix$year-$month-$day";

        if (!checkdate($month, $day, $year_prefix . $year)) {
            $errors[] = "Invalid IC format: the extracted date is not valid.";
        }
    }

    // Check for duplicate IC
    $ic_check_query = $conn->prepare("SELECT * FROM users WHERE ic = ?");
    $ic_check_query->bind_param("s", $ic);
    $ic_check_query->execute();
    $ic_check_result = $ic_check_query->get_result();
    if ($ic_check_result->num_rows > 0) {
        $errors[] = "An account with this IC already exists.";
    }

    // Check for duplicate username
    $username_check_query = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $username_check_query->bind_param("s", $username);
    $username_check_query->execute();
    $username_check_result = $username_check_query->get_result();
    if ($username_check_result->num_rows > 0) {
        $errors[] = "An account with this username already exists.";
    }

    // Check for duplicate email
    $email_check_query = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $email_check_query->bind_param("s", $email);
    $email_check_query->execute();
    $email_check_result = $email_check_query->get_result();
    if ($email_check_result->num_rows > 0) {
        $errors[] = "An account with this email already exists.";
    }

    if (empty($errors)) {
        $conn->begin_transaction();
        try {
            $user_count_query = $conn->query("SELECT MAX(CAST(SUBSTRING(user_id, 3, 3) AS UNSIGNED)) AS max_id FROM users");
            $user_count = ($user_count_query->fetch_assoc()['max_id'] ?? 0) + 1;
            $formatted_user_id = sprintf('US%03d%s%s%s', $user_count, $currentDay, $currentMonth, $currentYear);

            $result = $conn->query("SELECT student_id FROM students ORDER BY student_id DESC LIMIT 1");
            $nextNumber = ($result->num_rows > 0) ? ((int)substr($result->fetch_assoc()['student_id'], 2, 3) + 1) : 1;
            $student_id = sprintf("ST%03d%03d", $nextNumber, $user_count);

            $password_hashed = hash('sha256', $password);

            $stmt = $conn->prepare("INSERT INTO users (user_id, role_id, ic, name, username, password, email, address, phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssss", $formatted_user_id, $role_id, $ic, $name, $username, $password_hashed, $email, $address, $phone);
            $stmt->execute();

            $stmt = $conn->prepare("INSERT INTO students (student_id, user_id, dob) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $student_id, $formatted_user_id, $dob);
            $stmt->execute();

            $conn->commit();
            $successMessage = "New student added successfully.";
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
                    <a href="#">Add Student</a>
                </li>
            </ul>
        </div>
        
        <div class="page-category">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header text-center">
                        <h3 class="fw-bold mb-3">Add Student</h3>
                        <p class="text-muted text-center">Fill the details of the student</p>
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
                            <button type="submit" class="btn btn-primary mt-3 d-block mx-auto">Add Student</button>
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