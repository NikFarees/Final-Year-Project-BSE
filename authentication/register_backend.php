<?php
session_start();
include '../database/db_connection.php'; // Include the database connection file

// Initialize error messages and success message
$errors = [];
$successMessage = "";

$name = $username = $password = $confirm_password = $email = $ic = $address = $phone = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $email = trim($_POST['email']);
    $ic = trim($_POST['ic']);
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);
    $role_id = 'student'; // Role ID for students

    // Validate inputs
    if (empty($name)) $errors[] = "Name is required.";
    if (empty($username)) $errors[] = "Username is required.";
    if (empty($password)) $errors[] = "Password is required.";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match.";
    if (empty($email)) $errors[] = "Email is required.";
    if (empty($ic)) $errors[] = "IC is required.";
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

    // If no errors, proceed with database insertion
    if (empty($errors)) {
        $hashed_password = hash('sha256', $password); // Hash the password using SHA256

        // Set timezone to match your laptop's timezone
        date_default_timezone_set('Asia/Kuala_Lumpur');

        // Generate `user_id` based on the last user_id and the current date
        $current_day = date('d');
        $current_month = date('m');
        $current_year = date('y');

        // Get the last user_id and increment the number part
        $user_count_query = $conn->query("SELECT MAX(CAST(SUBSTRING(user_id, 3, 3) AS UNSIGNED)) AS max_id FROM users");
        $user_count_row = $user_count_query->fetch_assoc();
        $user_count = $user_count_row['max_id'] + 1; // Increment the max user_id
        $formatted_user_id = sprintf('US%03d' . $current_day . $current_month . $current_year, $user_count);

        // Insert into the `users` table
        $stmt = $conn->prepare("INSERT INTO users (user_id, role_id, ic, name, username, password, email, address, phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sssssssss", $formatted_user_id, $role_id, $ic, $name, $username, $hashed_password, $email, $address, $phone);
            if ($stmt->execute()) {
                // Get the last student_id and increment the number part
                $student_count_query = $conn->query("SELECT MAX(CAST(SUBSTRING(student_id, 3, 3) AS UNSIGNED)) AS max_id FROM students");
                $student_count_row = $student_count_query->fetch_assoc();
                $student_count = $student_count_row['max_id'] + 1; // Increment the max student_id

                // Extract user_id's numeric part to append to the student_id
                $user_id_numeric_part = substr($formatted_user_id, 2, 3); // Get the `xxx` part of the user_id

                // Format the student_id as STxxxnnn
                $formatted_student_id = sprintf('ST%03d%s', $student_count, $user_id_numeric_part);

                // Insert into the `students` table
                $student_stmt = $conn->prepare("INSERT INTO students (student_id, user_id, dob) VALUES (?, ?, ?)");
                $student_stmt->bind_param("sss", $formatted_student_id, $formatted_user_id, $dob);
                if ($student_stmt->execute()) {
                    $_SESSION['success_message'] = "Registration successful. You can now <a href='login_frontend.php'>login</a>.";
                    header("Location: register_frontend.php"); // Redirect to display success message
                    exit;
                } else {
                    $errors[] = "Error inserting into students table: " . $student_stmt->error;
                }
            } else {
                $errors[] = "Error inserting into users table: " . $stmt->error;
            }
        } else {
            $errors[] = "Database error: Unable to prepare statement.";
        }
    }

    // Save the previous input values in the session
    $_SESSION['register_input'] = [
        'name' => $name,
        'username' => $username,
        'email' => $email,
        'ic' => $ic,
        'address' => $address,
        'phone' => $phone
    ];

    $_SESSION['register_errors'] = $errors;
    header("Location: register_frontend.php"); // Redirect to display errors
    exit;
}
?>