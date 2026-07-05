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

// Debug - First check if messages exist
$check_query = "SELECT COUNT(*) as total FROM messages";
$check_result = mysqli_query($conn, $check_query);
$check_row = mysqli_fetch_assoc($check_result);
$total_messages = $check_row['total'];

// Get all messages
$query = "SELECT * FROM messages ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);

// Count unread messages
$unread_query = "SELECT COUNT(*) as count FROM messages WHERE is_read = 0";
$unread_result = mysqli_query($conn, $unread_query);
$unread_row = mysqli_fetch_assoc($unread_result);
$unread_count = $unread_row['count'];

// Mark message as read
if (isset($_GET['read']) && is_numeric($_GET['read'])) {
    $msg_id = $_GET['read'];
    $update = "UPDATE messages SET is_read = 1 WHERE id = $msg_id";
    mysqli_query($conn, $update);
    header("Location: admin_messages.php");
    exit();
}

// Delete message
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $msg_id = $_GET['delete'];
    $delete = "DELETE FROM messages WHERE id = $msg_id";
    if (mysqli_query($conn, $delete)) {
        $_SESSION['success'] = "Message deleted successfully!";
    }
    header("Location: admin_messages.php");
    exit();
}

include 'header.php';
include 'sidebar.php';
?>

<style>
.admin-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.admin-header h1 {
    margin: 0;
}

.message-table {
    background: white;
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    overflow-x: auto;
}

.message-table table {
    width: 100%;
    border-collapse: collapse;
}

.message-table th {
    background: #f8f9fa;
    padding: 15px;
    text-align: left;
    font-weight: 600;
    color: #555;
    border-bottom: 2px solid #dee2e6;
}

.message-table td {
    padding: 15px;
    border-bottom: 1px solid #eee;
    vertical-align: middle;
}

.message-table tr:hover {
    background: #f8f9fa;
}

.message-table tr.unread {
    background: #e8f0fe;
}

.message-table tr.unread:hover {
    background: #dce8f8;
}

.status-badge {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
    display: inline-block;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
}

.status-badge:hover {
    transform: scale(1.05);
    opacity: 0.9;
}

.status-read {
    background: #d4edda;
    color: #155724;
}

.status-unread {
    background: #fff3cd;
    color: #856404;
}

.btn-icon {
    padding: 8px 12px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.9rem;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-block;
    margin: 2px;
}

.btn-reply {
    background: #28a745;
    color: white;
}

.btn-reply:hover {
    background: #218838;
}

.btn-delete {
    background: #dc3545;
    color: white;
}

.btn-icon:hover {
    transform: translateY(-2px);
    opacity: 0.9;
}

