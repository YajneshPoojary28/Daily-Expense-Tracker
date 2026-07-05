<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$user = getUserProfile($user_id);

// Handle delete message
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $message_id = (int)$_GET['delete'];
    
    $check_query = "SELECT id, sender_id, receiver_id FROM messages WHERE id = $message_id";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        $msg = mysqli_fetch_assoc($check_result);
        if ($msg['sender_id'] == $user_id || $msg['receiver_id'] == $user_id) {
            $delete_query = "DELETE FROM messages WHERE id = $message_id";
            if (mysqli_query($conn, $delete_query)) {
                $_SESSION['success'] = "Message deleted successfully!";
            } else {
                $_SESSION['error'] = "Failed to delete message";
            }
        } else {
            $_SESSION['error'] = "You don't have permission to delete this message";
        }
    } else {
        $_SESSION['error'] = "Message not found";
    }
    header("Location: notifications.php");
    exit();
}

// Get all messages (sent by user AND replies from admin)
$messages_query = "SELECT * FROM messages 
                   WHERE sender_id = $user_id OR receiver_id = $user_id 
                   ORDER BY created_at DESC";
$messages_result = mysqli_query($conn, $messages_query);

// Count total messages
$total_messages = mysqli_num_rows($messages_result);

// Count unread messages (messages where user is receiver and unread)
$unread_query = "SELECT COUNT(*) as count FROM messages 
                 WHERE receiver_id = $user_id AND is_read = 0";
$unread_result = mysqli_query($conn, $unread_query);
$unread_row = mysqli_fetch_assoc($unread_result);
$unread_count = $unread_row['count'];

// Handle reply to admin
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reply_message'])) {
    $reply_message = mysqli_real_escape_string($conn, $_POST['reply_message']);
    $original_msg_id = (int)$_POST['original_msg_id'];
    
    $orig_query = "SELECT sender_id, receiver_id, subject FROM messages WHERE id = $original_msg_id";
    $orig_result = mysqli_query($conn, $orig_query);
    $orig = mysqli_fetch_assoc($orig_result);
    
    if ($orig) {
        $admin_id = ($orig['sender_id'] == $user_id) ? $orig['receiver_id'] : $orig['sender_id'];
        $subject = "RE: " . $orig['subject'];
        
        $insert = "INSERT INTO messages (sender_id, receiver_id, subject, message) 
                   VALUES ($user_id, $admin_id, '$subject', '$reply_message')";
        if (mysqli_query($conn, $insert)) {
            $_SESSION['success'] = "Reply sent successfully!";
        } else {
            $_SESSION['error'] = "Failed to send reply: " . mysqli_error($conn);
        }
    } else {
        $_SESSION['error'] = "Original message not found";
    }
    header("Location: notifications.php");
    exit();
}

include 'header.php';
include 'sidebar.php';
?>

<style>
.notifications-wrapper {
    max-width: 800px;
    margin: 0 auto;
    padding: 0 15px;
}

.notification-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    flex-wrap: wrap;
}

.notification-header h1 {
    margin: 0;
}

.notification-header .badge-count {
    background: #667eea;
    color: white;
    padding: 8px 15px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 600;
}

.notification-header .badge-count i {
    margin-right: 5px;
}

.stats-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    text-align: center;
}

.stat-card .number {
    font-size: 2rem;
    font-weight: bold;
    color: #333;
}

.stat-card .label {
    color: #666;
    font-size: 0.9rem;
    margin-top: 5px;
}

.stat-card .icon {
    font-size: 1.5rem;
    color: #667eea;
    margin-bottom: 5px;
}

.notification-item {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    border-left: 4px solid #667eea;
    transition: all 0.3s;
}

.notification-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.notification-item.unread {
    border-left-color: #ff6b6b;
    background: #f8f9fa;
}

.notification-item .notif-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    flex-wrap: wrap;
}

.notification-item .notif-subject {
    font-size: 1.1rem;
    font-weight: 600;
    color: #333;
}

.notification-item .notif-badge {
    font-size: 0.7rem;
    padding: 2px 10px;
    border-radius: 12px;
    font-weight: 600;
}

.notification-item .notif-badge.sent {
    background: #e3f2fd;
    color: #1976d2;
}

.notification-item .notif-badge.received {
    background: #fff3e0;
    color: #e65100;
}

.notification-item .notif-badge.reply {
    background: #e8f5e9;
    color: #2e7d32;
}

