<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

// If user is admin, redirect to admin dashboard
if (isAdmin()) {
    redirect('admin_dashboard.php');
}

$user_id = $_SESSION['user_id'];
$currency = $_SESSION['currency'];

// Get user profile data
$user = getUserProfile($user_id);

// Get total expenses
$total_query = "SELECT SUM(amount) as total FROM expenses WHERE user_id = $user_id";
$total_result = mysqli_query($conn, $total_query);
$total_row = mysqli_fetch_assoc($total_result);
$total_expenses = $total_row['total'] ?? 0;

// Get monthly expenses
$month_query = "SELECT SUM(amount) as monthly FROM expenses WHERE user_id = $user_id AND MONTH(expense_date) = MONTH(CURRENT_DATE())";
$month_result = mysqli_query($conn, $month_query);
$month_row = mysqli_fetch_assoc($month_result);
$monthly_expenses = $month_row['monthly'] ?? 0;

// Get today's expenses
$today_query = "SELECT SUM(amount) as today FROM expenses WHERE user_id = $user_id AND expense_date = CURRENT_DATE()";
$today_result = mysqli_query($conn, $today_query);
$today_row = mysqli_fetch_assoc($today_result);
$today_expenses = $today_row['today'] ?? 0;

// Get expense count
$count_query = "SELECT COUNT(*) as count FROM expenses WHERE user_id = $user_id";
$count_result = mysqli_query($conn, $count_query);
$count_row = mysqli_fetch_assoc($count_result);
$expense_count = $count_row['count'] ?? 0;

// Get category wise expenses for pie chart (current month)
$category_query = "SELECT category, SUM(amount) as total, COUNT(*) as count
                   FROM expenses 
                   WHERE user_id = $user_id 
                   AND MONTH(expense_date) = MONTH(CURRENT_DATE())
                   GROUP BY category 
                   ORDER BY total DESC";
$category_result = mysqli_query($conn, $category_query);

// Calculate total for percentages
$total_month = 0;
$categories_data = [];
while($row = mysqli_fetch_assoc($category_result)) {
    $categories_data[] = $row;
    $total_month += $row['total'];
}

// Get daily expenses for line chart (last 7 days)
$daily_query = "SELECT 
                    DATE_FORMAT(expense_date, '%a') as day_name,
                    DATE(expense_date) as date,
                    SUM(amount) as total
                FROM expenses 
                WHERE user_id = $user_id 
                AND expense_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)
                GROUP BY DATE(expense_date)
                ORDER BY date ASC";
$daily_result = mysqli_query($conn, $daily_query);

// Get recent expenses
$recent_query = "SELECT * FROM expenses WHERE user_id = $user_id ORDER BY expense_date DESC, created_at DESC LIMIT 10";
$recent_result = mysqli_query($conn, $recent_query);

include 'header.php';
include 'sidebar.php';
?>

<!-- Dashboard Styles -->
<style>
/* Welcome Profile Card */
.welcome-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 20px;
    padding: 30px;
    margin-bottom: 30px;
    color: white;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 20px;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
}

.welcome-content {
    display: flex;
    align-items: center;
    gap: 25px;
    flex-wrap: wrap;
}

.welcome-avatar {
    position: relative;
}

.welcome-avatar img {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    border: 4px solid rgba(255,255,255,0.3);
    object-fit: cover;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.welcome-text h2 {
    font-size: 1.8rem;
    margin-bottom: 5px;
    font-weight: 600;
}

.welcome-text p {
    opacity: 0.9;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}

.welcome-text p i {
    margin-right: 5px;
}

.role-badge {
    background: rgba(255,255,255,0.2);
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.role-badge i {
    font-size: 0.9rem;
}

.welcome-actions {
    display: flex;
    gap: 15px;
}

.btn-profile {
    background: white;
    color: #667eea;
    padding: 12px 25px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
}

.btn-profile:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.btn-quick-add {
    background: rgba(255,255,255,0.2);
    color: white;
    padding: 12px 25px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
    border: 2px solid rgba(255,255,255,0.3);
}

.btn-quick-add:hover {
    background: white;
    color: #667eea;
    transform: translateY(-2px);
}

/* Stats Row */
.stats-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.stat-item {
    background: white;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: all 0.3s;
    border: 1px solid rgba(0,0,0,0.03);
}

.stat-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
    border-color: #667eea20;
}

.stat-info h4 {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-info p {
    font-size: 2rem;
    font-weight: bold;
    color: #333;
    line-height: 1.2;
    margin-bottom: 5px;
}

.stat-info small {
    color: #999;
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    gap: 5px;
}

.stat-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #667eea10 0%, #764ba210 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.stat-icon i {
    font-size: 2rem;
    color: #667eea;
}

/* Charts Row */
.charts-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px;
    margin-bottom: 30px;
}

