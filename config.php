<?php
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'expense_tracker');

// Create connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Function to check if user is logged in with additional security
function isLoggedIn() {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    // Optional: Check session timeout (8 hours)
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > 28800)) {
        session_unset();
        session_destroy();
        return false;
    }
    
    return true;
}

// Function to redirect
function redirect($url) {
    header("Location: $url");
    exit();
}

// Function to sanitize input
function sanitize($data) {
    global $conn;
    return mysqli_real_escape_string($conn, htmlspecialchars(trim($data)));
}

// Get user currency
function getUserCurrency($user_id) {
    global $conn;
    $query = "SELECT currency FROM users WHERE id = $user_id";
    $result = mysqli_query($conn, $query);
    if ($row = mysqli_fetch_assoc($result)) {
        return $row['currency'];
    }
    return '$';
}

// Check if user is admin
function isAdmin() {
    global $conn;
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    $user_id = $_SESSION['user_id'];
    $query = "SELECT role FROM users WHERE id = $user_id";
    $result = mysqli_query($conn, $query);
    if ($row = mysqli_fetch_assoc($result)) {
        return isset($row['role']) && $row['role'] == 'admin';
    }
    return false;
}

// Get user profile data
function getUserProfile($user_id) {
    global $conn;
    $query = "SELECT * FROM users WHERE id = $user_id";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_assoc($result);
}

// Get total users (for admin)
function getTotalUsers() {
    global $conn;
    $query = "SELECT COUNT(*) as total FROM users";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    return $row['total'];
}

// Get pending users (for admin)
function getPendingUsers() {
    global $conn;
    $query = "SELECT COUNT(*) as total FROM users WHERE status = 0";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    return $row['total'];
}

// Get total expenses (for admin)
function getTotalExpenses() {
    global $conn;
    $query = "SELECT SUM(amount) as total FROM expenses";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    return $row['total'] ?? 0;
}

// Get active users today
function getActiveUsersToday() {
    global $conn;
    $query = "SELECT COUNT(DISTINCT user_id) as count FROM expenses WHERE DATE(expense_date) = CURDATE()";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    return $row['count'];
}

// Get user role
function getUserRole($user_id) {
    global $conn;
    $query = "SELECT role FROM users WHERE id = $user_id";
    $result = mysqli_query($conn, $query);
    if ($row = mysqli_fetch_assoc($result)) {
        return $row['role'] ?? 'user';
    }
    return 'user';
}

// Update last login
function updateLastLogin($user_id) {
    global $conn;
    $query = "UPDATE users SET last_login = NOW() WHERE id = $user_id";
    mysqli_query($conn, $query);
}

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Format currency
function formatCurrency($amount, $currency = '$') {
    return $currency . number_format($amount, 2);
}

// Get expense statistics for user
function getUserExpenseStats($user_id) {
    global $conn;
    $stats = [];
    
    // Total expenses
    $result = mysqli_query($conn, "SELECT COUNT(*) as count, SUM(amount) as total FROM expenses WHERE user_id = $user_id");
    $stats['total'] = mysqli_fetch_assoc($result);
    
    // Monthly expenses
    $result = mysqli_query($conn, "SELECT SUM(amount) as total FROM expenses WHERE user_id = $user_id AND MONTH(expense_date) = MONTH(CURRENT_DATE())");
    $stats['monthly'] = mysqli_fetch_assoc($result);
    
    // Today's expenses
    $result = mysqli_query($conn, "SELECT SUM(amount) as total FROM expenses WHERE user_id = $user_id AND expense_date = CURRENT_DATE()");
    $stats['today'] = mysqli_fetch_assoc($result);
    
    // Category breakdown
    $result = mysqli_query($conn, "SELECT category, SUM(amount) as total, COUNT(*) as count FROM expenses WHERE user_id = $user_id GROUP BY category");
    $stats['categories'] = [];
    while($row = mysqli_fetch_assoc($result)) {
        $stats['categories'][] = $row;
    }
    
    return $stats;
}

// Get system statistics (for admin)
function getSystemStats() {
    global $conn;
    $stats = [];
    
    // Total users
    $stats['total_users'] = getTotalUsers();
    
    // Pending approvals
    $stats['pending_users'] = getPendingUsers();
    
    // Total expenses
    $stats['total_expenses'] = getTotalExpenses();
    
    // Active today
    $stats['active_today'] = getActiveUsersToday();
    
    // Total transactions
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM expenses");
    $row = mysqli_fetch_assoc($result);
    $stats['total_transactions'] = $row['count'];
    
    // Average expense per user
    if ($stats['total_users'] > 0) {
        $stats['avg_per_user'] = $stats['total_expenses'] / $stats['total_users'];
    } else {
        $stats['avg_per_user'] = 0;
    }
    
    return $stats;
}

