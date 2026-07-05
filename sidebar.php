<?php
// Get current page name
$current_page = basename($_SERVER['PHP_SELF']);

// Check if user is admin
$is_admin = false;
if (isset($_SESSION['user_id'])) {
    global $conn;
    $user_id = $_SESSION['user_id'];
    $query = "SELECT role FROM users WHERE id = $user_id";
    $result = mysqli_query($conn, $query);
    if ($row = mysqli_fetch_assoc($result)) {
        $is_admin = ($row['role'] == 'admin');
    }
}
?>

<div class="sidebar">
    <div class="sidebar-header">
        <h3><i class="fas fa-wallet"></i> <span>ExpenseTracker</span></h3>
    </div>
    
    <ul class="nav-links">
        <?php if ($is_admin): ?>
            <!-- Admin Menu -->
            <li class="nav-section">ADMIN PANEL</li>
            
            <li>
                <a href="admin_dashboard.php" class="<?php echo $current_page == 'admin_dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-shield-alt"></i>
                    <span>Admin Dashboard</span>
                </a>
            </li>
            <li>
                <a href="admin_users.php" class="<?php echo $current_page == 'admin_users.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users-cog"></i>
                    <span>Manage Users</span>
                </a>
            </li>
            <li>
                <a href="admin_messages.php" class="<?php echo $current_page == 'admin_messages.php' ? 'active' : ''; ?>">
                    <i class="fas fa-envelope-open-text"></i>
                    <span>All Messages</span>
                    <?php
                    // Count unread messages for admin (messages where admin is receiver and unread)
                    if (isset($_SESSION['user_id'])) {
                        $admin_id = $_SESSION['user_id'];
                        $unread_query = "SELECT COUNT(*) as count FROM messages 
                                        WHERE receiver_id = $admin_id AND is_read = 0";
                        $unread_result = mysqli_query($conn, $unread_query);
                        $unread_row = mysqli_fetch_assoc($unread_result);
                        if ($unread_row['count'] > 0) {
                            echo '<span class="badge badge-pulse">' . $unread_row['count'] . '</span>';
                        }
                    }
                    ?>
                </a>
            </li>
            <li>
                <a href="admin_settings.php" class="<?php echo $current_page == 'admin_settings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </li>
            
            <li class="nav-section">ACCOUNT</li>
            <li>
                <a href="profile.php" class="<?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </a>
            </li>
            <li>
                <a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
            
        <?php else: ?>
            <!-- User Menu -->
            <li class="nav-section">MAIN MENU</li>
            
            <li>
                <a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="add_expense.php" class="<?php echo $current_page == 'add_expense.php' ? 'active' : ''; ?>">
                    <i class="fas fa-plus-circle"></i>
                    <span>Add Expense</span>
                </a>
            </li>
            <li>
                <a href="reports.php" class="<?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-pie"></i>
                    <span>Reports</span>
                </a>
            </li>
            <li>
                <a href="messages.php" class="<?php echo $current_page == 'messages.php' ? 'active' : ''; ?>">
                    <i class="fas fa-envelope"></i>
                    <span>Messages</span>
                </a>
            </li>
            <li>
                <a href="notifications.php" class="<?php echo $current_page == 'notifications.php' ? 'active' : ''; ?>">
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
                    <?php
                    // Show unread count for user (messages where user is receiver and unread)
                    if (isset($_SESSION['user_id'])) {
                        $user_id = $_SESSION['user_id'];
                        $unread_query = "SELECT COUNT(*) as count FROM messages 
                                        WHERE receiver_id = $user_id AND is_read = 0";
                        $unread_result = mysqli_query($conn, $unread_query);
                        $unread_row = mysqli_fetch_assoc($unread_result);
                        if ($unread_row['count'] > 0) {
                            echo '<span class="badge badge-pulse">' . $unread_row['count'] . '</span>';
                        }
                    }
                    ?>
                </a>
            </li>
            
            <li class="nav-section">ACCOUNT</li>
            <li>
                <a href="profile.php" class="<?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </a>
            </li>
            <li>
                <a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        <?php endif; ?>
    </ul>
