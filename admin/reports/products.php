<?php
require_once '../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';

// Get filter parameters
$period = isset($_GET['period']) ? $_GET['period'] : 'month';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-d', strtotime('-30 days'));
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');
$category_id = isset($_GET['category_id']) ? filter_var($_GET['category_id'], FILTER_SANITIZE_NUMBER_INT) : null;
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'revenue';

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

$category_condition = $category_id ? " AND p.category_id = $category_id" : "";

// Get product sales using the report_product_sales view with additional details
$stmt = $pdo->query("
    SELECT 
        p.id,
        p.product_name,
        p.price as unit_price,
        p.stock as current_stock,
        p.image,
        c.category_name,
        COALESCE(SUM(oi.quantity), 0) as total_quantity_sold,
        COALESCE(COUNT(DISTINCT o.id), 0) as order_count,
        COALESCE(SUM(oi.quantity * oi.price), 0) as total_revenue,
        COALESCE(AVG(oi.price), p.price) as avg_selling_price,
        MAX(o.order_date) as last_sold_date
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN order_items oi ON p.id = oi.product_id
    LEFT JOIN orders o ON oi.order_id = o.id AND o.order_status = 'completed' AND $date_condition
    WHERE 1=1 $category_condition
    GROUP BY p.id
    ORDER BY 
        CASE WHEN '$sort_by' = 'quantity' THEN COALESCE(SUM(oi.quantity), 0) END DESC,
        CASE WHEN '$sort_by' = 'revenue' THEN COALESCE(SUM(oi.quantity * oi.price), 0) END DESC,
        CASE WHEN '$sort_by' = 'orders' THEN COUNT(DISTINCT o.id) END DESC,
        CASE WHEN '$sort_by' = 'name' THEN p.product_name END ASC
");
$products = $stmt->fetchAll();

// Get categories for filter
$stmt = $pdo->query("SELECT * FROM categories ORDER BY category_name");
$categories = $stmt->fetchAll();

// Calculate totals
$total_revenue = array_sum(array_column($products, 'total_revenue'));
$total_quantity = array_sum(array_column($products, 'total_quantity_sold'));
$total_orders = array_sum(array_column($products, 'order_count'));
$avg_order_value = $total_orders > 0 ? $total_revenue / $total_orders : 0;

// Get category performance
$stmt = $pdo->query("
    SELECT 
        c.id,
        c.category_name,
        COUNT(DISTINCT p.id) as product_count,
        COALESCE(SUM(oi.quantity), 0) as quantity_sold,
        COALESCE(SUM(oi.quantity * oi.price), 0) as revenue
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id
    LEFT JOIN order_items oi ON p.id = oi.product_id
    LEFT JOIN orders o ON oi.order_id = o.id AND o.order_status = 'completed' AND $date_condition
    GROUP BY c.id
    HAVING quantity_sold > 0 OR revenue > 0
    ORDER BY revenue DESC
");
$category_performance = $stmt->fetchAll();

// Get top products by revenue (for chart)
$top_products = array_slice($products, 0, 10);

// Prepare chart data
$chart_labels = [];
$chart_revenue = [];
$chart_quantity = [];

foreach ($top_products as $product) {
    $chart_labels[] = strlen($product['product_name']) > 20 ? substr($product['product_name'], 0, 18) . '...' : $product['product_name'];
    $chart_revenue[] = $product['total_revenue'];
    $chart_quantity[] = $product['total_quantity_sold'];
}

// Status colors for stock levels
$stock_colors = [
    'high' => ['bg' => 'emerald-100', 'text' => 'emerald-700', 'dark_bg' => 'emerald-900/30', 'dark_text' => 'emerald-300'],
    'medium' => ['bg' => 'amber-100', 'text' => 'amber-700', 'dark_bg' => 'amber-900/30', 'dark_text' => 'amber-300'],
    'low' => ['bg' => 'rose-100', 'text' => 'rose-700', 'dark_bg' => 'rose-900/30', 'dark_text' => 'rose-300']
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
                        <i class="fas fa-chart-bar text-primary-500 mr-2"></i>
                        Product Performance Report
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        <i class="fas fa-cube mr-1"></i>
                        Analyze product sales, revenue, and inventory
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
                
                <div class="w-48">
                    <select name="category_id" class="w-full px-3 py-2.5 border border-primary-200 dark:border-primary-700 rounded-lg bg-white dark:bg-slate-900 text-sm focus:ring-2 focus:ring-primary-500">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $category_id == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['category_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="w-40">
                    <select name="sort_by" class="w-full px-3 py-2.5 border border-primary-200 dark:border-primary-700 rounded-lg bg-white dark:bg-slate-900 text-sm focus:ring-2 focus:ring-primary-500">
                        <option value="revenue" <?php echo $sort_by == 'revenue' ? 'selected' : ''; ?>>By Revenue</option>
                        <option value="quantity" <?php echo $sort_by == 'quantity' ? 'selected' : ''; ?>>By Quantity</option>
                        <option value="orders" <?php echo $sort_by == 'orders' ? 'selected' : ''; ?>>By Orders</option>
                        <option value="name" <?php echo $sort_by == 'name' ? 'selected' : ''; ?>>By Name</option>
                    </select>
                </div>
                
                <button type="submit" class="px-5 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium transition-colors">
                    <i class="fas fa-filter mr-2"></i>Apply Filters
                </button>
                
                <a href="products.php" class="px-5 py-2.5 border border-primary-300 dark:border-primary-600 text-primary-700 dark:text-primary-300 hover:bg-primary-50 dark:hover:bg-primary-800/30 rounded-lg text-sm font-medium transition-colors">
                    <i class="fas fa-redo mr-2"></i>Reset
                </a>
            </form>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-slate-800 rounded-xl p-5 bakery-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-500">Total Revenue</p>
                        <h4 class="text-2xl font-bold text-primary-600">KES <?php echo number_format($total_revenue, 2); ?></h4>
                    </div>
                    <div class="w-12 h-12 bg-primary-100 dark:bg-primary-800/50 rounded-xl flex items-center justify-center">
                        <i class="fas fa-coins text-primary-600 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-slate-800 rounded-xl p-5 bakery-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-500">Units Sold</p>
                        <h4 class="text-2xl font-bold text-secondary-600"><?php echo number_format($total_quantity); ?></h4>
                    </div>
                    <div class="w-12 h-12 bg-secondary-100 dark:bg-secondary-800/50 rounded-xl flex items-center justify-center">
                        <i class="fas fa-cube text-secondary-600 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-slate-800 rounded-xl p-5 bakery-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-500">Total Orders</p>
                        <h4 class="text-2xl font-bold text-accent-600"><?php echo number_format($total_orders); ?></h4>
                    </div>
                    <div class="w-12 h-12 bg-accent-100 dark:bg-accent-800/50 rounded-xl flex items-center justify-center">
                        <i class="fas fa-shopping-bag text-accent-600 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-slate-800 rounded-xl p-5 bakery-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-500">Avg Order Value</p>
                        <h4 class="text-2xl font-bold text-emerald-600">KES <?php echo number_format($avg_order_value, 2); ?></h4>
                    </div>
                    <div class="w-12 h-12 bg-emerald-100 dark:bg-emerald-800/50 rounded-xl flex items-center justify-center">
                        <i class="fas fa-chart-line text-emerald-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Category Performance -->
        <?php if (!empty($category_performance)): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <?php foreach ($category_performance as $cat): ?>
            <div class="bg-white dark:bg-slate-800 rounded-xl p-4 bakery-card">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-sm font-semibold text-slate-900 dark:text-white"><?php echo htmlspecialchars($cat['category_name']); ?></h4>
                    <span class="text-xs text-slate-500"><?php echo $cat['product_count']; ?> products</span>
                </div>
                <div class="flex justify-between items-end">
                    <div>
                        <p class="text-xs text-slate-500">Sold</p>
                        <p class="text-lg font-bold text-primary-600"><?php echo number_format($cat['quantity_sold']); ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-slate-500">Revenue</p>
                        <p class="text-base font-bold text-secondary-600">KES <?php echo number_format($cat['revenue'], 2); ?></p>
                    </div>
                </div>
                <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-1.5 mt-3">
                    <div class="bg-primary-600 h-1.5 rounded-full" style="width: <?php echo $total_revenue > 0 ? ($cat['revenue'] / $total_revenue * 100) : 0; ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Top Products Chart -->
        <?php if (!empty($top_products)): ?>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Revenue Chart -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 bakery-card">
                <h3 class="text-base font-semibold text-slate-900 dark:text-white mb-4">Top 10 Products by Revenue</h3>
                <div class="h-64">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <!-- Quantity Chart -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 bakery-card">
                <h3 class="text-base font-semibold text-slate-900 dark:text-white mb-4">Top 10 Products by Quantity Sold</h3>
                <div class="h-64">
                    <canvas id="quantityChart"></canvas>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Products Table -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 bakery-card">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white mb-4">Product Performance Details</h3>
            
            <?php if (empty($products)): ?>
            <div class="text-center py-12">
                <div class="w-24 h-24 bg-primary-100 dark:bg-primary-800/30 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-cube text-primary-500 text-3xl"></i>
                </div>
                <h4 class="text-base font-medium text-slate-900 dark:text-white mb-2">No Product Data Found</h4>
                <p class="text-sm text-slate-500 dark:text-slate-400">No sales data available for the selected period</p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-xs text-slate-500 dark:text-slate-400 border-b border-primary-200 dark:border-primary-700">
                            <th class="text-left py-3 font-medium">Product</th>
                            <th class="text-left font-medium">Category</th>
                            <th class="text-right font-medium">Unit Price</th>
                            <th class="text-right font-medium">Quantity Sold</th>
                            <th class="text-right font-medium">Orders</th>
                            <th class="text-right font-medium">Revenue</th>
                            <th class="text-right font-medium">Current Stock</th>
                            <th class="text-right font-medium">Last Sold</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-primary-100 dark:divide-primary-800">
                        <?php foreach ($products as $product): 
                            if ($product['total_quantity_sold'] == 0 && $product['total_revenue'] == 0) continue;
                            
                            // Determine stock status
                            if ($product['current_stock'] > 20) {
                                $stock_status = 'high';
                            } elseif ($product['current_stock'] > 5) {
                                $stock_status = 'medium';
                            } else {
                                $stock_status = 'low';
                            }
                            $stock_color = $stock_colors[$stock_status];
                        ?>
                        <tr class="hover:bg-primary-50 dark:hover:bg-primary-800/30 transition-colors">
                            <td class="py-3">
                                <div class="flex items-center">
                                    <?php if ($product['image']): ?>
                                    <img src="/beribakes/uploads/products/<?php echo $product['image']; ?>" 
                                         alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                                         class="w-8 h-8 rounded-lg object-cover mr-3"
                                         onerror="this.src='https://via.placeholder.com/32?text=No+Image'">
                                    <?php else: ?>
                                    <div class="w-8 h-8 bg-gradient-to-br from-primary-600 to-secondary-500 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-bread-slice text-white text-xs"></i>
                                    </div>
                                    <?php endif; ?>
                                    <span class="text-sm font-medium text-slate-900 dark:text-white">
                                        <?php echo htmlspecialchars($product['product_name']); ?>
                                    </span>
                                </div>
                            </td>
                            <td class="py-3 text-sm text-slate-600 dark:text-slate-400">
                                <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                            </td>
                            <td class="py-3 text-right text-sm text-slate-600 dark:text-slate-400">
                                KES <?php echo number_format($product['unit_price'], 2); ?>
                            </td>
                            <td class="py-3 text-right text-sm font-medium text-primary-600">
                                <?php echo number_format($product['total_quantity_sold']); ?>
                            </td>
                            <td class="py-3 text-right text-sm text-slate-600 dark:text-slate-400">
                                <?php echo $product['order_count']; ?>
                            </td>
                            <td class="py-3 text-right text-sm font-bold text-secondary-600">
                                KES <?php echo number_format($product['total_revenue'], 2); ?>
                            </td>
                            <td class="py-3 text-right">
                                <span class="px-2 py-1 bg-<?php echo $stock_color['bg']; ?> dark:bg-<?php echo $stock_color['dark_bg']; ?> text-<?php echo $stock_color['text']; ?> dark:text-<?php echo $stock_color['dark_text']; ?> rounded-full text-xs">
                                    <?php echo $product['current_stock']; ?> units
                                </span>
                            </td>
                            <td class="py-3 text-right text-sm text-slate-600 dark:text-slate-400">
                                <?php echo $product['last_sold_date'] ? date('M j, Y', strtotime($product['last_sold_date'])) : 'Never'; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="border-t border-primary-200 dark:border-primary-700">
                        <tr class="font-semibold">
                            <td colspan="3" class="py-3 text-sm text-slate-700 dark:text-slate-300">Totals</td>
                            <td class="py-3 text-right text-sm text-primary-600"><?php echo number_format($total_quantity); ?></td>
                            <td class="py-3 text-right text-sm text-slate-700 dark:text-slate-300"><?php echo number_format($total_orders); ?></td>
                            <td class="py-3 text-right text-sm text-secondary-600">KES <?php echo number_format($total_revenue, 2); ?></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Low Stock Alert -->
        <?php 
        $low_stock_products = array_filter($products, function($p) {
            return $p['current_stock'] <= 5 && $p['current_stock'] > 0;
        });
        if (!empty($low_stock_products)): 
        ?>
        <div class="mt-6 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-2xl p-5">
            <div class="flex items-center mb-4">
                <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center mr-3">
                    <i class="fas fa-exclamation-triangle text-amber-600"></i>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-amber-800 dark:text-amber-300">Low Stock Alert</h3>
                    <p class="text-sm text-amber-600 dark:text-amber-400">The following products are running low on inventory</p>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <?php foreach (array_slice($low_stock_products, 0, 4) as $product): ?>
                <div class="bg-white dark:bg-slate-800 rounded-xl p-3 flex items-center justify-between">
                    <span class="text-sm font-medium text-slate-900 dark:text-white"><?php echo htmlspecialchars($product['product_name']); ?></span>
                    <span class="px-2 py-1 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded-full text-xs">
                        <?php echo $product['current_stock']; ?> left
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="mt-3 text-right">
                <a href="../products/index.php?stock=low_stock" class="text-sm text-amber-600 hover:text-amber-700 font-medium">
                    View All <i class="fas fa-arrow-right ml-1"></i>
                </a>
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
    let csv = "Product,Category,Unit Price (KES),Quantity Sold,Orders,Revenue (KES),Current Stock,Last Sold\n";
    <?php foreach ($products as $product): ?>
    csv += "<?php echo str_replace(',', ' ', $product['product_name']); ?>,<?php echo $product['category_name'] ?? 'Uncategorized'; ?>,<?php echo $product['unit_price']; ?>,<?php echo $product['total_quantity_sold']; ?>,<?php echo $product['order_count']; ?>,<?php echo $product['total_revenue']; ?>,<?php echo $product['current_stock']; ?>,<?php echo $product['last_sold_date'] ? date('Y-m-d', strtotime($product['last_sold_date'])) : ''; ?>\n";
    <?php endforeach; ?>
    
    // Download CSV
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'product_performance_<?php echo date('Y-m-d'); ?>.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}

// Initialize Charts
document.addEventListener('DOMContentLoaded', function() {
    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    new Chart(revenueCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [{
                label: 'Revenue (KES)',
                data: <?php echo json_encode($chart_revenue); ?>,
                backgroundColor: '#b45309',
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'KES ' + context.raw.toFixed(2);
                        }
                    }
                }
            },
            scales: {
                x: {
                    ticks: {
                        callback: function(value) {
                            return 'KES ' + value;
                        }
                    }
                }
            }
        }
    });

    // Quantity Chart
    const quantityCtx = document.getElementById('quantityChart').getContext('2d');
    new Chart(quantityCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [{
                label: 'Quantity Sold',
                data: <?php echo json_encode($chart_quantity); ?>,
                backgroundColor: '#e6b17e',
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>