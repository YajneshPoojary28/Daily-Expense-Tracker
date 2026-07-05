<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if (!isAdmin()) {
    $_SESSION['error'] = "Access denied. Admin only.";
    redirect('dashboard.php');
}

$msg_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($msg_id == 0) {
    header("Location: admin_messages.php");
    exit();
}

// Get message details
$query = "SELECT m.*, 
          sender.username as sender_username,
          sender.full_name as sender_name,
          sender.email as sender_email,
          receiver.username as receiver_username,
          receiver.full_name as receiver_name
          FROM messages m
          JOIN users sender ON m.sender_id = sender.id
          JOIN users receiver ON m.receiver_id = receiver.id
          WHERE m.id = $msg_id";

$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    $_SESSION['error'] = "Message not found";
    header("Location: admin_messages.php");
    exit();
}

$msg = mysqli_fetch_assoc($result);

// Mark as read
mysqli_query($conn, "UPDATE messages SET is_read = 1 WHERE id = $msg_id");

include 'header.php';
include 'sidebar.php';
?>

<style>
.message-container {
    max-width: 800px;
    margin: 0 auto;
}

.message-card {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
}

.message-header {
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 20px;
    margin-bottom: 20px;
}

.message-header h2 {
    color: #333;
    margin-bottom: 10px;
}

.message-meta {
    display: flex;
    gap: 30px;
    flex-wrap: wrap;
    color: #666;
    font-size: 0.95rem;
}

.message-meta i {
    color: #667eea;
    width: 20px;
}

.message-body {
    padding: 20px 0;
    line-height: 1.8;
    color: #444;
    font-size: 1.05rem;
    min-height: 150px;
    white-space: pre-wrap;
    background: #fafafa;
    padding: 20px;
    border-radius: 8px;
}

.message-actions {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 2px solid #f0f0f0;
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.btn {
    padding: 12px 25px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-block;
}

.btn-back {
    background: #6c757d;
    color: white;
}

.btn-back:hover {
    background: #5a6268;
}

.btn-delete {
    background: #dc3545;
    color: white;
}

.btn-delete:hover {
    background: #c82333;
}

.status-badge {
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
    display: inline-block;
}

.status-read {
    background: #d4edda;
    color: #155724;
}

.status-unread {
    background: #fff3cd;
    color: #856404;
}

.alert {
    padding: 15px 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert i {
    font-size: 1.2rem;
}
</style>

<div class="message-container">
    <h1><i class="fas fa-envelope-open-text"></i> View Message</h1>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>
    
    <div class="message-card">
        <div class="message-header">
            <h2><?php echo htmlspecialchars($msg['subject']); ?></h2>
            <div class="message-meta">
                <span><i class="fas fa-user"></i> From: <strong><?php echo htmlspecialchars($msg['sender_name']); ?></strong> (@<?php echo htmlspecialchars($msg['sender_username']); ?>)</span>
                <span><i class="fas fa-user"></i> To: <strong><?php echo htmlspecialchars($msg['receiver_name']); ?></strong> (@<?php echo htmlspecialchars($msg['receiver_username']); ?>)</span>
                <span><i class="fas fa-calendar"></i> <?php echo date('F d, Y h:i A', strtotime($msg['created_at'])); ?></span>
                <span>
                    <?php if ($msg['is_read'] == 1): ?>
                        <span class="status-badge status-read"><i class="fas fa-check"></i> Read</span>
                    <?php else: ?>
                        <span class="status-badge status-unread"><i class="fas fa-envelope"></i> Unread</span>
                    <?php endif; ?>
                </span>
            </div>
        </div>
        
        <div class="message-body">
            <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
        </div>
        
        <div class="message-actions">
            <a href="admin_messages.php" class="btn btn-back">
                <i class="fas fa-arrow-left"></i> Back to Messages
            </a>
            <a href="admin_messages.php?delete=<?php echo $msg['id']; ?>" 
               class="btn btn-delete"
               onclick="return confirm('Are you sure you want to delete this message?')">
                <i class="fas fa-trash"></i> Delete
            </a>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>