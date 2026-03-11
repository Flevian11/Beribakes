<?php
require_once '../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';

// Handle product deletion
if (isset($_GET['delete'])) {
    $id = filter_var($_GET['delete'], FILTER_SANITIZE_NUMBER_INT);
    
    try {
        // Check if product has orders
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE product_id = ?");
        $stmt->execute([$id]);
        $order_count = $stmt->fetchColumn();
        
        if ($order_count > 0) {
            $_SESSION['error'] = "Cannot delete product: It has associated orders.";
        } else {
            // Get image filename before deleting
            $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $product = $stmt->fetch();
            
            // Delete product
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);
            
            // Delete image file if exists
            if ($product && $product['image'] && file_exists(__DIR__ . '/../../uploads/products/' . $product['image'])) {
                unlink(__DIR__ . '/../../uploads/products/' . $product['image']);
            }
            
            $_SESSION['success'] = "Product deleted successfully!";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error deleting product: " . $e->getMessage();
    }
    
    header("Location: index.php");
    exit;
}

// Get filter parameters
$category_id = isset($_GET['category']) ? filter_var($_GET['category'], FILTER_SANITIZE_NUMBER_INT) : null;
$stock_filter = isset($_GET['stock']) ? $_GET['stock'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query with filters
$sql = "SELECT p.*, c.category_name,
        CASE 
            WHEN p.stock = 0 THEN 'out_of_stock'
            WHEN p.stock <= 5 THEN 'low_stock'
            ELSE 'in_stock'
        END as stock_status
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE 1=1";
$params = [];

if ($category_id) {
    $sql .= " AND p.category_id = ?";
    $params[] = $category_id;
}

if ($stock_filter == 'low_stock') {
    $sql .= " AND p.stock <= 5 AND p.stock > 0";
} elseif ($stock_filter == 'out_of_stock') {
    $sql .= " AND p.stock = 0";
} elseif ($stock_filter == 'in_stock') {
    $sql .= " AND p.stock > 5";
}

if ($search) {
    $sql .= " AND (p.product_name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get categories for filter
$stmt = $pdo->query("SELECT * FROM categories ORDER BY category_name");
$categories = $stmt->fetchAll();

// Get statistics
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN stock > 5 THEN 1 ELSE 0 END) as in_stock,
        SUM(CASE WHEN stock <= 5 AND stock > 0 THEN 1 ELSE 0 END) as low_stock,
        SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END) as out_of_stock,
        COALESCE(SUM(stock), 0) as total_items,
        COALESCE(SUM(price * stock), 0) as inventory_value
    FROM products");
