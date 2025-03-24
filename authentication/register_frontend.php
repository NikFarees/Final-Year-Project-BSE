<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            /*background: url('/assets/car.jpeg') no-repeat center center fixed;*/
            background-size: cover;
            position: relative;
        }
        body::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }
        .register-container {
            background: rgba(0, 0, 0, 0.95);
            padding: 40px;
            border-radius: 10px;
            width: 600px;
            color: white;
            position: relative;
            z-index: 1;
        }
        .form-control {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: rgb(200, 200, 200);
        }
        .form-control::placeholder {
            color: rgb(200, 200, 200);
            opacity: 0.7;
        }
        .form-control:focus {
            background: rgba(255, 255, 255, 0.3);
            color: rgb(200, 200, 200);
        }
        .btn-register {
            background: purple;
            border: none;
            color: white;
        }
        .btn-register:hover {
            background: rgb(86, 1, 88);
        }
        .text-purple {
            color: purple !important;
        }
    </style>
</head>
<body>
    <div class="d-flex justify-content-center align-items-center vh-100">
        <div class="register-container text-center">
            <h2 class="mb-4">Sign Up</h2>
            <!-- PHP Code to Display Errors and Success Messages -->
            <?php
            session_start();
            if (isset($_SESSION['register_errors'])):
            ?>
                <div class="alert alert-danger">
                    <?php
                    foreach ($_SESSION['register_errors'] as $error) {
                        echo "<p>$error</p>";
                    }
                    unset($_SESSION['register_errors']); // Clear errors after displaying
                    ?>
                </div>
            <?php endif; ?>

            <?php
            if (isset($_SESSION['success_message'])):
            ?>
                <div class="alert alert-success">
                    <?php
                    echo $_SESSION['success_message'];
                    unset($_SESSION['success_message']); // Clear success message after displaying
                    ?>
                </div>
            <?php endif; ?>

            <form id="registerForm" action="register_backend.php" method="POST">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <input type="text" name="name" class="form-control" placeholder="Name" value="<?php echo isset($_SESSION['register_input']['name']) ? htmlspecialchars($_SESSION['register_input']['name']) : ''; ?>" required>
                    </div>
                    <div class="col-md-6">
                        <input type="text" name="username" class="form-control" placeholder="Username" value="<?php echo isset($_SESSION['register_input']['username']) ? htmlspecialchars($_SESSION['register_input']['username']) : ''; ?>" required>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <input type="password" name="password" class="form-control" placeholder="Password" required>
                    </div>
                    <div class="col-md-6">
                        <input type="password" name="confirm_password" class="form-control" placeholder="Confirm Password" required>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <input type="email" name="email" class="form-control" placeholder="Email" value="<?php echo isset($_SESSION['register_input']['email']) ? htmlspecialchars($_SESSION['register_input']['email']) : ''; ?>" required>
                    </div>
                    <div class="col-md-6">
                        <input type="text" name="ic" class="form-control" placeholder="IC" value="<?php echo isset($_SESSION['register_input']['ic']) ? htmlspecialchars($_SESSION['register_input']['ic']) : ''; ?>" required>
                    </div>
                </div>
                <div class="mb-3">
                    <input type="text" name="address" class="form-control" placeholder="Address" value="<?php echo isset($_SESSION['register_input']['address']) ? htmlspecialchars($_SESSION['register_input']['address']) : ''; ?>" required>
                </div>
                <div class="mb-3">
                    <input type="text" name="phone" class="form-control" placeholder="Phone" value="<?php echo isset($_SESSION['register_input']['phone']) ? htmlspecialchars($_SESSION['register_input']['phone']) : ''; ?>" required>
                </div>
                <button type="submit" class="btn btn-register w-100">Sign Up</button>
            </form>
            <div class="mt-3">
                <span style="color: rgb(200, 200, 200);">Already have an account? <a href="login_frontend.php" class="text-white">Sign in now</a></span>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>