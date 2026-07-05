<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if (!isAdmin()) {
    $_SESSION['error'] = "Access denied. Admin only.";
    redirect('dashboard.php');
}

$user_id = $_SESSION['user_id'];
$user = getUserProfile($user_id);
$success_message = '';
$error_message = '';

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // You can add settings updates here
    $success_message = "Settings updated successfully!";
}

include 'header.php';
include 'sidebar.php';
?>

<style>
.settings-container {
    max-width: 800px;
    margin: 0 auto;
}

.settings-card {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    margin-bottom: 30px;
}

.settings-card h2 {
    color: #333;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
}

.settings-card h2 i {
    color: #667eea;
    margin-right: 10px;
}

.setting-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 0;
    border-bottom: 1px solid #f0f0f0;
}

.setting-item:last-child {
    border-bottom: none;
}

.setting-info h4 {
    color: #333;
    margin-bottom: 5px;
}

.setting-info p {
    color: #666;
    font-size: 0.95rem;
    margin: 0;
}

.switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 26px;
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: #ccc;
    transition: .4s;
    border-radius: 26px;
}

.slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

input:checked + .slider {
    background: #667eea;
}

input:checked + .slider:before {
    transform: translateX(24px);
}

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

.alert i {
    font-size: 1.2rem;
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
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.system-info {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
}

.system-info p {
    margin: 8px 0;
    color: #555;
}

.system-info strong {
    color: #333;
}
</style>

<div class="settings-container">
    <h1><i class="fas fa-cog"></i> Settings</h1>
    
    <?php if ($success_message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>
    
    <!-- General Settings -->
    <div class="settings-card">
        <h2><i class="fas fa-sliders-h"></i> General Settings</h2>
        
        <form method="POST">
            <div class="setting-item">
                <div class="setting-info">
                    <h4>Allow User Registration</h4>
                    <p>Allow new users to register on the platform</p>
                </div>
                <label class="switch">
                    <input type="checkbox" checked>
                    <span class="slider"></span>
                </label>
            </div>
            
            <div class="setting-item">
                <div class="setting-info">
                    <h4>Require Email Verification</h4>
                    <p>Users must verify their email before logging in</p>
                </div>
                <label class="switch">
                    <input type="checkbox">
                    <span class="slider"></span>
                </label>
            </div>
            
            <div class="setting-item">
                <div class="setting-info">
                    <h4>Maintenance Mode</h4>
                    <p>Put the system in maintenance mode (only admins can access)</p>
                </div>
                <label class="switch">
                    <input type="checkbox">
                    <span class="slider"></span>
                </label>
            </div>
            
            <div style="margin-top: 20px;">
                <button type="submit" class="btn">
                    <i class="fas fa-save"></i> Save Settings
                </button>
            </div>
        </form>
    </div>
    
    <!-- System Information -->
    <div class="settings-card">
        <h2><i class="fas fa-info-circle"></i> System Information</h2>
        
        <div class="system-info">
            <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
            <p><strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE']; ?></p>
            <p><strong>Database:</strong> <?php echo DB_NAME; ?></p>
            <p><strong>Total Users:</strong> <?php echo getTotalUsers(); ?></p>
            <p><strong>Total Expenses:</strong> $<?php echo number_format(getTotalExpenses(), 2); ?></p>
            <p><strong>Server Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>