</div>

<div class="main-content">

<style>
/* Sidebar Styles */
.sidebar {
    width: 260px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    overflow-y: auto;
    transition: all 0.3s;
    z-index: 1000;
    box-shadow: 2px 0 10px rgba(0,0,0,0.1);
}

.sidebar-header {
    padding: 25px 20px;
    text-align: center;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.sidebar-header h3 {
    font-size: 1.3rem;
    font-weight: 600;
    margin: 0;
    letter-spacing: 0.5px;
}

.sidebar-header h3 i {
    margin-right: 10px;
}

.nav-links {
    list-style: none;
    padding: 10px 0;
    margin: 0;
}

.nav-links li {
    padding: 0;
    margin: 0;
}

.nav-links li a {
    color: rgba(255,255,255,0.85);
    text-decoration: none;
    display: flex;
    align-items: center;
    padding: 12px 25px;
    transition: all 0.3s ease;
    border-left: 4px solid transparent;
    position: relative;
    font-size: 0.95rem;
}

.nav-links li a:hover {
    background: rgba(255,255,255,0.1);
    color: white;
    border-left-color: rgba(255,255,255,0.5);
}

.nav-links li a.active {
    background: rgba(255,255,255,0.15);
    border-left-color: white;
    color: white;
    font-weight: 600;
}

.nav-links li a i {
    width: 22px;
    font-size: 1.1rem;
    margin-right: 12px;
    text-align: center;
}

.nav-links li a span {
    flex: 1;
}

.nav-links li a .badge {
    position: absolute;
    right: 20px;
    background: #ff6b6b;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.65rem;
    font-weight: bold;
    min-width: 18px;
    text-align: center;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.3); }
    100% { transform: scale(1); }
}
.badge-pulse {
    animation: pulse 1.5s infinite;
}

.nav-links .nav-section {
    padding: 20px 25px 8px;
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: rgba(255,255,255,0.4);
    font-weight: 700;
}

.main-content {
    flex: 1;
    margin-left: 260px;
    padding: 30px;
    min-height: 100vh;
    background: #f4f6f9;
    transition: all 0.3s;
}

.sidebar::-webkit-scrollbar {
    width: 4px;
}

.sidebar::-webkit-scrollbar-track {
    background: rgba(255,255,255,0.05);
}

.sidebar::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.2);
    border-radius: 4px;
}

.sidebar::-webkit-scrollbar-thumb:hover {
    background: rgba(255,255,255,0.3);
}

@media (max-width: 768px) {
    .sidebar {
        width: 70px;
    }
    
    .sidebar-header h3 span,
    .nav-links li a span,
    .nav-links .nav-section {
        display: none;
    }
    
    .nav-links li a {
        padding: 15px;
        justify-content: center;
    }
    
    .nav-links li a i {
        margin-right: 0;
        font-size: 1.2rem;
    }
    
    .nav-links li a .badge {
        right: 5px;
        top: 5px;
        font-size: 0.55rem;
        padding: 1px 6px;
        min-width: 14px;
    }
    
    .main-content {
        margin-left: 70px;
        padding: 20px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const currentLocation = window.location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll('.nav-links li a');
    
    navLinks.forEach(link => {
        const linkHref = link.getAttribute('href');
        if (linkHref === currentLocation) {
            link.classList.add('active');
        }
    });
});

function addTooltips() {
    if (window.innerWidth <= 768) {
        const navItems = document.querySelectorAll('.nav-links li a');
        navItems.forEach(item => {
            const text = item.querySelector('span')?.textContent;
            if (text) item.setAttribute('title', text);
        });
    }
}

window.addEventListener('load', addTooltips);
window.addEventListener('resize', addTooltips);
</script>