<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Fetch user details
$query = "SELECT * FROM users WHERE id = $user_id";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $full_name = sanitize($_POST['full_name']);
        $email = sanitize($_POST['email']);
        $currency = sanitize($_POST['currency']);
        
        $errors = [];
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }
        
        // Check if email already exists for another user
        $check_email = "SELECT id FROM users WHERE email = '$email' AND id != $user_id";
        $email_result = mysqli_query($conn, $check_email);
        if (mysqli_num_rows($email_result) > 0) {
            $errors[] = "Email already exists";
        }
        
        // Handle avatar upload
        $avatar_path = $user['avatar']; // Keep existing avatar by default
        
        // Check if file was uploaded
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['avatar']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $file_size = $_FILES['avatar']['size'];
            
            // Check file size (max 2MB)
            if ($file_size > 2097152) {
                $errors[] = "File size must be less than 2MB";
            }
            
            if (in_array($ext, $allowed)) {
                // Create uploads directory if it doesn't exist
                $upload_dir = 'uploads/avatars/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Generate unique filename
                $new_filename = 'user_' . $user_id . '_' . time() . '.' . $ext;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_path)) {
                    // Delete old avatar if it exists and is not default
                    if (!empty($user['avatar']) && file_exists($user['avatar']) && strpos($user['avatar'], 'gravatar') === false) {
                        unlink($user['avatar']);
                    }
                    $avatar_path = $upload_path;
                } else {
                    $errors[] = "Failed to upload avatar. Please check folder permissions.";
                }
            } else {
                $errors[] = "Invalid file type. Allowed: " . implode(', ', $allowed);
            }
        }
        
        if (empty($errors)) {
            // Use prepared statement for security
            $update_query = "UPDATE users SET 
                            full_name = '$full_name', 
                            email = '$email', 
                            currency = '$currency',
                            avatar = '$avatar_path'
                            WHERE id = $user_id";
            
            if (mysqli_query($conn, $update_query)) {
                $_SESSION['full_name'] = $full_name;
                $_SESSION['currency'] = $currency;
                $success_message = "Profile updated successfully!";
                
                // Refresh user data
                $result = mysqli_query($conn, $query);
                $user = mysqli_fetch_assoc($result);
            } else {
                $error_message = "Failed to update profile: " . mysqli_error($conn);
            }
        } else {
            $error_message = implode('<br>', $errors);
        }
    }
    
    // Handle password change
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        $errors = [];
        
        if (empty($current_password)) {
            $errors[] = "Current password is required";
        } elseif (!password_verify($current_password, $user['password'])) {
            $errors[] = "Current password is incorrect";
        }
        
        if (empty($new_password)) {
            $errors[] = "New password is required";
        } elseif (strlen($new_password) < 6) {
            $errors[] = "New password must be at least 6 characters";
        }
        
        if ($new_password != $confirm_password) {
            $errors[] = "New passwords do not match";
        }
        
        if (empty($errors)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET password = '$hashed_password' WHERE id = $user_id";
            
            if (mysqli_query($conn, $update_query)) {
                $success_message = "Password changed successfully!";
            } else {
                $error_message = "Failed to change password";
            }
        } else {
            $error_message = implode('<br>', $errors);
        }
    }
}

include 'header.php';
include 'sidebar.php';
?>

<!-- Add this CSS for profile page -->
<style>
.profile-wrapper {
    max-width: 800px;
    margin: 0 auto;
}

.profile-header-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 40px;
    border-radius: 15px;
    margin-bottom: 30px;
    text-align: center;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
}

.profile-avatar {
    position: relative;
    width: 150px;
    height: 150px;
    margin: 0 auto 20px;
}

.profile-avatar img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    border: 4px solid white;
    object-fit: cover;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.avatar-upload {
    position: absolute;
    bottom: 0;
    right: 0;
    background: white;
    color: #667eea;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    transition: all 0.3s;
}

.avatar-upload:hover {
    transform: scale(1.1);
    background: #f0f0f0;
}

.avatar-upload input {
    display: none;
}

