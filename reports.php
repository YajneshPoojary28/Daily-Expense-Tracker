<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$currency = $_SESSION['currency'];

// Get filter parameters
$filter_type = isset($_GET['filter']) ? $_GET['filter'] : 'month';
$custom_start = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$custom_end = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Build date condition based on filter
$date_condition = "";
$period_title = "";

switch($filter_type) {
    case 'week':
        $date_condition = "AND expense_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        $period_title = "Last 7 Days";
        break;
    case 'month':
        $date_condition = "AND MONTH(expense_date) = MONTH(CURDATE()) AND YEAR(expense_date) = YEAR(CURDATE())";
        $period_title = "This Month";
        break;
    case 'last_month':
        $date_condition = "AND MONTH(expense_date) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) 
                          AND YEAR(expense_date) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))";
        $period_title = "Last Month";
        break;
    case 'year':
        $date_condition = "AND YEAR(expense_date) = YEAR(CURDATE())";
        $period_title = "This Year";
        break;
    case 'all':
        $date_condition = "";
        $period_title = "All Time";
        break;
    case 'custom':
        if ($custom_start && $custom_end) {
            $date_condition = "AND expense_date BETWEEN '$custom_start' AND '$custom_end'";
            $period_title = "$custom_start to $custom_end";
        } else {
            $filter_type = 'month';
            $date_condition = "AND MONTH(expense_date) = MONTH(CURDATE()) AND YEAR(expense_date) = YEAR(CURDATE())";
            $period_title = "This Month";
        }
        break;
    default:
        $date_condition = "AND MONTH(expense_date) = MONTH(CURDATE()) AND YEAR(expense_date) = YEAR(CURDATE())";
        $period_title = "This Month";
}

// Get summary statistics
$total_query = "SELECT COUNT(*) as count, SUM(amount) as total FROM expenses WHERE user_id = $user_id $date_condition";
$total_result = mysqli_query($conn, $total_query);
$summary = mysqli_fetch_assoc($total_result);

// Get category breakdown - store in array first
$category_data = [];
$category_query = "SELECT category, COUNT(*) as count, SUM(amount) as total 
                   FROM expenses 
                   WHERE user_id = $user_id $date_condition 
                   GROUP BY category 
                   ORDER BY total DESC";
$category_result = mysqli_query($conn, $category_query);
while ($row = mysqli_fetch_assoc($category_result)) {
    $category_data[] = $row;
}

// Get daily breakdown - store in array first
$daily_data = [];
$daily_query = "SELECT DATE(expense_date) as date, COUNT(*) as count, SUM(amount) as total 
                FROM expenses 
                WHERE user_id = $user_id $date_condition 
                GROUP BY DATE(expense_date) 
                ORDER BY date DESC";
$daily_result = mysqli_query($conn, $daily_query);
while ($row = mysqli_fetch_assoc($daily_result)) {
    $daily_data[] = $row;
}

// Get largest expense
$largest_query = "SELECT * FROM expenses WHERE user_id = $user_id $date_condition ORDER BY amount DESC LIMIT 1";
$largest_result = mysqli_query($conn, $largest_query);
$largest = mysqli_fetch_assoc($largest_result);

// Get smallest expense
$smallest_query = "SELECT * FROM expenses WHERE user_id = $user_id $date_condition AND amount > 0 ORDER BY amount ASC LIMIT 1";
$smallest_result = mysqli_query($conn, $smallest_query);
$smallest = mysqli_fetch_assoc($smallest_result);

// Prepare chart data
$cat_labels = [];
$cat_data = [];
$cat_colors = ['#667eea', '#764ba2', '#ff6b6b', '#4ecdc4', '#45b7d1', '#f39c12', '#e74c3c', '#3498db', '#2ecc71', '#e67e22'];

foreach ($category_data as $cat) {
    $cat_labels[] = $cat['category'];
    $cat_data[] = (float)$cat['total'];
}

$daily_labels = [];
$daily_chart_data = [];
foreach ($daily_data as $day) {
    $daily_labels[] = date('M d', strtotime($day['date']));
    $daily_chart_data[] = (float)$day['total'];
}

include 'header.php';
include 'sidebar.php';
?>

<style>
/* Keep your existing CSS exactly as is */
.reports-wrapper {
    padding: 20px 0;
}

.filter-section {
    background: white;
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 30px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
}

.filter-title {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
    color: #333;
}

.filter-title i {
    color: #667eea;
    font-size: 1.3rem;
}

.filter-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

.filter-btn {
    padding: 10px 20px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    background: white;
    color: #666;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s;
}

.filter-btn:hover {
    border-color: #667eea;
    color: #667eea;
}

.filter-btn.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-color: transparent;
}

.custom-date-form {
    display: flex;
    gap: 15px;
    align-items: flex-end;
    flex-wrap: wrap;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 2px solid #f0f0f0;
}

.custom-date-form .form-group {
    flex: 1;
    min-width: 200px;
    margin-bottom: 0;
}

.custom-date-form button {
    padding: 12px 30px;
}

