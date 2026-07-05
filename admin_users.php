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
$user = getUserProfile($user_id);

// Handle search
$search_term = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = sanitize($_GET['search']);
    $users_query = "SELECT * FROM users WHERE 
                    username LIKE '%$search_term%' OR 
                    email LIKE '%$search_term%' OR 
                    full_name LIKE '%$search_term%' 
                    ORDER BY created_at DESC";
} else {
    $users_query = "SELECT * FROM users ORDER BY created_at DESC";
}

$users_result = mysqli_query($conn, $users_query);

// Handle delete user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_user'])) {
    $target_user_id = sanitize($_POST['user_id']);
    
    if ($target_user_id != $_SESSION['user_id']) {
        // First delete all expenses of the user
        $delete_expenses = "DELETE FROM expenses WHERE user_id = $target_user_id";
        mysqli_query($conn, $delete_expenses);
        
        // Then delete the user
        $delete_query = "DELETE FROM users WHERE id = $target_user_id";
        if (mysqli_query($conn, $delete_query)) {
            $_SESSION['success'] = "User deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete user";
        }
    } else {
        $_SESSION['error'] = "You cannot delete your own account!";
    }
    redirect('admin_users.php');
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

.user-table {
    background: white;
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    overflow-x: auto;
}

.user-table table {
    width: 100%;
    border-collapse: collapse;
}

.user-table th {
    background: #f8f9fa;
    padding: 15px;
    text-align: left;
    font-weight: 600;
    color: #555;
    border-bottom: 2px solid #dee2e6;
}

.user-table td {
    padding: 15px;
    border-bottom: 1px solid #eee;
    vertical-align: middle;
}

.user-table tr:hover {
    background: #f8f9fa;
}

.role-badge {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
    display: inline-block;
}

.role-admin {
    background: #cce5ff;
    color: #004085;
}

.role-user {
    background: #e2e3e5;
    color: #383d41;
}

.admin-actions {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

.btn-icon {
    padding: 8px 12px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.9rem;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.btn-delete {
    background: #dc3545;
    color: white;
}

.btn-delete:hover {
    background: #c82333;
    transform: translateY(-2px);
}

.search-box {
    margin-bottom: 20px;
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

.search-box input {
    flex: 1;
    min-width: 200px;
    padding: 12px 15px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s;
}

.search-box input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.search-box .btn-search {
    padding: 12px 25px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s;
    white-space: nowrap;
}

.search-box .btn-search:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.search-box .btn-clear {
    background: #6c757d;
    color: white;
    padding: 12px 20px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    white-space: nowrap;
}

.search-box .btn-clear:hover {
    background: #5a6268;
    transform: translateY(-2px);
}

.search-info {
    margin-bottom: 15px;
    padding: 10px 15px;
    background: #e8f0fe;
    border-radius: 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}

.search-info span {
    color: #333;
}

.search-info strong {
    color: #667eea;
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
    margin: 0 0 5px 0;
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

/* Responsive */
@media (max-width: 768px) {
    .admin-actions {
        flex-direction: column;
        gap: 5px;
    }
    
    .btn-icon {
        width: 100%;
        justify-content: center;
    }
    
    .search-box {
        flex-direction: column;
    }
    
    .search-box input {
        min-width: 100%;
        width: 100%;
    }
    
    .search-box .btn-search,
    .search-box .btn-clear {
        width: 100%;
        justify-content: center;
    }
}
</style>

<div class="admin-header">
    <h1><i class="fas fa-users-cog"></i> Manage Users</h1>
    <a href="admin_dashboard.php" style="background: #6c757d; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none;">
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

<!-- Search Box -->
<form method="GET" action="" class="search-box">
    <input type="text" id="searchInput" name="search" placeholder="Search users by name, email, or username..." value="<?php echo htmlspecialchars($search_term); ?>">
    <button type="submit" class="btn-search"><i class="fas fa-search"></i> Search</button>
    <?php if (!empty($search_term)): ?>
        <a href="admin_users.php" class="btn-clear">
            <i class="fas fa-times-circle"></i> Clear
        </a>
    <?php endif; ?>
</form>

<!-- Search Info -->
<?php if (!empty($search_term)): ?>
    <div class="search-info">
        <span><i class="fas fa-search"></i> Showing results for: <strong>"<?php echo htmlspecialchars($search_term); ?>"</strong></span>
        <span>Found <strong><?php echo mysqli_num_rows($users_result); ?></strong> user(s)</span>
    </div>
<?php endif; ?>

<!-- Users Table -->
<div class="user-table">
    <?php if (mysqli_num_rows($users_result) > 0): ?>
    <table id="usersTable">
        <thead>
            <tr>
                <th>User</th>
                <th>Contact</th>
                <th>Joined</th>
                <th>Role</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($user_row = mysqli_fetch_assoc($users_result)): ?>
            <tr>
                <td>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <img src="<?php 
                            if (!empty($user_row['avatar']) && file_exists($user_row['avatar'])) {
                                echo $user_row['avatar'];
                            } else {
                                echo 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($user_row['email']))) . '?d=mp&s=40';
                            }
                        ?>" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                        <div>
                            <strong><?php echo htmlspecialchars($user_row['full_name']); ?></strong><br>
                            <small style="color: #666;">@<?php echo htmlspecialchars($user_row['username']); ?></small>
                            <?php if ($user_row['id'] == $_SESSION['user_id']): ?>
                                <span style="background: #e8f0fe; color: #667eea; padding: 2px 10px; border-radius: 12px; font-size: 0.7rem; font-weight: 600; display: inline-block;">
                                    <i class="fas fa-user-check"></i> You
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </td>
                <td>
                    <i class="fas fa-envelope" style="color: #667eea; margin-right: 5px;"></i>
                    <?php echo htmlspecialchars($user_row['email']); ?>
                </td>
                <td><?php echo date('M d, Y', strtotime($user_row['created_at'])); ?></td>
                <td>
                    <?php if ($user_row['role'] == 'admin'): ?>
                        <span class="role-badge role-admin"><i class="fas fa-crown"></i> Admin</span>
                    <?php else: ?>
                        <span class="role-badge role-user"><i class="fas fa-user"></i> User</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="admin-actions">
                        <?php if ($user_row['id'] != $_SESSION['user_id']): ?>
                            <form method="POST" style="display: inline;" onsubmit="return confirmDelete('<?php echo htmlspecialchars($user_row['full_name']); ?>', '<?php echo htmlspecialchars($user_row['username']); ?>')">
                                <input type="hidden" name="user_id" value="<?php echo $user_row['id']; ?>">
                                <button type="submit" name="delete_user" class="btn-icon btn-delete" title="Delete User">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        <?php else: ?>
                            <span style="color: #999; font-size: 0.85rem;"><i class="fas fa-lock"></i> Cannot delete yourself</span>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="empty-state">
        <i class="fas fa-users"></i>
        <p>No users found</p>
        <?php if (!empty($search_term)): ?>
            <p class="sub-text">Try a different search term</p>
            <a href="admin_users.php"><i class="fas fa-arrow-left"></i> Show all users</a>
        <?php else: ?>
            <p class="sub-text">No registered users yet</p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<script>
function confirmDelete(fullName, username) {
    return confirm(`Are you sure you want to delete user "${fullName}" (@${username})?\n\nThis action cannot be undone!\nAll their expenses and data will be permanently deleted.`);
}

// Auto-submit search on Enter key
document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        this.form.submit();
    }
});
</script>

<?php include 'footer.php'; ?>