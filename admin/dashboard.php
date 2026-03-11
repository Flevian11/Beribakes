<?php
require_once 'includes/auth.php';
require_once __DIR__ . '/../config/db.php';

// Get real statistics from database with proper null handling
try {
    // Today's stats
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT 
        COUNT(DISTINCT o.id) as orders_today,
        COALESCE(SUM(o.total_amount), 0) as revenue_today,
        COUNT(DISTINCT CASE WHEN o.order_status = 'pending' THEN o.id END) as pending_orders,
        COUNT(DISTINCT CASE WHEN o.order_status = 'processing' THEN o.id END) as processing_orders,
        COUNT(DISTINCT CASE WHEN o.order_status = 'completed' THEN o.id END) as completed_orders
        FROM orders o 
        WHERE DATE(o.order_date) = ?");
    $stmt->execute([$today]);
    $today_stats = $stmt->fetch();
    
    // Ensure all values exist
    $today_stats = array_merge([
        'orders_today' => 0,
        'revenue_today' => 0,
        'pending_orders' => 0,
        'processing_orders' => 0,
        'completed_orders' => 0
    ], $today_stats ?: []);

    // Total products with category breakdown
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_products,
            SUM(CASE WHEN stock > 0 THEN 1 ELSE 0 END) as active_products,
            SUM(CASE WHEN stock <= 5 AND stock > 0 THEN 1 ELSE 0 END) as low_stock,
            SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END) as out_of_stock,
            COUNT(DISTINCT category_id) as categories
        FROM products");
    $product_stats = $stmt->fetch();
    
    $product_stats = array_merge([
        'total_products' => 0,
        'active_products' => 0,
        'low_stock' => 0,
        'out_of_stock' => 0,
        'categories' => 0
    ], $product_stats ?: []);

    // Category distribution
    $stmt = $pdo->query("
        SELECT c.category_name, COUNT(p.id) as product_count
        FROM categories c
        LEFT JOIN products p ON c.id = p.category_id
        GROUP BY c.id, c.category_name
        ORDER BY product_count DESC
        LIMIT 5");
    $categories = $stmt->fetchAll();

    // Recent transactions with icons
    $stmt = $pdo->query("
        SELECT lt.*, la.account_name, la.account_type,
               CASE 
                   WHEN lt.reference_type = 'order' THEN 'shopping-bag'
                   WHEN lt.reference_type = 'purchase' THEN 'truck'
                   WHEN lt.reference_type = 'expense' THEN 'receipt'
                   ELSE 'exchange-alt'
               END as icon
        FROM ledger_transactions lt
        JOIN ledger_accounts la ON lt.account_id = la.id
        ORDER BY lt.created_at DESC 
        LIMIT 8");
    $recent_transactions = $stmt->fetchAll();

    // Today's production schedule with status colors
    $stmt = $pdo->prepare("
        SELECT oi.*, p.product_name, o.order_status, o.id as order_id,
               TIME(o.order_date) as order_time,
               CASE 
                   WHEN o.order_status = 'pending' THEN 'warning'
                   WHEN o.order_status = 'processing' THEN 'info'
                   WHEN o.order_status = 'completed' THEN 'success'
                   ELSE 'secondary'
               END as status_color
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN orders o ON oi.order_id = o.id
        WHERE DATE(o.order_date) = ? 
        ORDER BY o.order_date DESC
        LIMIT 6");
    $stmt->execute([$today]);
    $production_items = $stmt->fetchAll();

    // Top selling products with trends
    $stmt = $pdo->query("
        SELECT p.product_name, 
               SUM(oi.quantity) as total_sold,
               SUM(oi.quantity * oi.price) as revenue,
               COUNT(DISTINCT o.id) as order_count,
               p.stock,
               p.image,
               CASE 
                   WHEN SUM(oi.quantity) > 50 THEN 'success'
                   WHEN SUM(oi.quantity) > 20 THEN 'warning'
                   ELSE 'info'
               END as trend_color
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.order_status = 'completed'
        GROUP BY oi.product_id
        ORDER BY total_sold DESC
        LIMIT 5");
    $top_products = $stmt->fetchAll();

    // Revenue trend with comparison
    $stmt = $pdo->query("
        SELECT 
            DATE(order_date) as date,
            COUNT(*) as orders,
            SUM(total_amount) as revenue,
            SUM(total_amount) - LAG(SUM(total_amount)) OVER (ORDER BY DATE(order_date)) as daily_change
        FROM orders
        WHERE order_status = 'completed'
        AND order_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(order_date)
        ORDER BY date");
    $revenue_trend = $stmt->fetchAll();

    // Format for chart with colors
    $revenue_labels = [];
    $revenue_data = [];
    $revenue_changes = [];
    foreach ($revenue_trend as $day) {
        $revenue_labels[] = date('D', strtotime($day['date']));
        $revenue_data[] = $day['revenue'] ?? 0;
        $revenue_changes[] = $day['daily_change'] ?? 0;
    }

    // Fill missing days
    if (count($revenue_labels) < 7) {
        $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $revenue_labels = $days;
        $revenue_data = array_pad($revenue_data, 7, 0);
    }

    // Customer stats
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_customers,
            COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_customers
        FROM customers");
    $customer_stats = $stmt->fetch();
    
    $customer_stats = array_merge([
        'total_customers' => 0,
        'new_customers' => 0
    ], $customer_stats ?: []);

    // Order status summary
    $stmt = $pdo->query("
        SELECT 
            order_status,
            COUNT(*) as count,
            SUM(total_amount) as total
        FROM orders
        WHERE DATE(order_date) = CURDATE()
        GROUP BY order_status");
    $order_statuses = $stmt->fetchAll();
    
    $status_summary = [
        'pending' => ['count' => 0, 'total' => 0, 'color' => 'warning'],
        'processing' => ['count' => 0, 'total' => 0, 'color' => 'info'],
        'completed' => ['count' => 0, 'total' => 0, 'color' => 'success'],
        'cancelled' => ['count' => 0, 'total' => 0, 'color' => 'danger']
    ];
    
    foreach ($order_statuses as $status) {
        $status_summary[$status['order_status']] = [
            'count' => $status['count'],
            'total' => $status['total'],
            'color' => $status_summary[$status['order_status']]['color']
        ];
    }

} catch (PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
    // Set default values
    $today_stats = ['orders_today' => 0, 'revenue_today' => 0, 'pending_orders' => 0, 'processing_orders' => 0, 'completed_orders' => 0];
    $product_stats = ['total_products' => 0, 'active_products' => 0, 'low_stock' => 0, 'out_of_stock' => 0, 'categories' => 0];
    $categories = [];
    $recent_transactions = [];
    $production_items = [];
    $top_products = [];
    $revenue_labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    $revenue_data = [0, 0, 0, 0, 0, 0, 0];
    $revenue_changes = [0, 0, 0, 0, 0, 0, 0];
    $customer_stats = ['total_customers' => 0, 'new_customers' => 0];
    $status_summary = [
        'pending' => ['count' => 0, 'total' => 0, 'color' => 'warning'],
        'processing' => ['count' => 0, 'total' => 0, 'color' => 'info'],
        'completed' => ['count' => 0, 'total' => 0, 'color' => 'success'],
        'cancelled' => ['count' => 0, 'total' => 0, 'color' => 'danger']
    ];
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<!-- Main Content -->
<div class="flex-1 flex flex-col min-h-screen lg:ml-64 m-0 p-0">
    <!-- Header -->
    <header class="sticky top-0 z-30 bg-white/80 dark:bg-slate-900/80 backdrop-blur-sm border-b border-primary-200/50 dark:border-primary-800/50">
        <div class="flex items-center justify-between px-6 py-3">
            <!-- Left Section -->
            <div class="flex items-center space-x-4">
                <button id="toggleSidebar" class="lg:hidden p-2 hover:bg-primary-100 dark:hover:bg-primary-800 rounded-lg transition-colors">
                    <i class="fas fa-bars text-slate-600 dark:text-slate-400"></i>
                </button>
                <div>
                    <h1 class="text-lg font-semibold text-slate-900 dark:text-white">
                        <i class="fas fa-chess-queen text-primary-500 mr-2"></i>
                        Welcome back, <?php echo isset($current_user['name']) ? explode(' ', $current_user['name'])[0] : 'Admin'; ?>!
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        <i class="far fa-calendar-alt text-primary-400 mr-1"></i>
                        <?php echo date('l, F j, Y'); ?> · 
                        <span class="text-primary-500 font-medium">BeriBakes Dashboard</span>
                    </p>
                </div>
            </div>

            <!-- Right Section -->
            <div class="flex items-center space-x-2">
                <!-- Quick Actions -->
                <div class="hidden md:flex items-center space-x-1 mr-2">
                    
                    <a href="products/add.php"
                    class="p-2 hover:bg-primary-100 dark:hover:bg-primary-800 rounded-lg transition-colors group tooltip"
                    title="Add Product">
                        <i class="fa-solid fa-circle-plus text-primary-600 dark:text-primary-400"></i>
                        <span class="tooltip-text">Add Product</span>
                    </a>

                    <a href="orders/index.php?action=new"
                    class="p-2 hover:bg-primary-100 dark:hover:bg-primary-800 rounded-lg transition-colors group tooltip"
                    title="New Order">
                        <i class="fa-solid fa-truck text-secondary-600 dark:text-secondary-400"></i>
                        <span class="tooltip-text">New Order</span>
                    </a>

                    <a href="reports/sales.php"
                    class="p-2 hover:bg-primary-100 dark:hover:bg-primary-800 rounded-lg transition-colors group tooltip"
                    title="Reports">
                        <i class="fa-solid fa-chart-column text-accent-600 dark:text-accent-400"></i>
                        <span class="tooltip-text">View Reports</span>
                    </a>

                </div>

                <!-- Dark Mode Toggle -->
                <button id="themeToggle"
                        class="p-2 hover:bg-primary-100 dark:hover:bg-primary-800 rounded-lg transition-colors tooltip"
                        title="Toggle Theme">
                    <i class="fa-solid fa-moon text-slate-600 dark:text-slate-400"></i>
                    <span class="tooltip-text">Toggle Theme</span>
                </button>

                <!-- Profile Dropdown -->
                <div class="relative">
                    <button id="profileToggle" class="flex items-center space-x-2 p-1.5 hover:bg-primary-100 dark:hover:bg-primary-800 rounded-xl transition-colors">
                        <div class="w-9 h-9 bg-gradient-to-br from-primary-600 to-secondary-500 rounded-full flex items-center justify-center text-white text-sm font-semibold shadow-lg">
                            <?php 
                            $name = isset($current_user['name']) ? $current_user['name'] : 'Admin User';
                            $initials = '';
                            $words = explode(' ', $name);
                            foreach ($words as $w) {
                                if (!empty($w)) $initials .= strtoupper(substr($w, 0, 1));
                            }
                            echo substr($initials, 0, 2);
                            ?>
                        </div>
                        <div class="hidden lg:block text-left">
                            <div class="text-sm font-medium text-slate-900 dark:text-white">
                                <?php echo htmlspecialchars($name); ?>
                            </div>
                            <div class="text-2xs text-slate-500 dark:text-slate-400 flex items-center">
                                <i class="fas fa-circle text-emerald-500 text-2xs mr-1"></i>
                                <?php echo isset($current_user['role']) ? ucfirst($current_user['role']) : 'Administrator'; ?>
                            </div>
                        </div>
                        <i class="fas fa-chevron-down text-xs text-slate-400 hidden lg:block"></i>
                    </button>

                    <!-- Dropdown Menu -->
                    <div id="profileDropdown" class="absolute right-0 mt-2 w-56 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-primary-200 dark:border-primary-700 hidden z-50 animate-slide-up overflow-hidden">
                        <div class="p-3 bg-gradient-to-r from-primary-600 to-secondary-500 text-white">
                            <p class="text-sm font-semibold"><?php echo htmlspecialchars($name); ?></p>
                            <p class="text-2xs opacity-80"><?php echo isset($current_user['email']) ? $current_user['email'] : 'admin@beribakes.com'; ?></p>
                        </div>
                        <div class="p-2">
                            <a href="#profile" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-primary-100 dark:hover:bg-primary-700/50 transition-colors text-sm">
                                <i class="fas fa-user-circle text-primary-600 w-5"></i>
                                <span>My Profile</span>
                            </a>
                            <a href="#settings" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-primary-100 dark:hover:bg-primary-700/50 transition-colors text-sm">
                                <i class="fas fa-cog text-primary-600 w-5"></i>
                                <span>Settings</span>
                            </a>
                            <div class="border-t border-primary-200 dark:border-primary-700 my-2"></div>
                            <a href="logout.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/20 transition-colors text-sm">
                                <i class="fas fa-sign-out-alt w-5"></i>
                                <span>Sign Out</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-1 p-6 bg-slate-50/50 dark:bg-primary-900/50">
        <!-- Welcome Banner -->
        <div class="mb-6 bg-gradient-to-r from-primary-600 to-secondary-500 rounded-2xl p-5 text-white shadow-lg relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-20 -mt-20"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 bg-black/10 rounded-full -ml-10 -mb-10"></div>
            <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-2xl font-bold mb-2 flex items-center">
                        <i class="fas fa-bread-slice mr-3 text-3xl"></i>
                        BeriBakes Bakery Dashboard
                    </h2>
                    <p class="text-white/90 max-w-2xl">
                        Track your bakery's performance, manage orders, and monitor production all in one place.
                        <?php if ($product_stats['low_stock'] > 0): ?>
                        <span class="inline-flex items-center ml-2 px-3 py-1 bg-amber-500/30 rounded-full text-sm">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            <?php echo $product_stats['low_stock']; ?> items need attention
                        </span>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="mt-4 md:mt-0 flex space-x-2">
                    <span class="px-4 py-2 bg-white/20 rounded-xl backdrop-blur-sm text-sm">
                        <i class="fas fa-store-alt mr-2"></i>
                        Open Today
                    </span>
                    <span class="px-4 py-2 bg-white/20 rounded-xl backdrop-blur-sm text-sm">
                        <i class="fas fa-clock mr-2"></i>
                        <?php echo date('h:i A'); ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Key Metrics Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
            <!-- Revenue Card -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 bakery-card card-hover relative overflow-hidden group">
                <div class="absolute top-0 right-0 w-20 h-20 bg-primary-500/5 rounded-full -mr-5 -mt-5 group-hover:scale-150 transition-transform duration-500"></div>
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mb-1 flex items-center">
                            <i class="fas fa-chart-line text-primary-500 mr-1"></i>
                            Today's Revenue
                        </p>
                        <h3 class="text-2xl font-bold text-slate-900 dark:text-white">
                            KES <?php echo number_format($today_stats['revenue_today'], 2); ?>
                        </h3>
                        <div class="flex items-center mt-2 space-x-2">
                            <span class="text-xs px-2 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 rounded-full">
                                <i class="fas fa-arrow-up mr-1"></i>
                                <?php 
                                $yesterday_rev = $revenue_data[count($revenue_data)-2] ?? 0;
                                $change = $yesterday_rev > 0 ? round(($today_stats['revenue_today'] - $yesterday_rev) / $yesterday_rev * 100, 1) : 0;
                                echo $change > 0 ? '+' . $change : $change;
                                ?>%
                            </span>
                            <span class="text-xs text-slate-500">vs yesterday</span>
                        </div>
                    </div>
                    <div class="w-14 h-14 bg-gradient-to-br from-primary-500 to-secondary-500 rounded-2xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-coins text-white text-2xl"></i>
                    </div>
                </div>
                <a href="ledger/transactions.php" class="mt-3 text-xs text-primary-600 dark:text-primary-400 hover:underline flex items-center group-hover:translate-x-1 transition-transform">
                    View transactions <i class="fas fa-arrow-right ml-1 text-xs"></i>
                </a>
            </div>

            <!-- Orders Card -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 bakery-card card-hover relative overflow-hidden group">
                <div class="absolute top-0 right-0 w-20 h-20 bg-secondary-500/5 rounded-full -mr-5 -mt-5 group-hover:scale-150 transition-transform duration-500"></div>
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mb-1 flex items-center">
                            <i class="fas fa-shopping-bag text-secondary-500 mr-1"></i>
                            Today's Orders
                        </p>
                        <h3 class="text-2xl font-bold text-slate-900 dark:text-white">
                            <?php echo $today_stats['orders_today']; ?>
                        </h3>
                        <div class="flex mt-2 space-x-1">
                            <?php if ($status_summary['pending']['count'] > 0): ?>
                            <span class="text-xs px-2 py-1 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded-full">
                                <?php echo $status_summary['pending']['count']; ?> pending
                            </span>
                            <?php endif; ?>
                            <?php if ($status_summary['processing']['count'] > 0): ?>
                            <span class="text-xs px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-full">
                                <?php echo $status_summary['processing']['count']; ?> processing
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="w-14 h-14 bg-gradient-to-br from-secondary-500 to-accent-500 rounded-2xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-truck text-white text-2xl"></i>
                    </div>
                </div>
                <a href="orders/index.php" class="mt-3 text-xs text-secondary-600 dark:text-secondary-400 hover:underline flex items-center group-hover:translate-x-1 transition-transform">
                    Manage orders <i class="fas fa-arrow-right ml-1 text-xs"></i>
                </a>
            </div>

            <!-- Products Card -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 bakery-card card-hover relative overflow-hidden group">
                <div class="absolute top-0 right-0 w-20 h-20 bg-accent-500/5 rounded-full -mr-5 -mt-5 group-hover:scale-150 transition-transform duration-500"></div>
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mb-1 flex items-center">
                            <i class="fas fa-bread-slice text-accent-500 mr-1"></i>
                            Product Inventory
                        </p>
                        <h3 class="text-2xl font-bold text-slate-900 dark:text-white">
                            <?php echo $product_stats['active_products']; ?>/<?php echo $product_stats['total_products']; ?>
                        </h3>
                        <div class="flex flex-wrap mt-2 gap-1">
                            <?php if ($product_stats['low_stock'] > 0): ?>
                            <span class="text-xs px-2 py-1 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded-full flex items-center">
                                <i class="fas fa-exclamation-triangle mr-1 text-2xs"></i>
                                <?php echo $product_stats['low_stock']; ?> low
                            </span>
                            <?php endif; ?>
                            <?php if ($product_stats['out_of_stock'] > 0): ?>
                            <span class="text-xs px-2 py-1 bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-300 rounded-full flex items-center">
                                <i class="fas fa-times-circle mr-1 text-2xs"></i>
                                <?php echo $product_stats['out_of_stock']; ?> out
                            </span>
                            <?php endif; ?>
                            <span class="text-xs px-2 py-1 bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 rounded-full">
                                <?php echo $product_stats['categories']; ?> categories
                            </span>
                        </div>
                    </div>
                    <div class="w-14 h-14 bg-gradient-to-br from-accent-500 to-primary-500 rounded-2xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-cube text-white text-2xl"></i>
                    </div>
                </div>
                <a href="products/index.php" class="mt-3 text-xs text-accent-600 dark:text-accent-400 hover:underline flex items-center group-hover:translate-x-1 transition-transform">
                    View products <i class="fas fa-arrow-right ml-1 text-xs"></i>
                </a>
            </div>

            <!-- Customers Card -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 bakery-card card-hover relative overflow-hidden group">
                <div class="absolute top-0 right-0 w-20 h-20 bg-emerald-500/5 rounded-full -mr-5 -mt-5 group-hover:scale-150 transition-transform duration-500"></div>
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mb-1 flex items-center">
                            <i class="fas fa-users text-emerald-500 mr-1"></i>
                            Total Customers
                        </p>
                        <h3 class="text-2xl font-bold text-slate-900 dark:text-white">
                            <?php echo $customer_stats['total_customers']; ?>
                        </h3>
                        <div class="flex items-center mt-2">
                            <span class="text-xs px-2 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 rounded-full flex items-center">
                                <i class="fas fa-user-plus mr-1 text-2xs"></i>
                                +<?php echo $customer_stats['new_customers']; ?> this month
                            </span>
                        </div>
                    </div>
                    <div class="w-14 h-14 bg-gradient-to-br from-emerald-500 to-teal-500 rounded-2xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-user-friends text-white text-2xl"></i>
                    </div>
                </div>
                <a href="customers/index.php" class="mt-3 text-xs text-emerald-600 dark:text-emerald-400 hover:underline flex items-center group-hover:translate-x-1 transition-transform">
                    View customers <i class="fas fa-arrow-right ml-1 text-xs"></i>
                </a>
            </div>
        </div>

        <!-- Charts and Production Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <!-- Revenue Chart Card -->
            <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-2xl p-5 bakery-card">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white flex items-center">
                            <i class="fas fa-chart-line text-primary-500 mr-2"></i>
                            Revenue Trend
                        </h3>
                        <p class="text-xs text-slate-500">Last 7 days performance</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-xs px-3 py-1 bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 rounded-full">
                            Total: KES <?php echo number_format(array_sum($revenue_data), 2); ?>
                        </span>
                        <a href="reports/sales.php" class="text-xs text-primary-600 hover:underline">
                            Full Report <i class="fas fa-external-link-alt ml-1"></i>
                        </a>
                    </div>
                </div>
                <div class="h-64">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <!-- Order Status Card -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 bakery-card">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center">
                    <i class="fas fa-tasks text-secondary-500 mr-2"></i>
                    Today's Order Status
                </h3>
                <div class="space-y-4">
                    <!-- Pending -->
                    <div class="relative">
                        <div class="flex items-center justify-between mb-1">
                            <div class="flex items-center">
                                <div class="w-2 h-2 bg-amber-500 rounded-full mr-2"></div>
                                <span class="text-sm text-slate-600 dark:text-slate-400">Pending</span>
                            </div>
                            <span class="text-sm font-semibold"><?php echo $status_summary['pending']['count']; ?></span>
                        </div>
                        <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2">
                            <div class="bg-amber-500 h-2 rounded-full" style="width: <?php echo $today_stats['orders_today'] > 0 ? ($status_summary['pending']['count'] / $today_stats['orders_today'] * 100) : 0; ?>%"></div>
                        </div>
                    </div>
                    <!-- Processing -->
                    <div class="relative">
                        <div class="flex items-center justify-between mb-1">
                            <div class="flex items-center">
                                <div class="w-2 h-2 bg-blue-500 rounded-full mr-2"></div>
                                <span class="text-sm text-slate-600 dark:text-slate-400">Processing</span>
                            </div>
                            <span class="text-sm font-semibold"><?php echo $status_summary['processing']['count']; ?></span>
                        </div>
                        <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2">
                            <div class="bg-blue-500 h-2 rounded-full" style="width: <?php echo $today_stats['orders_today'] > 0 ? ($status_summary['processing']['count'] / $today_stats['orders_today'] * 100) : 0; ?>%"></div>
                        </div>
                    </div>
                    <!-- Completed -->
                    <div class="relative">
                        <div class="flex items-center justify-between mb-1">
                            <div class="flex items-center">
                                <div class="w-2 h-2 bg-emerald-500 rounded-full mr-2"></div>
                                <span class="text-sm text-slate-600 dark:text-slate-400">Completed</span>
                            </div>
                            <span class="text-sm font-semibold"><?php echo $status_summary['completed']['count']; ?></span>
                        </div>
                        <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2">
                            <div class="bg-emerald-500 h-2 rounded-full" style="width: <?php echo $today_stats['orders_today'] > 0 ? ($status_summary['completed']['count'] / $today_stats['orders_today'] * 100) : 0; ?>%"></div>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-t border-primary-200 dark:border-primary-700">
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Total Revenue Today</span>
                            <span class="font-bold text-primary-600">KES <?php echo number_format($today_stats['revenue_today'], 2); ?></span>
                        </div>
                        <div class="flex justify-between text-sm mt-2">
                            <span class="text-slate-500">Average Order Value</span>
                            <span class="font-bold text-secondary-600">
                                KES <?php echo $today_stats['orders_today'] > 0 ? number_format($today_stats['revenue_today'] / $today_stats['orders_today'], 2) : 0; ?>
                            </span>
                        </div>
                    </div>
                </div>
                <a href="orders/index.php" class="mt-4 block text-center text-sm bg-primary-100 dark:bg-primary-800/30 hover:bg-primary-200 dark:hover:bg-primary-700/50 text-primary-700 dark:text-primary-300 rounded-xl py-2 transition-colors">
                    <i class="fas fa-eye mr-2"></i>View All Orders
                </a>
            </div>
        </div>

        <!-- Production and Top Products Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Today's Production Schedule -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 bakery-card">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white flex items-center">
                        <i class="fas fa-clock text-amber-500 mr-2"></i>
                        Today's Production
                    </h3>
                    <a href="orders/index.php" class="text-xs text-primary-600 hover:underline">
                        View All <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
                
                <?php if (empty($production_items)): ?>
                <div class="text-center py-8">
                    <div class="w-20 h-20 bg-primary-100 dark:bg-primary-800/30 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-clock text-primary-500 text-3xl"></i>
                    </div>
                    <p class="text-slate-500 dark:text-slate-400">No production scheduled for today</p>
                    <a href="orders/index.php?action=new" class="mt-3 inline-block text-sm text-primary-600 hover:underline">
                        Create new order <i class="fas fa-plus-circle ml-1"></i>
                    </a>
                </div>
                <?php else: ?>
                <div class="space-y-3 max-h-96 overflow-y-auto pr-2 hide-scrollbar">
                    <?php foreach ($production_items as $item): 
                        $colors = [
                            'pending' => ['bg' => 'amber-100', 'text' => 'amber-700', 'dark_bg' => 'amber-900/30', 'dark_text' => 'amber-300', 'icon' => 'hourglass-half'],
                            'processing' => ['bg' => 'blue-100', 'text' => 'blue-700', 'dark_bg' => 'blue-900/30', 'dark_text' => 'blue-300', 'icon' => 'cogs'],
                            'completed' => ['bg' => 'emerald-100', 'text' => 'emerald-700', 'dark_bg' => 'emerald-900/30', 'dark_text' => 'emerald-300', 'icon' => 'check-circle'],
                            'cancelled' => ['bg' => 'rose-100', 'text' => 'rose-700', 'dark_bg' => 'rose-900/30', 'dark_text' => 'rose-300', 'icon' => 'times-circle']
                        ];
                        $status = $item['order_status'];
                        $style = $colors[$status] ?? $colors['pending'];
                    ?>
                    <div class="flex items-center justify-between p-3 bg-primary-50 dark:bg-primary-800/30 rounded-xl hover:shadow-md transition-shadow">
                        <div class="flex items-center space-x-3 flex-1">
                            <div class="w-10 h-10 bg-gradient-to-br from-primary-600 to-secondary-500 rounded-xl flex items-center justify-center text-white">
                                <i class="fas fa-<?php echo $style['icon']; ?>"></i>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-sm font-medium text-slate-900 dark:text-white">
                                        <?php echo htmlspecialchars($item['product_name']); ?>
                                    </h4>
                                    <span class="text-xs text-slate-500">
                                        <i class="far fa-clock mr-1"></i>
                                        <?php echo date('h:i A', strtotime($item['order_time'] ?? 'now')); ?>
                                    </span>
                                </div>
                                <div class="flex items-center justify-between mt-1">
                                    <span class="text-xs text-slate-500">
                                        Qty: <span class="font-semibold"><?php echo $item['quantity']; ?></span> units
                                    </span>
                                    <span class="text-xs px-2 py-0.5 bg-<?php echo $style['bg']; ?> dark:bg-<?php echo $style['dark_bg']; ?> text-<?php echo $style['text']; ?> dark:text-<?php echo $style['dark_text']; ?> rounded-full">
                                        <i class="fas fa-<?php echo $style['icon']; ?> mr-1 text-2xs"></i>
                                        <?php echo ucfirst($status); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <a href="orders/view.php?id=<?php echo $item['order_id']; ?>" class="ml-2 p-2 hover:bg-primary-200 dark:hover:bg-primary-700 rounded-lg transition-colors">
                            <i class="fas fa-chevron-right text-slate-400"></i>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Top Selling Products -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 bakery-card">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white flex items-center">
                        <i class="fas fa-crown text-amber-500 mr-2"></i>
                        Top Selling Products
                    </h3>
                    <a href="reports/products.php" class="text-xs text-primary-600 hover:underline">
                        Full Report <i class="fas fa-chart-bar ml-1"></i>
                    </a>
                </div>

                <?php if (empty($top_products)): ?>
                <div class="text-center py-8">
                    <div class="w-20 h-20 bg-primary-100 dark:bg-primary-800/30 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-chart-bar text-primary-500 text-3xl"></i>
                    </div>
                    <p class="text-slate-500 dark:text-slate-400">No sales data available yet</p>
                </div>
                <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($top_products as $index => $product): 
                        $trend_colors = [
                            'success' => ['bg' => 'emerald-100', 'text' => 'emerald-700', 'dark_bg' => 'emerald-900/30', 'dark_text' => 'emerald-300'],
                            'warning' => ['bg' => 'amber-100', 'text' => 'amber-700', 'dark_bg' => 'amber-900/30', 'dark_text' => 'amber-300'],
                            'info' => ['bg' => 'blue-100', 'text' => 'blue-700', 'dark_bg' => 'blue-900/30', 'dark_text' => 'blue-300']
                        ];
                        $color = $trend_colors[$product['trend_color']] ?? $trend_colors['info'];
                    ?>
                    <div class="flex items-center space-x-3 p-2 hover:bg-primary-50 dark:hover:bg-primary-800/30 rounded-xl transition-colors">
                        <div class="flex-shrink-0 w-8 h-8 bg-<?php echo $color['bg']; ?> dark:bg-<?php echo $color['dark_bg']; ?> rounded-full flex items-center justify-center font-bold text-<?php echo $color['text']; ?> dark:text-<?php echo $color['dark_text']; ?>">
                            #<?php echo $index + 1; ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-900 dark:text-white truncate">
                                <?php echo htmlspecialchars($product['product_name']); ?>
                            </p>
                            <div class="flex items-center text-xs text-slate-500 mt-1">
                                <span class="mr-3">
                                    <i class="fas fa-chart-line mr-1"></i>
                                    <?php echo $product['total_sold']; ?> sold
                                </span>
                                <span>
                                    <i class="fas fa-shopping-bag mr-1"></i>
                                    <?php echo $product['order_count']; ?> orders
                                </span>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-primary-600 dark:text-primary-400">
                                KES <?php echo number_format($product['revenue'], 2); ?>
                            </p>
                            <p class="text-2xs text-slate-500">
                                Stock: <?php echo $product['stock']; ?>
                            </p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Category Distribution -->
                <?php if (!empty($categories)): ?>
                <div class="mt-4 pt-4 border-t border-primary-200 dark:border-primary-700">
                    <h4 class="text-sm font-semibold text-slate-900 dark:text-white mb-3">Categories</h4>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($categories as $cat): ?>
                        <span class="px-3 py-1 bg-primary-100 dark:bg-primary-800/30 text-primary-700 dark:text-primary-300 rounded-full text-xs flex items-center">
                            <i class="fas fa-tag mr-1 text-2xs"></i>
                            <?php echo htmlspecialchars($cat['category_name']); ?> (<?php echo $cat['product_count']; ?>)
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Transactions Table -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 bakery-card">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white flex items-center">
                    <i class="fas fa-exchange-alt text-primary-500 mr-2"></i>
                    Recent Transactions
                </h3>
                <div class="flex items-center space-x-2">
                    <a href="ledger/accounts.php" class="text-sm text-primary-600 hover:underline">Accounts</a>
                    <span class="text-slate-300">|</span>
                    <a href="ledger/transactions.php" class="text-sm text-primary-600 hover:underline flex items-center">
                        View All <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>

            <?php if (empty($recent_transactions)): ?>
            <div class="text-center py-8">
                <div class="w-20 h-20 bg-primary-100 dark:bg-primary-800/30 rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="fas fa-exchange-alt text-primary-500 text-3xl"></i>
                </div>
                <p class="text-slate-500 dark:text-slate-400">No transactions recorded</p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-xs text-slate-500 dark:text-slate-400 border-b border-primary-200 dark:border-primary-700">
                            <th class="text-left py-3 font-medium">Date & Time</th>
                            <th class="text-left font-medium">Account</th>
                            <th class="text-left font-medium">Description</th>
                            <th class="text-right font-medium">Type</th>
                            <th class="text-right font-medium">Amount</th>
                            <th class="text-center font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-primary-100 dark:divide-primary-800">
                        <?php foreach ($recent_transactions as $trans): 
                            $type_color = $trans['transaction_type'] == 'credit' ? 'emerald' : 'rose';
                            $type_icon = $trans['transaction_type'] == 'credit' ? 'arrow-up' : 'arrow-down';
                        ?>
                        <tr class="hover:bg-primary-50 dark:hover:bg-primary-800/30 transition-colors">
                            <td class="py-3 text-sm">
                                <?php echo date('M d, H:i', strtotime($trans['created_at'])); ?>
                            </td>
                            <td class="py-3 text-sm">
                                <span class="flex items-center">
                                    <i class="fas fa-<?php echo $trans['icon'] ?? 'exchange-alt'; ?> text-primary-500 mr-2 text-xs"></i>
                                    <?php echo htmlspecialchars($trans['account_name']); ?>
                                </span>
                            </td>
                            <td class="py-3 text-sm text-slate-600 dark:text-slate-400">
                                <?php echo htmlspecialchars($trans['description'] ?? '-'); ?>
                            </td>
                            <td class="py-3 text-sm text-right">
                                <span class="px-2 py-1 bg-<?php echo $type_color; ?>-100 dark:bg-<?php echo $type_color; ?>-900/30 text-<?php echo $type_color; ?>-700 dark:text-<?php echo $type_color; ?>-300 rounded-full text-xs">
                                    <i class="fas fa-<?php echo $type_icon; ?> mr-1"></i>
                                    <?php echo ucfirst($trans['transaction_type']); ?>
                                </span>
                            </td>
                            <td class="py-3 text-sm text-right font-medium <?php echo $trans['transaction_type'] == 'credit' ? 'text-emerald-600' : 'text-rose-600'; ?>">
                                <?php echo $trans['transaction_type'] == 'credit' ? '+' : '-'; ?>
                                KES <?php echo number_format($trans['amount'], 2); ?>
                            </td>
                            <td class="py-3 text-center">
                                <a href="ledger/transactions.php?id=<?php echo $trans['id']; ?>" class="p-1 hover:bg-primary-200 dark:hover:bg-primary-700 rounded-lg transition-colors">
                                    <i class="fas fa-eye text-slate-400"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Quick Action Footer -->
        <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-3">
            <a href="products/add.php" class="flex items-center justify-center space-x-2 p-3 bg-primary-100 dark:bg-primary-800/30 hover:bg-primary-200 dark:hover:bg-primary-700/50 rounded-xl text-primary-700 dark:text-primary-300 transition-colors">
                <i class="fas fa-plus-circle"></i>
                <span class="text-sm font-medium">Add Product</span>
            </a>
            <a href="orders/index.php?action=new" class="flex items-center justify-center space-x-2 p-3 bg-secondary-100 dark:bg-secondary-800/30 hover:bg-secondary-200 dark:hover:bg-secondary-700/50 rounded-xl text-secondary-700 dark:text-secondary-300 transition-colors">
                <i class="fas fa-truck"></i>
                <span class="text-sm font-medium">New Order</span>
            </a>
            <a href="ledger/transactions.php?action=new" class="flex items-center justify-center space-x-2 p-3 bg-accent-100 dark:bg-accent-800/30 hover:bg-accent-200 dark:hover:bg-accent-700/50 rounded-xl text-accent-700 dark:text-accent-300 transition-colors">
                <i class="fas fa-exchange-alt"></i>
                <span class="text-sm font-medium">Add Transaction</span>
            </a>
            <a href="reports/sales.php" class="flex items-center justify-center space-x-2 p-3 bg-emerald-100 dark:bg-emerald-800/30 hover:bg-emerald-200 dark:hover:bg-emerald-700/50 rounded-xl text-emerald-700 dark:text-emerald-300 transition-colors">
                <i class="fas fa-chart-line"></i>
                <span class="text-sm font-medium">View Reports</span>
            </a>
        </div>
    </main>
</div>

<script>
    // Initialize Revenue Chart
    const ctx = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($revenue_labels); ?>,
            datasets: [{
                label: 'Revenue (KES)',
                data: <?php echo json_encode($revenue_data); ?>,
                borderColor: '#b45309',
                backgroundColor: 'rgba(180, 83, 9, 0.1)',
                borderWidth: 3,
                pointBackgroundColor: '#e6b17e',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7,
                tension: 0.4,
                fill: true
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
                            return 'KES ' + context.parsed.y.toFixed(2);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(180, 83, 9, 0.1)'
                    },
                    ticks: {
                        callback: function(value) {
                            return 'KES ' + value;
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
</script>

<?php include 'includes/footer.php'; ?>