// Log user activity (optional)
function logActivity($user_id, $action, $details = '') {
    global $conn;
    $query = "INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    $ip = $_SERVER['REMOTE_ADDR'];
    mysqli_stmt_bind_param($stmt, "isss", $user_id, $action, $details, $ip);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

// Check if table exists
function tableExists($table_name) {
    global $conn;
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$table_name'");
    return mysqli_num_rows($result) > 0;
}

// Get user by email
function getUserByEmail($email) {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $user;
}

// Get user by username
function getUserByUsername($username) {
    global $conn;
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $user;
}

// Update user status
function updateUserStatus($user_id, $status) {
    global $conn;
    $stmt = mysqli_prepare($conn, "UPDATE users SET status = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $status, $user_id);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $result;
}

// Make user admin
function makeUserAdmin($user_id) {
    global $conn;
    $stmt = mysqli_prepare($conn, "UPDATE users SET role = 'admin' WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $result;
}

// Remove admin privileges
function removeAdmin($user_id) {
    global $conn;
    $stmt = mysqli_prepare($conn, "UPDATE users SET role = 'user' WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $result;
}

// Get all users (for admin)
function getAllUsers($limit = null, $offset = null) {
    global $conn;
    $query = "SELECT * FROM users ORDER BY created_at DESC";
    if ($limit) {
        $query .= " LIMIT $limit";
        if ($offset) {
            $query .= " OFFSET $offset";
        }
    }
    $result = mysqli_query($conn, $query);
    $users = [];
    while($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
    return $users;
}

// Search users
function searchUsers($search_term) {
    global $conn;
    $search_term = "%$search_term%";
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE username LIKE ? OR email LIKE ? OR full_name LIKE ? ORDER BY created_at DESC");
    mysqli_stmt_bind_param($stmt, "sss", $search_term, $search_term, $search_term);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $users = [];
    while($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
    mysqli_stmt_close($stmt);
    return $users;
}

// Delete user (admin only)
function deleteUser($user_id) {
    global $conn;
    $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $result;
}

// Get recent activities (for admin dashboard)
function getRecentActivities($limit = 10) {
    global $conn;
    if (tableExists('activity_logs')) {
        $query = "SELECT a.*, u.username, u.full_name 
                  FROM activity_logs a 
                  JOIN users u ON a.user_id = u.id 
                  ORDER BY a.created_at DESC 
                  LIMIT $limit";
        $result = mysqli_query($conn, $query);
        $activities = [];
        while($row = mysqli_fetch_assoc($result)) {
            $activities[] = $row;
        }
        return $activities;
    }
    return [];
}

// Create activity logs table if not exists
function createActivityLogsTable() {
    global $conn;
    $sql = "CREATE TABLE IF NOT EXISTS activity_logs (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        action VARCHAR(100) NOT NULL,
        details TEXT,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    mysqli_query($conn, $sql);
}

// Initialize database tables
function initializeDatabase() {
    global $conn;
    
    // Create activity logs table
    createActivityLogsTable();
    
    // Add role column if not exists
    $result = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'role'");
    if (mysqli_num_rows($result) == 0) {
        mysqli_query($conn, "ALTER TABLE users ADD COLUMN role VARCHAR(20) DEFAULT 'user'");
    }
    
    // Add status column if not exists
    $result = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'status'");
    if (mysqli_num_rows($result) == 0) {
        mysqli_query($conn, "ALTER TABLE users ADD COLUMN status TINYINT DEFAULT 1");
    }
    
    // Add last_login column if not exists
    $result = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'last_login'");
    if (mysqli_num_rows($result) == 0) {
        mysqli_query($conn, "ALTER TABLE users ADD COLUMN last_login DATETIME DEFAULT NULL");
    }
    
    // Add avatar column if not exists
    $result = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'avatar'");
    if (mysqli_num_rows($result) == 0) {
        mysqli_query($conn, "ALTER TABLE users ADD COLUMN avatar VARCHAR(255) DEFAULT NULL");
    }
}

// Run initialization
initializeDatabase();

// Set default admin user (optional - run once)
function createDefaultAdmin() {
    global $conn;
    $check_admin = mysqli_query($conn, "SELECT id FROM users WHERE role = 'admin'");
    if (mysqli_num_rows($check_admin) == 0) {
        // Create default admin (you should change these credentials)
        $username = 'admin';
        $email = 'admin@expensetracker.com';
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $full_name = 'System Administrator';
        
        $query = "INSERT INTO users (username, email, password, full_name, role, status) 
                  VALUES ('$username', '$email', '$password', '$full_name', 'admin', 1)";
        mysqli_query($conn, $query);
    }
}

// Uncomment to create default admin (first time only)
// createDefaultAdmin();
?>