<?php
require_once '../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';

// Handle customer deletion (soft delete or hard delete - we'll use soft delete by moving to inactive)
if (isset($_GET['delete'])) {
    $id = filter_var($_GET['delete'], FILTER_SANITIZE_NUMBER_INT);

    try {
        // Check if customer has orders
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE customer_id = ?");
        $stmt->execute([$id]);
        $order_count = $stmt->fetchColumn();

        if ($order_count > 0) {
            // Instead of deleting, we can mark as inactive (if you add a status column)
            // For now, we'll just show an error
            $_SESSION['error'] = "Cannot delete customer: They have existing orders. You can only deactivate them.";
        } else {
            // Delete customer if no orders
            $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['success'] = "Customer deleted successfully!";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error deleting customer: " . $e->getMessage();
    }

    header("Location: index.php");
    exit;
}

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_filter = isset($_GET['date_range']) ? $_GET['date_range'] : 'all';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Build query with filters
$sql = "SELECT c.*, 
        COUNT(DISTINCT o.id) as total_orders,
        COALESCE(SUM(o.total_amount), 0) as total_spent,
        MAX(o.order_date) as last_order_date,
        CASE 
            WHEN MAX(o.order_date) >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 'active'
            WHEN MAX(o.order_date) IS NOT NULL THEN 'inactive'
            ELSE 'no_orders'
        END as customer_status
        FROM customers c
        LEFT JOIN orders o ON c.id = o.customer_id
        WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (c.name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Date filter for registration
if ($date_filter == 'today') {
    $sql .= " AND DATE(c.created_at) = CURDATE()";
} elseif ($date_filter == 'week') {
    $sql .= " AND c.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
} elseif ($date_filter == 'month') {
    $sql .= " AND c.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
} elseif ($date_filter == 'year') {
    $sql .= " AND c.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
}

$sql .= " GROUP BY c.id";

// Sorting
if ($sort == 'newest') {
    $sql .= " ORDER BY c.created_at DESC";
} elseif ($sort == 'oldest') {
    $sql .= " ORDER BY c.created_at ASC";
} elseif ($sort == 'most_orders') {
    $sql .= " ORDER BY total_orders DESC";
} elseif ($sort == 'highest_spent') {
    $sql .= " ORDER BY total_spent DESC";
} elseif ($sort == 'recent_order') {
    $sql .= " ORDER BY last_order_date DESC";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();

// Get customer statistics
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_customers,
        COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_customers_30d,
        COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as new_customers_7d,
        (
            SELECT COUNT(DISTINCT customer_id) 
            FROM orders 
            WHERE order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ) as active_customers_30d,
        (
            SELECT COALESCE(SUM(total_amount), 0)
            FROM orders 
            WHERE order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ) as revenue_30d,
        (
            SELECT COUNT(*)
            FROM orders
        ) as total_orders
    FROM customers");
$stats = $stmt->fetch();

// Get top customers by spending
$stmt = $pdo->query("
    SELECT c.id, c.name, c.email, 
           COUNT(o.id) as order_count,
           COALESCE(SUM(o.total_amount), 0) as total_spent
    FROM customers c
    JOIN orders o ON c.id = o.customer_id
    GROUP BY c.id
    ORDER BY total_spent DESC
    LIMIT 5");
$top_customers = $stmt->fetchAll();

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
                        <i class="fas fa-users text-primary-500 mr-2"></i>
                        Customer Management
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        <i class="far fa-address-card mr-1"></i>
                        View and manage your bakery's customers
                    </p>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <button onclick="exportCustomers()" class="px-4 py-2 border border-primary-300 dark:border-primary-600 text-primary-700 dark:text-primary-300 rounded-xl text-sm font-semibold hover:bg-primary-50 dark:hover:bg-primary-800/30 transition-colors">
                    <i class="fas fa-download mr-2"></i>
                    Export
                </button>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-1 p-6 bg-slate-50/50 dark:bg-primary-900/50">

        <!-- Alert Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="mb-4 bg-emerald-100 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 px-4 py-3 rounded-xl flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?php
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                    ?>
                </div>
                <button onclick="this.parentElement.remove()" class="text-emerald-700 dark:text-emerald-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="mb-4 bg-rose-100 dark:bg-rose-900/30 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 px-4 py-3 rounded-xl flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>
                </div>
                <button onclick="this.parentElement.remove()" class="text-rose-700 dark:text-rose-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        <?php endif; ?>

        <!-- Customer Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-slate-800 rounded-xl p-5 bakery-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-500">Total Customers</p>
                        <h4 class="text-2xl font-bold text-primary-600"><?php echo number_format($stats['total_customers'] ?? 0); ?></h4>
                        <p class="text-xs text-emerald-600 mt-1">
                            <i class="fas fa-arrow-up"></i> +<?php echo $stats['new_customers_30d'] ?? 0; ?> this month
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-primary-100 dark:bg-primary-800/50 rounded-xl flex items-center justify-center">
                        <i class="fas fa-users text-primary-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-xl p-5 bakery-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-500">Active Customers (30d)</p>
                        <h4 class="text-2xl font-bold text-emerald-600"><?php echo number_format($stats['active_customers_30d'] ?? 0); ?></h4>
                        <p class="text-xs text-slate-500 mt-1">
                            <?php echo $stats['total_customers'] > 0 ? round(($stats['active_customers_30d'] / $stats['total_customers']) * 100, 1) : 0; ?>% of total
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-emerald-100 dark:bg-emerald-800/50 rounded-xl flex items-center justify-center">
                        <i class="fas fa-user-check text-emerald-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-xl p-5 bakery-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-500">Revenue from Customers</p>
                        <h4 class="text-2xl font-bold text-secondary-600">KES <?php echo number_format($stats['revenue_30d'] ?? 0, 2); ?></h4>
                        <p class="text-xs text-slate-500 mt-1">Last 30 days</p>
                    </div>
                    <div class="w-12 h-12 bg-secondary-100 dark:bg-secondary-800/50 rounded-xl flex items-center justify-center">
                        <i class="fas fa-chart-line text-secondary-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-xl p-5 bakery-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-500">Total Orders</p>
                        <h4 class="text-2xl font-bold text-accent-600"><?php echo number_format($stats['total_orders'] ?? 0); ?></h4>
                        <p class="text-xs text-slate-500 mt-1">All time</p>
                    </div>
                    <div class="w-12 h-12 bg-accent-100 dark:bg-accent-800/50 rounded-xl flex items-center justify-center">
                        <i class="fas fa-shopping-bag text-accent-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters and Search -->
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 bakery-card mb-6">
            <form method="GET" class="flex flex-wrap items-center gap-4">
                <div class="flex-1 min-w-[250px]">
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400"></i>
                        <input type="text" name="search" placeholder="Search by name, email, or phone..."
                            value="<?php echo htmlspecialchars($search); ?>"
                            class="w-full pl-10 pr-4 py-2.5 border border-primary-200 dark:border-primary-700 rounded-lg bg-white dark:bg-slate-900 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    </div>
                </div>

                <div class="w-44">
                    <select name="date_range" class="w-full px-3 py-2.5 border border-primary-200 dark:border-primary-700 rounded-lg bg-white dark:bg-slate-900 text-sm focus:ring-2 focus:ring-primary-500">
                        <option value="all" <?php echo $date_filter == 'all' ? 'selected' : ''; ?>>All Time</option>
                        <option value="today" <?php echo $date_filter == 'today' ? 'selected' : ''; ?>>Today</option>
                        <option value="week" <?php echo $date_filter == 'week' ? 'selected' : ''; ?>>This Week</option>
                        <option value="month" <?php echo $date_filter == 'month' ? 'selected' : ''; ?>>This Month</option>
                        <option value="year" <?php echo $date_filter == 'year' ? 'selected' : ''; ?>>This Year</option>
                    </select>
                </div>

                <div class="w-44">
                    <select name="sort" class="w-full px-3 py-2.5 border border-primary-200 dark:border-primary-700 rounded-lg bg-white dark:bg-slate-900 text-sm focus:ring-2 focus:ring-primary-500">
                        <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="oldest" <?php echo $sort == 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                        <option value="most_orders" <?php echo $sort == 'most_orders' ? 'selected' : ''; ?>>Most Orders</option>
                        <option value="highest_spent" <?php echo $sort == 'highest_spent' ? 'selected' : ''; ?>>Highest Spent</option>
                        <option value="recent_order" <?php echo $sort == 'recent_order' ? 'selected' : ''; ?>>Recent Order</option>
                    </select>
                </div>

                <button type="submit" class="px-5 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium transition-colors">
                    <i class="fas fa-filter mr-2"></i>Apply Filters
                </button>

                <a href="index.php" class="px-5 py-2.5 border border-primary-300 dark:border-primary-600 text-primary-700 dark:text-primary-300 hover:bg-primary-50 dark:hover:bg-primary-800/30 rounded-lg text-sm font-medium transition-colors">
                    <i class="fas fa-redo mr-2"></i>Reset
                </a>
            </form>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Customers List - Left Column (2/3 width) -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 bakery-card">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center">
                        <i class="fas fa-list text-primary-500 mr-2"></i>
                        Customer Directory
                        <span class="ml-auto text-sm font-normal text-slate-500"><?php echo count($customers); ?> customers</span>
                    </h3>

                    <?php if (empty($customers)): ?>
                        <div class="text-center py-12">
                            <div class="w-24 h-24 bg-primary-100 dark:bg-primary-800/30 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-users text-primary-500 text-3xl"></i>
                            </div>
                            <h4 class="text-base font-medium text-slate-900 dark:text-white mb-2">No Customers Found</h4>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Try adjusting your search or filter criteria</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="text-xs text-slate-500 dark:text-slate-400 border-b border-primary-200 dark:border-primary-700">
                                        <th class="text-left py-3 font-medium">Customer</th>
                                        <th class="text-left font-medium">Contact</th>
                                        <th class="text-center font-medium">Orders</th>
                                        <th class="text-right font-medium">Total Spent</th>
                                        <th class="text-center font-medium">Status</th>
                                        <th class="text-center font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-primary-100 dark:divide-primary-800">
                                    <?php foreach ($customers as $customer):
                                        $status_colors = [
                                            'active' => ['bg' => 'emerald-100', 'text' => 'emerald-700', 'dark_bg' => 'emerald-900/30', 'dark_text' => 'emerald-300', 'icon' => 'circle', 'label' => 'Active'],
                                            'inactive' => ['bg' => 'amber-100', 'text' => 'amber-700', 'dark_bg' => 'amber-900/30', 'dark_text' => 'amber-300', 'icon' => 'circle', 'label' => 'Inactive'],
                                            'no_orders' => ['bg' => 'slate-100', 'text' => 'slate-700', 'dark_bg' => 'slate-800/50', 'dark_text' => 'slate-400', 'icon' => 'circle', 'label' => 'No Orders']
                                        ];
                                        $status = $status_colors[$customer['customer_status']] ?? $status_colors['no_orders'];
                                    ?>
                                        <tr class="hover:bg-primary-50 dark:hover:bg-primary-800/30 transition-colors">
                                            <td class="py-3">
                                                <div class="flex items-center space-x-3">
                                                    <div class="w-10 h-10 bg-gradient-to-br from-primary-600 to-secondary-500 rounded-full flex items-center justify-center text-white text-sm font-semibold">
                                                        <?php
                                                        $name = $customer['name'] ?? 'Unknown';
                                                        $initials = '';
                                                        $words = explode(' ', $name);
                                                        foreach ($words as $w) {
                                                            if (!empty($w)) $initials .= strtoupper(substr($w, 0, 1));
                                                        }
                                                        echo substr($initials, 0, 2);
                                                        ?>
                                                    </div>
                                                    <div>
                                                        <p class="text-sm font-medium text-slate-900 dark:text-white">
                                                            <?php echo htmlspecialchars($customer['name'] ?? 'N/A'); ?>
                                                        </p>
                                                        <p class="text-xs text-slate-500">
                                                            Customer since <?php echo date('M Y', strtotime($customer['created_at'])); ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-3">
                                                <div class="text-sm">
                                                    <p class="text-slate-600 dark:text-slate-400">
                                                        <i class="fas fa-envelope text-xs text-primary-500 mr-1"></i>
                                                        <?php echo htmlspecialchars($customer['email'] ?? 'N/A'); ?>
                                                    </p>
                                                    <p class="text-xs text-slate-500 mt-1">
                                                        <i class="fas fa-phone-alt text-xs text-secondary-500 mr-1"></i>
                                                        <?php echo htmlspecialchars($customer['phone'] ?? 'N/A'); ?>
                                                    </p>
                                                </div>
                                            </td>
                                            <td class="py-3 text-center">
                                                <span class="text-sm font-semibold text-primary-600">
                                                    <?php echo $customer['total_orders']; ?>
                                                </span>
                                            </td>
                                            <td class="py-3 text-right">
                                                <span class="text-sm font-bold text-secondary-600">
                                                    KES <?php echo number_format($customer['total_spent'], 2); ?>
                                                </span>
                                            </td>
                                            <td class="py-3 text-center">
                                                <span class="inline-flex items-center px-2 py-1 bg-<?php echo $status['bg']; ?> dark:bg-<?php echo $status['dark_bg']; ?> text-<?php echo $status['text']; ?> dark:text-<?php echo $status['dark_text']; ?> rounded-full text-xs">
                                                    <i class="fas fa-<?php echo $status['icon']; ?> mr-1 text-2xs"></i>
                                                    <?php echo $status['label']; ?>
                                                </span>
                                            </td>
                                          <td class="py-3 text-center">
    <div class="flex items-center justify-center gap-2">

        <button onclick="viewCustomer(<?php echo $customer['id']; ?>)"
        class="px-2.5 py-1 text-[11px] font-semibold bg-primary-100 hover:bg-primary-200 dark:bg-primary-800/50 dark:hover:bg-primary-700 text-primary-700 dark:text-primary-300 rounded-full transition whitespace-nowrap">

        View

        </button>

        <button onclick="confirmDelete(<?php echo $customer['id']; ?>, '<?php echo htmlspecialchars($customer['name'] ?? 'this customer'); ?>')"
        class="px-2.5 py-1 text-[11px] font-semibold bg-rose-100 hover:bg-rose-200 dark:bg-rose-900/30 dark:hover:bg-rose-800 text-rose-700 dark:text-rose-300 rounded-full transition whitespace-nowrap">

        Delete

        </button>

    </div>
</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column - Customer Insights -->
            <div class="space-y-6">
                <!-- Top Customers Card -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 bakery-card">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center">
                        <i class="fas fa-crown text-amber-500 mr-2"></i>
                        Top Customers
                    </h3>

                    <?php if (empty($top_customers)): ?>
                        <p class="text-sm text-slate-500 text-center py-4">No customer data available</p>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($top_customers as $index => $customer): ?>
                                <div class="flex items-center justify-between p-3 bg-primary-50 dark:bg-primary-800/30 rounded-xl">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 <?php echo $index == 0 ? 'bg-amber-500' : ($index == 1 ? 'bg-slate-400' : ($index == 2 ? 'bg-amber-700' : 'bg-primary-600')); ?> rounded-full flex items-center justify-center text-white text-sm font-bold">
                                            #<?php echo $index + 1; ?>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-slate-900 dark:text-white">
                                                <?php echo htmlspecialchars($customer['name']); ?>
                                            </p>
                                            <p class="text-xs text-slate-500">
                                                <?php echo $customer['order_count']; ?> orders
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-bold text-primary-600">
                                            KES <?php echo number_format($customer['total_spent'], 2); ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Customer Activity Card -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 bakery-card">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center">
                        <i class="fas fa-chart-pie text-primary-500 mr-2"></i>
                        Customer Activity
                    </h3>

                    <div class="space-y-4">
                        <!-- Active Customers -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm text-slate-600 dark:text-slate-400">Active (30 days)</span>
                                <span class="text-sm font-semibold text-emerald-600"><?php echo $stats['active_customers_30d'] ?? 0; ?></span>
                            </div>
                            <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2.5 overflow-hidden">
                                <?php
                                $active_percentage = $stats['total_customers'] > 0
                                    ? min(($stats['active_customers_30d'] / $stats['total_customers']) * 100, 100)
                                    : 0;
                                ?>
                                <div class="bg-emerald-500 h-2.5 rounded-full transition-all duration-500"
                                    style="width: <?php echo $active_percentage; ?>%;"></div>
                            </div>
                            <p class="text-xs text-slate-500 mt-1">
                                <?php echo number_format($active_percentage, 1); ?>% of total customers
                            </p>
                        </div>

                        <!-- New Customers (7 days) -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm text-slate-600 dark:text-slate-400">New (7 days)</span>
                                <span class="text-sm font-semibold text-blue-600"><?php echo $stats['new_customers_7d'] ?? 0; ?></span>
                            </div>
                            <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2.5 overflow-hidden">
                                <?php
                                $new_7d_percentage = $stats['total_customers'] > 0
                                    ? min(($stats['new_customers_7d'] / $stats['total_customers']) * 100, 100)
                                    : 0;
                                ?>
                                <div class="bg-blue-500 h-2.5 rounded-full transition-all duration-500"
                                    style="width: <?php echo $new_7d_percentage; ?>%;"></div>
                            </div>
                        </div>

                        <!-- New Customers (30 days) -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm text-slate-600 dark:text-slate-400">New (30 days)</span>
                                <span class="text-sm font-semibold text-secondary-600"><?php echo $stats['new_customers_30d'] ?? 0; ?></span>
                            </div>
                            <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2.5 overflow-hidden">
                                <?php
                                $new_30d_percentage = $stats['total_customers'] > 0
                                    ? min(($stats['new_customers_30d'] / $stats['total_customers']) * 100, 100)
                                    : 0;
                                ?>
                                <div class="bg-secondary-500 h-2.5 rounded-full transition-all duration-500"
                                    style="width: <?php echo $new_30d_percentage; ?>%;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Stats -->
                    <div class="mt-4 pt-4 border-t border-primary-200 dark:border-primary-700 grid grid-cols-2 gap-3">
                        <div>
                            <p class="text-2xs text-slate-500">Total Customers</p>
                            <p class="text-lg font-bold text-primary-600"><?php echo $stats['total_customers'] ?? 0; ?></p>
                        </div>
                        <div>
                            <p class="text-2xs text-slate-500">Avg Order Value</p>
                            <p class="text-lg font-bold text-secondary-600">
                                KES <?php echo ($stats['total_orders'] ?? 0) > 0
                                        ? number_format(($stats['revenue_30d'] ?? 0) / ($stats['total_orders'] ?? 1), 2)
                                        : 0; ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats Card -->
                <div class="bg-gradient-to-br from-primary-600 to-secondary-500 rounded-2xl p-5 text-white">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-sm font-medium text-white/80">Customer Insights</h4>
                        <i class="fas fa-chart-pie text-white/60 text-xl"></i>
                    </div>

                    <p class="text-3xl font-bold mb-1"><?php echo number_format($stats['total_customers'] ?? 0); ?></p>
                    <p class="text-xs text-white/70 mb-4">Total registered customers</p>

                    <div class="grid grid-cols-2 gap-3">
                        <?php
                        // Get today's new customers
                        $stmt = $pdo->query("SELECT COUNT(*) FROM customers WHERE DATE(created_at) = CURDATE()");
                        $new_today = $stmt->fetchColumn();

                        // Get average orders per customer
                        $avg_orders = ($stats['total_customers'] ?? 0) > 0
                            ? round(($stats['total_orders'] ?? 0) / ($stats['total_customers'] ?? 1), 1)
                            : 0;
                        ?>
                        <div class="bg-white/10 rounded-xl p-3 backdrop-blur-sm">
                            <i class="fas fa-user-plus text-white/80 mb-1"></i>
                            <p class="text-2xs text-white/70">New Today</p>
                            <p class="text-xl font-semibold"><?php echo $new_today; ?></p>
                        </div>
                        <div class="bg-white/10 rounded-xl p-3 backdrop-blur-sm">
                            <i class="fas fa-shopping-bag text-white/80 mb-1"></i>
                            <p class="text-2xs text-white/70">Avg Orders</p>
                            <p class="text-xl font-semibold"><?php echo $avg_orders; ?></p>
                        </div>
                    </div>

                    <!-- Mini trend indicator -->
                    <?php if (($stats['new_customers_30d'] ?? 0) > 0): ?>
                        <div class="mt-3 pt-3 border-t border-white/20 flex items-center justify-between">
                            <span class="text-2xs text-white/70">Monthly growth</span>
                            <span class="text-xs font-semibold text-white flex items-center">
                                <i class="fas fa-arrow-up mr-1"></i>
                                <?php
                                $growth = $stats['total_customers'] > 0
                                    ? round(($stats['new_customers_30d'] / $stats['total_customers']) * 100, 1)
                                    : 0;
                                echo $growth; ?>%
                            </span>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </main>
</div>

<!-- Customer Details Modal -->
<div id="customerModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-black/50 transition-opacity" onclick="closeCustomerModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

        <div class="inline-block align-middle bg-white dark:bg-slate-800 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-2xl sm:w-full">
            <div id="customerModalContent" class="bg-white dark:bg-slate-800">
                <!-- Content will be loaded dynamically -->
                <div class="p-8 text-center">
                    <i class="fas fa-spinner fa-spin text-primary-600 text-2xl"></i>
                    <p class="mt-2 text-sm text-slate-500">Loading customer details...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-black/50 transition-opacity" onclick="closeDeleteModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

        <div class="inline-block align-middle bg-white dark:bg-slate-800 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full">
            <div class="bg-white dark:bg-slate-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-rose-100 dark:bg-rose-900/30 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-exclamation-triangle text-rose-600 dark:text-rose-400"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-semibold text-slate-900 dark:text-white" id="modal-title">
                            Delete Customer
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-slate-500 dark:text-slate-400" id="deleteModalMessage">
                                Are you sure you want to delete this customer? This action cannot be undone.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-slate-50 dark:bg-slate-900/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <a href="#" id="confirmDeleteBtn" class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-rose-600 text-base font-medium text-white hover:bg-rose-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                    Delete
                </a>
                <button type="button" onclick="closeDeleteModal()" class="mt-3 w-full inline-flex justify-center rounded-lg border border-primary-300 dark:border-primary-600 shadow-sm px-4 py-2 bg-white dark:bg-slate-800 text-base font-medium text-slate-700 dark:text-slate-300 hover:bg-primary-50 dark:hover:bg-primary-800/30 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function viewCustomer(customerId) {
        document.getElementById('customerModal').classList.remove('hidden');

        // Load customer details via AJAX
        fetch(`get_customer.php?id=${customerId}`)
            .then(response => response.text())
            .then(html => {
                document.getElementById('customerModalContent').innerHTML = html;
            })
            .catch(error => {
                document.getElementById('customerModalContent').innerHTML = `
                <div class="p-8 text-center">
                    <i class="fas fa-exclamation-circle text-rose-600 text-3xl mb-3"></i>
                    <p class="text-sm text-slate-600">Error loading customer details</p>
                    <button onclick="closeCustomerModal()" class="mt-4 px-4 py-2 bg-primary-600 text-white rounded-lg text-sm">Close</button>
                </div>
            `;
            });
    }

    function closeCustomerModal() {
        document.getElementById('customerModal').classList.add('hidden');
    }

    function confirmDelete(customerId, customerName) {
        document.getElementById('deleteModal').classList.remove('hidden');
        document.getElementById('deleteModalMessage').innerHTML = `Are you sure you want to delete <strong>${customerName}</strong>? This action cannot be undone.`;
        document.getElementById('confirmDeleteBtn').href = `index.php?delete=${customerId}`;
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
    }

    function exportCustomers() {
        // Get current filters
        const search = document.querySelector('input[name="search"]').value;
        const dateRange = document.querySelector('select[name="date_range"]').value;
        const sort = document.querySelector('select[name="sort"]').value;

        window.location.href = `export_customers.php?search=${encodeURIComponent(search)}&date_range=${dateRange}&sort=${sort}`;
    }

    // Close modals on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeDeleteModal();
            closeCustomerModal();
        }
    });

    // Close modals when clicking outside
    window.onclick = function(event) {
        const deleteModal = document.getElementById('deleteModal');
        const customerModal = document.getElementById('customerModal');

        if (event.target === deleteModal) {
            closeDeleteModal();
        }
        if (event.target === customerModal) {
            closeCustomerModal();
        }
    }
</script>

<!-- Tooltip styles (add to your existing styles) -->
<style>
    .tooltip {
        position: relative;
        display: inline-block;
    }

    .tooltip .tooltip-text {
        visibility: hidden;
        position: absolute;
        z-index: 100;
        background: rgba(15, 23, 42, 0.95);
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
        white-space: nowrap;
        bottom: 125%;
        left: 50%;
        transform: translateX(-50%);
        opacity: 0;
        transition: opacity 0.2s;
        pointer-events: none;
    }

    .tooltip:hover .tooltip-text {
        visibility: visible;
        opacity: 1;
    }
</style>

<?php include '../includes/footer.php'; ?>