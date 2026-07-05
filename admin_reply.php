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
$message_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($message_id == 0) {
    header("Location: admin_messages.php");
    exit();
}

// Get the original message
$query = "SELECT m.*, 
          sender.username as sender_username,
          sender.full_name as sender_name,
          sender.email as sender_email
          FROM messages m
          JOIN users sender ON m.sender_id = sender.id
          WHERE m.id = $message_id";

$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    $_SESSION['error'] = "Message not found";
    header("Location: admin_messages.php");
    exit();
}

$msg = mysqli_fetch_assoc($result);

// Handle reply
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $reply_message = mysqli_real_escape_string($conn, $_POST['reply_message']);
    $subject = "RE: " . $msg['subject'];
    $sender_id = $user_id; // Admin
    $receiver_id = $msg['sender_id']; // Original sender
    
    // Insert reply as a new message
    $insert = "INSERT INTO messages (sender_id, receiver_id, subject, message) 
               VALUES ($sender_id, $receiver_id, '$subject', '$reply_message')";
    
    if (mysqli_query($conn, $insert)) {
        // Mark original message as read
        $update = "UPDATE messages SET is_read = 1 WHERE id = $message_id";
        mysqli_query($conn, $update);
        
        $_SESSION['success'] = "Reply sent successfully!";
        header("Location: admin_messages.php");
        exit();
    } else {
        $_SESSION['error'] = "Failed to send reply: " . mysqli_error($conn);
    }
}

include 'header.php';
include 'sidebar.php';
?>

<style>
.reply-container {
    max-width: 700px;
    margin: 0 auto;
}

.reply-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    flex-wrap: wrap;
}

.reply-header h1 {
    margin: 0;
}

.reply-header .btn-back {
    background: #6c757d;
    color: white;
    padding: 10px 20px;
    border-radius: 8px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
}

.reply-header .btn-back:hover {
    background: #5a6268;
    transform: translateY(-2px);
}

.original-message {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 25px;
    border-left: 4px solid #667eea;
}

.original-message .meta {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    margin-bottom: 10px;
    font-size: 0.95rem;
    color: #555;
}

.original-message .meta strong {
    color: #333;
}

.original-message .meta i {
    color: #667eea;
    width: 18px;
}

.original-message .content {
    padding: 10px 15px;
    background: white;
    border-radius: 8px;
    color: #333;
    line-height: 1.6;
}

.reply-form {
    background: white;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
}

.reply-form .form-group {
    margin-bottom: 20px;
}

.reply-form .form-group label {
    display: block;
    margin-bottom: 8px;
    color: #555;
    font-weight: 600;
}

.reply-form .form-group label i {
    color: #667eea;
    margin-right: 5px;
}

.reply-form .form-group textarea {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 1rem;
    font-family: inherit;
    transition: all 0.3s;
    min-height: 150px;
    resize: vertical;
}

.reply-form .form-group textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
}

.reply-form .btn-send {
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

.reply-form .btn-send:hover {
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

@media (max-width: 768px) {
    .reply-header {
        flex-direction: column;
        gap: 10px;
        align-items: flex-start;
    }
}
</style>

<div class="reply-container">
    <div class="reply-header">
        <h1><i class="fas fa-reply"></i> Reply to Message</h1>
        <a href="admin_messages.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Back to Messages
        </a>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Original Message -->
    <div class="original-message">
        <div class="meta">
            <span><i class="fas fa-user"></i> <strong>From:</strong> <?php echo htmlspecialchars($msg['sender_name']); ?> (@<?php echo htmlspecialchars($msg['sender_username']); ?>)</span>
            <span><i class="fas fa-envelope"></i> <strong>Subject:</strong> <?php echo htmlspecialchars($msg['subject']); ?></span>
            <span><i class="fas fa-calendar"></i> <strong>Date:</strong> <?php echo date('M d, Y h:i A', strtotime($msg['created_at'])); ?></span>
        </div>
        <div class="content">
            <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
        </div>
    </div>

    <!-- Reply Form -->
    <div class="reply-form">
        <form method="POST">
            <div class="form-group">
                <label for="reply_message"><i class="fas fa-align-left"></i> Your Reply</label>
                <textarea id="reply_message" name="reply_message" placeholder="Type your reply here..." required></textarea>
            </div>
            <button type="submit" class="btn-send">
                <i class="fas fa-paper-plane"></i> Send Reply
            </button>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>