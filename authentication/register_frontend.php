<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DriveFlow | Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #3498db;
            --primary-dark: #2980b9;
            --secondary: #2c3e50;
            --light: #ecf0f1;
            --danger: #e74c3c;
            --success: #2ecc71;
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-image: linear-gradient(135deg, rgba(52, 152, 219, 0.8), rgba(44, 62, 80, 0.9)), 
                              url('https://images.unsplash.com/photo-1449965408869-eaa3f722e40d?ixlib=rb-4.0.3&auto=format&fit=crop&w=1500&q=80');
            background-size: cover;
            background-position: center;
            padding: 30px 0;
        }

        .register-container {
            width: 100%;
            max-width: 800px;
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

        .btn-register {
            background-color: var(--primary);
            border: none;
            padding: 12px;
            font-weight: 600;
            font-size: 1rem;
            border-radius: 5px;
            width: 100%;
            margin-top: 10px;
            transition: all 0.3s;
            color: white;
        }

        .btn-register:hover, .btn-register:focus {
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

        .form-label {
            font-weight: 500;
            margin-bottom: 5px;
            color: #495057;
        }

        .form-text {
            font-size: 0.8rem;
            color: #6c757d;
        }

        .progress {
            height: 6px;
            margin-top: 5px;
        }

        .password-strength {
            font-size: 0.8rem;
            margin-top: 5px;
        }

        /* For password strength indicator */
        .weak {
            color: var(--danger);
        }

        .medium {
            color: #f39c12;
        }

        .strong {
            color: var(--success);
        }
    </style>
</head>

<body>
    <div class="register-container">
        <div class="card">
            <div class="card-header">
                <div class="logo">DriveFlow</div>
                <div class="subtext">Create Your Account</div>
                <i class="fas fa-user-plus icon-background"></i>
            </div>
            <div class="card-body">
                <?php
                if (isset($_SESSION['register_errors'])):
                ?>
                    <div class="alert alert-danger" role="alert">
                        <?php
                        foreach ($_SESSION['register_errors'] as $error) {
                            echo "<p class='mb-0'><i class='fas fa-exclamation-circle me-2'></i>$error</p>";
                        }
                        unset($_SESSION['register_errors']); // Clear errors after displaying
                        ?>
                    </div>
                <?php endif; ?>

                <?php
                if (isset($_SESSION['success_message'])):
                ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success_message']; ?>
                        <?php unset($_SESSION['success_message']); // Clear success message after displaying ?>
                    </div>
                <?php endif; ?>

                <form id="registerForm" action="register_backend.php" method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" name="name" id="name" class="form-control" placeholder="Enter your full name" value="<?php echo isset($_SESSION['register_input']['name']) ? htmlspecialchars($_SESSION['register_input']['name']) : ''; ?>" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-at"></i></span>
                                    <input type="text" name="username" id="username" class="form-control" placeholder="Choose a username" value="<?php echo isset($_SESSION['register_input']['username']) ? htmlspecialchars($_SESSION['register_input']['username']) : ''; ?>" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" name="password" id="password" class="form-control" placeholder="Create a password" required>
                                </div>
                                <div class="progress mt-2">
                                    <div id="password-strength-meter" class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <div id="password-strength-text" class="password-strength"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Confirm your password" required>
                                </div>
                                <div id="password-match" class="form-text"></div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" name="email" id="email" class="form-control" placeholder="Enter your email" value="<?php echo isset($_SESSION['register_input']['email']) ? htmlspecialchars($_SESSION['register_input']['email']) : ''; ?>" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="ic" class="form-label">IC Number</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                    <input type="text" name="ic" id="ic" class="form-control" placeholder="Enter your IC number" value="<?php echo isset($_SESSION['register_input']['ic']) ? htmlspecialchars($_SESSION['register_input']['ic']) : ''; ?>" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-home"></i></span>
                            <input type="text" name="address" id="address" class="form-control" placeholder="Enter your address" value="<?php echo isset($_SESSION['register_input']['address']) ? htmlspecialchars($_SESSION['register_input']['address']) : ''; ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                            <input type="text" name="phone" id="phone" class="form-control" placeholder="Enter your phone number" value="<?php echo isset($_SESSION['register_input']['phone']) ? htmlspecialchars($_SESSION['register_input']['phone']) : ''; ?>" required>
                        </div>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="termsCheck" required>
                        <label class="form-check-label" for="termsCheck">
                            I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms and Conditions</a> and <a href="#" data-bs-toggle="modal" data-bs-target="#privacyModal">Privacy Policy</a>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-register">
                        <i class="fas fa-user-plus me-2"></i>Create Account
                    </button>
                </form>
            </div>
            <div class="card-footer">
                <span>Already have an account? <a href="login_frontend.php" class="fw-bold">Sign in</a></span>
            </div>
        </div>
    </div>

    <!-- Terms Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="termsModalLabel">Terms and Conditions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Terms and conditions content -->
                    <h6>1. Acceptance of Terms</h6>
                    <p>By registering an account with DriveFlow, you agree to be bound by these Terms and Conditions.</p>
                    
                    <h6>2. User Accounts</h6>
                    <p>You are responsible for maintaining the confidentiality of your account information and password. You are responsible for all activities that occur under your account.</p>
                    
                    <h6>3. User Obligations</h6>
                    <p>You agree to provide accurate, current, and complete information during the registration process and to update such information to keep it accurate, current, and complete.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Privacy Modal -->
    <div class="modal fade" id="privacyModal" tabindex="-1" aria-labelledby="privacyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="privacyModalLabel">Privacy Policy</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Privacy policy content -->
                    <h6>1. Information We Collect</h6>
                    <p>We collect personal information that you provide to us, including name, email address, phone number, and identification details.</p>
                    
                    <h6>2. How We Use Your Information</h6>
                    <p>Your information is used to provide and improve our services, communicate with you, and ensure compliance with legal requirements.</p>
                    
                    <h6>3. Data Security</h6>
                    <p>We implement appropriate security measures to protect your personal information from unauthorized access or disclosure.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password strength meter
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const strengthMeter = document.getElementById('password-strength-meter');
        const strengthText = document.getElementById('password-strength-text');
        const passwordMatch = document.getElementById('password-match');

        passwordInput.addEventListener('input', updateStrengthMeter);
        
        function updateStrengthMeter() {
            const val = passwordInput.value;
            const result = calculatePasswordStrength(val);
            
            // Update the strength meter
            strengthMeter.style.width = result.score + '%';
            
            // Update the color and text based on strength
            if (result.score < 40) {
                strengthMeter.className = 'progress-bar bg-danger';
                strengthText.className = 'password-strength weak';
                strengthText.textContent = 'Weak password';
            } else if (result.score < 80) {
                strengthMeter.className = 'progress-bar bg-warning';
                strengthText.className = 'password-strength medium';
                strengthText.textContent = 'Medium strength password';
            } else {
                strengthMeter.className = 'progress-bar bg-success';
                strengthText.className = 'password-strength strong';
                strengthText.textContent = 'Strong password';
            }
        }

        function calculatePasswordStrength(password) {
            let score = 0;
            
            // Length check
            if (password.length > 6) score += 20;
            if (password.length > 10) score += 20;
            
            // Complexity checks
            if (/[A-Z]/.test(password)) score += 20; // Has uppercase
            if (/[a-z]/.test(password)) score += 10; // Has lowercase
            if (/[0-9]/.test(password)) score += 20; // Has number
            if (/[^A-Za-z0-9]/.test(password)) score += 30; // Has special char
            
            return {
                score: Math.min(100, score)
            };
        }

        // Password match check
        confirmPasswordInput.addEventListener('input', function() {
            if (passwordInput.value === confirmPasswordInput.value) {
                passwordMatch.className = 'form-text text-success';
                passwordMatch.textContent = 'Passwords match';
            } else {
                passwordMatch.className = 'form-text text-danger';
                passwordMatch.textContent = 'Passwords do not match';
            }
        });

        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(event) {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            
            // Check if passwords match
            if (password !== confirmPassword) {
                event.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            // Check if terms are accepted
            if (!document.getElementById('termsCheck').checked) {
                event.preventDefault();
                alert('You must accept the Terms and Conditions to register.');
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>