.chart-box {
    background: white;
    border-radius: 20px;
    padding: 25px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    border: 1px solid rgba(0,0,0,0.03);
}

.chart-box h3 {
    margin-bottom: 20px;
    color: #333;
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    gap: 10px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
}

.chart-box h3 i {
    color: #667eea;
    font-size: 1.3rem;
}

.chart-container {
    position: relative;
    height: 300px;
    width: 100%;
    margin-bottom: 20px;
}

/* Percentage Table */
.percentage-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    font-size: 0.95rem;
    border: 1px solid #eee;
    border-radius: 12px;
    overflow: hidden;
}

.percentage-table th {
    background: #f8f9fa;
    padding: 12px 15px;
    text-align: left;
    font-weight: 600;
    color: #555;
    border-bottom: 2px solid #dee2e6;
}

.percentage-table td {
    padding: 12px 15px;
    border-bottom: 1px solid #eee;
}

.percentage-table tr:last-child td {
    border-bottom: none;
}

.percentage-table tbody tr:hover {
    background: #f8f9fa;
}

.category-indicator {
    display: inline-block;
    width: 12px;
    height: 12px;
    border-radius: 3px;
    margin-right: 8px;
}

.text-right {
    text-align: right;
}

.font-bold {
    font-weight: 600;
}

/* Action Buttons */
.action-buttons {
    margin: 20px 0 30px;
    display: flex;
    gap: 15px;
    justify-content: flex-end;
    flex-wrap: wrap;
}

.btn-action {
    padding: 12px 25px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border: none;
    cursor: pointer;
    font-size: 0.95rem;
}

.btn-print {
    background: #17a2b8;
    color: white;
}

.btn-export {
    background: #28a745;
    color: white;
}