.profile-tabs {
    display: flex;
    background: white;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.profile-tab {
    flex: 1;
    padding: 15px;
    text-align: center;
    cursor: pointer;
    border-bottom: 3px solid transparent;
    transition: all 0.3s;
}

.profile-tab.active {
    border-bottom-color: #667eea;
    color: #667eea;
    font-weight: bold;
}

.profile-tab i {
    margin-right: 10px;
}

.tab-content {
    background: white;
    border-radius: 10px;
    padding: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.tab-pane {
    display: none;
}

.tab-pane.active {
    display: block;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}

.stat-badge {
    background: linear-gradient(135deg, #667eea10 0%, #764ba210 100%);
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.stat-badge i {
    font-size: 2rem;
    color: #667eea;
}

.stat-badge-info h4 {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 5px;
}

.stat-badge-info p {
    font-size: 1.5rem;
    font-weight: bold;
    color: #333;
}

/* Alert Styles */
.alert {
    padding: 15px 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert i {
    font-size: 1.2rem;
}

/* Form Styles */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    color: #555;
    font-weight: 500;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 10px 15px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-group input[readonly] {
    background: #f5f5f5;
    cursor: not-allowed;
}


.btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 12px 30px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 1rem;
    font-weight: 600;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%; /* Makes button full width of its container */
    text-align: center;
    line-height: 1.2;
    white-space: nowrap;
}

/* If you want the button to be auto-width instead of full width */
.btn-auto {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    text-align: center;
    width: auto;
    min-width: 150px; /* Minimum width for better appearance */
}

/* For the specific Save Changes button in profile */
.btn-save {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 12px 30px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 1rem;
    font-weight: 600;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%;
    text-align: center;
    margin-top: 20px;
}

.btn-save:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.btn-save i {
    font-size: 1.1rem;
}
</style>

<div class="profile-wrapper">
    <h1>Profile Settings</h1>
    
    <?php if ($success_message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>
    
    <div class="profile-header-card">
        <div class="profile-avatar">
            <img src="<?php 
                if (!empty($user['avatar']) && file_exists($user['avatar'])) {
                    echo $user['avatar'];
                } else {
                    echo 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($user['email']))) . '?d=mp&s=150';
                }
            ?>" 
                 alt="Profile Avatar" 
                 id="avatar-preview">
            <label for="avatar-upload" class="avatar-upload">
                <i class="fas fa-camera"></i>
            </label>
        </div>
        <h2><?php echo htmlspecialchars($user['full_name']); ?></h2>
        <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></p>
        <p><i class="fas fa-calendar"></i> Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
    </div>
    
    <div class="profile-tabs">
        <div class="profile-tab active" onclick="showTab('profile')">
            <i class="fas fa-user"></i> Profile Info
        </div>
        <div class="profile-tab" onclick="showTab('password')">
            <i class="fas fa-lock"></i> Change Password
        </div>
        <div class="profile-tab" onclick="showTab('stats')">
            <i class="fas fa-chart-bar"></i> Account Stats
        </div>
    </div>
    
    <div class="tab-content">
        <!-- Profile Info Tab -->
        <div class="tab-pane active" id="profile-tab">
            <form method="POST" enctype="multipart/form-data" id="profile-form">
                <input type="hidden" name="update_profile" value="1">
                
                <!-- Avatar upload field inside form -->
                <input type="file" id="avatar-upload" name="avatar" accept="image/*" style="display: none;">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled readonly>
                        <small style="color: #666;">Username cannot be changed</small>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="currency">Preferred Currency</label>
                        <select id="currency" name="currency">
                            <option value="$" <?php echo $user['currency'] == '$' ? 'selected' : ''; ?>>USD ($) - US Dollar</option>
                            <option value="€" <?php echo $user['currency'] == '€' ? 'selected' : ''; ?>>EUR (€) - Euro</option>
                            <option value="£" <?php echo $user['currency'] == '£' ? 'selected' : ''; ?>>GBP (£) - British Pound</option>
                            <option value="¥" <?php echo $user['currency'] == '¥' ? 'selected' : ''; ?>>JPY (¥) - Japanese Yen</option>
                            <option value="₹" <?php echo $user['currency'] == '₹' ? 'selected' : ''; ?>>INR (₹) - Indian Rupee</option>
                            <option value="₩" <?php echo $user['currency'] == '₩' ? 'selected' : ''; ?>>KRW (₩) - South Korean Won</option>
                        </select>
                    </div>
                </div>
                
                <button type="submit" class="btn-save">
                    <i  class="fas fa-save"></i> Save Changes
                </button>
            </form>
        </div>
        
        <!-- Change Password Tab -->
        <div class="tab-pane" id="password-tab">
            <form method="POST" id="password-form">
                <input type="hidden" name="change_password" value="1">
                
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" required>
                        <small style="color: #666;">Minimum 6 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>
                
                <button type="submit" class="btn">
                    <i class="fas fa-key"></i> Change Password
                </button>
            </form>
        </div>
        
        <!-- Account Stats Tab -->
        <div class="tab-pane" id="stats-tab">
            <?php
            // Get account statistics
            $stats = [];
            
            // Total expenses
            $result = mysqli_query($conn, "SELECT COUNT(*) as count, SUM(amount) as total FROM expenses WHERE user_id = $user_id");
            $stats['total'] = mysqli_fetch_assoc($result);
            
            // Current month expenses
            $result = mysqli_query($conn, "SELECT SUM(amount) as total FROM expenses WHERE user_id = $user_id AND MONTH(expense_date) = MONTH(CURRENT_DATE())");
            $stats['month'] = mysqli_fetch_assoc($result);
            
            // Average daily expense
            $result = mysqli_query($conn, "SELECT AVG(daily_total) as avg_daily FROM (SELECT SUM(amount) as daily_total FROM expenses WHERE user_id = $user_id GROUP BY expense_date) as daily");
            $stats['daily_avg'] = mysqli_fetch_assoc($result);
            
            // Most used category
            $result = mysqli_query($conn, "SELECT category, COUNT(*) as count FROM expenses WHERE user_id = $user_id GROUP BY category ORDER BY count DESC LIMIT 1");
            $stats['top_category'] = mysqli_fetch_assoc($result);
            ?>
            
            <div class="stat-badge">
                <div class="stat-badge-info">
                    <h4>Total Expenses</h4>
                    <p><?php echo $user['currency'] . number_format($stats['total']['total'] ?? 0, 2); ?></p>
                </div>
                <i class="fas fa-chart-line"></i>
            </div>
            
            <div class="stat-badge">
                <div class="stat-badge-info">
                    <h4>This Month</h4>
                    <p><?php echo $user['currency'] . number_format($stats['month']['total'] ?? 0, 2); ?></p>
                </div>
                <i class="fas fa-calendar-alt"></i>
            </div>
            
            <div class="stat-badge">
                <div class="stat-badge-info">
                    <h4>Daily Average</h4>
                    <p><?php echo $user['currency'] . number_format($stats['daily_avg']['avg_daily'] ?? 0, 2); ?></p>
                </div>
                <i class="fas fa-clock"></i>
            </div>
            
            <?php if ($stats['top_category']['category']): ?>
            <div class="stat-badge">
                <div class="stat-badge-info">
                    <h4>Most Used Category</h4>
                    <p><?php echo $stats['top_category']['category']; ?> (<?php echo $stats['top_category']['count']; ?> times)</p>
                </div>
                <i class="fas fa-tag"></i>
            </div>
            <?php endif; ?>
            
            <div style="margin-top: 20px; text-align: center;">
                <a href="export.php" class="btn" style="background: #28a745;">
                    <i class="fas fa-download"></i> Download My Data
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function showTab(tab) {
    // Hide all tabs
    document.querySelectorAll('.tab-pane').forEach(pane => {
        pane.classList.remove('active');
    });
    
    // Remove active class from all tab buttons
    document.querySelectorAll('.profile-tab').forEach(tabBtn => {
        tabBtn.classList.remove('active');
    });
    
    // Show selected tab
    document.getElementById(tab + '-tab').classList.add('active');
    
    // Add active class to clicked tab button
    event.target.closest('.profile-tab').classList.add('active');
}

// Avatar preview - trigger file input when camera icon is clicked
document.querySelector('.avatar-upload').addEventListener('click', function() {
    document.getElementById('avatar-upload').click();
});

// Avatar preview - show preview when file is selected
document.getElementById('avatar-upload').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        // Check file size (max 2MB)
        if (file.size > 2097152) {
            alert('File size must be less than 2MB');
            this.value = '';
            return;
        }
        
        // Check file type
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
            alert('Invalid file type. Allowed: JPG, JPEG, PNG, GIF');
            this.value = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('avatar-preview').src = e.target.result;
        }
        reader.readAsDataURL(file);
    }
});

// Password match validation
document.getElementById('password-form').addEventListener('submit', function(e) {
    const newPass = document.getElementById('new_password').value;
    const confirmPass = document.getElementById('confirm_password').value;
    const currentPass = document.getElementById('current_password').value;
    
    if (currentPass === '') {
        e.preventDefault();
        alert('Please enter your current password');
        return;
    }
    
    if (newPass !== confirmPass) {
        e.preventDefault();
        alert('New passwords do not match!');
        return;
    }
    
    if (newPass.length < 6) {
        e.preventDefault();
        alert('Password must be at least 6 characters long!');
        return;
    }
});

// Profile form submission - show loading state
document.getElementById('profile-form').addEventListener('submit', function(e) {
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
});
</script>

<?php
include 'footer.php';
?>