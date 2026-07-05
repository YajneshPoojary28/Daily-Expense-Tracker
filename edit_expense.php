<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$currency = $_SESSION['currency'];
$expense_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch expense details
$query = "SELECT * FROM expenses WHERE id = $expense_id AND user_id = $user_id";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    $_SESSION['error'] = "Expense not found";
    redirect('dashboard.php');
}

$expense = mysqli_fetch_assoc($result);
$categories = ['Food & Dining', 'Transportation', 'Shopping', 'Entertainment', 'Bills & Utilities', 'Healthcare', 'Education', 'Other'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = sanitize($_POST['title']);
    $amount = sanitize($_POST['amount']);
    $category = sanitize($_POST['category']);
    $description = sanitize($_POST['description']);
    $expense_date = sanitize($_POST['expense_date']);
    
    $errors = [];
    
    if (empty($title)) $errors[] = "Title is required";
    if (empty($amount) || !is_numeric($amount) || $amount <= 0) $errors[] = "Valid amount is required";
    if (empty($category)) $errors[] = "Category is required";
    
    if (empty($errors)) {
        $update_query = "UPDATE expenses SET 
                        title = '$title',
                        amount = '$amount',
                        category = '$category',
                        description = '$description',
                        expense_date = '$expense_date'
                        WHERE id = $expense_id AND user_id = $user_id";
        
        if (mysqli_query($conn, $update_query)) {
            $_SESSION['success'] = "Expense updated successfully!";
            redirect('dashboard.php');
        } else {
            $errors[] = "Failed to update expense: " . mysqli_error($conn);
        }
    }
}

include 'header.php';
include 'sidebar.php';
?>

<h1>Edit Expense</h1>

<div class="profile-container">
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach($errors as $error): ?>
                <p><?php echo $error; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="title">Title</label>
            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($expense['title']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="amount">Amount (<?php echo $currency; ?>)</label>
            <input type="number" step="0.01" id="amount" name="amount" value="<?php echo $expense['amount']; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="category">Category</label>
            <select id="category" name="category" required>
                <?php foreach($categories as $cat): ?>
                    <option value="<?php echo $cat; ?>" <?php echo $expense['category'] == $cat ? 'selected' : ''; ?>>
                        <?php echo $cat; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="expense_date">Date</label>
            <input type="date" id="expense_date" name="expense_date" value="<?php echo $expense['expense_date']; ?>">
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($expense['description']); ?></textarea>
        </div>
        
        <button type="submit" class="btn">Update Expense</button>
        <a href="dashboard.php" class="btn" style="background: #6c757d; text-align: center; text-decoration: none; display: inline-block; margin-top: 10px;">Cancel</a>
    </form>
</div>

<?php
include 'footer.php';
?>