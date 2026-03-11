<?php
require_once '../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';

// Get filter parameters
$period = isset($_GET['period']) ? $_GET['period'] : 'month';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-d', strtotime('-30 days'));
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');
$payment_method = isset($_GET['payment_method']) ? $_GET['payment_method'] : 'all';

// Build date condition
$date_condition = "";
if ($period == 'today') {
    $date_condition = "DATE(o.order_date) = CURDATE()";
} elseif ($period == 'yesterday') {
    $date_condition = "DATE(o.order_date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
} elseif ($period == 'week') {
    $date_condition = "o.order_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
} elseif ($period == 'month') {
    $date_condition = "o.order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
} elseif ($period == 'year') {
    $date_condition = "o.order_date >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
} elseif ($period == 'custom') {
    $date_condition = "DATE(o.order_date) BETWEEN '$from_date' AND '$to_date'";
}

// Get sales summary using the report_sales view
$stmt = $pdo->query("
    SELECT * FROM report_sales 
    WHERE sales_date BETWEEN 
        CASE 
            WHEN '$period' = 'today' THEN CURDATE()
            WHEN '$period' = 'yesterday' THEN DATE_SUB(CURDATE(), INTERVAL 1 DAY)
            WHEN '$period' = 'week' THEN DATE_SUB(NOW(), INTERVAL 7 DAY)
            WHEN '$period' = 'month' THEN DATE_SUB(NOW(), INTERVAL 30 DAY)
            WHEN '$period' = 'year' THEN DATE_SUB(NOW(), INTERVAL 1 YEAR)
            ELSE '$from_date'
        END
        AND 
        CASE 
            WHEN '$period' = 'today' THEN CURDATE()
            WHEN '$period' = 'yesterday' THEN DATE_SUB(CURDATE(), INTERVAL 1 DAY)
            WHEN '$period' = 'week' THEN NOW()
            WHEN '$period' = 'month' THEN NOW()
            WHEN '$period' = 'year' THEN NOW()
            ELSE '$to_date'
        END
    ORDER BY sales_date DESC
");
$daily_sales = $stmt->fetchAll();

// Get overall sales statistics
$payment_condition = $payment_method != 'all' ? " AND o.payment_method = '$payment_method'" : "";

$stmt = $pdo->query("
    SELECT 
        COUNT(DISTINCT o.id) as total_orders,
        COUNT(DISTINCT o.customer_id) as unique_customers,
        COALESCE(SUM(o.total_amount), 0) as total_revenue,
        COALESCE(AVG(o.total_amount), 0) as avg_order_value,
        COALESCE(SUM(CASE WHEN o.payment_method = 'cash' THEN o.total_amount END), 0) as cash_sales,
        COALESCE(SUM(CASE WHEN o.payment_method = 'mpesa' THEN o.total_amount END), 0) as mpesa_sales,
        COALESCE(SUM(CASE WHEN o.payment_method = 'card' THEN o.total_amount END), 0) as card_sales,
        COUNT(CASE WHEN o.payment_method = 'cash' THEN 1 END) as cash_orders,
        COUNT(CASE WHEN o.payment_method = 'mpesa' THEN 1 END) as mpesa_orders,
        COUNT(CASE WHEN o.payment_method = 'card' THEN 1 END) as card_orders,
        MIN(DATE(o.order_date)) as first_order_date,
        MAX(DATE(o.order_date)) as last_order_date
    FROM orders o
    WHERE o.order_status = 'completed'
    AND $date_condition
    $payment_condition
");
$stats = $stmt->fetch();

// Get sales by day of week
$stmt = $pdo->query("
    SELECT 
        DAYNAME(o.order_date) as day_name,
        DAYOFWEEK(o.order_date) as day_num,
        COUNT(*) as order_count,
        COALESCE(SUM(o.total_amount), 0) as revenue
    FROM orders o
    WHERE o.order_status = 'completed'
    AND $date_condition
    $payment_condition
    GROUP BY DAYOFWEEK(o.order_date), DAYNAME(o.order_date)
    ORDER BY day_num
");
$weekday_sales = $stmt->fetchAll();

// Get hourly sales distribution
$stmt = $pdo->query("
    SELECT 
        HOUR(o.order_date) as hour,
        COUNT(*) as order_count,
        COALESCE(SUM(o.total_amount), 0) as revenue
    FROM orders o
    WHERE o.order_status = 'completed'
    AND $date_condition
    $payment_condition
    GROUP BY HOUR(o.order_date)
    ORDER BY hour
");
$hourly_sales = $stmt->fetchAll();

// Get top customers by sales
$stmt = $pdo->query("
    SELECT 
        c.id,
        c.name,
        c.email,
        c.phone,
        COUNT(o.id) as order_count,
        COALESCE(SUM(o.total_amount), 0) as total_spent,
        MAX(o.order_date) as last_order
    FROM customers c
    JOIN orders o ON c.id = o.customer_id
    WHERE o.order_status = 'completed'
    AND $date_condition
    $payment_condition
    GROUP BY c.id
    ORDER BY total_spent DESC
    LIMIT 10
");
$top_customers = $stmt->fetchAll();

// Get payment method breakdown
$stmt = $pdo->query("
    SELECT 
        COALESCE(o.payment_method, 'unknown') as payment_method,
        COUNT(*) as order_count,
        COALESCE(SUM(o.total_amount), 0) as total,
        COALESCE(AVG(o.total_amount), 0) as average
    FROM orders o
    WHERE o.order_status = 'completed'
    AND $date_condition
    GROUP BY COALESCE(o.payment_method, 'unknown')
");
$payment_breakdown = $stmt->fetchAll();

// Calculate growth compared to previous period
$prev_date_condition = "";
if ($period == 'today') {
    $prev_date_condition = "DATE(o.order_date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
} elseif ($period == 'yesterday') {
    $prev_date_condition = "DATE(o.order_date) = DATE_SUB(CURDATE(), INTERVAL 2 DAY)";
} elseif ($period == 'week') {
    $prev_date_condition = "o.order_date BETWEEN DATE_SUB(NOW(), INTERVAL 14 DAY) AND DATE_SUB(NOW(), INTERVAL 7 DAY)";
} elseif ($period == 'month') {
    $prev_date_condition = "o.order_date BETWEEN DATE_SUB(NOW(), INTERVAL 60 DAY) AND DATE_SUB(NOW(), INTERVAL 30 DAY)";
} elseif ($period == 'year') {
    $prev_date_condition = "o.order_date BETWEEN DATE_SUB(NOW(), INTERVAL 2 YEAR) AND DATE_SUB(NOW(), INTERVAL 1 YEAR)";
}

if ($prev_date_condition) {
    $stmt = $pdo->query("
        SELECT 
            COALESCE(COUNT(*), 0) as prev_orders,
            COALESCE(SUM(o.total_amount), 0) as prev_revenue
        FROM orders o
        WHERE o.order_status = 'completed'
        AND $prev_date_condition
        $payment_condition
    ");
    $prev_stats = $stmt->fetch();
    
    $order_growth = $prev_stats['prev_orders'] > 0 ? (($stats['total_orders'] - $prev_stats['prev_orders']) / $prev_stats['prev_orders'] * 100) : 0;
    $revenue_growth = $prev_stats['prev_revenue'] > 0 ? (($stats['total_revenue'] - $prev_stats['prev_revenue']) / $prev_stats['prev_revenue'] * 100) : 0;
} else {
    $order_growth = 0;
    $revenue_growth = 0;
}

// Prepare chart data
$chart_labels = [];
$chart_revenue = [];
$chart_orders = [];

foreach ($daily_sales as $day) {
    $chart_labels[] = date('M d', strtotime($day['sales_date']));
    $chart_revenue[] = $day['total_sales'];
    $chart_orders[] = $day['total_orders'];
}

// Payment method colors
$payment_colors = [
    'cash' => ['bg' => 'emerald-100', 'text' => 'emerald-700', 'dark_bg' => 'emerald-900/30', 'dark_text' => 'emerald-300', 'icon' => 'money-bill-wave'],
    'mpesa' => ['bg' => 'secondary-100', 'text' => 'secondary-700', 'dark_bg' => 'secondary-900/30', 'dark_text' => 'secondary-300', 'icon' => 'mobile-alt'],
    'card' => ['bg' => 'blue-100', 'text' => 'blue-700', 'dark_bg' => 'blue-900/30', 'dark_text' => 'blue-300', 'icon' => 'credit-card'],
    'unknown' => ['bg' => 'slate-100', 'text' => 'slate-700', 'dark_bg' => 'slate-800/50', 'dark_text' => 'slate-400', 'icon' => 'question-circle']
];

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<!-- Main Content -->
<div class="flex-1 flex flex-col min-h-screen lg:ml-64 m-0 p-0">
    <!-- Header -->
    <header class="sticky top-0 z-30 bg-white/80 dark:bg-slate-900/80 backdrop-blur-sm border-b border-primary-200/50 dark:border-primary-800/50">
        <div class="flex items-center justify-between px-6 py-3">
            <div class="flex items-center space-x-4">
                <button id="toggleSidebar" class="lg:hidden p-2 hover:bg-primary-100 dark:hover:bg-primary-800 rounded-lg transition-colors">
                    <i class="fas fa-bars text-slate-600 dark:text-slate-400"></i>
                </button>
                <div>
                    <h1 class="text-lg font-semibold text-slate-900 dark:text-white">
                        <i class="fas fa-chart-line text-primary-500 mr-2"></i>
                        Sales Report
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        <i class="far fa-calendar-alt mr-1"></i>
                        Analyze sales performance and trends
                    </p>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <button onclick="exportToCSV()" class="px-4 py-2 border border-primary-300 dark:border-primary-600 text-primary-700 dark:text-primary-300 rounded-xl text-sm font-semibold hover:bg-primary-50 dark:hover:bg-primary-800/30 transition-colors flex items-center">
                    <i class="fas fa-download mr-2"></i>
                    Export CSV
                </button>
                <button onclick="window.print()" class="px-4 py-2 bg-primary-100 hover:bg-primary-200 dark:bg-primary-800/50 dark:hover:bg-primary-700 text-primary-700 dark:text-primary-300 rounded-xl text-sm font-semibold transition-colors flex items-center">
                    <i class="fas fa-print mr-2"></i>
                    Print
                </button>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-1 p-6 bg-slate-50/50 dark:bg-primary-900/50">
        
        <!-- Filter Bar -->
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 bakery-card mb-6">
            <form method="GET" class="flex flex-wrap items-center gap-4">
                <div class="w-40">
                    <select name="period" id="period" class="w-full px-3 py-2.5 border border-primary-200 dark:border-primary-700 rounded-lg bg-white dark:bg-slate-900 text-sm focus:ring-2 focus:ring-primary-500" onchange="toggleCustomDate(this)">
                        <option value="today" <?php echo $period == 'today' ? 'selected' : ''; ?>>Today</option>
                        <option value="yesterday" <?php echo $period == 'yesterday' ? 'selected' : ''; ?>>Yesterday</option>
                        <option value="week" <?php echo $period == 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                        <option value="month" <?php echo $period == 'month' ? 'selected' : ''; ?>>Last 30 Days</option>
                        <option value="year" <?php echo $period == 'year' ? 'selected' : ''; ?>>Last 12 Months</option>
                        <option value="custom" <?php echo $period == 'custom' ? 'selected' : ''; ?>>Custom Range</option>
                    </select>
                </div>
                
                <div id="custom_date_range" class="flex items-center gap-2 <?php echo $period != 'custom' ? 'hidden' : ''; ?>">
                    <input type="date" name="from_date" value="<?php echo $from_date; ?>" 
                           class="px-3 py-2.5 border border-primary-200 dark:border-primary-700 rounded-lg bg-white dark:bg-slate-900 text-sm">
                    <span class="text-slate-500">to</span>
                    <input type="date" name="to_date" value="<?php echo $to_date; ?>" 
                           class="px-3 py-2.5 border border-primary-200 dark:border-primary-700 rounded-lg bg-white dark:bg-slate-900 text-sm">
                </div>
                
                <div class="w-40">
                    <select name="payment_method" class="w-full px-3 py-2.5 border border-primary-200 dark:border-primary-700 rounded-lg bg-white dark:bg-slate-900 text-sm focus:ring-2 focus:ring-primary-500">
                        <option value="all" <?php echo $payment_method == 'all' ? 'selected' : ''; ?>>All Payments</option>
                        <option value="cash" <?php echo $payment_method == 'cash' ? 'selected' : ''; ?>>Cash Only</option>
                        <option value="mpesa" <?php echo $payment_method == 'mpesa' ? 'selected' : ''; ?>>M-Pesa Only</option>
                        <option value="card" <?php echo $payment_method == 'card' ? 'selected' : ''; ?>>Card Only</option>
                    </select>
                </div>
                
                <button type="submit" class="px-5 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium transition-colors">
                    <i class="fas fa-filter mr-2"></i>Apply Filters
                </button>
                
                <a href="sales.php" class="px-5 py-2.5 border border-primary-300 dark:border-primary-600 text-primary-700 dark:text-primary-300 hover:bg-primary-50 dark:hover:bg-primary-800/30 rounded-lg text-sm font-medium transition-colors">
                    <i class="fas fa-redo mr-2"></i>Reset
                </a>
            </form>
        </div>

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-slate-800 rounded-xl p-5 bakery-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-500">Total Revenue</p>
                        <h4 class="text-2xl font-bold text-primary-600">KES <?php echo number_format($stats['total_revenue'], 2); ?></h4>
                        <p class="text-xs flex items-center mt-1 <?php echo $revenue_growth >= 0 ? 'text-emerald-600' : 'text-rose-600'; ?>">
                            <i class="fas fa-<?php echo $revenue_growth >= 0 ? 'arrow-up' : 'arrow-down'; ?> mr-1"></i>
                            <?php echo abs(round($revenue_growth, 1)); ?>% vs previous period
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-primary-100 dark:bg-primary-800/50 rounded-xl flex items-center justify-center">
                        <i class="fas fa-coins text-primary-600 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-slate-800 rounded-xl p-5 bakery-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-500">Total Orders</p>
                        <h4 class="text-2xl font-bold text-secondary-600"><?php echo number_format($stats['total_orders']); ?></h4>
                        <p class="text-xs flex items-center mt-1 <?php echo $order_growth >= 0 ? 'text-emerald-600' : 'text-rose-600'; ?>">
                            <i class="fas fa-<?php echo $order_growth >= 0 ? 'arrow-up' : 'arrow-down'; ?> mr-1"></i>
                            <?php echo abs(round($order_growth, 1)); ?>% vs previous period
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-secondary-100 dark:bg-secondary-800/50 rounded-xl flex items-center justify-center">
                        <i class="fas fa-shopping-bag text-secondary-600 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-slate-800 rounded-xl p-5 bakery-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-500">Average Order Value</p>
                        <h4 class="text-2xl font-bold text-accent-600">KES <?php echo number_format($stats['avg_order_value'], 2); ?></h4>
                        <p class="text-xs text-slate-500 mt-1">From <?php echo $stats['unique_customers']; ?> customers</p>
                    </div>
                    <div class="w-12 h-12 bg-accent-100 dark:bg-accent-800/50 rounded-xl flex items-center justify-center">
                        <i class="fas fa-chart-line text-accent-600 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-slate-800 rounded-xl p-5 bakery-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-500">Period</p>
                        <h4 class="text-lg font-bold text-primary-600"><?php echo date('M d', strtotime($stats['first_order_date'])); ?> - <?php echo date('M d, Y', strtotime($stats['last_order_date'])); ?></h4>
                        <p class="text-xs text-slate-500 mt-1"><?php echo count($daily_sales); ?> days</p>
                    </div>
                    <div class="w-12 h-12 bg-primary-100 dark:bg-primary-800/50 rounded-xl flex items-center justify-center">
                        <i class="fas fa-calendar-alt text-primary-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Daily Sales Chart -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 bakery-card">
                <h3 class="text-base font-semibold text-slate-900 dark:text-white mb-4">Daily Sales Trend</h3>
                <div class="h-64">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>

            <!-- Payment Method Distribution -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 bakery-card">
                <h3 class="text-base font-semibold text-slate-900 dark:text-white mb-4">Sales by Payment Method</h3>
                <div class="h-64">
                    <canvas id="paymentChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Second Row Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Sales by Day of Week -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 bakery-card">
                <h3 class="text-base font-semibold text-slate-900 dark:text-white mb-4">Sales by Day of Week</h3>
                <div class="h-64">
                    <canvas id="weekdayChart"></canvas>
                </div>
            </div>

            <!-- Hourly Sales Distribution -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 bakery-card">
                <h3 class="text-base font-semibold text-slate-900 dark:text-white mb-4">Hourly Sales Distribution</h3>
                <div class="h-64">
                    <canvas id="hourlyChart"></canvas>
                </div>
            </div>
        </div>

      <!-- Payment Method Breakdown -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <?php 
    $total_payment = array_sum(array_column($payment_breakdown, 'total'));
    foreach ($payment_breakdown as $payment): 
        $payment_method = $payment['payment_method'] ?? 'unknown';
        $color = $payment_colors[$payment_method] ?? [
            'bg' => 'slate-100', 
            'text' => 'slate-700', 
            'dark_bg' => 'slate-800/50', 
            'dark_text' => 'slate-400',
            'icon' => 'question-circle'
        ];
        $percentage = $total_payment > 0 ? round(($payment['total'] / $total_payment) * 100, 1) : 0;
        $display_name = $payment_method != 'unknown' ? ucfirst($payment_method) : 'Other';
    ?>
    <div class="bg-white dark:bg-slate-800 rounded-xl p-4 bakery-card">
        <div class="flex items-center justify-between mb-2">
            <div class="flex items-center">
                <div class="w-8 h-8 bg-<?php echo $color['bg']; ?> dark:bg-<?php echo $color['dark_bg']; ?> rounded-lg flex items-center justify-center mr-3">
                    <i class="fas fa-<?php echo $color['icon']; ?> text-<?php echo $color['text']; ?>"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-900 dark:text-white"><?php echo $display_name; ?></p>
                    <p class="text-xs text-slate-500"><?php echo $payment['order_count']; ?> orders</p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-lg font-bold text-<?php echo $color['text']; ?>">KES <?php echo number_format($payment['total'], 2); ?></p>
                <p class="text-xs text-slate-500"><?php echo $percentage; ?>% of total</p>
            </div>
        </div>
        <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-1.5">
            <div class="bg-<?php echo $color['text']; ?> h-1.5 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

        <!-- Daily Sales Table -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 bakery-card mb-6">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white mb-4">Daily Sales Breakdown</h3>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-xs text-slate-500 dark:text-slate-400 border-b border-primary-200 dark:border-primary-700">
                            <th class="text-left py-3 font-medium">Date</th>
                            <th class="text-right font-medium">Orders</th>
                            <th class="text-right font-medium">Revenue</th>
                            <th class="text-right font-medium">Avg Order Value</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-primary-100 dark:divide-primary-800">
                        <?php foreach ($daily_sales as $day): ?>
                        <tr class="hover:bg-primary-50 dark:hover:bg-primary-800/30 transition-colors">
                            <td class="py-2 text-sm text-slate-600 dark:text-slate-400">
                                <?php echo date('F j, Y', strtotime($day['sales_date'])); ?>
                            </td>
                            <td class="py-2 text-right text-sm text-slate-600 dark:text-slate-400">
                                <?php echo $day['total_orders']; ?>
                            </td>
                            <td class="py-2 text-right text-sm font-medium text-primary-600">
                                KES <?php echo number_format($day['total_sales'], 2); ?>
                            </td>
                            <td class="py-2 text-right text-sm text-slate-600 dark:text-slate-400">
                                KES <?php echo $day['total_orders'] > 0 ? number_format($day['total_sales'] / $day['total_orders'], 2) : 0; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="border-t border-primary-200 dark:border-primary-700">
                        <tr class="font-semibold">
                            <td class="py-3 text-sm text-slate-700 dark:text-slate-300">Total</td>
                            <td class="py-3 text-right text-sm text-slate-700 dark:text-slate-300"><?php echo array_sum(array_column($daily_sales, 'total_orders')); ?></td>
                            <td class="py-3 text-right text-sm text-primary-600">KES <?php echo number_format(array_sum(array_column($daily_sales, 'total_sales')), 2); ?></td>
                            <td class="py-3 text-right text-sm text-slate-700 dark:text-slate-300">-</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Top Customers Table -->
        <?php if (!empty($top_customers)): ?>
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 bakery-card">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white mb-4">Top Customers</h3>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-xs text-slate-500 dark:text-slate-400 border-b border-primary-200 dark:border-primary-700">
                            <th class="text-left py-3 font-medium">Customer</th>
                            <th class="text-left font-medium">Contact</th>
                            <th class="text-right font-medium">Orders</th>
                            <th class="text-right font-medium">Total Spent</th>
                            <th class="text-right font-medium">Last Order</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-primary-100 dark:divide-primary-800">
                        <?php foreach ($top_customers as $customer): ?>
                        <tr class="hover:bg-primary-50 dark:hover:bg-primary-800/30 transition-colors">
                            <td class="py-2 text-sm font-medium text-slate-900 dark:text-white">
                                <?php echo htmlspecialchars($customer['name']); ?>
                            </td>
                            <td class="py-2 text-sm text-slate-600 dark:text-slate-400">
                                <?php echo htmlspecialchars($customer['email'] ?? $customer['phone']); ?>
                            </td>
                            <td class="py-2 text-right text-sm text-slate-600 dark:text-slate-400">
                                <?php echo $customer['order_count']; ?>
                            </td>
                            <td class="py-2 text-right text-sm font-medium text-primary-600">
                                KES <?php echo number_format($customer['total_spent'], 2); ?>
                            </td>
                            <td class="py-2 text-right text-sm text-slate-600 dark:text-slate-400">
                                <?php echo date('M j, Y', strtotime($customer['last_order'])); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </main>
</div>

<script>
function toggleCustomDate(select) {
    const customDiv = document.getElementById('custom_date_range');
    if (select.value === 'custom') {
        customDiv.classList.remove('hidden');
    } else {
        customDiv.classList.add('hidden');
    }
}

function exportToCSV() {
    // Create CSV content
    let csv = "Date,Orders,Revenue (KES)\n";
    <?php foreach ($daily_sales as $day): ?>
    csv += "<?php echo date('Y-m-d', strtotime($day['sales_date'])); ?>,<?php echo $day['total_orders']; ?>,<?php echo $day['total_sales']; ?>\n";
    <?php endforeach; ?>
    
    // Download CSV
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'sales_report_<?php echo date('Y-m-d'); ?>.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}

// Initialize Charts
document.addEventListener('DOMContentLoaded', function() {
    // Sales Trend Chart
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    new Chart(salesCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [{
                label: 'Revenue (KES)',
                data: <?php echo json_encode($chart_revenue); ?>,
                borderColor: '#b45309',
                backgroundColor: 'rgba(180, 83, 9, 0.1)',
                tension: 0.4,
                fill: true,
                yAxisID: 'y'
            }, {
                label: 'Orders',
                data: <?php echo json_encode($chart_orders); ?>,
                borderColor: '#e6b17e',
                backgroundColor: 'rgba(230, 177, 126, 0.1)',
                tension: 0.4,
                fill: true,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    ticks: {
                        callback: function(value) {
                            return 'KES ' + value;
                        }
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    grid: {
                        drawOnChartArea: false,
                    },
                }
            }
        }
    });

    // Payment Method Chart
    const paymentCtx = document.getElementById('paymentChart').getContext('2d');
    new Chart(paymentCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_column($payment_breakdown, 'payment_method')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($payment_breakdown, 'total')); ?>,
                backgroundColor: ['#b45309', '#e6b17e', '#f5b56c'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        boxWidth: 8
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            let value = context.raw || 0;
                            let total = context.dataset.data.reduce((a, b) => a + b, 0);
                            let percentage = ((value / total) * 100).toFixed(1);
                            return `${label}: KES ${value.toFixed(2)} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });

    // Weekday Chart
    const weekdayCtx = document.getElementById('weekdayChart').getContext('2d');
    new Chart(weekdayCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($weekday_sales, 'day_name')); ?>,
            datasets: [{
                label: 'Revenue',
                data: <?php echo json_encode(array_column($weekday_sales, 'revenue')); ?>,
                backgroundColor: '#b45309',
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'KES ' + value;
                        }
                    }
                }
            }
        }
    });

    // Hourly Chart
    const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
    const hours = <?php echo json_encode(array_column($hourly_sales, 'hour')); ?>.map(h => h + ':00');
    new Chart(hourlyCtx, {
        type: 'line',
        data: {
            labels: hours,
            datasets: [{
                label: 'Orders',
                data: <?php echo json_encode(array_column($hourly_sales, 'order_count')); ?>,
                borderColor: '#b45309',
                backgroundColor: 'rgba(180, 83, 9, 0.1)',
                tension: 0.4,
                fill: true,
                yAxisID: 'y'
            }, {
                label: 'Revenue',
                data: <?php echo json_encode(array_column($hourly_sales, 'revenue')); ?>,
                borderColor: '#e6b17e',
                backgroundColor: 'rgba(230, 177, 126, 0.1)',
                tension: 0.4,
                fill: true,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Orders'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Revenue (KES)'
                    },
                    grid: {
                        drawOnChartArea: false,
                    },
                    ticks: {
                        callback: function(value) {
                            return 'KES ' + value;
                        }
                    }
                }
            }
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>