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

// Get student ID from query parameter
$student_id = isset($_GET['id']) ? $_GET['id'] : '';

// Fetch current student data
$sql = "SELECT u.*, s.student_id 
        FROM users u 
        JOIN students s ON u.user_id = s.user_id 
        WHERE s.student_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Student not found!',
                confirmButtonText: 'Back to Manage Student'
            }).then(() => {
                window.location.href = 'list_student.php';
            });
          </script>";
    exit;
}

$student = $result->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ic = trim($_POST['ic']);
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);

    // Validate inputs
    if (empty($ic)) $errors[] = "IC is required.";
    if (empty($name)) $errors[] = "Name is required.";
    if (empty($username)) $errors[] = "Username is required.";
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    if (!empty($new_password) && $new_password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    // If no errors, proceed with database update
    if (empty($errors)) {
        $password_hashed = !empty($new_password) ? hash('sha256', $new_password) : $student['password'];

        // Update user data in the users table
        $sql = "UPDATE users SET 
                    ic = ?, 
                    name = ?, 
                    username = ?, 
                    password = ?, 
                    email = ?, 
                    address = ?, 
                    phone = ? 
                WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "ssssssss",
            $ic,
            $name,
            $username,
            $password_hashed,
            $email,
            $address,
            $phone,
            $student['user_id']
        );

        if ($stmt->execute()) {
            echo "<script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Student details updated successfully!',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.href = 'list_student.php';
                    });
                  </script>";
            exit;
        } else {
            $errors[] = "Error updating student: " . $stmt->error;
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
            <h4 class="page-title">Manage Student</h4>
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
                    <a href="/pages/admin/manage_student/list_student.php">Student List</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Edit Student</a>
                </li>
            </ul>
        </div>

        <div class="page-category">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="fw-bold mb-3 text-center">Edit Student</h3>
                        <p class="text-muted text-center">Update the details of the student</p>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="name">Name</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($student['name']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="username">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($student['username']); ?>" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="email">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="ic">IC</label>
                                    <input type="text" class="form-control" id="ic" name="ic" value="<?php echo htmlspecialchars($student['ic']); ?>" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="new_password">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password">
                                </div>
                                <div class="col-md-6">
                                    <label for="confirm_password">Confirm Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label for="address">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($student['address']); ?></textarea>
                            </div>

                            <div class="form-group mb-4">
                                <label for="phone">Phone</label>
                                <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($student['phone']); ?>">
                            </div>

                            <button type="submit" class="btn btn-primary mt-3 d-block mx-auto">Update Student</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../../include/footer.html'; ?>