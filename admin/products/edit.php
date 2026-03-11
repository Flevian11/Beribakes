<?php
require_once '../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';

// Get product ID from URL
$id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT) : 0;

// Fetch product details
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    $_SESSION['error'] = "Product not found";
    header("Location: index.php");
    exit;
}

// Get categories for dropdown
$stmt = $pdo->query("SELECT * FROM categories ORDER BY category_name");
$categories = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name = trim($_POST['product_name']);
    $category_id = filter_var($_POST['category_id'], FILTER_SANITIZE_NUMBER_INT);
    $price = filter_var($_POST['price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $stock = filter_var($_POST['stock'], FILTER_SANITIZE_NUMBER_INT);
    $description = trim($_POST['description']);
    $remove_image = isset($_POST['remove_image']) ? true : false;
    
    $errors = [];
    
    // Validation
    if (empty($product_name)) {
        $errors[] = "Product name is required";
    }
    
    if (empty($category_id)) {
        $errors[] = "Category is required";
    }
    
    if (empty($price) || $price <= 0) {
        $errors[] = "Valid price is required";
    }
    
    if ($stock === '' || $stock < 0) {
        $errors[] = "Valid stock quantity is required";
    }
    
    $image_name = $product['image']; // Keep existing image by default
    
    // Handle image removal
    if ($remove_image && $product['image']) {
        $old_image_path = __DIR__ . '/../../uploads/products/' . $product['image'];
        if (file_exists($old_image_path)) {
            unlink($old_image_path);
        }
        $image_name = null;
    }
    
    // Handle new image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($_FILES['image']['type'], $allowed_types)) {
            $errors[] = "Only JPG, PNG, GIF, and WEBP images are allowed";
        }
        
        if ($_FILES['image']['size'] > $max_size) {
            $errors[] = "Image size must be less than 5MB";
        }
        
        if (empty($errors)) {
            $upload_dir = __DIR__ . '/../../uploads/products/';
            
            // Delete old image if exists
            if ($product['image'] && file_exists($upload_dir . $product['image'])) {
                unlink($upload_dir . $product['image']);
            }
            
            // Generate unique filename
            $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image_name = uniqid() . '_' . time() . '.' . $extension;
            $upload_path = $upload_dir . $image_name;
            
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $errors[] = "Failed to upload image";
                $image_name = $product['image']; // Revert to old image on failure
            }
        }
    }
    
    // If no errors, update database
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE products 
                SET product_name = ?, category_id = ?, price = ?, stock = ?, description = ?, image = ?
                WHERE id = ?
            ");
            
            $stmt->execute([$product_name, $category_id, $price, $stock, $description, $image_name, $id]);
            
            $_SESSION['success'] = "Product updated successfully!";
            header("Location: index.php");
            exit;
            
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
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
                        <i class="fas fa-edit text-primary-500 mr-2"></i>
                        Edit Product
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Update product information for "<?php echo htmlspecialchars($product['product_name']); ?>"
                    </p>
                </div>
            </div>
            <a href="index.php" class="px-4 py-2 border border-primary-300 dark:border-primary-600 text-primary-700 dark:text-primary-300 rounded-xl text-sm font-semibold hover:bg-primary-50 dark:hover:bg-primary-800/30 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Products
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

            <!-- Edit Product Form -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 bakery-card">
                <form method="POST" enctype="multipart/form-data" class="space-y-6">
                    <!-- Product Name -->
                    <div>
                        <label for="product_name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            <i class="fas fa-tag text-primary-500 mr-2"></i>
                            Product Name <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" id="product_name" name="product_name" 
                               value="<?php echo htmlspecialchars($product['product_name']); ?>"
                               class="w-full px-4 py-3 border border-primary-200 dark:border-primary-700 rounded-xl bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors"
                               required>
                    </div>

                    <!-- Category and Price Row -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Category -->
                        <div>
                            <label for="category_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                <i class="fas fa-folder text-secondary-500 mr-2"></i>
                                Category <span class="text-rose-500">*</span>
                            </label>
                            <select id="category_id" name="category_id" 
                                    class="w-full px-4 py-3 border border-primary-200 dark:border-primary-700 rounded-xl bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors"
                                    required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $product['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Price -->
                        <div>
                            <label for="price" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                <i class="fas fa-coins text-accent-500 mr-2"></i>
                                Price (KES) <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-slate-500">KES</span>
                                <input type="number" id="price" name="price" step="0.01" min="0"
                                       value="<?php echo htmlspecialchars($product['price']); ?>"
                                       class="w-full pl-16 pr-4 py-3 border border-primary-200 dark:border-primary-700 rounded-xl bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors"
                                       required>
                            </div>
                        </div>
                    </div>

                    <!-- Stock and Image Row -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Stock Quantity -->
                        <div>
                            <label for="stock" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                <i class="fas fa-boxes text-emerald-500 mr-2"></i>
                                Stock Quantity <span class="text-rose-500">*</span>
                            </label>
                            <input type="number" id="stock" name="stock" min="0" step="1"
                                   value="<?php echo htmlspecialchars($product['stock']); ?>"
                                   class="w-full px-4 py-3 border border-primary-200 dark:border-primary-700 rounded-xl bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors"
                                   required>
                        </div>

                        <!-- Product Image -->
                        <div>
                            <label for="image" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                <i class="fas fa-image text-primary-500 mr-2"></i>
                                Product Image
                            </label>
                            <input type="file" id="image" name="image" accept="image/*"
                                   class="w-full px-4 py-3 border border-primary-200 dark:border-primary-700 rounded-xl bg-white dark:bg-slate-900 text-slate-900 dark:text-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary-100 file:text-primary-700 hover:file:bg-primary-200 transition-colors">
                            <p class="mt-2 text-xs text-slate-500">Max size: 5MB. Leave empty to keep current image</p>
                        </div>
                    </div>

                    <!-- Current Image Preview -->
                    <?php if ($product['image']): ?>
                    <div class="p-4 bg-primary-50 dark:bg-primary-800/30 rounded-xl">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center space-x-4">
                                <img src="/beribakes/uploads/products/<?php echo $product['image']; ?>" 
                                     alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                                     class="w-20 h-20 object-cover rounded-lg border-2 border-white dark:border-slate-700 shadow-sm"
                                     onerror="this.src='https://via.placeholder.com/80?text=Error'">
                                <div>
                                    <p class="text-sm font-medium text-slate-900 dark:text-white">Current Image</p>
                                    <p class="text-xs text-slate-500"><?php echo $product['image']; ?></p>
                                </div>
                            </div>
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" name="remove_image" value="1" class="rounded border-primary-300 text-rose-600 focus:ring-rose-500">
                                <span class="text-sm text-rose-600 dark:text-rose-400">Remove image</span>
                            </label>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            <i class="fas fa-align-left text-secondary-500 mr-2"></i>
                            Description
                        </label>
                        <textarea id="description" name="description" rows="4"
                                  class="w-full px-4 py-3 border border-primary-200 dark:border-primary-700 rounded-xl bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors"
                                  placeholder="Enter product description, ingredients, or notes..."><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex items-center justify-end space-x-3 pt-6 border-t border-primary-200 dark:border-primary-700">
                        <a href="index.php" class="px-6 py-3 border border-primary-300 dark:border-primary-600 text-primary-700 dark:text-primary-300 rounded-xl text-sm font-semibold hover:bg-primary-50 dark:hover:bg-primary-800/30 transition-colors">
                            Cancel
                        </a>
                        <button type="submit" class="px-6 py-3 bg-gradient-to-r from-primary-600 to-secondary-500 text-white rounded-xl text-sm font-semibold hover:shadow-lg transition-all flex items-center">
                            <i class="fas fa-save mr-2"></i>
                            Update Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>