.btn-action:hover {
    transform: translateY(-2px);
    opacity: 0.9;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

/* Category Badge */
.category-badge {
    background: #e3f2fd;
    color: #1976d2;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
    display: inline-block;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 50px;
    color: #999;
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 15px;
    color: #ddd;
}

.empty-state p {
    font-size: 1.1rem;
}

.empty-state a {
    color: #667eea;
    text-decoration: none;
    font-weight: 600;
}

.empty-state a:hover {
    text-decoration: underline;
}

/* Recent Expenses Table */
.expense-table {
    background: white;
    border-radius: 20px;
    padding: 25px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    border: 1px solid rgba(0,0,0,0.03);
    overflow-x: auto;
}

.expense-table h2 {
    margin-bottom: 20px;
    color: #333;
    font-size: 1.3rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.expense-table h2 i {
    color: #667eea;
}

.expense-table table {
    width: 100%;
    border-collapse: collapse;
}

.expense-table th {
    background: #f8f9fa;
    padding: 15px;
    text-align: left;
    font-weight: 600;
    color: #555;
    border-bottom: 2px solid #dee2e6;
}

.expense-table td {
    padding: 15px;
    border-bottom: 1px solid #eee;
    vertical-align: middle;
}

.expense-table tbody tr:hover {
    background: #f8f9fa;
}

.action-btn {
    padding: 6px 12px;
    border-radius: 6px;
    color: white;
    text-decoration: none;
    margin: 0 3px;
    display: inline-block;
    transition: all 0.3s;
}

.action-btn:hover {
    transform: translateY(-2px);
    opacity: 0.9;
}

.edit-btn {
    background: #28a745;
}

.delete-btn {
    background: #dc3545;
}

/* Progress Bar */
.progress-bar {
    background: #eee;
    height: 6px;
    width: 60px;
    border-radius: 3px;
    overflow: hidden;
}

.progress-fill {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    height: 100%;
    border-radius: 3px;
    transition: width 0.3s;
}

/* Responsive */
@media (max-width: 992px) {
    .stats-row {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .charts-row {
        grid-template-columns: 1fr;
    }
    
    .welcome-card {
        flex-direction: column;
        text-align: center;
    }
    
    .welcome-content {
        justify-content: center;
    }
}

@media (max-width: 768px) {
    .stats-row {
        grid-template-columns: 1fr;
    }
    
    .welcome-text h2 {
        font-size: 1.5rem;
    }
    
    .welcome-actions {
        width: 100%;
        justify-content: center;
    }
    
    .action-buttons {
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .welcome-content {
        flex-direction: column;
        gap: 15px;
    }
    
    .btn-profile, .btn-quick-add {
        width: 100%;
        justify-content: center;
    }
    
    .welcome-actions {
        flex-direction: column;
        gap: 10px;
    }
}
</style>

<!-- Welcome Profile Card -->
<div class="welcome-card">
    <div class="welcome-content">
        <div class="welcome-avatar">
            <img src="<?php 
                if (!empty($user['avatar']) && file_exists($user['avatar'])) {
                    echo $user['avatar'];
                } else {
                    echo 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($user['email']))) . '?d=mp&s=80';
                }
            ?>" alt="Profile Avatar">
        </div>
        <div class="welcome-text">
            <h2>Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>! 👋</h2>
            <p>
                <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></span>
                <span><i class="fas fa-calendar-alt"></i> Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></span>
                <?php if (isAdmin()): ?>
                    <span class="role-badge">
                        <i class="fas fa-crown"></i> Administrator
                    </span>
                <?php else: ?>
                    <span class="role-badge">
                        <i class="fas fa-user"></i> Member
                    </span>
                <?php endif; ?>
            </p>
        </div>
    </div>
    <div class="welcome-actions">
        <a href="profile.php" class="btn-profile">
            <i class="fas fa-user-edit"></i> Edit Profile
        </a>
        <a href="add_expense.php" class="btn-quick-add">
            <i class="fas fa-plus-circle"></i> Quick Add
        </a>
    </div>
</div>

<!-- Statistics Cards -->
<div class="stats-row">
    <div class="stat-item">
        <div class="stat-info">
            <h4>Total Expenses</h4>
            <p><?php echo $currency . number_format($total_expenses, 2); ?></p>
            <small><i class="fas fa-chart-line"></i> Lifetime total</small>
        </div>
        <div class="stat-icon">
            <i class="fas fa-chart-line"></i>
        </div>
    </div>
    
    <div class="stat-item">
        <div class="stat-info">
            <h4>This Month</h4>
            <p><?php echo $currency . number_format($monthly_expenses, 2); ?></p>
            <small><i class="fas fa-calendar-alt"></i> <?php echo date('F Y'); ?></small>
        </div>
        <div class="stat-icon">
            <i class="fas fa-calendar-alt"></i>
        </div>
    </div>
    
    <div class="stat-item">
        <div class="stat-info">
            <h4>Today</h4>
            <p><?php echo $currency . number_format($today_expenses, 2); ?></p>
            <small><i class="fas fa-sun"></i> <?php echo date('l'); ?></small>
        </div>
        <div class="stat-icon">
            <i class="fas fa-sun"></i>
        </div>
    </div>
    
    <div class="stat-item">
        <div class="stat-info">
            <h4>Transactions</h4>
            <p><?php echo $expense_count; ?></p>
            <small><i class="fas fa-receipt"></i> Total entries</small>
        </div>
        <div class="stat-icon">
            <i class="fas fa-receipt"></i>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="charts-row">
    <!-- Pie Chart with Percentage Table Below -->
    <div class="chart-box">
        <h3>
            <i class="fas fa-chart-pie"></i>
            Expenses by Category - This Month
        </h3>
        <div class="chart-container">
            <canvas id="categoryChart"></canvas>
        </div>
        
        <!-- Percentage Table Below Pie Chart -->
        <?php if ($total_month > 0): ?>
        <table class="percentage-table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th class="text-right">Value (<?php echo $currency; ?>)</th>
                    <th class="text-right">Percentage (%)</th>
                </tr>
            </thead>
            <tbody id="percentageTableBody">
                <!-- Populated by JavaScript -->
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-chart-pie"></i>
            <p>No expenses this month</p>
            <a href="add_expense.php" style="color: #667eea;">Add your first expense</a>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Line Chart - Daily Trend -->
    <div class="chart-box">
        <h3>
            <i class="fas fa-chart-line"></i>
            Daily Expense Trend - Last 7 Days
        </h3>
        <div class="chart-container">
            <canvas id="dailyChart"></canvas>
        </div>
        <?php if ($total_month == 0): ?>
        <div class="empty-state" style="padding: 20px;">
            <i class="fas fa-chart-line"></i>
            <p>No data available</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Action Buttons -->
<div class="action-buttons">
    <button onclick="window.print()" class="btn-action btn-print">
        <i class="fas fa-print"></i> Print Dashboard
    </button>
    <a href="export.php" class="btn-action btn-export">
        <i class="fas fa-download"></i> Export as CSV
    </a>
</div>

<!-- Recent Expenses Table -->
<div class="expense-table">
    <h2>
        <i class="fas fa-history"></i>
        Recent Transactions
    </h2>
    
    <?php if (mysqli_num_rows($recent_result) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Title</th>
                <th>Category</th>
                <th>Amount</th>
                <th>% of Month</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            mysqli_data_seek($recent_result, 0);
            while($expense = mysqli_fetch_assoc($recent_result)): 
                $percentage = $total_month > 0 ? round(($expense['amount'] / $total_month) * 100, 1) : 0;
            ?>
                <tr>
                    <td><?php echo date('M d, Y', strtotime($expense['expense_date'])); ?></td>
                    <td><?php echo htmlspecialchars($expense['title']); ?></td>
                    <td><span class="category-badge"><?php echo htmlspecialchars($expense['category']); ?></span></td>
                    <td><strong><?php echo $currency . number_format($expense['amount'], 2); ?></strong></td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span style="min-width: 45px;"><?php echo $percentage; ?>%</span>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $percentage; ?>%;"></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <a href="edit_expense.php?id=<?php echo $expense['id']; ?>" class="action-btn edit-btn" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="delete_expense.php?id=<?php echo $expense['id']; ?>" 
                           class="action-btn delete-btn" 
                           title="Delete"
                           onclick="return confirm('Are you sure you want to delete this expense?')">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="empty-state">
        <i class="fas fa-receipt"></i>
        <p>No expenses yet. Start by adding your first expense!</p>
        <a href="add_expense.php" class="btn-profile" style="display: inline-block; margin-top: 15px;">
            <i class="fas fa-plus-circle"></i> Add Expense
        </a>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Prepare data for charts
<?php
// Pie Chart Data with Percentages
$pie_labels = [];
$pie_data = [];
$pie_percentages = [];
$pie_colors = ['#667eea', '#764ba2', '#ff6b6b', '#4ecdc4', '#45b7d1', '#f39c12', '#e74c3c', '#3498db', '#2ecc71', '#e67e22'];

foreach ($categories_data as $row) {
    $pie_labels[] = $row['category'];
    $pie_data[] = (float)$row['total'];
    $percentage = $total_month > 0 ? round(($row['total'] / $total_month) * 100, 1) : 0;
    $pie_percentages[] = $percentage;
}

// Line Chart Data
$line_labels = [];
$line_data = [];

// Initialize last 7 days with 0 values
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $line_labels[] = date('D', strtotime($date));
    $line_data[] = 0;
}

// Fill with actual data
mysqli_data_seek($daily_result, 0);
while($row = mysqli_fetch_assoc($daily_result)) {
    $day_name = date('D', strtotime($row['date']));
    // Find the index for this day
    for ($i = 0; $i < 7; $i++) {
        if ($line_labels[$i] == $day_name) {
            $line_data[$i] = (float)$row['total'];
            break;
        }
    }
}
?>

// Create pie chart only if there's data
<?php if ($total_month > 0): ?>
const ctx = document.getElementById('categoryChart').getContext('2d');
const categoryChart = new Chart(ctx, {
    type: 'pie',
    data: {
        labels: <?php echo json_encode($pie_labels); ?>,
        datasets: [{
            data: <?php echo json_encode($pie_data); ?>,
            backgroundColor: <?php echo json_encode(array_slice($pie_colors, 0, count($pie_labels))); ?>,
            borderWidth: 0,
            hoverOffset: 15
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.raw || 0;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((value / total) * 100).toFixed(1);
                        return `${label}: <?php echo $currency; ?>${value.toFixed(2)} (${percentage}%)`;
                    }
                }
            }
        }
    }
});

