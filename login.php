<?php
require_once 'config.php';

// Check if user is already logged in
if (isLoggedIn()) {
    // Redirect based on role
    if (isAdmin()) {
        redirect('admin_dashboard.php');
    } else {
        redirect('dashboard.php');
    }
}

$errors = [];
$success = '';

// Only process login when form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $errors[] = "Please fill in all fields";
    } else {
        // Use prepared statement to prevent SQL injection
        $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE username = ? OR email = ?");
        mysqli_stmt_bind_param($stmt, "ss", $username, $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            if (password_verify($password, $user['password'])) {
                // Check if status column exists and handle accordingly
                if (isset($user['status'])) {
                    // Status column exists
                    if ($user['status'] == 0) {
                        $errors[] = "Your account is pending approval. Please wait for admin to activate your account.";
                    } elseif ($user['status'] == 2) {
                        $errors[] = "Your account has been rejected. Please contact administrator for assistance.";
                    } else {
                        // Login successful - status is 1 (approved)
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['full_name'] = $user['full_name'];
                        $_SESSION['currency'] = $user['currency'];
                        $_SESSION['login_time'] = time();
                        
                        // Check if user is admin and redirect accordingly
                        if ($user['role'] == 'admin') {
                            redirect('admin_dashboard.php');
                        } else {
                            redirect('dashboard.php');
                        }
                    }
                } else {
                    // Status column doesn't exist - allow login for all users
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['currency'] = $user['currency'];
                    $_SESSION['login_time'] = time();
                    
                    // Check if user is admin and redirect accordingly
                    if ($user['role'] == 'admin') {
                        redirect('admin_dashboard.php');
                    } else {
                        redirect('dashboard.php');
                    }
                }
            } else {
                $errors[] = "Invalid password";
            }
        } else {
            $errors[] = "User not found";
        }
        mysqli_stmt_close($stmt);
    }
}