.summary-cards {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.summary-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: transform 0.3s;
}

.summary-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(102,126,234,0.15);
}

.summary-info h4 {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.summary-info p {
    font-size: 2rem;
    font-weight: bold;
    color: #333;
    line-height: 1.2;
}

.summary-info small {
    color: #999;
    font-size: 0.8rem;
}

.summary-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #667eea10 0%, #764ba210 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.summary-icon i {
    font-size: 2rem;
    color: #667eea;
}

.charts-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px;
    margin-bottom: 30px;
}

.chart-box {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
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
}

.chart-container {
    position: relative;
    height: 300px;
    width: 100%;
}

.data-table {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    margin-bottom: 30px;
    overflow-x: auto;
}

.data-table h3 {
    margin-bottom: 20px;
    color: #333;
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.data-table h3 i {
    color: #667eea;
}

.data-table table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th {
    background: #f8f9fa;
    padding: 15px;
    text-align: left;
    font-weight: 600;
    color: #555;
    border-bottom: 2px solid #dee2e6;
}

.data-table td {
    padding: 12px 15px;
    border-bottom: 1px solid #eee;
}

.data-table tr:hover {
    background: #f8f9fa;
}

.category-badge {
    background: #e3f2fd;
    color: #1976d2;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    display: inline-block;
}

.text-right {
    text-align: right;
}

.period-title {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px 25px;
    border-radius: 10px;
    margin-bottom: 25px;
    display: inline-block;
}

/* Empty state styling */
.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #999;
}

.empty-state i {
    font-size: 3rem;
    display: block;
    margin-bottom: 15px;
    color: #ddd;
}

.empty-state p {
    font-size: 1.1rem;
    margin: 0;
}

