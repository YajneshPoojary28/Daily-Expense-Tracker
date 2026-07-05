<?php
require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$errors = [];
$success_message = '';
$full_name = $username = $email = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = sanitize($_POST['full_name']);
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    $errors = [];
    
    // Validation
    if (empty($full_name)) {
        $errors[] = "Full name is required";
    }
    
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "Username can only contain letters, numbers, and underscores";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    
    if ($password != $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    // Check if username or email already exists
    if (empty($errors)) {
        // Use prepared statement to prevent SQL injection
        $check_stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ? OR email = ?");
        mysqli_stmt_bind_param($check_stmt, "ss", $username, $email);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if (mysqli_num_rows($check_result) > 0) {
            // Check which one exists
            $check_username = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
            mysqli_stmt_bind_param($check_username, "s", $username);
            mysqli_stmt_execute($check_username);
            $username_result = mysqli_stmt_get_result($check_username);
            
            $check_email = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
            mysqli_stmt_bind_param($check_email, "s", $email);
            mysqli_stmt_execute($check_email);
            $email_result = mysqli_stmt_get_result($check_email);
            
            if (mysqli_num_rows($username_result) > 0) {
                $errors[] = "Username already exists";
            }
            if (mysqli_num_rows($email_result) > 0) {
                $errors[] = "Email already exists";
            }
            
            mysqli_stmt_close($check_username);
            mysqli_stmt_close($check_email);
        }
        mysqli_stmt_close($check_stmt);
    }
    
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert without status column
        $insert_stmt = mysqli_prepare($conn, "INSERT INTO users (username, email, password, full_name) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($insert_stmt, "ssss", $username, $email, $hashed_password, $full_name);
        
        if (mysqli_stmt_execute($insert_stmt)) {
            $success_message = "Registration successful! You can now login with your credentials.";
            // Clear form data
            $full_name = $username = $email = '';
        } else {
            $errors[] = "Registration failed: " . mysqli_error($conn);
        }
        mysqli_stmt_close($insert_stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Expense Tracker</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }

        .register-container {
            width: 100%;
            max-width: 480px;
            margin: 0 auto;
        }

        .register-card {
            background: white;
            border-radius: 20px;
            padding: 40px 35px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .register-header i {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 10px;
        }

        .register-header h2 {
            color: #333;
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .register-header p {
            color: #666;
            font-size: 0.95rem;
        }

        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            font-size: 0.95rem;
        }

        .alert-success {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #dcfce7;
        }

        .alert-error {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fee2e2;
        }

        .alert i {
            font-size: 1.2rem;
            margin-top: 2px;
        }

        .alert-content {
            flex: 1;
        }

        .alert-content p {
            margin-bottom: 5px;
        }

        .alert-content ul {
            margin-left: 20px;
            margin-top: 5px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            color: #4b5563;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .input-group {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            color: #9ca3af;
            font-size: 1rem;
            transition: color 0.3s;
        }

        .form-group input {
            width: 100%;
            padding: 12px 20px 12px 45px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.3s;
            background: #f9fafb;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .form-group input:focus + .input-icon {
            color: #667eea;
        }

        .form-group input::placeholder {
            color: #9ca3af;
            font-size: 0.9rem;
        }

        .password-strength {
            margin-top: 8px;
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: width 0.3s, background-color 0.3s;
        }

        .password-requirements {
            margin-top: 8px;
            font-size: 0.8rem;
            color: #6b7280;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .requirement {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .requirement i {
            font-size: 0.7rem;
        }

        .requirement.met {
            color: #10b981;
        }

        .requirement.met i {
            color: #10b981;
        }

        .btn-register {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin: 25px 0 20px;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-register:active {
            transform: translateY(0);
        }

        .btn-register:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .login-link {
            text-align: center;
            color: #6b7280;
            font-size: 0.95rem;
        }

        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .login-link a:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .info-note {
            margin-top: 20px;
            padding: 12px;
            background: #f3f4f6;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
            color: #4b5563;
            border: 1px solid #e5e7eb;
        }

        .info-note i {
            color: #667eea;
            font-size: 1.1rem;
        }

        @media (max-width: 480px) {
            .register-card {
                padding: 30px 20px;
            }
            
            .register-header i {
                font-size: 2.5rem;
            }
            
            .register-header h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <i class="fas fa-user-plus"></i>
                <h2>Create Account</h2>
                <p>Join Expense Tracker to manage your finances</p>
            </div>
            
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <div class="alert-content">
                        <?php echo $success_message; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <div class="alert-content">
                        <p>Please fix the following errors:</p>
                        <ul>
                            <?php foreach($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Info Note -->
            <div class="info-note">
                <i class="fas fa-info-circle"></i>
                <span><strong>Note:</strong> Fill in all fields to create your account.</span>
            </div>
            
            <form method="POST" action="" id="registerForm">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <div class="input-group">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" id="full_name" name="full_name" 
                               value="<?php echo htmlspecialchars($full_name); ?>" 
                               placeholder="John Doe" 
                               required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-group">
                        <i class="fas fa-at input-icon"></i>
                        <input type="text" id="username" name="username" 
                               value="<?php echo htmlspecialchars($username); ?>" 
                               placeholder="johndoe123" 
                               required
                               minlength="3"
                               pattern="[a-zA-Z0-9_]+"
                               title="Username can only contain letters, numbers, and underscores">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-group">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($email); ?>" 
                               placeholder="john@example.com" 
                               required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-group">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" 
                               placeholder="••••••••" 
                               required>
                    </div>
                    <div class="password-strength">
                        <div class="password-strength-bar" id="strengthBar"></div>
                    </div>
                    <div class="password-requirements" id="requirements">
                        <span class="requirement" id="req-length">
                            <i class="fas fa-circle"></i> Min 6 chars
                        </span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="input-group">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="confirm_password" name="confirm_password" 
                               placeholder="••••••••" 
                               required>
                    </div>
                    <div id="matchIndicator" style="margin-top: 5px; font-size: 0.8rem;"></div>
                </div>
                
                <button type="submit" class="btn-register" id="registerBtn">
                    <i class="fas fa-user-plus"></i>
                    Create Account
                </button>
                
                <div class="login-link">
                    Already have an account? <a href="login.php">Sign In</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Password strength checker
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        const strengthBar = document.getElementById('strengthBar');
        const reqLength = document.getElementById('req-length');
        const matchIndicator = document.getElementById('matchIndicator');
        const registerBtn = document.getElementById('registerBtn');
        const registerForm = document.getElementById('registerForm');

        password.addEventListener('input', checkPasswordStrength);
        confirmPassword.addEventListener('input', checkPasswordMatch);

        function checkPasswordStrength() {
            const pass = password.value;
            
            // Check length
            if (pass.length >= 6) {
                reqLength.classList.add('met');
                reqLength.innerHTML = '<i class="fas fa-check-circle"></i> Min 6 chars';
                strengthBar.style.width = '100%';
                strengthBar.style.backgroundColor = '#10b981';
            } else {
                reqLength.classList.remove('met');
                reqLength.innerHTML = '<i class="fas fa-circle"></i> Min 6 chars';
                strengthBar.style.width = (pass.length / 6 * 100) + '%';
                strengthBar.style.backgroundColor = '#ef4444';
            }
            
            checkPasswordMatch();
        }

        function checkPasswordMatch() {
            const pass = password.value;
            const confirm = confirmPassword.value;
            
            if (confirm.length === 0) {
                matchIndicator.innerHTML = '';
                return;
            }
            
            if (pass === confirm) {
                matchIndicator.innerHTML = '<i class="fas fa-check-circle" style="color: #10b981;"></i> Passwords match';
                matchIndicator.style.color = '#10b981';
            } else {
                matchIndicator.innerHTML = '<i class="fas fa-exclamation-circle" style="color: #ef4444;"></i> Passwords do not match';
                matchIndicator.style.color = '#ef4444';
            }
        }

        // Form submission validation
        registerForm.addEventListener('submit', function(e) {
            const pass = password.value;
            const confirm = confirmPassword.value;
            const username = document.getElementById('username').value;
            
            // Basic validation
            if (username.length < 3) {
                e.preventDefault();
                alert('Username must be at least 3 characters long.');
                return;
            }
            
            if (pass.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long.');
                return;
            }
            
            if (pass !== confirm) {
                e.preventDefault();
                alert('Passwords do not match!');
                return;
            }
            
            // Disable button to prevent double submission
            registerBtn.disabled = true;
            registerBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Account...';
        });

        // Show/hide password functionality
        function addPasswordToggle() {
            const passwordFields = ['password', 'confirm_password'];
            
            passwordFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                const wrapper = field.parentNode;
                
                const toggleBtn = document.createElement('span');
                toggleBtn.innerHTML = '<i class="fas fa-eye" style="cursor: pointer; position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #9ca3af;"></i>';
                toggleBtn.style.zIndex = '10';
                toggleBtn.onclick = function() {
                    const type = field.getAttribute('type') === 'password' ? 'text' : 'password';
                    field.setAttribute('type', type);
                    this.innerHTML = type === 'password' ? 
                        '<i class="fas fa-eye" style="cursor: pointer; color: #9ca3af;"></i>' : 
                        '<i class="fas fa-eye-slash" style="cursor: pointer; color: #667eea;"></i>';
                };
                
                wrapper.appendChild(toggleBtn);
            });
        }

        addPasswordToggle();
    </script>
</body>
</html>