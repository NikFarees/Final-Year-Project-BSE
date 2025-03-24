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

// Get instructor ID from query parameter
$instructor_id = isset($_GET['id']) ? $_GET['id'] : '';

// Fetch current instructor data
$sql = "SELECT u.*, i.instructor_id 
        FROM users u 
        JOIN instructors i ON u.user_id = i.user_id 
        WHERE i.instructor_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $instructor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Instructor not found!',
                confirmButtonText: 'Back to Manage Instructor'
            }).then(() => {
                window.location.href = 'instructor.php';
            });
          </script>";
    exit;
}

$instructor = $result->fetch_assoc();

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
        $password_hashed = !empty($new_password) ? password_hash($new_password, PASSWORD_DEFAULT) : $instructor['password'];

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
            $instructor['user_id']
        );

        if ($stmt->execute()) {
            echo "<script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Instructor details updated successfully!',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.href = 'list_instructor.php';
                    });
                  </script>";
            exit;
        } else {
            $errors[] = "Error updating instructor: " . $stmt->error;
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
            <h4 class="page-title">Manage Instructor</h4>
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
                    <a href="/pages/admin/manage_instructor/list_instructor.php">Instructor List</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Edit Instructor</a>
                </li>
            </ul>
        </div>

        <div class="page-category">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="fw-bold mb-3 text-center">Edit Instructor</h3>
                        <p class="text-muted text-center">Update the details of the instructor</p>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="name">Name</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($instructor['name']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="username">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($instructor['username']); ?>" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="email">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($instructor['email']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="ic">IC</label>
                                    <input type="text" class="form-control" id="ic" name="ic" value="<?php echo htmlspecialchars($instructor['ic']); ?>" required>
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
                                <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($instructor['address']); ?></textarea>
                            </div>

                            <div class="form-group mb-4">
                                <label for="phone">Phone</label>
                                <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($instructor['phone']); ?>">
                            </div>

                            <button type="submit" class="btn btn-primary mt-3 d-block mx-auto">Update Instructor</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../../include/footer.html'; ?>