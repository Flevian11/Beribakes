<?php
require_once '../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';

// Get products for dropdown (only those with stock > 0)
$stmt = $pdo->query("SELECT p.*, c.category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.stock > 0 ORDER BY p.product_name");
$products = $stmt->fetchAll();

// Get customers for dropdown
$stmt = $pdo->query("SELECT id, name, phone, email FROM customers ORDER BY name");
$customers = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = !empty($_POST['customer_id']) ? filter_var($_POST['customer_id'], FILTER_SANITIZE_NUMBER_INT) : null;
    $payment_method = filter_var($_POST['payment_method'], FILTER_SANITIZE_STRING);
    $delivery_address = trim($_POST['delivery_address'] ?? '');
    $order_status = 'pending';
    
    $product_ids = $_POST['product_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    
    $errors = [];
    
    // Validate payment method
    if (!in_array($payment_method, ['cash', 'mpesa', 'card'])) {
        $errors[] = "Please select a valid payment method";
    }
    
    // Validate at least one product
    if (empty($product_ids)) {
        $errors[] = "Please add at least one product to the order";
    }
    
    // Calculate total and validate stock
    $total_amount = 0;
    $items = [];
    
    foreach ($product_ids as $index => $product_id) {
        if (!empty($product_id) && isset($quantities[$index]) && $quantities[$index] > 0) {
            // Get product price and stock
            $stmt = $pdo->prepare("SELECT id, product_name, price, stock FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();
            
            if ($product) {
                if ($quantities[$index] > $product['stock']) {
                    $errors[] = "Insufficient stock for {$product['product_name']}. Available: {$product['stock']}";
                } else {
                    $items[] = [
                        'product_id' => $product_id,
                        'product_name' => $product['product_name'],
                        'quantity' => $quantities[$index],
                        'price' => $product['price']
                    ];
                    $total_amount += $product['price'] * $quantities[$index];
                }
            }
        }
    }
    
    if (empty($items)) {
        $errors[] = "No valid items to order";
    }
    
    // If no errors, insert order
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // 1. Insert order
            $stmt = $pdo->prepare("
                INSERT INTO orders (customer_id, total_amount, delivery_address, payment_method, order_status) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$customer_id, $total_amount, $delivery_address, $payment_method, $order_status]);
            $order_id = $pdo->lastInsertId();
            
            // 2. Insert order items and update stock
            $item_stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stock_stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            $movement_stmt = $pdo->prepare("INSERT INTO stock_movements (product_id, movement_type, quantity, reference) VALUES (?, 'out', ?, ?)");
            
            foreach ($items as $item) {
                $item_stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
                $stock_stmt->execute([$item['quantity'], $item['product_id']]);
                $movement_stmt->execute([$item['product_id'], $item['quantity'], "Order #$order_id"]);
            }
            
            // 3. Insert payment record (pending)
            $payment_stmt = $pdo->prepare("
                INSERT INTO payments (order_id, payment_method, amount, payment_status) 
                VALUES (?, ?, ?, 'pending')
            ");
            $payment_stmt->execute([$order_id, $payment_method, $total_amount]);
            
            // 4. Log activity
            $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user_id'] ?? 1, "Created order #$order_id"]);
            
            $pdo->commit();
            
            $_SESSION['success'] = "Order #$order_id created successfully!";
            header("Location: view.php?id=$order_id");
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Error creating order: " . $e->getMessage();
        }
    }
}

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
                        <i class="fas fa-plus-circle text-primary-500 mr-2"></i>
                        Create New Order
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Add a new customer order to the system
                    </p>
                </div>
            </div>
            <a href="index.php" class="px-4 py-2 border border-primary-300 dark:border-primary-600 text-primary-700 dark:text-primary-300 rounded-xl text-sm font-semibold hover:bg-primary-50 dark:hover:bg-primary-800/30 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Orders
            </a>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-1 p-6 bg-slate-50/50 dark:bg-primary-900/50">
        <div class="max-w-4xl mx-auto">
            <!-- Error Messages -->
            <?php if (!empty($errors)): ?>
            <div class="mb-6 bg-rose-100 dark:bg-rose-900/30 border border-rose-200 dark:border-rose-800 rounded-xl p-4">
                <div class="flex items-start">
                    <i class="fas fa-exclamation-circle text-rose-600 dark:text-rose-400 mt-0.5 mr-3"></i>
                    <div>
                        <h4 class="text-sm font-semibold text-rose-800 dark:text-rose-200 mb-2">Please fix the following errors:</h4>
                        <ul class="list-disc list-inside text-sm text-rose-700 dark:text-rose-300 space-y-1">
                            <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- New Order Form -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 bakery-card">
                <form method="POST" id="orderForm">
                    <!-- Customer Selection -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">
                            <i class="fas fa-user text-primary-500 mr-2"></i>
                            Customer
                        </label>
                        <div class="flex items-center space-x-3">
                            <select name="customer_id" class="flex-1 px-4 py-3 border border-primary-200 dark:border-primary-700 rounded-xl bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                <option value="">Walk-in Customer (No Account)</option>
                                <?php foreach ($customers as $customer): ?>
                                <option value="<?php echo $customer['id']; ?>" <?php echo (isset($_POST['customer_id']) && $_POST['customer_id'] == $customer['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($customer['name']); ?> - <?php echo htmlspecialchars($customer['phone']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <a href="../customers/add.php" class="px-4 py-3 bg-primary-100 hover:bg-primary-200 dark:bg-primary-800/50 dark:hover:bg-primary-700 text-primary-700 dark:text-primary-300 rounded-xl transition-colors" target="_blank">
                                <i class="fas fa-user-plus"></i>
                            </a>
                        </div>
                        <p class="mt-2 text-xs text-slate-500">Leave as "Walk-in Customer" for in-store purchases without account</p>
                    </div>

                    <!-- Order Items -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">
                            <i class="fas fa-shopping-bag text-primary-500 mr-2"></i>
                            Order Items
                        </label>
                        
                        <div id="items-container" class="space-y-3">
                            <!-- First item row -->
                            <div class="item-row flex items-center space-x-3">
                                <select name="product_id[]" class="product-select flex-1 px-4 py-3 border border-primary-200 dark:border-primary-700 rounded-xl bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500" required>
                                    <option value="">Select Product</option>
                                    <?php foreach ($products as $product): ?>
                                    <option value="<?php echo $product['id']; ?>" data-price="<?php echo $product['price']; ?>" data-stock="<?php echo $product['stock']; ?>">
                                        <?php echo htmlspecialchars($product['product_name']); ?> - KES <?php echo number_format($product['price'], 2); ?> (Stock: <?php echo $product['stock']; ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="number" name="quantity[]" placeholder="Qty" min="1" value="1" 
                                       class="quantity-input w-24 px-4 py-3 border border-primary-200 dark:border-primary-700 rounded-xl bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500" required>
                                <span class="item-total text-sm font-medium text-primary-600 w-24 text-right">KES 0.00</span>
                                <button type="button" onclick="removeItem(this)" class="remove-btn p-3 text-rose-600 hover:bg-rose-100 dark:hover:bg-rose-900/30 rounded-lg transition-colors">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                        
                        <button type="button" onclick="addItem()" class="mt-3 px-4 py-2 border border-primary-300 dark:border-primary-600 text-primary-700 dark:text-primary-300 rounded-lg text-sm hover:bg-primary-50 dark:hover:bg-primary-800/30 transition-colors flex items-center">
                            <i class="fas fa-plus mr-2"></i>
                            Add Another Item
                        </button>
                    </div>

                    <!-- Order Summary -->
                    <div class="mb-6 p-4 bg-primary-50 dark:bg-primary-800/30 rounded-xl">
                        <h4 class="text-sm font-semibold text-slate-900 dark:text-white mb-3">Order Summary</h4>
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-600 dark:text-slate-400">Subtotal</span>
                                <span class="font-medium" id="subtotal">KES 0.00</span>
                            </div>
                            <div class="border-t border-primary-200 dark:border-primary-700 pt-2 flex justify-between">
                                <span class="font-semibold text-slate-900 dark:text-white">Total</span>
                                <span class="text-xl font-bold text-primary-600" id="total">KES 0.00</span>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Method & Delivery -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">
                                <i class="fas fa-credit-card text-primary-500 mr-2"></i>
                                Payment Method <span class="text-rose-500">*</span>
                            </label>
                            <div class="space-y-2">
                                <label class="flex items-center space-x-3 p-3 border border-primary-200 dark:border-primary-700 rounded-xl cursor-pointer hover:bg-primary-50 dark:hover:bg-primary-800/30">
                                    <input type="radio" name="payment_method" value="cash" class="text-primary-600 focus:ring-primary-500" checked>
                                    <i class="fas fa-money-bill-wave text-emerald-600 w-5"></i>
                                    <span class="text-sm">Cash</span>
                                </label>
                                <label class="flex items-center space-x-3 p-3 border border-primary-200 dark:border-primary-700 rounded-xl cursor-pointer hover:bg-primary-50 dark:hover:bg-primary-800/30">
                                    <input type="radio" name="payment_method" value="mpesa" class="text-primary-600 focus:ring-primary-500">
                                    <i class="fas fa-mobile-alt text-secondary-600 w-5"></i>
                                    <span class="text-sm">M-Pesa</span>
                                </label>
                                <label class="flex items-center space-x-3 p-3 border border-primary-200 dark:border-primary-700 rounded-xl cursor-pointer hover:bg-primary-50 dark:hover:bg-primary-800/30">
                                    <input type="radio" name="payment_method" value="card" class="text-primary-600 focus:ring-primary-500">
                                    <i class="fas fa-credit-card text-blue-600 w-5"></i>
                                    <span class="text-sm">Card</span>
                                </label>
                            </div>
                        </div>
                        
                        <div>
                            <label for="delivery_address" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">
                                <i class="fas fa-map-marker-alt text-primary-500 mr-2"></i>
                                Delivery Address
                            </label>
                            <textarea id="delivery_address" name="delivery_address" rows="4"
                                      class="w-full px-4 py-3 border border-primary-200 dark:border-primary-700 rounded-xl bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500"
                                      placeholder="Enter delivery address (leave empty for pickup)"><?php echo isset($_POST['delivery_address']) ? htmlspecialchars($_POST['delivery_address']) : ''; ?></textarea>
                            <p class="mt-2 text-xs text-slate-500">Leave empty if customer will pick up from bakery</p>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex items-center justify-end space-x-3 pt-6 border-t border-primary-200 dark:border-primary-700">
                        <a href="index.php" class="px-6 py-3 border border-primary-300 dark:border-primary-600 text-primary-700 dark:text-primary-300 rounded-xl text-sm font-semibold hover:bg-primary-50 dark:hover:bg-primary-800/30 transition-colors">
                            Cancel
                        </a>
                        <button type="submit" class="px-6 py-3 bg-gradient-to-r from-primary-600 to-secondary-500 text-white rounded-xl text-sm font-semibold hover:shadow-lg transition-all flex items-center">
                            <i class="fas fa-check-circle mr-2"></i>
                            Create Order
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<script>
let itemCount = 1;

function addItem() {
    const container = document.getElementById('items-container');
    const newRow = document.createElement('div');
    newRow.className = 'item-row flex items-center space-x-3';
    newRow.innerHTML = `
        <select name="product_id[]" class="product-select flex-1 px-4 py-3 border border-primary-200 dark:border-primary-700 rounded-xl bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500" required onchange="updateItemTotal(this)">
            <option value="">Select Product</option>
            <?php foreach ($products as $product): ?>
            <option value="<?php echo $product['id']; ?>" data-price="<?php echo $product['price']; ?>" data-stock="<?php echo $product['stock']; ?>">
                <?php echo htmlspecialchars($product['product_name']); ?> - KES <?php echo number_format($product['price'], 2); ?> (Stock: <?php echo $product['stock']; ?>)
            </option>
            <?php endforeach; ?>
        </select>
        <input type="number" name="quantity[]" placeholder="Qty" min="1" value="1" 
               class="quantity-input w-24 px-4 py-3 border border-primary-200 dark:border-primary-700 rounded-xl bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500" required onchange="updateItemTotal(this)">
        <span class="item-total text-sm font-medium text-primary-600 w-24 text-right">KES 0.00</span>
        <button type="button" onclick="removeItem(this)" class="remove-btn p-3 text-rose-600 hover:bg-rose-100 dark:hover:bg-rose-900/30 rounded-lg transition-colors">
            <i class="fas fa-trash-alt"></i>
        </button>
    `;
    container.appendChild(newRow);
    updateAllTotals();
}

function removeItem(button) {
    const rows = document.querySelectorAll('.item-row');
    if (rows.length > 1) {
        button.closest('.item-row').remove();
        updateAllTotals();
    } else {
        alert('You need at least one item in the order');
    }
}

function updateItemTotal(element) {
    const row = element.closest('.item-row');
    const select = row.querySelector('.product-select');
    const quantity = row.querySelector('.quantity-input').value;
    const totalSpan = row.querySelector('.item-total');
    
    const selectedOption = select.options[select.selectedIndex];
    if (selectedOption && selectedOption.value) {
        const price = parseFloat(selectedOption.dataset.price);
        const total = price * parseInt(quantity || 0);
        totalSpan.textContent = 'KES ' + total.toFixed(2);
    } else {
        totalSpan.textContent = 'KES 0.00';
    }
    
    updateAllTotals();
}

function updateAllTotals() {
    let subtotal = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const select = row.querySelector('.product-select');
        const quantity = row.querySelector('.quantity-input').value;
        
        const selectedOption = select.options[select.selectedIndex];
        if (selectedOption && selectedOption.value) {
            const price = parseFloat(selectedOption.dataset.price);
            subtotal += price * parseInt(quantity || 0);
        }
    });
    
    document.getElementById('subtotal').textContent = 'KES ' + subtotal.toFixed(2);
    document.getElementById('total').textContent = 'KES ' + subtotal.toFixed(2);
}

// Initialize event listeners for first row
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.product-select, .quantity-input').forEach(el => {
        el.addEventListener('change', function() {
            updateItemTotal(this);
        });
        el.addEventListener('keyup', function() {
            updateItemTotal(this);
        });
    });
    updateAllTotals();
});
</script>

<?php include '../includes/footer.php'; ?>