$stats = $stmt->fetch();

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
                        <i class="fas fa-bread-slice text-primary-500 mr-2"></i>
                        Product Management
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        <i class="far fa-list-alt mr-1"></i>
                        Manage your bakery products, prices, and inventory
                    </p>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <a href="add.php" class="px-4 py-2 bg-gradient-to-r from-primary-600 to-secondary-500 text-white rounded-xl text-sm font-semibold hover:shadow-lg transition-all flex items-center">
                    <i class="fas fa-plus-circle mr-2"></i>
                    Add New Product
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

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
            <div class="bg-white dark:bg-slate-800 rounded-xl p-4 bakery-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-500">Total Products</p>
                        <h4 class="text-xl font-bold text-primary-600"><?php echo $stats['total'] ?? 0; ?></h4>
                    </div>
                    <div class="w-10 h-10 bg-primary-100 dark:bg-primary-800/50 rounded-lg flex items-center justify-center">
                        <i class="fas fa-box text-primary-600"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-slate-800 rounded-xl p-4 bakery-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-500">In Stock</p>
                        <h4 class="text-xl font-bold text-emerald-600"><?php echo $stats['in_stock'] ?? 0; ?></h4>
                    </div>
                    <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-800/50 rounded-lg flex items-center justify-center">
                        <i class="fas fa-check-circle text-emerald-600"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-slate-800 rounded-xl p-4 bakery-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-500">Low Stock</p>
                        <h4 class="text-xl font-bold text-amber-600"><?php echo $stats['low_stock'] ?? 0; ?></h4>
                    </div>
                    <div class="w-10 h-10 bg-amber-100 dark:bg-amber-800/50 rounded-lg flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-amber-600"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-slate-800 rounded-xl p-4 bakery-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-500">Out of Stock</p>
                        <h4 class="text-xl font-bold text-rose-600"><?php echo $stats['out_of_stock'] ?? 0; ?></h4>
                    </div>
                    <div class="w-10 h-10 bg-rose-100 dark:bg-rose-800/50 rounded-lg flex items-center justify-center">
                        <i class="fas fa-times-circle text-rose-600"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-slate-800 rounded-xl p-4 bakery-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-500">Inventory Value</p>
                        <h4 class="text-xl font-bold text-secondary-600">KES <?php echo number_format($stats['inventory_value'] ?? 0, 2); ?></h4>
                    </div>
                    <div class="w-10 h-10 bg-secondary-100 dark:bg-secondary-800/50 rounded-lg flex items-center justify-center">
                        <i class="fas fa-coins text-secondary-600"></i>
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
                        <input type="text" name="search" placeholder="Search products..." 
                               value="<?php echo htmlspecialchars($search); ?>"
                               class="w-full pl-10 pr-4 py-2 border border-primary-200 dark:border-primary-700 rounded-lg bg-white dark:bg-slate-900 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    </div>
                </div>
                
                <div class="w-48">
                    <select name="category" class="w-full px-3 py-2 border border-primary-200 dark:border-primary-700 rounded-lg bg-white dark:bg-slate-900 text-sm focus:ring-2 focus:ring-primary-500">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $category_id == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['category_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="w-40">
                    <select name="stock" class="w-full px-3 py-2 border border-primary-200 dark:border-primary-700 rounded-lg bg-white dark:bg-slate-900 text-sm focus:ring-2 focus:ring-primary-500">
                        <option value="all" <?php echo $stock_filter == 'all' ? 'selected' : ''; ?>>All Stock</option>
                        <option value="in_stock" <?php echo $stock_filter == 'in_stock' ? 'selected' : ''; ?>>In Stock</option>
                        <option value="low_stock" <?php echo $stock_filter == 'low_stock' ? 'selected' : ''; ?>>Low Stock</option>
                        <option value="out_of_stock" <?php echo $stock_filter == 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
                    </select>
                </div>
                
                <button type="submit" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium transition-colors">
                    <i class="fas fa-filter mr-2"></i>Apply Filters
                </button>
                
                <a href="index.php" class="px-4 py-2 border border-primary-300 dark:border-primary-600 text-primary-700 dark:text-primary-300 hover:bg-primary-50 dark:hover:bg-primary-800/30 rounded-lg text-sm font-medium transition-colors">
                    <i class="fas fa-redo mr-2"></i>Reset
                </a>
            </form>
        </div>

        <!-- Products Grid -->
        <?php if (empty($products)): ?>
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-12 text-center bakery-card">
            <div class="w-24 h-24 bg-primary-100 dark:bg-primary-800/30 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-bread-slice text-primary-500 text-4xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">No Products Found</h3>
            <p class="text-slate-500 dark:text-slate-400 mb-4">Get started by adding your first bakery product</p>
            <a href="add.php" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-primary-600 to-secondary-500 text-white rounded-xl text-sm font-semibold hover:shadow-lg transition-all">
                <i class="fas fa-plus-circle mr-2"></i>
                Add Your First Product
            </a>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
            <?php foreach ($products as $product): 
                $status_colors = [
                    'in_stock' => ['bg' => 'emerald-100', 'text' => 'emerald-700', 'dark_bg' => 'emerald-900/30', 'dark_text' => 'emerald-300', 'icon' => 'check-circle', 'label' => 'In Stock'],
                    'low_stock' => ['bg' => 'amber-100', 'text' => 'amber-700', 'dark_bg' => 'amber-900/30', 'dark_text' => 'amber-300', 'icon' => 'exclamation-triangle', 'label' => 'Low Stock'],
                    'out_of_stock' => ['bg' => 'rose-100', 'text' => 'rose-700', 'dark_bg' => 'rose-900/30', 'dark_text' => 'rose-300', 'icon' => 'times-circle', 'label' => 'Out of Stock']
                ];
                $status = $status_colors[$product['stock_status']];
                $image_path = $product['image'] ? '/beribakes/uploads/products/' . $product['image'] : 'https://via.placeholder.com/300x200?text=No+Image';
            ?>
            <div class="bg-white dark:bg-slate-800 rounded-2xl bakery-card card-hover overflow-hidden group">
                <!-- Product Image -->
                <div class="relative h-48 bg-gradient-to-br from-primary-100 to-secondary-100 dark:from-primary-800/30 dark:to-secondary-800/30">
                    <img src="<?php echo $image_path; ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>" 
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                         onerror="this.src='https://via.placeholder.com/300x200?text=No+Image'">
                    
                    <!-- Status Badge -->
                    <div class="absolute top-3 right-3">
                        <span class="px-2 py-1 bg-<?php echo $status['bg']; ?> dark:bg-<?php echo $status['dark_bg']; ?> text-<?php echo $status['text']; ?> dark:text-<?php echo $status['dark_text']; ?> rounded-full text-xs font-medium flex items-center">
                            <i class="fas fa-<?php echo $status['icon']; ?> mr-1"></i>
                            <?php echo $status['label']; ?>
                        </span>
                    </div>
                    
                    <!-- Category Badge -->
                    <div class="absolute top-3 left-3">
                        <span class="px-2 py-1 bg-white/90 dark:bg-slate-900/90 text-primary-700 dark:text-primary-300 rounded-full text-xs font-medium backdrop-blur-sm">
                            <i class="fas fa-tag mr-1"></i>
                            <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                        </span>
                    </div>
                </div>
                
                <!-- Product Info -->
                <div class="p-4">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-1 truncate">
                        <?php echo htmlspecialchars($product['product_name']); ?>
                    </h3>
                    
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-3 line-clamp-2">
                        <?php echo htmlspecialchars($product['description'] ?? 'No description available'); ?>
                    </p>
                    
                    <!-- Price and Stock -->
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <span class="text-xs text-slate-500">Price</span>
                            <p class="text-xl font-bold text-primary-600 dark:text-primary-400">
                                KES <?php echo number_format($product['price'], 2); ?>
                            </p>
                        </div>
                        <div class="text-right">
                            <span class="text-xs text-slate-500">Stock</span>
                            <p class="text-lg font-semibold <?php echo $product['stock'] <= 5 ? 'text-amber-600' : 'text-slate-900 dark:text-white'; ?>">
                                <?php echo $product['stock']; ?> units
                            </p>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="flex items-center space-x-2 pt-3 border-t border-primary-200 dark:border-primary-700">
                        <a href="edit.php?id=<?php echo $product['id']; ?>" 
                           class="flex-1 flex items-center justify-center px-3 py-2 bg-primary-100 dark:bg-primary-800/30 hover:bg-primary-200 dark:hover:bg-primary-700/50 text-primary-700 dark:text-primary-300 rounded-lg text-sm font-medium transition-colors">
                            <i class="fas fa-edit mr-2"></i>
                            Edit
                        </a>
                        <button onclick="confirmDelete(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['product_name']); ?>')"
                                class="flex-1 flex items-center justify-center px-3 py-2 bg-rose-100 dark:bg-rose-900/30 hover:bg-rose-200 dark:hover:bg-rose-800/50 text-rose-700 dark:text-rose-300 rounded-lg text-sm font-medium transition-colors">
                            <i class="fas fa-trash-alt mr-2"></i>
                            Delete
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Products Count -->
        <div class="mt-4 text-sm text-slate-500 dark:text-slate-400">
            Showing <?php echo count($products); ?> products
        </div>
        <?php endif; ?>
    </main>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div class="fixed inset-0 bg-black/50 transition-opacity" onclick="closeDeleteModal()"></div>

        <!-- Center modal -->
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        
        <div class="inline-block align-middle bg-white dark:bg-slate-800 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full">
            <div class="bg-white dark:bg-slate-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-rose-100 dark:bg-rose-900/30 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-exclamation-triangle text-rose-600 dark:text-rose-400"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-semibold text-slate-900 dark:text-white" id="modal-title">
                            Delete Product
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-slate-500 dark:text-slate-400" id="deleteModalMessage">
                                Are you sure you want to delete this product? This action cannot be undone.
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
function confirmDelete(productId, productName) {
    document.getElementById('deleteModal').classList.remove('hidden');
    document.getElementById('deleteModalMessage').innerHTML = `Are you sure you want to delete <strong>${productName}</strong>? This action cannot be undone.`;
    document.getElementById('confirmDeleteBtn').href = `index.php?delete=${productId}`;
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
</script>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

<?php include '../includes/footer.php'; ?>