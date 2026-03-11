<?php
require_once '../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';

$id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT) : 0;

// Get order details with payment info
$stmt = $pdo->prepare("
    SELECT o.*, 
           c.name as customer_name, 
           c.phone as customer_phone,
           c.email as customer_email,
           c.created_at as customer_since,
           pay.payment_status,
           pay.paid_at
    FROM orders o
    LEFT JOIN customers c ON o.customer_id = c.id
    LEFT JOIN payments pay ON o.id = pay.order_id
    WHERE o.id = ?
");
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) {
    $_SESSION['error'] = "Order not found";
    header("Location: index.php");
    exit;
}

// Get order items
$stmt = $pdo->prepare("
    SELECT oi.*, p.product_name, p.image
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->execute([$id]);
$items = $stmt->fetchAll();

// Calculate subtotal
$subtotal = 0;
foreach ($items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

// Get stock movements for this order
$stmt = $pdo->prepare("
    SELECT * FROM stock_movements 
    WHERE reference = ? 
    ORDER BY created_at DESC
");
$stmt->execute(["Order #$id"]);
$movements = $stmt->fetchAll();

// Status colors
$status_colors = [
    'pending' => ['bg' => 'amber-100', 'text' => 'amber-700', 'dark_bg' => 'amber-900/30', 'dark_text' => 'amber-300', 'icon' => 'clock'],
    'processing' => ['bg' => 'blue-100', 'text' => 'blue-700', 'dark_bg' => 'blue-900/30', 'dark_text' => 'blue-300', 'icon' => 'cogs'],
    'completed' => ['bg' => 'emerald-100', 'text' => 'emerald-700', 'dark_bg' => 'emerald-900/30', 'dark_text' => 'emerald-300', 'icon' => 'check-circle'],
    'cancelled' => ['bg' => 'rose-100', 'text' => 'rose-700', 'dark_bg' => 'rose-900/30', 'dark_text' => 'rose-300', 'icon' => 'times-circle']
];
$status = $status_colors[$order['order_status']] ?? $status_colors['pending'];

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
                        <i class="fas fa-file-invoice text-primary-500 mr-2"></i>
                        Order #<?php echo $order['id']; ?> Details
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        <i class="far fa-calendar-alt mr-1"></i>
                        Placed on <?php echo date('F j, Y \a\t g:i A', strtotime($order['order_date'])); ?>
                    </p>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <a href="index.php" class="px-4 py-2 border border-primary-300 dark:border-primary-600 text-primary-700 dark:text-primary-300 rounded-xl text-sm font-semibold hover:bg-primary-50 dark:hover:bg-primary-800/30 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Orders
                </a>
                <a href="invoice.php?id=<?php echo $order['id']; ?>" target="_blank" 
                   class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-xl text-sm font-semibold transition-colors flex items-center">
                    <i class="fas fa-print mr-2"></i>
                    Print Invoice
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-1 p-6 bg-slate-50/50 dark:bg-primary-900/50">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column - Order Items -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Order Items Card -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 bakery-card">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center">
                        <i class="fas fa-shopping-bag text-primary-500 mr-2"></i>
                        Order Items
                    </h3>
                    
                    <div class="space-y-4">
                        <?php foreach ($items as $item): ?>
                        <div class="flex items-center justify-between p-3 bg-primary-50 dark:bg-primary-800/30 rounded-xl">
                            <div class="flex items-center space-x-3">
                                <div class="w-12 h-12 bg-gradient-to-br from-primary-600 to-secondary-500 rounded-lg flex items-center justify-center text-white">
                                    <i class="fas fa-<?php echo $item['product_name'] == 'Bread' ? 'bread-slice' : 'cookie-bite'; ?>"></i>
                                </div>
                                <div>
                                    <h4 class="text-sm font-semibold text-slate-900 dark:text-white">
                                        <?php echo htmlspecialchars($item['product_name']); ?>
                                    </h4>
                                    <p class="text-xs text-slate-500">
                                        KES <?php echo number_format($item['price'], 2); ?> × <?php echo $item['quantity']; ?>
                                    </p>
                                </div>
                            </div>
                            <p class="text-lg font-bold text-primary-600">
                                KES <?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                            </p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Stock Movements Card -->
                <?php if (!empty($movements)): ?>
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 bakery-card">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center">
                        <i class="fas fa-boxes text-primary-500 mr-2"></i>
                        Stock Movements
                    </h3>
                    
                    <div class="space-y-2">
                        <?php foreach ($movements as $movement): ?>
                        <div class="flex items-center justify-between p-2 bg-primary-50 dark:bg-primary-800/30 rounded-lg text-sm">
                            <span class="text-slate-600 dark:text-slate-400">
                                <i class="fas fa-arrow-down text-rose-500 mr-2"></i>
                                Product ID: <?php echo $movement['product_id']; ?>
                            </span>
                            <span class="font-medium">Quantity: <?php echo $movement['quantity']; ?></span>
                            <span class="text-xs text-slate-500"><?php echo date('M j, H:i', strtotime($movement['created_at'])); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Status Timeline -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 bakery-card">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center">
                        <i class="fas fa-history text-primary-500 mr-2"></i>
                        Order Timeline
                    </h3>
                    
                    <div class="space-y-4">
                        <!-- Order Placed -->
                        <div class="flex items-start space-x-3">
                            <div class="relative">
                                <div class="w-8 h-8 bg-emerald-100 dark:bg-emerald-900/30 rounded-full flex items-center justify-center">
                                    <i class="fas fa-check text-emerald-600"></i>
                                </div>
                                <div class="absolute top-8 left-4 w-0.5 h-12 bg-emerald-200 dark:bg-emerald-800"></div>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-900 dark:text-white">Order Placed</p>
                                <p class="text-xs text-slate-500"><?php echo date('F j, Y \a\t g:i A', strtotime($order['order_date'])); ?></p>
                            </div>
                        </div>
                        
                        <!-- Payment -->
                        <div class="flex items-start space-x-3">
                            <div class="relative">
                                <div class="w-8 h-8 <?php echo $order['payment_status'] == 'paid' ? 'bg-emerald-100 dark:bg-emerald-900/30' : 'bg-amber-100 dark:bg-amber-900/30'; ?> rounded-full flex items-center justify-center">
                                    <i class="fas fa-<?php echo $order['payment_method'] == 'mpesa' ? 'mobile-alt' : ($order['payment_method'] == 'card' ? 'credit-card' : 'money-bill-wave'); ?> text-<?php echo $order['payment_status'] == 'paid' ? 'emerald-600' : 'amber-600'; ?>"></i>
                                </div>
                                <?php if ($order['order_status'] != 'completed' && $order['order_status'] != 'cancelled'): ?>
                                <div class="absolute top-8 left-4 w-0.5 h-12 bg-slate-200 dark:bg-slate-700"></div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-900 dark:text-white">
                                    Payment: <?php echo ucfirst($order['payment_method'] ?? 'Not specified'); ?>
                                    <?php if ($order['payment_status'] == 'paid'): ?>
                                    <span class="ml-2 text-xs text-emerald-600">(Paid)</span>
                                    <?php else: ?>
                                    <span class="ml-2 text-xs text-amber-600">(Pending)</span>
                                    <?php endif; ?>
                                </p>
                                <?php if ($order['paid_at']): ?>
                                <p class="text-xs text-slate-500">Paid on <?php echo date('F j, Y \a\t g:i A', strtotime($order['paid_at'])); ?></p>
                                <?php else: ?>
                                <p class="text-xs text-slate-500">Awaiting payment</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Processing -->
                        <?php if (in_array($order['order_status'], ['processing', 'completed'])): ?>
                        <div class="flex items-start space-x-3">
                            <div class="relative">
                                <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
                                    <i class="fas fa-cogs text-blue-600"></i>
                                </div>
                                <?php if ($order['order_status'] == 'processing'): ?>
                                <div class="absolute top-8 left-4 w-0.5 h-12 bg-blue-200 dark:bg-blue-800"></div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-900 dark:text-white">Processing</p>
                                <p class="text-xs text-slate-500">Order is being prepared</p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Completed -->
                        <?php if ($order['order_status'] == 'completed'): ?>
                        <div class="flex items-start space-x-3">
                            <div class="w-8 h-8 bg-emerald-100 dark:bg-emerald-900/30 rounded-full flex items-center justify-center">
                                <i class="fas fa-check-double text-emerald-600"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-900 dark:text-white">Completed</p>
                                <p class="text-xs text-slate-500">Order has been fulfilled</p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Cancelled -->
                        <?php if ($order['order_status'] == 'cancelled'): ?>
                        <div class="flex items-start space-x-3">
                            <div class="w-8 h-8 bg-rose-100 dark:bg-rose-900/30 rounded-full flex items-center justify-center">
                                <i class="fas fa-times-circle text-rose-600"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-900 dark:text-white">Cancelled</p>
                                <p class="text-xs text-slate-500">Order was cancelled</p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column - Order Summary & Customer Info -->
            <div class="space-y-6">
                <!-- Status Update Card -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 bakery-card">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center">
                        <i class="fas fa-edit text-primary-500 mr-2"></i>
                        Update Status
                    </h3>
                    
                    <form method="POST" action="index.php" class="space-y-3">
                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                        <div class="grid grid-cols-2 gap-2">
                            <button type="submit" name="update_status" value="1" onclick="this.form.status.value='pending'"
                                    class="px-3 py-3 bg-amber-100 hover:bg-amber-200 dark:bg-amber-900/30 dark:hover:bg-amber-800/50 text-amber-700 dark:text-amber-300 rounded-xl text-sm font-medium transition-colors flex items-center justify-center space-x-2 <?php echo $order['order_status'] == 'pending' ? 'ring-2 ring-amber-500' : ''; ?>">
                                <i class="fas fa-clock"></i>
                                <span>Pending</span>
                            </button>
                            <button type="submit" name="update_status" value="1" onclick="this.form.status.value='processing'"
                                    class="px-3 py-3 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900/30 dark:hover:bg-blue-800/50 text-blue-700 dark:text-blue-300 rounded-xl text-sm font-medium transition-colors flex items-center justify-center space-x-2 <?php echo $order['order_status'] == 'processing' ? 'ring-2 ring-blue-500' : ''; ?>">
                                <i class="fas fa-cogs"></i>
                                <span>Processing</span>
                            </button>
                            <button type="submit" name="update_status" value="1" onclick="this.form.status.value='completed'"
                                    class="px-3 py-3 bg-emerald-100 hover:bg-emerald-200 dark:bg-emerald-900/30 dark:hover:bg-emerald-800/50 text-emerald-700 dark:text-emerald-300 rounded-xl text-sm font-medium transition-colors flex items-center justify-center space-x-2 <?php echo $order['order_status'] == 'completed' ? 'ring-2 ring-emerald-500' : ''; ?>">
                                <i class="fas fa-check-circle"></i>
                                <span>Completed</span>
                            </button>
                            <button type="submit" name="update_status" value="1" onclick="this.form.status.value='cancelled'"
                                    class="px-3 py-3 bg-rose-100 hover:bg-rose-200 dark:bg-rose-900/30 dark:hover:bg-rose-800/50 text-rose-700 dark:text-rose-300 rounded-xl text-sm font-medium transition-colors flex items-center justify-center space-x-2 <?php echo $order['order_status'] == 'cancelled' ? 'ring-2 ring-rose-500' : ''; ?>">
                                <i class="fas fa-times-circle"></i>
                                <span>Cancelled</span>
                            </button>
                        </div>
                        <input type="hidden" name="status" value="">
                    </form>
                </div>

                <!-- Order Summary Card -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 bakery-card">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center">
                        <i class="fas fa-receipt text-primary-500 mr-2"></i>
                        Order Summary
                    </h3>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-600 dark:text-slate-400">Subtotal</span>
                            <span class="font-medium text-slate-900 dark:text-white">KES <?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        <div class="border-t border-primary-200 dark:border-primary-700 pt-3 flex justify-between">
                            <span class="font-semibold text-slate-900 dark:text-white">Total</span>
                            <span class="text-xl font-bold text-primary-600">KES <?php echo number_format($order['total_amount'], 2); ?></span>
                        </div>
                        
                        <?php if ($order['payment_status'] == 'paid'): ?>
                        <div class="mt-2 p-2 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg text-center">
                            <i class="fas fa-check-circle text-emerald-600 mr-1"></i>
                            <span class="text-sm text-emerald-700 dark:text-emerald-300">Payment received</span>
                        </div>
                        <?php else: ?>
                        <div class="mt-2 p-2 bg-amber-50 dark:bg-amber-900/20 rounded-lg text-center">
                            <i class="fas fa-clock text-amber-600 mr-1"></i>
                            <span class="text-sm text-amber-700 dark:text-amber-300">Payment pending</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Customer Information Card -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 bakery-card">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center">
                        <i class="fas fa-user-circle text-primary-500 mr-2"></i>
                        Customer Information
                    </h3>
                    
                    <div class="space-y-3">
                        <div class="flex items-center space-x-3 p-3 bg-primary-50 dark:bg-primary-800/30 rounded-xl">
                            <div class="w-10 h-10 bg-gradient-to-br from-primary-600 to-secondary-500 rounded-full flex items-center justify-center text-white font-bold">
                                <?php 
                                $name = $order['customer_name'] ?? 'WC';
                                $initials = '';
                                $words = explode(' ', $name);
                                foreach ($words as $w) {
                                    if (!empty($w)) $initials .= strtoupper(substr($w, 0, 1));
                                }
                                echo substr($initials, 0, 2);
                                ?>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">
                                    <?php echo htmlspecialchars($order['customer_name'] ?? 'Walk-in Customer'); ?>
                                </p>
                                <p class="text-xs text-slate-500">
                                    <?php echo $order['customer_since'] ? 'Customer since ' . date('M Y', strtotime($order['customer_since'])) : 'Guest checkout'; ?>
                                </p>
                            </div>
                        </div>
                        
                        <?php if ($order['customer_email']): ?>
                        <div class="flex items-center space-x-3 text-sm">
                            <i class="fas fa-envelope text-primary-500 w-5"></i>
                            <span class="text-slate-600 dark:text-slate-400"><?php echo htmlspecialchars($order['customer_email']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($order['customer_phone']): ?>
                        <div class="flex items-center space-x-3 text-sm">
                            <i class="fas fa-phone-alt text-secondary-500 w-5"></i>
                            <span class="text-slate-600 dark:text-slate-400"><?php echo htmlspecialchars($order['customer_phone']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($order['delivery_address']): ?>
                        <div class="flex items-start space-x-3 text-sm">
                            <i class="fas fa-map-marker-alt text-accent-500 w-5 mt-0.5"></i>
                            <span class="text-slate-600 dark:text-slate-400"><?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?></span>
                        </div>
                        <?php else: ?>
                        <div class="flex items-center space-x-3 text-sm">
                            <i class="fas fa-store text-emerald-500 w-5"></i>
                            <span class="text-slate-600 dark:text-slate-400">Will pick up from bakery</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Payment Details Card -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 bakery-card">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center">
                        <i class="fas fa-credit-card text-primary-500 mr-2"></i>
                        Payment Details
                    </h3>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-600 dark:text-slate-400">Method</span>
                            <span class="font-medium flex items-center">
                                <i class="fas fa-<?php echo $order['payment_method'] == 'cash' ? 'money-bill-wave' : ($order['payment_method'] == 'mpesa' ? 'mobile-alt' : 'credit-card'); ?> mr-2 text-<?php echo $order['payment_method'] == 'cash' ? 'emerald' : ($order['payment_method'] == 'mpesa' ? 'secondary' : 'blue'); ?>-500"></i>
                                <?php echo ucfirst($order['payment_method'] ?? 'Not specified'); ?>
                            </span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-600 dark:text-slate-400">Status</span>
                            <span class="font-medium <?php echo $order['payment_status'] == 'paid' ? 'text-emerald-600' : 'text-amber-600'; ?>">
                                <?php echo ucfirst($order['payment_status'] ?? 'pending'); ?>
                            </span>
                        </div>
                        <?php if ($order['paid_at']): ?>
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-600 dark:text-slate-400">Paid on</span>
                            <span class="font-medium"><?php echo date('M j, Y \a\t g:i A', strtotime($order['paid_at'])); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>