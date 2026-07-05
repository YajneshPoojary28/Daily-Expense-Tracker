<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$currency = $_SESSION['currency'];
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
    if (empty($expense_date)) $expense_date = date('Y-m-d');
    
    if (empty($errors)) {
        $query = "INSERT INTO expenses (user_id, title, amount, category, description, expense_date) 
                  VALUES ($user_id, '$title', '$amount', '$category', '$description', '$expense_date')";
        
        if (mysqli_query($conn, $query)) {
            $_SESSION['success'] = "Expense added successfully!";
            redirect('dashboard.php');
        } else {
            $errors[] = "Failed to add expense: " . mysqli_error($conn);
        }
    }
}

include 'header.php';
include 'sidebar.php';
?>

<!-- Add Form Styles -->
<style>
.form-container {
    max-width: 600px;
    margin: 0 auto;
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #555;
    font-weight: 500;
    font-size: 0.95rem;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 15px;
    transition: all 0.3s;
    font-family: inherit;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-group textarea {
    resize: vertical;
    min-height: 100px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-actions {
    display: flex;
    gap: 15px;
    margin-top: 30px;
}

.btn {
    padding: 12px 30px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-block;
    text-align: center;
    flex: 1;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
    transform: translateY(-2px);
}

.alert {
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

h1 {
    margin-bottom: 30px;
    color: #333;
}
</style>

<h1>Add New Expense</h1>

<div class="form-container">
    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php foreach($errors as $error): ?>
                <p style="margin: 5px 0;"><?php echo $error; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <!-- Title Field -->
        <div class="form-group">
            <label for="title">
                <i class="fas fa-heading" style="color: #667eea; margin-right: 5px;"></i>
                Title
            </label>
            <input type="text" id="title" name="title" placeholder="e.g., Grocery Shopping, Dinner, Uber Ride" required>
        </div>
        
        <!-- Amount Field -->
        <div class="form-group">
            <label for="amount">
                <i class="fas fa-dollar-sign" style="color: #667eea; margin-right: 5px;"></i>
                Amount (<?php echo $currency; ?>)
            </label>
            <input type="number" step="0.01" id="amount" name="amount" placeholder="0.00" required>
        </div>
        
        <!-- Category Field -->
        <div class="form-group">
            <label for="category">
                <i class="fas fa-tag" style="color: #667eea; margin-right: 5px;"></i>
                Category
            </label>
            <select id="category" name="category" required>
                <option value="">Select Category</option>
                <?php foreach($categories as $cat): ?>
                    <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- Date Field -->
        <div class="form-group">
            <label for="expense_date">
                <i class="fas fa-calendar" style="color: #667eea; margin-right: 5px;"></i>
                Date
            </label>
            <input type="date" id="expense_date" name="expense_date" value="<?php echo date('Y-m-d'); ?>">
        </div>
        
        <!-- Description Field - Shows here between Date and Buttons -->
        <div class="form-group">
            <label for="description">
                <i class="fas fa-align-left" style="color: #667eea; margin-right: 5px;"></i>
                Description
            </label>
            <textarea id="description" name="description" rows="4" placeholder="Enter additional details about this expense..."></textarea>
        </div>
        
        <!-- Form Actions - Add Expense and Cancel buttons -->
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> Add Expense
            </button>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Cancel
            </a>
        </div>
    </form>
</div>

<?php
include 'footer.php';
?>