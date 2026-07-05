<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$expense_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verify expense belongs to user
$check_query = "SELECT id FROM expenses WHERE id = $expense_id AND user_id = $user_id";
$check_result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($check_result) == 1) {
    $delete_query = "DELETE FROM expenses WHERE id = $expense_id AND user_id = $user_id";
    
    if (mysqli_query($conn, $delete_query)) {
        $_SESSION['success'] = "Expense deleted successfully!";
    } else {
        $_SESSION['error'] = "Failed to delete expense";
    }
} else {
    $_SESSION['error'] = "Expense not found";
}

redirect('dashboard.php');
?>