.notification-item .notif-date {
    color: #999;
    font-size: 0.85rem;
}

.notification-item .notif-message {
    color: #555;
    font-size: 0.95rem;
    line-height: 1.6;
    margin: 10px 0;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 8px;
    white-space: pre-wrap;
}

.notification-item .notif-status {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.notification-item .notif-status.read {
    background: #d4edda;
    color: #155724;
}

.notification-item .notif-status.unread {
    background: #fff3cd;
    color: #856404;
}

.notification-item .notif-status i {
    font-size: 0.7rem;
}

.notification-item .notif-actions {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #eee;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.btn-sm {
    padding: 6px 15px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.85rem;
    font-weight: 500;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.btn-sm:hover {
    transform: translateY(-2px);
}

.btn-delete-sm {
    background: #dc3545;
    color: white;
}

.btn-delete-sm:hover {
    background: #c82333;
}

.btn-reply-sm {
    background: #28a745;
    color: white;
}

.btn-reply-sm:hover {
    background: #218838;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}

.empty-state i {
    font-size: 4rem;
    display: block;
    margin-bottom: 15px;
    color: #ddd;
}

.empty-state p {
    font-size: 1.1rem;
    margin: 0 0 10px 0;
}

.empty-state .sub-text {
    font-size: 0.95rem;
    color: #bbb;
}

.empty-state a {
    display: inline-block;
    margin-top: 15px;
    color: #667eea;
    text-decoration: none;
    font-weight: 500;
}

.empty-state a:hover {
    text-decoration: underline;
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

/* Reply Modal */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    border-radius: 15px;
    padding: 30px;
    max-width: 500px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
}

.modal-header h3 {
    margin: 0;
    color: #333;
}

.modal-header h3 i {
    color: #28a745;
}

.close-modal {
    cursor: pointer;
    font-size: 1.5rem;
    color: #999;
    transition: color 0.3s;
}

.close-modal:hover {
    color: #333;
}

.modal .form-group {
    margin-bottom: 15px;
}

.modal .form-group label {
    display: block;
    margin-bottom: 5px;
    color: #555;
    font-weight: 500;
}

.modal .form-group label i {
    color: #667eea;
}

.modal .form-group textarea {
    width: 100%;
    padding: 10px 15px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 1rem;
    font-family: inherit;
    transition: all 0.3s;
    min-height: 100px;
    resize: vertical;
}

.modal .form-group textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
}

.modal .btn-send {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 12px 30px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 1rem;
    font-weight: 600;
    width: 100%;
    transition: all 0.3s;
}

.modal .btn-send:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102,126,234,0.4);
}

/* Responsive */
@media (max-width: 768px) {
    .stats-row {
        grid-template-columns: 1fr;
    }
    
    .notification-header {
        flex-direction: column;
        gap: 10px;
        text-align: center;
    }
    
    .notification-item .notif-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
    
    .modal-content {
        padding: 20px;
    }
}
</style>

