<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize($_POST['email']);
    
    $errors = [];
    $success = '';
    
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
                $success = "Password reset link has been generated. <br> 
                           <a href='$reset_link' style='color: #667eea;'>Click here to reset your password</a>";
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
    <title>Forgot Password - Expense Tracker</title>
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
        .container {
            width: 100%;
            max-width: 420px;
            margin: 0 auto;
        }
        .card {
            background: white;
            border-radius: 20px;
            padding: 40px 35px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            animation: slideUp 0.5s ease;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .header {
            text-align: center;
            margin-bottom: 35px;
        }
        .header i {
            font-size: 3.5rem;
            color: #667eea;
            margin-bottom: 15px;
        }
        .header h2 {
            color: #333;
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .header p {
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
        .form-group {
            margin-bottom: 22px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #4b5563;
            font-weight: 500;
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
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }
        .btn {
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
            margin-bottom: 20px;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        .back-link {
            text-align: center;
        }
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <i class="fas fa-key"></i>
                <h2>Forgot Password</h2>
                <p>Enter your email to reset your password</p>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <div><?php echo $errors[0]; ?></div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <div><?php echo $success; ?></div>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-group">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" id="email" name="email" placeholder="john@example.com" required>
                    </div>
                </div>
                
                <button type="submit" class="btn">
                    <i class="fas fa-paper-plane"></i> Send Reset Link
                </button>
                
                <div class="back-link">
                    <a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>