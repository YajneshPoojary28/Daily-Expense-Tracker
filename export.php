<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Get all expenses for the user
$query = "SELECT expense_date, title, category, amount, description 
          FROM expenses 
          WHERE user_id = $user_id 
          ORDER BY expense_date DESC";
$result = mysqli_query($conn, $query);

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="expenses_' . date('Y-m-d') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add CSV headers
fputcsv($output, ['Date', 'Title', 'Category', 'Amount', 'Description']);

// Add data rows
while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, [
        $row['expense_date'],
        $row['title'],
        $row['category'],
        $row['amount'],
        $row['description']
    ]);
}

fclose($output);
exit();
?>