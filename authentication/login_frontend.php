<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
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

        .login-container {
            background: rgba(0, 0, 0, 0.95);
            padding: 40px;
            border-radius: 10px;
            width: 350px;
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

        .btn-login {
            background: purple;
            border: none;
            color: white;
        }

        .btn-login:hover {
            background: rgb(86, 1, 88);
        }

        .text-purple {
            color: purple !important;
        }
    </style>
</head>

<body>
    <div class="d-flex justify-content-center align-items-center vh-100">
        <div class="login-container text-center">
            <h2 class="mb-4">Sign In</h2>

            <!-- PHP Code to Display Errors -->
            <?php
            session_start();
            if (isset($_SESSION['login_errors'])):
            ?>
                <div class="alert alert-danger">
                    <?php
                    foreach ($_SESSION['login_errors'] as $error) {
                        echo "<p>$error</p>";
                    }
                    unset($_SESSION['login_errors']); // Clear errors after displaying
                    ?>
                </div>
            <?php endif; ?>

            <form id="loginForm" action="login_backend.php" method="POST">
                <div class="mb-3">
                    <input type="text" class="form-control" name="username" placeholder="Username" required>
                </div>
                <div class="mb-3">
                    <input type="password" class="form-control" name="password" placeholder="Password" required>
                </div>
                <button type="submit" class="btn btn-login w-100">Sign In</button>
            </form>
            <div class="mt-3">
                <span style="color: rgb(200, 200, 200);">Don't have an account? <a href="register_frontend.php" class="text-white">Sign up now</a></span>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>