<div class="notifications-wrapper">
    <div class="notification-header">
        <h1><i class="fas fa-bell"></i> Notifications</h1>
        <span class="badge-count">
            <i class="fas fa-envelope"></i> Total: <?php echo $total_messages; ?> 
            <?php if ($unread_count > 0): ?>
                | <i class="fas fa-envelope-open"></i> Unread: <span style="color: #ff6b6b;"><?php echo $unread_count; ?></span>
            <?php endif; ?>
        </span>
    </div>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="icon"><i class="fas fa-envelope"></i></div>
            <div class="number"><?php echo $total_messages; ?></div>
            <div class="label">Total Messages</div>
        </div>
        <div class="stat-card">
            <div class="icon"><i class="fas fa-envelope-open"></i></div>
            <div class="number"><?php echo $unread_count; ?></div>
            <div class="label">Unread</div>
        </div>
        <div class="stat-card">
            <div class="icon"><i class="fas fa-check-circle"></i></div>
            <div class="number"><?php echo $total_messages - $unread_count; ?></div>
            <div class="label">Read</div>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Messages List -->
    <?php if ($total_messages > 0): ?>
        <?php 
        $msg_counter = 0;
        while($msg = mysqli_fetch_assoc($messages_result)): 
            $msg_counter++;
            $is_reply = ($msg['sender_id'] != $user_id);
            $is_unread = ($msg['receiver_id'] == $user_id && $msg['is_read'] == 0);
        ?>
        <div class="notification-item <?php echo $is_unread ? 'unread' : ''; ?>">
            <div class="notif-header">
                <span class="notif-subject">
                    <?php echo htmlspecialchars($msg['subject']); ?>
                    <?php if ($is_reply): ?>
                        <span class="notif-badge reply"><i class="fas fa-reply"></i> Reply</span>
                    <?php else: ?>
                        <span class="notif-badge sent"><i class="fas fa-paper-plane"></i> Sent</span>
                    <?php endif; ?>
                </span>
                <span class="notif-date">
                    <i class="far fa-clock"></i> 
                    <?php echo date('M d, Y h:i A', strtotime($msg['created_at'])); ?>
                </span>
            </div>
            
            <div class="notif-message">
                <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
            </div>
            
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                <?php if ($is_reply): ?>
                    <span style="color: #28a745; font-size: 0.85rem;">
                        <i class="fas fa-check-circle"></i> Admin replied to you
                    </span>
                <?php else: ?>
                    <span class="notif-status <?php echo ($msg['is_read'] == 1) ? 'read' : 'unread'; ?>">
                        <i class="fas <?php echo ($msg['is_read'] == 1) ? 'fa-check-circle' : 'fa-envelope'; ?>"></i>
                        <?php echo ($msg['is_read'] == 1) ? 'Read by Admin' : 'Unread'; ?>
                    </span>
                <?php endif; ?>
            </div>
            
            <div class="notif-actions">
                <?php if ($is_reply): ?>
                    <span style="color: #28a745; font-size: 0.85rem; padding: 6px 15px; background: #d4edda; border-radius: 6px;">
                        <i class="fas fa-check"></i> Admin Response
                    </span>
                <?php endif; ?>
                
                <?php if ($is_unread): ?>
                    <a href="mark_read.php?id=<?php echo $msg['id']; ?>" class="btn-sm" style="background: #17a2b8; color: white;">
                        <i class="fas fa-check"></i> Mark as Read
                    </a>
                <?php endif; ?>
                
                <!-- Reply Button -->
                <button onclick="openReplyModal(<?php echo $msg['id']; ?>, '<?php echo addslashes($msg['subject']); ?>')" 
                        class="btn-sm btn-reply-sm">
                    <i class="fas fa-reply"></i> Reply
                </button>
                
                <!-- Delete Button -->
                <?php if (isset($msg['id']) && !empty($msg['id'])): ?>
                <a href="notifications.php?delete=<?php echo $msg['id']; ?>" 
                   class="btn-sm btn-delete-sm"
                   onclick="return confirm('Are you sure you want to delete this message?')">
                    <i class="fas fa-trash"></i> Delete
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
    <div class="empty-state">
        <i class="fas fa-bell-slash"></i>
        <p>No notifications yet</p>
        <p class="sub-text">When you send messages to admin, they will appear here</p>
        <a href="messages.php"><i class="fas fa-paper-plane"></i> Send a Message</a>
    </div>
    <?php endif; ?>
</div>

<!-- Reply Modal -->
<div id="replyModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-reply"></i> Reply to Message</h3>
            <span class="close-modal" onclick="closeReplyModal()">&times;</span>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="original_msg_id" id="original_msg_id">
            
            <div class="form-group">
                <label for="reply_subject"><i class="fas fa-heading"></i> Subject</label>
                <input type="text" id="reply_subject" name="reply_subject" class="form-control" readonly style="background: #f8f9fa; padding: 10px 15px; border: 2px solid #e5e7eb; border-radius: 8px; width: 100%;">
            </div>
            <div class="form-group">
                <label for="reply_message"><i class="fas fa-align-left"></i> Your Reply</label>
                <textarea id="reply_message" name="reply_message" placeholder="Type your reply here..." required></textarea>
            </div>
            <button type="submit" name="reply_message" class="btn-send">
                <i class="fas fa-paper-plane"></i> Send Reply
            </button>
        </form>
    </div>
</div>

<script>
function openReplyModal(msgId, subject) {
    document.getElementById('replyModal').style.display = 'flex';
    document.getElementById('original_msg_id').value = msgId;
    document.getElementById('reply_subject').value = 'RE: ' + subject;
    document.getElementById('reply_message').focus();
}

function closeReplyModal() {
    document.getElementById('replyModal').style.display = 'none';
    document.getElementById('reply_message').value = '';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('replyModal');
    if (event.target == modal) {
        closeReplyModal();
    }
}

// Close modal on Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeReplyModal();
    }
});
</script>

<?php include 'footer.php'; ?>