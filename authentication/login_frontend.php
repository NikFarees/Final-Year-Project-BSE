<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DriveFlow | Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #3498db;
            --primary-dark: #2980b9;
            --secondary: #2c3e50;
            --light: #ecf0f1;
            --danger: #e74c3c;
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-image: linear-gradient(135deg, rgba(52, 152, 219, 0.8), rgba(44, 62, 80, 0.9)), 
                              url('https://images.unsplash.com/photo-1449965408869-eaa3f722e40d?ixlib=rb-4.0.3&auto=format&fit=crop&w=1500&q=80');
            background-size: cover;
            background-position: center;
        }

        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 20px;
        }

        .card {
            border-radius: 10px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            background-color: rgba(255, 255, 255, 0.9);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .card-header {
            background-color: var(--primary);
            color: white;
            text-align: center;
            padding: 20px;
            position: relative;
        }

        .logo {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
            letter-spacing: 1px;
        }

        .subtext {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .card-body {
            padding: 30px;
        }

        .form-control {
            border-radius: 5px;
            padding: 12px 15px;
            height: auto;
            font-size: 1rem;
            border: 1px solid #ddd;
            background-color: #f8f9fa;
            margin-bottom: 15px;
        }

        .form-control:focus {
            box-shadow: none;
            border-color: var(--primary);
        }

        .input-group-text {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-right: none;
        }

        .btn-login {
            background-color: var(--primary);
            border: none;
            padding: 12px;
            font-weight: 600;
            font-size: 1rem;
            border-radius: 5px;
            width: 100%;
            margin-top: 10px;
            transition: all 0.3s;
        }

        .btn-login:hover, .btn-login:focus {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .additional-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            font-size: 0.9rem;
        }

        a {
            color: var(--primary);
            text-decoration: none;
            transition: color 0.3s;
        }

        a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .alert {
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 15px 0;
            color: #6c757d;
        }

        .divider::before, .divider::after {
            content: "";
            flex: 1;
            border-bottom: 1px solid #dee2e6;
        }

        .divider::before {
            margin-right: 10px;
        }

        .divider::after {
            margin-left: 10px;
        }

        .card-footer {
            background-color: rgba(0, 0, 0, 0.03);
            padding: 15px 30px;
            text-align: center;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }

        .icon-background {
            position: absolute;
            right: 20px;
            bottom: 10px;
            font-size: 5rem;
            opacity: 0.1;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="card">
            <div class="card-header">
                <div class="logo">DriveFlow</div>
                <div class="subtext">Driving School Management System</div>
                <i class="fas fa-car icon-background"></i>
            </div>
            <div class="card-body">
                <?php
                if (isset($_SESSION['login_errors'])):
                ?>
                    <div class="alert alert-danger" role="alert">
                        <?php
                        foreach ($_SESSION['login_errors'] as $error) {
                            echo "<p class='mb-0'><i class='fas fa-exclamation-circle me-2'></i>$error</p>";
                        }
                        unset($_SESSION['login_errors']); // Clear errors after displaying
                        ?>
                    </div>
                <?php endif; ?>

                <form id="loginForm" action="login_backend.php" method="POST">
                    <div class="input-group mb-3">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" name="username" placeholder="Username" required>
                    </div>
                    <div class="input-group mb-3">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" name="password" id="password" placeholder="Password" required>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="showPassword">
                        <label class="form-check-label" for="showPassword">
                            Show password
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary btn-login">
                        <i class="fas fa-sign-in-alt me-2"></i>Sign In
                    </button>
                </form>

                <div class="additional-actions">
                    <a href="#"><i class="fas fa-question-circle me-1"></i>Forgot password?</a>
                    <a href="register_frontend.php"><i class="fas fa-user-plus me-1"></i>Register</a>
                </div>
            </div>
            <div class="card-footer">
                <span>New to DriveFlow? <a href="register_frontend.php" class="fw-bold">Create an account</a></span>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show/hide password functionality
        const passwordInput = document.getElementById('password');
        const showPasswordCheckbox = document.getElementById('showPassword');
        
        showPasswordCheckbox.addEventListener('change', function() {
            passwordInput.type = this.checked ? 'text' : 'password';
        });
    </script>
</body>

</html>