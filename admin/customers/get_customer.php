<?php
require_once '../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';

$id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT) : 0;

try {
    // Get customer details
    $stmt = $pdo->prepare("
        SELECT c.*, 
               COUNT(DISTINCT o.id) as total_orders,
               COALESCE(SUM(o.total_amount), 0) as total_spent,
               MAX(o.order_date) as last_order_date
        FROM customers c
        LEFT JOIN orders o ON c.id = o.customer_id
        WHERE c.id = ?
        GROUP BY c.id
    ");
    $stmt->execute([$id]);
    $customer = $stmt->fetch();
    
    if (!$customer) {
        throw new Exception("Customer not found");
    }
    
    // Get recent orders
    $stmt = $pdo->prepare("
        SELECT o.*, COUNT(oi.id) as item_count
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.customer_id = ?
        GROUP BY o.id
        ORDER BY o.order_date DESC
        LIMIT 5
    ");
    $stmt->execute([$id]);
    $recent_orders = $stmt->fetchAll();
    
} catch (Exception $e) {
    echo '<div class="p-8 text-center">
            <i class="fas fa-exclamation-circle text-rose-600 text-3xl mb-3"></i>
            <p class="text-sm text-slate-600">Error loading customer details</p>
            <button onclick="closeCustomerModal()" class="mt-4 px-4 py-2 bg-primary-600 text-white rounded-lg text-sm">Close</button>
          </div>';
    exit;
}
?>

<div class="p-6">
    <!-- Modal Header -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center space-x-3">
            <div class="w-12 h-12 bg-gradient-to-br from-primary-600 to-secondary-500 rounded-full flex items-center justify-center text-white text-lg font-bold">
                <?php 
                $name = $customer['name'] ?? 'U';
                $initials = '';
                $words = explode(' ', $name);
                foreach ($words as $w) {
                    if (!empty($w)) $initials .= strtoupper(substr($w, 0, 1));
                }
                echo substr($initials, 0, 2);
                ?>
            </div>
            <div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white">
                    <?php echo htmlspecialchars($customer['name']); ?>
                </h3>
                <p class="text-sm text-slate-500">
                    Customer since <?php echo date('F j, Y', strtotime($customer['created_at'])); ?>
                </p>
            </div>
        </div>
        <button onclick="closeCustomerModal()" class="p-2 hover:bg-primary-100 dark:hover:bg-primary-800 rounded-lg transition-colors">
            <i class="fas fa-times text-slate-400"></i>
        </button>
    </div>

    <!-- Customer Stats -->
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-primary-50 dark:bg-primary-800/30 rounded-xl p-4 text-center">
            <p class="text-2xs text-slate-500">Total Orders</p>
            <p class="text-2xl font-bold text-primary-600"><?php echo $customer['total_orders']; ?></p>
        </div>
        <div class="bg-secondary-50 dark:bg-secondary-800/30 rounded-xl p-4 text-center">
            <p class="text-2xs text-slate-500">Total Spent</p>
            <p class="text-2xl font-bold text-secondary-600">KES <?php echo number_format($customer['total_spent'], 2); ?></p>
        </div>
        <div class="bg-accent-50 dark:bg-accent-800/30 rounded-xl p-4 text-center">
            <p class="text-2xs text-slate-500">Last Order</p>
            <p class="text-lg font-bold text-accent-600">
                <?php echo $customer['last_order_date'] ? date('M j', strtotime($customer['last_order_date'])) : 'Never'; ?>
            </p>
        </div>
    </div>

    <!-- Contact Information -->
    <div class="mb-6">
        <h4 class="text-sm font-semibold text-slate-900 dark:text-white mb-3 flex items-center">
            <i class="fas fa-address-card text-primary-500 mr-2"></i>
            Contact Information
        </h4>
        <div class="grid grid-cols-2 gap-4">
            <div class="p-3 border border-primary-200 dark:border-primary-700 rounded-lg">
                <p class="text-2xs text-slate-500 mb-1">Email Address</p>
                <p class="text-sm text-slate-900 dark:text-white">
                    <i class="fas fa-envelope text-primary-500 mr-2"></i>
                    <?php echo htmlspecialchars($customer['email'] ?? 'N/A'); ?>
                </p>
            </div>
            <div class="p-3 border border-primary-200 dark:border-primary-700 rounded-lg">
                <p class="text-2xs text-slate-500 mb-1">Phone Number</p>
                <p class="text-sm text-slate-900 dark:text-white">
                    <i class="fas fa-phone-alt text-secondary-500 mr-2"></i>
                    <?php echo htmlspecialchars($customer['phone'] ?? 'N/A'); ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Recent Orders -->
    <?php if (!empty($recent_orders)): ?>
    <div>
        <h4 class="text-sm font-semibold text-slate-900 dark:text-white mb-3 flex items-center">
            <i class="fas fa-shopping-bag text-primary-500 mr-2"></i>
            Recent Orders
        </h4>
        <div class="space-y-3">
            <?php foreach ($recent_orders as $order): ?>
            <div class="flex items-center justify-between p-3 bg-primary-50 dark:bg-primary-800/30 rounded-lg">
                <div>
                    <p class="text-sm font-medium text-slate-900 dark:text-white">
                        Order #<?php echo $order['id']; ?>
                    </p>
                    <p class="text-xs text-slate-500">
                        <?php echo date('M j, Y \a\t g:i A', strtotime($order['order_date'])); ?>
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-sm font-bold text-primary-600">
                        KES <?php echo number_format($order['total_amount'], 2); ?>
                    </p>
                    <span class="inline-block px-2 py-0.5 text-2xs rounded-full 
                        <?php echo $order['order_status'] == 'completed' ? 'bg-emerald-100 text-emerald-700' : 
                              ($order['order_status'] == 'pending' ? 'bg-amber-100 text-amber-700' : 
                               'bg-blue-100 text-blue-700'); ?>">
                        <?php echo ucfirst($order['order_status']); ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($customer['total_orders'] > 5): ?>
        <div class="mt-4 text-center">
            <a href="orders/index.php?customer_id=<?php echo $customer['id']; ?>" class="text-sm text-primary-600 hover:underline">
                View all <?php echo $customer['total_orders']; ?> orders <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Modal Footer -->
    <div class="mt-6 pt-4 border-t border-primary-200 dark:border-primary-700 flex justify-end space-x-3">
        <button onclick="closeCustomerModal()" class="px-4 py-2 border border-primary-300 dark:border-primary-600 text-primary-700 dark:text-primary-300 rounded-lg text-sm hover:bg-primary-50 dark:hover:bg-primary-800/30 transition-colors">
            Close
        </button>
        <a href="mailto:<?php echo $customer['email']; ?>" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm transition-colors flex items-center">
            <i class="fas fa-envelope mr-2"></i>
            Send Email
        </a>
    </div>
</div>