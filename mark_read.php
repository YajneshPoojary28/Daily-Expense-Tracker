<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$message_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verify message belongs to user
$check_query = "SELECT id FROM messages WHERE id = $message_id AND receiver_id = $user_id";
$check_result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($check_result) == 1) {
    $update = "UPDATE messages SET is_read = 1 WHERE id = $message_id";
    mysqli_query($conn, $update);
    $_SESSION['success'] = "Message marked as read";
} else {
    $_SESSION['error'] = "Message not found";
}

header("Location: notifications.php");
exit();
?>