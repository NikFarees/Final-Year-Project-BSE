<?php
session_start();
include '../database/db_connection.php';

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Prepared statement to prevent SQL injection
    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // Hash the input password and compare with stored hash
            $hashed_password = hash('sha256', $password);

            if ($hashed_password === $user['password']) {
                // Regenerate session ID for security
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['role_id'] = $user['role_id'];

                // Redirect based on user role (ensure the role_id is numeric)
                switch ($user['role_id']) {
                    case 'admin':
                        header("Location: ../pages/admin/dashboard.php");
                        break;
                    case 'instructor':
                        header("Location: ../pages/instructor/dashboard.php");
                        break;
                    case 'student':
                        header("Location: ../pages/student/dashboard.php");
                        break;
                    default:
                        $errors[] = "Invalid role.";
                        break;
                }
                exit;
            } else {
                $errors[] = "Invalid password.";
            }
        } else {
            $errors[] = "No user found with that username.";
        }
        $stmt->close();
    } else {
        $errors[] = "Database error: Unable to prepare statement.";
    }

    // Store errors in session and redirect back
    $_SESSION['login_errors'] = $errors;
    header("Location: login_frontend.php");
    exit;
}