.message-preview {
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
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

.btn-back {
    background: #6c757d;
    color: white;
    padding: 10px 20px;
    border-radius: 8px;
    text-decoration: none;
    display: inline-block;
}

.btn-back:hover {
    background: #5a6268;
    color: white;
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
    margin: 0;
}

.message-count {
    background: #667eea;
    color: white;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    margin-left: 10px;
}

.total-messages {
    margin-bottom: 20px;
    padding: 10px 15px;
    background: #f8f9fa;
    border-radius: 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
}

.total-messages span {
    color: #555;
}

.total-messages strong {
    color: #667eea;
}

.sender-info {
    display: flex;
    flex-direction: column;
}

.sender-info .name {
    font-weight: 600;
    color: #333;
}

.sender-info .username {
    font-size: 0.8rem;
    color: #888;
}

/* Reply badge in table */
.reply-badge {
    background: #28a745;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.65rem;
    font-weight: 600;
    margin-left: 5px;
}

/* Alignment fixes */
.text-left {
    text-align: left;
}
.text-center {
    text-align: center;
}
.text-right {
    text-align: right;
}
</style>

<div class="admin-header">
    <h1>
        <i class="fas fa-envelope-open-text"></i> All Messages
        <span class="message-count"><?php echo $total_messages; ?></span>
    </h1>
    <a href="admin_dashboard.php" class="btn-back">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
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

<!-- Total Messages Info -->
<div class="total-messages">
    <span><i class="fas fa-inbox"></i> Total Messages: <strong><?php echo $total_messages; ?></strong></span>
    <?php if ($total_messages > 0): ?>
        <span>
            <i class="fas fa-envelope"></i> Unread: <strong style="color: #dc3545;"><?php echo $unread_count; ?></strong>
        </span>
    <?php endif; ?>
</div>

<div class="message-table">
    <?php if ($total_messages > 0): ?>
    <table>
        <thead>
            <tr>
                <th class="text-left">From</th>
                <th class="text-left">To</th>
                <th class="text-left">Subject</th>
                <th class="text-left">Message</th>
                <th class="text-left">Date</th>
                <th class="text-left">Status</th>
                <th class="text-center">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            // Fetch all messages into an array first
            $messages = [];
            while($row = mysqli_fetch_assoc($result)) {
                $messages[] = $row;
            }
            
            // If no messages were fetched, try a different approach
            if (empty($messages)) {
                $query2 = "SELECT id, sender_id, receiver_id, subject, message, is_read, created_at FROM messages ORDER BY created_at DESC";
                $result2 = mysqli_query($conn, $query2);
                while($row = mysqli_fetch_assoc($result2)) {
                    $messages[] = $row;
                }
            }
            
            // Display messages
            foreach($messages as $msg): 
                // Get sender info
                $sender_name = 'Unknown';
                $sender_username = 'unknown';
                $sender_query = "SELECT username, full_name FROM users WHERE id = " . $msg['sender_id'];
                $sender_result = mysqli_query($conn, $sender_query);
                if($sender_row = mysqli_fetch_assoc($sender_result)) {
                    $sender_name = !empty($sender_row['full_name']) ? $sender_row['full_name'] : $sender_row['username'];
                    $sender_username = $sender_row['username'];
                }
                
                // Get receiver info
                $receiver_name = 'Unknown';
                $receiver_username = 'unknown';
                $receiver_query = "SELECT username, full_name FROM users WHERE id = " . $msg['receiver_id'];
                $receiver_result = mysqli_query($conn, $receiver_query);
                if($receiver_row = mysqli_fetch_assoc($receiver_result)) {
                    $receiver_name = !empty($receiver_row['full_name']) ? $receiver_row['full_name'] : $receiver_row['username'];
                    $receiver_username = $receiver_row['username'];
                }
                
                // Check if this is a reply (admin is sender)
                $is_reply = ($msg['sender_id'] == $user_id);
            ?>
            <tr class="<?php echo ($msg['is_read'] == 0) ? 'unread' : ''; ?>">
                <td class="text-left">
                    <div class="sender-info">
                        <span class="name"><?php echo htmlspecialchars($sender_name); ?></span>
                        <span class="username">@<?php echo htmlspecialchars($sender_username); ?></span>
                        <?php if ($is_reply): ?>
                            <span class="reply-badge"><i class="fas fa-reply"></i> Reply</span>
                        <?php endif; ?>
                    </div>
                </td>
                <td class="text-left">
                    <div class="sender-info">
                        <span class="name"><?php echo htmlspecialchars($receiver_name); ?></span>
                        <span class="username">@<?php echo htmlspecialchars($receiver_username); ?></span>
                    </div>
                </td>
                <td class="text-left"><strong><?php echo htmlspecialchars($msg['subject']); ?></strong></td>
                <td class="text-left">
                    <div class="message-preview" title="<?php echo htmlspecialchars($msg['message']); ?>">
                        <?php 
                        $msg_text = htmlspecialchars($msg['message']);
                        echo strlen($msg_text) > 50 ? substr($msg_text, 0, 50) . '...' : $msg_text; 
                        ?>
                    </div>
                </td>
                <td class="text-left"><?php echo date('M d, Y h:i A', strtotime($msg['created_at'])); ?></td>
                <td class="text-left">
                    <?php if ($msg['is_read'] == 1): ?>
                        <span class="status-badge status-read">
                            <i class="fas fa-check-circle"></i> Read
                        </span>
                    <?php else: ?>
                        <a href="admin_messages.php?read=<?php echo $msg['id']; ?>" class="status-badge status-unread" title="Click to mark as read">
                            <i class="fas fa-envelope"></i> Unread
                        </a>
                    <?php endif; ?>
                </td>
                <td class="text-center">
                    <div style="display: flex; gap: 5px; flex-wrap: wrap; justify-content: center;">
                        <?php if (!$is_reply): ?>
                            <!-- Reply button only for messages from users -->
                            <a href="admin_reply.php?id=<?php echo $msg['id']; ?>" class="btn-icon btn-reply" title="Reply to this message">
                                <i class="fas fa-reply"></i>
                            </a>
                        <?php endif; ?>
                        <a href="admin_messages.php?delete=<?php echo $msg['id']; ?>" 
                           class="btn-icon btn-delete" 
                           title="Delete Message"
                           onclick="return confirm('Are you sure you want to delete this message?')">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="empty-state">
        <i class="fas fa-inbox"></i>
        <p>No messages found</p>
        <p style="color: #bbb; font-size: 0.95rem;">Messages from users will appear here</p>
    </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>