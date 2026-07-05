<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$user = getUserProfile($user_id);

// Ensure messages table exists
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Get admin (the one with role 'admin')
$admin = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, full_name, email FROM users WHERE role = 'admin' LIMIT 1"));

// Handle message
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);
    
    if ($admin) {
        $insert = "INSERT INTO messages (sender_id, receiver_id, subject, message) 
                   VALUES ($user_id, {$admin['id']}, '$subject', '$message')";
        if (mysqli_query($conn, $insert)) {
            $_SESSION['success'] = "Message sent successfully!";
        } else {
            $_SESSION['error'] = "Failed to send message: " . mysqli_error($conn);
        }
    } else {
        $_SESSION['error'] = "No admin found to send message to.";
    }
    header("Location: messages.php");
    exit();
}

include 'header.php';
include 'sidebar.php';
?>

<style>
.messages-wrapper { 
    max-width: 700px; 
    margin: 0 auto; 
    padding: 0 15px;
}

.admin-card { 
    background: linear-gradient(135deg, #667eea, #764ba2); 
    color: white; 
    padding: 25px; 
    border-radius: 15px; 
    margin-bottom: 30px; 
    box-shadow: 0 5px 20px rgba(102,126,234,0.3);
}
.admin-card h3 { margin-bottom: 10px; }
.admin-card p { opacity: 0.9; }
.admin-card i { margin-right: 8px; }

.form-card { 
    background: white; 
    padding: 30px; 
    border-radius: 15px; 
    box-shadow: 0 5px 20px rgba(0,0,0,0.05); 
}
.form-group { margin-bottom: 20px; }
.form-group label { 
    display: block; 
    margin-bottom: 8px; 
    color: #555; 
    font-weight: 600; 
}
.form-group label i {
    color: #667eea;
    margin-right: 5px;
}
.form-group input, 
.form-group textarea { 
    width: 100%; 
    padding: 12px 15px; 
    border: 2px solid #e5e7eb; 
    border-radius: 8px; 
    font-size: 1rem; 
    font-family: inherit; 
    transition: all 0.3s;
}
.form-group input:focus, 
.form-group textarea:focus { 
    outline: none; 
    border-color: #667eea; 
    box-shadow: 0 0 0 3px rgba(102,126,234,0.1); 
}
.form-group textarea { min-height: 120px; resize: vertical; }

.btn { 
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
    color: white; 
    padding: 14px 30px; 
    border: none; 
    border-radius: 8px; 
    cursor: pointer; 
    font-size: 1rem; 
    font-weight: 600; 
    transition: all 0.3s; 
    width: 100%; 
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}
.btn:hover { 
    transform: translateY(-2px); 
    box-shadow: 0 5px 15px rgba(102,126,234,0.4); 
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
.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
.alert i {
    font-size: 1.2rem;
}

.success-link {
    color: #155724;
    font-weight: 600;
    text-decoration: underline;
}
.success-link:hover {
    color: #0d4a1a;
}

/* Notification badge pulse */
@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}
.badge-pulse {
    animation: pulse 2s infinite;
}

/* View Notifications Button */
.view-notif-btn {
    display: inline-block;
    background: #667eea;
    color: white;
    padding: 10px 20px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s;
    margin-top: 10px;
}
.view-notif-btn:hover {
    background: #5a6fd6;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102,126,234,0.4);
    color: white;
}
</style>

<div class="messages-wrapper">
    <h1><i class="fas fa-envelope"></i> Contact Admin</h1>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <div>
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <br>
                <a href="notifications.php" class="success-link">
                    <i class="fas fa-bell"></i> View in Notifications →
                </a>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($admin): ?>
    <div class="admin-card">
        <h3><i class="fas fa-shield-alt"></i> <?php echo htmlspecialchars($admin['full_name']); ?></h3>
        <p><i class="fas fa-envelope"></i> <?php echo $admin['email']; ?></p>
    </div>
    <?php else: ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i>
        No administrator found. Please contact system administrator.
    </div>
    <?php endif; ?>
    
    <div class="form-card">
        <form method="POST">
            <div class="form-group">
                <label for="subject"><i class="fas fa-heading"></i> Subject</label>
                <input type="text" id="subject" name="subject" placeholder="Enter message subject" required>
            </div>
            <div class="form-group">
                <label for="message"><i class="fas fa-align-left"></i> Message</label>
                <textarea id="message" name="message" placeholder="Write your message here..." required rows="5"></textarea>
            </div>
            <button type="submit" class="btn">
                <i class="fas fa-paper-plane"></i> Send Message
            </button>
        </form>
    </div>
    
    <!-- View Notifications Button -->
    <div style="text-align: center; margin-top: 25px;">
        <a href="notifications.php" class="view-notif-btn">
            <i class="fas fa-bell"></i> View All Notifications
        </a>
    </div>
</div>

<?php include 'footer.php'; ?>