// Handle Forgot Password Request (if submitted via AJAX or form)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['forgot_password'])) {
    $email = sanitize($_POST['forgot_email']);
    
    if (empty($email)) {
        $errors[] = "Please enter your email address";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    } else {
        // Check if email exists
        $stmt = mysqli_prepare($conn, "SELECT id, username, full_name FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Delete any existing tokens for this user
            $delete_stmt = mysqli_prepare($conn, "DELETE FROM password_resets WHERE user_id = ?");
            mysqli_stmt_bind_param($delete_stmt, "i", $user['id']);
            mysqli_stmt_execute($delete_stmt);
            mysqli_stmt_close($delete_stmt);
            
            // Store token in database
            $insert_stmt = mysqli_prepare($conn, "INSERT INTO password_resets (user_id, token, expiry) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($insert_stmt, "iss", $user['id'], $token, $expiry);
            
            if (mysqli_stmt_execute($insert_stmt)) {
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/MINI/reset_password.php?token=" . $token;
                $success = "Password reset link has been sent to your email.<br>
                           <small>(Demo: <a href='$reset_link' style='color: #10b981;'>Click here to reset password</a>)</small>";
            } else {
                $errors[] = "Failed to generate reset link. Please try again.";
            }
            mysqli_stmt_close($insert_stmt);
        } else {
            $errors[] = "No account found with this email address.";
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Expense Tracker</title>
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

        .login-container {
            width: 100%;
            max-width: 420px;
            margin: 0 auto;
        }

        .login-card {
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

        .login-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .login-header i {
            font-size: 3.5rem;
            color: #667eea;
            margin-bottom: 15px;
        }

        .login-header h2 {
            color: #333;
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .login-header p {
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

        .alert-error {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fee2e2;
        }

        .alert-warning {
            background: #fffbeb;
            color: #b45309;
            border: 1px solid #fef3c7;
        }

        .alert-success {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #dcfce7;
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

        .alert-content a {
            color: #667eea;
            text-decoration: underline;
        }

        .form-group {
            margin-bottom: 22px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #4b5563;
            font-weight: 500;
            font-size: 0.95rem;
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
            font-size: 1.1rem;
            transition: color 0.3s;
        }

        .form-group input {
            width: 100%;
            padding: 14px 20px 14px 45px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
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
            font-size: 0.95rem;
        }

        .form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            color: #4b5563;
            font-size: 0.95rem;
        }

        .remember-me input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
            accent-color: #667eea;
        }

        .forgot-link {
            color: #667eea;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            transition: color 0.3s;
            cursor: pointer;
        }

        .forgot-link:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 25px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .register-link {
            text-align: center;
            color: #6b7280;
            font-size: 0.95rem;
        }

        .register-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .register-link a:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .approval-note {
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

        .approval-note i {
            color: #667eea;
            font-size: 1.1rem;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
            z-index: 1000;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            padding: 35px;
            max-width: 400px;
            width: 90%;
            position: relative;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            text-align: center;
            margin-bottom: 25px;
        }

        .modal-header i {
            font-size: 2.5rem;
            color: #667eea;
            margin-bottom: 10px;
        }

        .modal-header h3 {
            color: #333;
            font-size: 1.5rem;
            margin-bottom: 5px;
        }

        .modal-header p {
            color: #666;
            font-size: 0.9rem;
        }

        .close-modal {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 1.5rem;
            cursor: pointer;
            color: #9ca3af;
            transition: color 0.3s;
        }

        .close-modal:hover {
            color: #667eea;
        }

        .btn-forgot {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-forgot:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.4);
        }

        .btn-forgot:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 30px 20px;
            }
            
            .login-header i {
                font-size: 3rem;
            }
            
            .login-header h2 {
                font-size: 1.5rem;
            }
            
            .form-options {
                flex-direction: column;
                gap: 12px;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="fas fa-wallet"></i>
                <h2>Welcome Back</h2>
                <p>Sign in to manage your expenses</p>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <div class="alert-content">
                        <?php foreach($errors as $error): ?>
                            <p><?php echo $error; ?></p>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <div class="alert-content">
                        <?php echo $success; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Show success message from registration if exists -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <div class="alert-content">
                        <p><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            
            <form method="POST" action="" id="loginForm">
                <input type="hidden" name="login" value="1">
                
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <div class="input-group">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" id="username" name="username" 
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                               placeholder="Enter your username or email" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-group">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" 
                               placeholder="Enter your password" required>
                    </div>
                </div>
                
                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember"> 
                        <span>Remember me</span>
                    </label>
                    <a href="#" class="forgot-link" onclick="openForgotModal()">Forgot Password?</a>
                </div>
                
                <button type="submit" class="btn-login" id="loginBtn">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In
                </button>
                
                <div class="register-link">
                    Don't have an account? <a href="register.php">Create Account</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Forgot Password Modal -->
    <div id="forgotModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeForgotModal()">&times;</span>
            
            <div class="modal-header">
                <i class="fas fa-key"></i>
                <h3>Reset Password</h3>
                <p>Enter your email to receive a password reset link</p>
            </div>
            
            <form method="POST" action="" id="forgotForm">
                <input type="hidden" name="forgot_password" value="1">
                
                <div class="form-group">
                    <label for="forgot_email">Email Address</label>
                    <div class="input-group">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" id="forgot_email" name="forgot_email" 
                               placeholder="john@example.com" required>
                    </div>
                </div>
                
                <button type="submit" class="btn-forgot" id="forgotBtn">
                    <i class="fas fa-paper-plane"></i>
                    Send Reset Link
                </button>
            </form>
        </div>
    </div>

    <script>
        // Login form submission
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            const btn = document.getElementById('loginBtn');
            
            if (username === '' || password === '') {
                e.preventDefault();
                alert('Please fill in all fields');
                return;
            }
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing In...';
        });

        // Forgot Password Modal Functions
        function openForgotModal() {
            document.getElementById('forgotModal').style.display = 'flex';
        }

        function closeForgotModal() {
            document.getElementById('forgotModal').style.display = 'none';
            document.getElementById('forgotForm').reset();
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('forgotModal');
            if (event.target == modal) {
                closeForgotModal();
            }
        }

        // Forgot form submission
        document.getElementById('forgotForm').addEventListener('submit', function(e) {
            const email = document.getElementById('forgot_email').value.trim();
            const btn = document.getElementById('forgotBtn');
            
            if (email === '') {
                e.preventDefault();
                alert('Please enter your email address');
                return;
            }
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
        });

        // Show/hide password functionality
        function addPasswordToggle() {
            const passwordField = document.getElementById('password');
            const wrapper = passwordField.parentNode;
            
            const toggleBtn = document.createElement('span');
            toggleBtn.innerHTML = '<i class="fas fa-eye" style="cursor: pointer; position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #9ca3af;"></i>';
            toggleBtn.style.zIndex = '10';
            toggleBtn.onclick = function() {
                const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordField.setAttribute('type', type);
                this.innerHTML = type === 'password' ? 
                    '<i class="fas fa-eye" style="cursor: pointer; color: #9ca3af;"></i>' : 
                    '<i class="fas fa-eye-slash" style="cursor: pointer; color: #667eea;"></i>';
            };
            
            wrapper.appendChild(toggleBtn);
        }

        addPasswordToggle();
    </script>
</body>
</html>