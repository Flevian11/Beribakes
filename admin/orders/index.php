<?php
require_once '../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';

// Handle order status update
if (isset($_POST['update_status'])) {
    $order_id = filter_var($_POST['order_id'], FILTER_SANITIZE_NUMBER_INT);
    $new_status = filter_var($_POST['status'], FILTER_SANITIZE_STRING);
    
    try {
        $pdo->beginTransaction();
        
        // Update order status
        $stmt = $pdo->prepare("UPDATE orders SET order_status = ? WHERE id = ?");
        $stmt->execute([$new_status, $order_id]);
        
        // If order is completed, update payment status and create ledger entry
        if ($new_status == 'completed') {
            // Update payment status
            $stmt = $pdo->prepare("UPDATE payments SET payment_status = 'paid', paid_at = NOW() WHERE order_id = ?");
            $stmt->execute([$order_id]);
            
            // Get order details for ledger
            $stmt = $pdo->prepare("SELECT total_amount, payment_method FROM orders WHERE id = ?");
            $stmt->execute([$order_id]);
            $order = $stmt->fetch();
            
            // Create ledger transaction for sales
            // Determine account based on payment method
            $account_id = ($order['payment_method'] == 'cash') ? 1 : 2; // 1=Cash, 2=M-Pesa
            
            $stmt = $pdo->prepare("
                INSERT INTO ledger_transactions (account_id, transaction_type, amount, reference_type, reference_id, description) 
                VALUES (?, 'credit', ?, 'order', ?, 'Payment for order #' . ?)
            ");
            $stmt->execute([$account_id, $order['total_amount'], $order_id, $order_id]);
        }
        
        // Log activity
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'] ?? 1, "Updated order #$order_id status to $new_status"]);
        
        $pdo->commit();
        
        $_SESSION['success'] = "Order #$order_id status updated to " . ucfirst($new_status);
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error updating order: " . $e->getMessage();
    }
    
    header("Location: index.php");
    exit;
}

// Handle order deletion
if (isset($_GET['delete'])) {
    $id = filter_var($_GET['delete'], FILTER_SANITIZE_NUMBER_INT);
    
    try {
        $pdo->beginTransaction();
        
        // Check if order has payments
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE order_id = ?");
        $stmt->execute([$id]);
        $payment_count = $stmt->fetchColumn();
        
        if ($payment_count > 0) {
            $_SESSION['error'] = "Cannot delete order: It has associated payments.";
            header("Location: index.php");
            exit;
        }
        
        // Delete order items first
        $stmt = $pdo->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmt->execute([$id]);
        
        // Delete order
        $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->execute([$id]);
        
        // Log activity
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'] ?? 1, "Deleted order #$id"]);
        
        $pdo->commit();
        $_SESSION['success'] = "Order deleted successfully!";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error deleting order: " . $e->getMessage();
    }
    
    header("Location: index.php");
    exit;
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$date_filter = isset($_GET['date_range']) ? $_GET['date_range'] : 'today';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$payment_filter = isset($_GET['payment']) ? $_GET['payment'] : 'all';

// Build query with filters
$sql = "SELECT o.*, 
        c.name as customer_name, 
        c.phone as customer_phone,
        c.email as customer_email,
        COUNT(oi.id) as item_count,
        COALESCE(SUM(oi.quantity), 0) as total_items,
        GROUP_CONCAT(p.product_name SEPARATOR ', ') as product_names,
        pay.payment_status
        FROM orders o
        LEFT JOIN customers c ON o.customer_id = c.id
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.id
        LEFT JOIN payments pay ON o.id = pay.order_id
        WHERE 1=1";
$params = [];

// Status filter
if ($status_filter != 'all') {
    $sql .= " AND o.order_status = ?";
    $params[] = $status_filter;
}

// Payment method filter
if ($payment_filter != 'all') {
    $sql .= " AND o.payment_method = ?";
    $params[] = $payment_filter;
}

// Date filter
if ($date_filter == 'today') {
    $sql .= " AND DATE(o.order_date) = CURDATE()";
} elseif ($date_filter == 'yesterday') {
    $sql .= " AND DATE(o.order_date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
} elseif ($date_filter == 'week') {
    $sql .= " AND o.order_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
} elseif ($date_filter == 'month') {
    $sql .= " AND o.order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
}

// Search
if ($search) {
    $sql .= " AND (o.id LIKE ? OR c.name LIKE ? OR c.phone LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$sql .= " GROUP BY o.id ORDER BY o.order_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Get order statistics
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_orders,
        COUNT(CASE WHEN order_status = 'pending' THEN 1 END) as pending_count,
        COUNT(CASE WHEN order_status = 'processing' THEN 1 END) as processing_count,
        COUNT(CASE WHEN order_status = 'completed' THEN 1 END) as completed_count,
        COUNT(CASE WHEN order_status = 'cancelled' THEN 1 END) as cancelled_count,
        COALESCE(SUM(CASE WHEN order_status = 'completed' THEN total_amount END), 0) as completed_revenue,
        COALESCE(SUM(total_amount), 0) as total_revenue,
        COUNT(DISTINCT customer_id) as unique_customers,
        AVG(total_amount) as avg_order_value
    FROM orders
    WHERE DATE(order_date) = CURDATE()");
$today_stats = $stmt->fetch();

// Get status colors for badges
$status_colors = [
    'pending' => ['bg' => 'amber-100', 'text' => 'amber-700', 'dark_bg' => 'amber-900/30', 'dark_text' => 'amber-300', 'icon' => 'clock', 'label' => 'Pending'],
    'processing' => ['bg' => 'blue-100', 'text' => 'blue-700', 'dark_bg' => 'blue-900/30', 'dark_text' => 'blue-300', 'icon' => 'cogs', 'label' => 'Processing'],
    'completed' => ['bg' => 'emerald-100', 'text' => 'emerald-700', 'dark_bg' => 'emerald-900/30', 'dark_text' => 'emerald-300', 'icon' => 'check-circle', 'label' => 'Completed'],
    'cancelled' => ['bg' => 'rose-100', 'text' => 'rose-700', 'dark_bg' => 'rose-900/30', 'dark_text' => 'rose-300', 'icon' => 'times-circle', 'label' => 'Cancelled']
];

// Get payment method colors
$payment_colors = [
    'cash' => ['bg' => 'emerald-100', 'text' => 'emerald-700', 'dark_bg' => 'emerald-900/30', 'dark_text' => 'emerald-300', 'icon' => 'money-bill-wave'],
    'mpesa' => ['bg' => 'secondary-100', 'text' => 'secondary-700', 'dark_bg' => 'secondary-900/30', 'dark_text' => 'secondary-300', 'icon' => 'mobile-alt'],
    'card' => ['bg' => 'blue-100', 'text' => 'blue-700', 'dark_bg' => 'blue-900/30', 'dark_text' => 'blue-300', 'icon' => 'credit-card']
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
                        <i class="fas fa-truck text-primary-500 mr-2"></i>
                        Order Management
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        <i class="far fa-clock mr-1"></i>
                        Track, manage, and process customer orders
                    </p>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <a href="add.php" class="px-4 py-2 bg-gradient-to-r from-primary-600 to-secondary-500 text-white rounded-xl text-sm font-semibold hover:shadow-lg transition-all flex items-center">
                    <i class="fas fa-plus-circle mr-2"></i>
                    New Order
                </a>
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

        <!-- Today's Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-4 mb-6">
            <div class="bg-white dark:bg-slate-800 rounded-xl p-4 bakery-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-500">Today's Orders</p>
                        <h4 class="text-xl font-bold text-primary-600"><?php echo $today_stats['total_orders'] ?? 0; ?></h4>
                    </div>
                    <div class="w-10 h-10 bg-primary-100 dark:bg-primary-800/50 rounded-lg flex items-center justify-center">
                        <i class="fas fa-shopping-bag text-primary-600"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-slate-800 rounded-xl p-4 bakery-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-500">Pending</p>
                        <h4 class="text-xl font-bold text-amber-600"><?php echo $today_stats['pending_count'] ?? 0; ?></h4>
                    </div>
                    <div class="w-10 h-10 bg-amber-100 dark:bg-amber-800/50 rounded-lg flex items-center justify-center">
                        <i class="fas fa-clock text-amber-600"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-slate-800 rounded-xl p-4 bakery-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-500">Processing</p>
                        <h4 class="text-xl font-bold text-blue-600"><?php echo $today_stats['processing_count'] ?? 0; ?></h4>
                    </div>
                    <div class="w-10 h-10 bg-blue-100 dark:bg-blue-800/50 rounded-lg flex items-center justify-center">
                        <i class="fas fa-cogs text-blue-600"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-slate-800 rounded-xl p-4 bakery-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-500">Completed</p>
                        <h4 class="text-xl font-bold text-emerald-600"><?php echo $today_stats['completed_count'] ?? 0; ?></h4>
                    </div>
                    <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-800/50 rounded-lg flex items-center justify-center">
                        <i class="fas fa-check-circle text-emerald-600"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-slate-800 rounded-xl p-4 bakery-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-500">Today's Revenue</p>
                        <h4 class="text-xl font-bold text-secondary-600">KES <?php echo number_format($today_stats['completed_revenue'] ?? 0, 2); ?></h4>
                    </div>
                    <div class="w-10 h-10 bg-secondary-100 dark:bg-secondary-800/50 rounded-lg flex items-center justify-center">
                        <i class="fas fa-coins text-secondary-600"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-slate-800 rounded-xl p-4 bakery-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-500">Avg Order Value</p>
                        <h4 class="text-xl font-bold text-accent-600">KES <?php echo number_format($today_stats['avg_order_value'] ?? 0, 2); ?></h4>
                    </div>
                    <div class="w-10 h-10 bg-accent-100 dark:bg-accent-800/50 rounded-lg flex items-center justify-center">
                        <i class="fas fa-chart-line text-accent-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters and Search -->
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 bakery-card mb-6">
            <form method="GET" class="flex flex-wrap items-center gap-4">
                <div class="flex-1 min-w-[200px]">
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400"></i>
                        <input type="text" name="search" placeholder="Search by order #, customer, or phone..." 
                               value="<?php echo htmlspecialchars($search); ?>"
                               class="w-full pl-10 pr-4 py-2.5 border border-primary-200 dark:border-primary-700 rounded-lg bg-white dark:bg-slate-900 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    </div>
                </div>
                
                <div class="w-40">
                    <select name="status" class="w-full px-3 py-2.5 border border-primary-200 dark:border-primary-700 rounded-lg bg-white dark:bg-slate-900 text-sm focus:ring-2 focus:ring-primary-500">
                        <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="processing" <?php echo $status_filter == 'processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                
                <div class="w-40">
                    <select name="payment" class="w-full px-3 py-2.5 border border-primary-200 dark:border-primary-700 rounded-lg bg-white dark:bg-slate-900 text-sm focus:ring-2 focus:ring-primary-500">
                        <option value="all" <?php echo $payment_filter == 'all' ? 'selected' : ''; ?>>All Payments</option>
                        <option value="cash" <?php echo $payment_filter == 'cash' ? 'selected' : ''; ?>>Cash</option>
                        <option value="mpesa" <?php echo $payment_filter == 'mpesa' ? 'selected' : ''; ?>>M-Pesa</option>
                        <option value="card" <?php echo $payment_filter == 'card' ? 'selected' : ''; ?>>Card</option>
                    </select>
                </div>
                
                <div class="w-40">
                    <select name="date_range" class="w-full px-3 py-2.5 border border-primary-200 dark:border-primary-700 rounded-lg bg-white dark:bg-slate-900 text-sm focus:ring-2 focus:ring-primary-500">
                        <option value="today" <?php echo $date_filter == 'today' ? 'selected' : ''; ?>>Today</option>
                        <option value="yesterday" <?php echo $date_filter == 'yesterday' ? 'selected' : ''; ?>>Yesterday</option>
                        <option value="week" <?php echo $date_filter == 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                        <option value="month" <?php echo $date_filter == 'month' ? 'selected' : ''; ?>>Last 30 Days</option>
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

        <!-- Orders List -->
        <?php if (empty($orders)): ?>
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-12 text-center bakery-card">
            <div class="w-24 h-24 bg-primary-100 dark:bg-primary-800/30 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-truck text-primary-500 text-4xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">No Orders Found</h3>
            <p class="text-slate-500 dark:text-slate-400 mb-4">No orders match your current filters</p>
            <a href="add.php" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-primary-600 to-secondary-500 text-white rounded-xl text-sm font-semibold hover:shadow-lg transition-all">
                <i class="fas fa-plus-circle mr-2"></i>
                Create New Order
            </a>
        </div>
        <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($orders as $order): 
                $status = $status_colors[$order['order_status']] ?? $status_colors['pending'];
                $payment = $payment_colors[$order['payment_method']] ?? ['bg' => 'slate-100', 'text' => 'slate-700', 'dark_bg' => 'slate-800/50', 'dark_text' => 'slate-400', 'icon' => 'question'];
                $product_list = $order['product_names'] ? explode(', ', $order['product_names']) : [];
                $product_preview = count($product_list) > 2 ? array_slice($product_list, 0, 2) : $product_list;
            ?>
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 bakery-card hover:shadow-lg transition-all">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <!-- Order Info -->
                    <div class="flex items-start space-x-4 flex-1">
                        <div class="w-12 h-12 bg-gradient-to-br from-primary-600 to-secondary-500 rounded-xl flex items-center justify-center text-white font-bold text-lg">
                            #<?php echo $order['id']; ?>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center flex-wrap gap-2 mb-1">
                                <h4 class="text-base font-semibold text-slate-900 dark:text-white">
                                    Order #<?php echo $order['id']; ?>
                                </h4>
                                <span class="px-2 py-1 bg-<?php echo $status['bg']; ?> dark:bg-<?php echo $status['dark_bg']; ?> text-<?php echo $status['text']; ?> dark:text-<?php echo $status['dark_text']; ?> rounded-full text-xs flex items-center">
                                    <i class="fas fa-<?php echo $status['icon']; ?> mr-1"></i>
                                    <?php echo $status['label']; ?>
                                </span>
                                <span class="px-2 py-1 bg-<?php echo $payment['bg']; ?> dark:bg-<?php echo $payment['dark_bg']; ?> text-<?php echo $payment['text']; ?> dark:text-<?php echo $payment['dark_text']; ?> rounded-full text-xs flex items-center">
                                    <i class="fas fa-<?php echo $payment['icon']; ?> mr-1"></i>
                                    <?php echo ucfirst($order['payment_method'] ?? 'Not set'); ?>
                                </span>
                                <?php if ($order['payment_status'] == 'paid'): ?>
                                <span class="px-2 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 rounded-full text-xs flex items-center">
                                    <i class="fas fa-check-circle mr-1"></i>
                                    Paid
                                </span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Customer Info -->
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm mb-2">
                                <span class="text-slate-600 dark:text-slate-400">
                                    <i class="fas fa-user text-primary-500 mr-1 text-xs"></i>
                                    <?php echo htmlspecialchars($order['customer_name'] ?? 'Walk-in Customer'); ?>
                                </span>
                                <?php if ($order['customer_phone']): ?>
                                <span class="text-slate-600 dark:text-slate-400">
                                    <i class="fas fa-phone text-secondary-500 mr-1 text-xs"></i>
                                    <?php echo htmlspecialchars($order['customer_phone']); ?>
                                </span>
                                <?php endif; ?>
                                <span class="text-slate-600 dark:text-slate-400">
                                    <i class="far fa-clock text-accent-500 mr-1 text-xs"></i>
                                    <?php echo date('M j, Y \a\t g:i A', strtotime($order['order_date'])); ?>
                                </span>
                            </div>
                            
                            <!-- Products Preview -->
                            <?php if (!empty($product_preview)): ?>
                            <div class="flex flex-wrap items-center gap-2">
                                <?php foreach ($product_preview as $product): ?>
                                <span class="text-xs bg-primary-50 dark:bg-primary-800/30 text-primary-700 dark:text-primary-300 px-2 py-1 rounded-full">
                                    <?php echo htmlspecialchars($product); ?>
                                </span>
                                <?php endforeach; ?>
                                <?php if (count($product_list) > 2): ?>
                                <span class="text-xs text-slate-500">+<?php echo count($product_list) - 2; ?> more</span>
                                <?php endif; ?>
                                <span class="text-xs text-slate-500">
                                    (<?php echo $order['total_items']; ?> items)
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Order Total and Actions -->
                    <div class="flex items-center justify-between lg:flex-col lg:items-end gap-3 lg:gap-1">
                        <div class="text-right">
                            <p class="text-xs text-slate-500">Total Amount</p>
                            <p class="text-xl font-bold text-primary-600 dark:text-primary-400">
                                KES <?php echo number_format($order['total_amount'], 2); ?>
                            </p>
                        </div>
                        
                        <div class="flex items-center space-x-2">
                            <!-- Quick Status Update Dropdown -->
                            <div class="relative group">
                                <button class="p-2 bg-primary-100 hover:bg-primary-200 dark:bg-primary-800/50 dark:hover:bg-primary-700 rounded-lg transition-colors" title="Update Status">
                                    <i class="fas fa-edit text-primary-700 dark:text-primary-300"></i>
                                </button>
                                <div class="absolute right-0 bottom-full mb-2 hidden group-hover:block hover:block z-50">
                                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-primary-200 dark:border-primary-700 p-2 min-w-[160px]">
                                        <form method="POST" class="space-y-1">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <?php foreach (['pending', 'processing', 'completed', 'cancelled'] as $status_option): ?>
                                            <button type="submit" name="update_status" value="1" 
                                                    onclick="this.form.status.value='<?php echo $status_option; ?>'"
                                                    class="w-full text-left px-3 py-2 text-sm rounded-lg hover:bg-primary-100 dark:hover:bg-primary-700/50 transition-colors flex items-center space-x-2 <?php echo $order['order_status'] == $status_option ? 'bg-primary-100 dark:bg-primary-700/50' : ''; ?>">
                                                <i class="fas fa-<?php 
                                                    echo $status_option == 'pending' ? 'clock' : 
                                                        ($status_option == 'processing' ? 'cogs' : 
                                                        ($status_option == 'completed' ? 'check-circle' : 'times-circle')); 
                                                ?> w-4 text-<?php 
                                                    echo $status_option == 'pending' ? 'amber' : 
                                                        ($status_option == 'processing' ? 'blue' : 
                                                        ($status_option == 'completed' ? 'emerald' : 'rose')); 
                                                ?>-500"></i>
                                                <span><?php echo ucfirst($status_option); ?></span>
                                            </button>
                                            <?php endforeach; ?>
                                            <input type="hidden" name="status" value="">
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-2">

<a href="view.php?id=<?php echo $order['id']; ?>"
class="px-2.5 py-1 text-[11px] font-semibold bg-primary-600 hover:bg-primary-700 text-white rounded-full transition whitespace-nowrap">

View

</a>

<a href="invoice.php?id=<?php echo $order['id']; ?>" target="_blank"
class="px-2.5 py-1 text-[11px] font-semibold bg-secondary-200 hover:bg-secondary-300 dark:bg-secondary-800 dark:hover:bg-secondary-700 text-secondary-800 dark:text-secondary-200 rounded-full transition whitespace-nowrap">

Invoice

</a>

<?php if ($order['order_status'] != 'completed'): ?>

<button onclick="confirmDelete(<?php echo $order['id']; ?>, <?php echo $order['id']; ?>)"
class="px-2.5 py-1 text-[11px] font-semibold bg-rose-200 hover:bg-rose-300 dark:bg-rose-900/40 dark:hover:bg-rose-800 text-rose-800 dark:text-rose-300 rounded-full transition whitespace-nowrap">

Delete

</button>

<?php endif; ?>

</div>

                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Orders Summary -->
        <div class="mt-4 text-sm text-slate-500 dark:text-slate-400 flex items-center justify-between">
            <span>Showing <?php echo count($orders); ?> orders</span>
            <span class="font-medium">Total: KES <?php echo number_format(array_sum(array_column($orders, 'total_amount')), 2); ?></span>
        </div>
        <?php endif; ?>
    </main>
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
                            Delete Order
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-slate-500 dark:text-slate-400" id="deleteModalMessage">
                                Are you sure you want to delete this order? This action cannot be undone.
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
function confirmDelete(orderId, orderNumber) {
    document.getElementById('deleteModal').classList.remove('hidden');
    document.getElementById('deleteModalMessage').innerHTML = `Are you sure you want to delete <strong>Order #${orderNumber}</strong>? This action cannot be undone.`;
    document.getElementById('confirmDeleteBtn').href = `index.php?delete=${orderId}`;
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDeleteModal();
    }
});

// Handle status update form submission
document.querySelectorAll('form[method="POST"]').forEach(form => {
    form.addEventListener('submit', function(e) {
        const statusInput = this.querySelector('input[name="status"]');
        if (!statusInput.value) {
            e.preventDefault();
        }
    });
});
</script>

<!-- Tooltip styles -->
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

.group:hover .group-hover\:block {
    display: block;
}
</style>

<?php include '../includes/footer.php'; ?>