@media (max-width: 992px) {
    .summary-cards {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .charts-row {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .summary-cards {
        grid-template-columns: 1fr;
    }
    
    .custom-date-form {
        flex-direction: column;
    }
    
    .custom-date-form .form-group {
        width: 100%;
    }
}
</style>

<div class="reports-wrapper">
    <h1>Expense Reports</h1>
    
    <!-- Filter Section -->
    <div class="filter-section">
        <div class="filter-title">
            <i class="fas fa-filter"></i>
            <h2>Filter Reports</h2>
        </div>
        
        <div class="filter-buttons">
            <a href="?filter=week" class="filter-btn <?php echo $filter_type == 'week' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-week"></i> Last 7 Days
            </a>
            <a href="?filter=month" class="filter-btn <?php echo $filter_type == 'month' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-alt"></i> This Month
            </a>
            <a href="?filter=last_month" class="filter-btn <?php echo $filter_type == 'last_month' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-minus"></i> Last Month
            </a>
            <a href="?filter=year" class="filter-btn <?php echo $filter_type == 'year' ? 'active' : ''; ?>">
                <i class="fas fa-calendar"></i> This Year
            </a>
            <a href="?filter=all" class="filter-btn <?php echo $filter_type == 'all' ? 'active' : ''; ?>">
                <i class="fas fa-infinity"></i> All Time
            </a>
        </div>
        
        <!-- Custom Date Range -->
        <form method="GET" class="custom-date-form">
            <input type="hidden" name="filter" value="custom">
            <div class="form-group">
                <label for="start_date">Start Date</label>
                <input type="date" id="start_date" name="start_date" value="<?php echo $custom_start; ?>" required>
            </div>
            <div class="form-group">
                <label for="end_date">End Date</label>
                <input type="date" id="end_date" name="end_date" value="<?php echo $custom_end; ?>" required>
            </div>
            <button type="submit" class="btn">
                <i class="fas fa-search"></i> Apply
            </button>
        </form>
    </div>
    
    <!-- Period Title -->
    <div class="period-title">
        <i class="fas fa-clock"></i> Showing: <strong><?php echo $period_title; ?></strong>
    </div>
    
    <!-- Summary Cards -->
    <div class="summary-cards">
        <div class="summary-card">
            <div class="summary-info">
                <h4>Total Expenses</h4>
                <p><?php echo $currency . number_format($summary['total'] ?? 0, 2); ?></p>
                <small><?php echo $summary['count'] ?? 0; ?> transactions</small>
            </div>
            <div class="summary-icon">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>
        
        <div class="summary-card">
            <div class="summary-info">
                <h4>Average Per Day</h4>
                <p><?php 
                    $days = count($daily_data) ?: 1;
                    echo $currency . number_format(($summary['total'] ?? 0) / $days, 2); 
                ?></p>
                <small>Daily average</small>
            </div>
            <div class="summary-icon">
                <i class="fas fa-calculator"></i>
            </div>
        </div>
        
        <div class="summary-card">
            <div class="summary-info">
                <h4>Largest Expense</h4>
                <p><?php echo $largest ? $currency . number_format($largest['amount'], 2) : $currency . '0.00'; ?></p>
                <small><?php echo $largest ? $largest['title'] : 'No expenses'; ?></small>
            </div>
            <div class="summary-icon">
                <i class="fas fa-arrow-up"></i>
            </div>
        </div>
        
        <div class="summary-card">
            <div class="summary-info">
                <h4>Smallest Expense</h4>
                <p><?php echo $smallest ? $currency . number_format($smallest['amount'], 2) : $currency . '0.00'; ?></p>
                <small><?php echo $smallest ? $smallest['title'] : 'No expenses'; ?></small>
            </div>
            <div class="summary-icon">
                <i class="fas fa-arrow-down"></i>
            </div>
        </div>
    </div>
    
    <!-- Charts Row -->
    <div class="charts-row">
        <!-- Category Pie Chart -->
        <div class="chart-box">
            <h3><i class="fas fa-chart-pie"></i> Expenses by Category</h3>
            <div class="chart-container">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>
        
        <!-- Daily Bar Chart -->
        <div class="chart-box">
            <h3><i class="fas fa-chart-bar"></i> Daily Expenses</h3>
            <div class="chart-container">
                <canvas id="dailyChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Category Breakdown Table -->
    <div class="data-table">
        <h3><i class="fas fa-tags"></i> Category Breakdown</h3>
        <?php if (!empty($category_data)): ?>
        <table>
            <thead>
                <tr>
                    <th style="text-align: left;">Category</th>
                    <th style="text-align: right;">Transactions</th>
                    <th style="text-align: right;">Total Amount</th>
                    <th style="text-align: right;">Average</th>
                    <th style="text-align: right;">Percentage</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total_amount = $summary['total'] ?? 0;
                foreach($category_data as $cat): 
                    $percentage = $total_amount > 0 ? round(($cat['total'] / $total_amount) * 100, 1) : 0;
                    $avg = $cat['count'] > 0 ? $cat['total'] / $cat['count'] : 0;
                ?>
                <tr>
                    <td style="text-align: left;"><span class="category-badge"><?php echo htmlspecialchars($cat['category']); ?></span></td>
                    <td style="text-align: right;"><?php echo $cat['count']; ?></td>
                    <td style="text-align: right;"><strong><?php echo $currency . number_format($cat['total'], 2); ?></strong></td>
                    <td style="text-align: right;"><?php echo $currency . number_format($avg, 2); ?></td>
                    <td style="text-align: right;">
                        <div style="display: flex; align-items: center; gap: 10px; justify-content: flex-end;">
                            <span><?php echo $percentage; ?>%</span>
                            <div style="width: 60px; height: 6px; background: #eee; border-radius: 3px;">
                                <div style="width: <?php echo $percentage; ?>%; height: 100%; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 3px;"></div>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-chart-pie"></i>
            <p>No expenses found for this period</p>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Daily Breakdown Table -->
    <div class="data-table">
        <h3><i class="fas fa-calendar-day"></i> Daily Breakdown</h3>
        <?php if (!empty($daily_data)): ?>
        <table>
            <thead>
                <tr>
                    <th style="text-align: left;">Date</th>
                    <th style="text-align: right;">Transactions</th>
                    <th style="text-align: right;">Total Amount</th>
                    <th style="text-align: right;">Average</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($daily_data as $day): 
                    $avg = $day['count'] > 0 ? $day['total'] / $day['count'] : 0;
                ?>
                <tr>
                    <td style="text-align: left;"><?php echo date('M d, Y', strtotime($day['date'])); ?></td>
                    <td style="text-align: right;"><?php echo $day['count']; ?></td>
                    <td style="text-align: right;"><strong><?php echo $currency . number_format($day['total'], 2); ?></strong></td>
                    <td style="text-align: right;"><?php echo $currency . number_format($avg, 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-calendar"></i>
            <p>No daily data available</p>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Export Options -->
    <div style="text-align: right; margin-top: 20px;">
        <a href="export.php?filter=<?php echo $filter_type; ?>&start_date=<?php echo $custom_start; ?>&end_date=<?php echo $custom_end; ?>" class="btn">
            <i class="fas fa-download"></i> Export as CSV
        </a>
    </div>
</div>

<script>
// Category Pie Chart
<?php if (!empty($cat_data)): ?>
new Chart(document.getElementById('categoryChart'), {
    type: 'pie',
    data: {
        labels: <?php echo json_encode($cat_labels); ?>,
        datasets: [{
            data: <?php echo json_encode($cat_data); ?>,
            backgroundColor: <?php echo json_encode(array_slice($cat_colors, 0, count($cat_labels))); ?>,
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: { boxWidth: 12, padding: 15 }
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
<?php endif; ?>

// Daily Bar Chart
<?php if (!empty($daily_chart_data)): ?>
new Chart(document.getElementById('dailyChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($daily_labels); ?>,
        datasets: [{
            label: 'Daily Expenses',
            data: <?php echo json_encode($daily_chart_data); ?>,
            backgroundColor: '#667eea',
            borderRadius: 5
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '<?php echo $currency; ?>' + value;
                    }
                }
            }
        }
    }
});
<?php endif; ?>
</script>

<?php include 'footer.php'; ?>