// Populate percentage table
const tableBody = document.getElementById('percentageTableBody');
if (tableBody) {
    const colors = <?php echo json_encode(array_slice($pie_colors, 0, count($pie_labels))); ?>;
    const labels = <?php echo json_encode($pie_labels); ?>;
    const percentages = <?php echo json_encode($pie_percentages); ?>;
    const amounts = <?php echo json_encode($pie_data); ?>;

    let tableHtml = '';
    for (let i = 0; i < labels.length; i++) {
        tableHtml += `
            <tr>
                <td>
                    <span class="category-indicator" style="background: ${colors[i]};"></span>
                    ${labels[i]}
                </td>
                <td class="text-right"><?php echo $currency; ?>${amounts[i].toFixed(2)}</td>
                <td class="text-right font-bold">${percentages[i]}%</td>
            </tr>
        `;
    }
    tableBody.innerHTML = tableHtml;
}
<?php endif; ?>

// Create line chart
<?php if (array_sum($line_data) > 0): ?>
new Chart(document.getElementById('dailyChart'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode($line_labels); ?>,
        datasets: [{
            label: 'Daily Expenses',
            data: <?php echo json_encode($line_data); ?>,
            borderColor: '#667eea',
            backgroundColor: 'rgba(102, 126, 234, 0.1)',
            borderWidth: 3,
            pointBackgroundColor: '#667eea',
            pointBorderColor: 'white',
            pointBorderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 7,
            fill: true,
            tension: 0.3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return `Amount: <?php echo $currency; ?>${context.raw.toFixed(2)}`;
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(0,0,0,0.05)'
                },
                ticks: {
                    callback: function(value) {
                        return '<?php echo $currency; ?>' + value;
                    }
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});
<?php endif; ?>
</script>

<?php
include 'footer.php';
?>