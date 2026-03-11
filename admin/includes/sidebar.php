<?php
// Get current page to set active state
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// Define ADMIN_URL if not already defined
if (!defined('ADMIN_URL')) {
    require_once __DIR__ . '/config.php';
}
?>
<!-- Mobile Sidebar Overlay -->
<div id="sidebarOverlay" class="fixed inset-0 z-40 bg-black/50 lg:hidden hidden"></div>

<!-- Sidebar -->
<aside id="sidebar" class="fixed lg:sticky top-0 left-0 h-screen w-64 z-50 bg-white/95 dark:bg-slate-900/95 backdrop-blur-sm border-r border-primary-200/50 dark:border-primary-800/50 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out flex flex-col">
    
    <!-- Sidebar Header -->
    <div class="p-5 border-b border-primary-200/50 dark:border-primary-800/50">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="relative">
                    <div class="w-9 h-9 bg-gradient-to-br from-primary-600 to-secondary-500 rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-bread-slice text-white text-sm"></i>
                    </div>
                    <div class="absolute -bottom-1 -right-1 w-3 h-3 bg-secondary-500 rounded-full border-2 border-white dark:border-slate-900"></div>
                </div>
                <div>
                    <div class="text-sm font-bold bg-gradient-to-r from-primary-600 to-secondary-500 bg-clip-text text-transparent">BeriBakes</div>
                    <div class="text-2xs text-slate-500 dark:text-slate-400">Bakery Management</div>
                </div>
            </div>
            <button id="closeSidebar" class="lg:hidden p-1.5 hover:bg-primary-100 dark:hover:bg-primary-800 rounded-lg transition-colors">
                <i class="fas fa-times text-sm text-slate-500 dark:text-slate-400"></i>
            </button>
        </div>
    </div>

    <!-- Admin Profile -->
    <div class="p-5 border-b border-primary-200/50 dark:border-primary-800/50">
        <div class="flex items-center space-x-3">
            <div class="relative">
                <div class="w-10 h-10 bg-gradient-to-br from-primary-600 to-secondary-500 rounded-full flex items-center justify-center text-white text-sm font-semibold shadow-lg">
                    <?php 
                    $initials = 'A';
                    if (isset($current_user)) {
                        $name = $current_user['name'] ?? 'Admin';
                        $words = explode(' ', $name);
                        $initials = '';
                        foreach ($words as $w) {
                            if (!empty($w)) $initials .= strtoupper(substr($w, 0, 1));
                        }
                    }
                    echo substr($initials, 0, 2);
                    ?>
                </div>
                <div class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-emerald-500 rounded-full border border-white dark:border-slate-900"></div>
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-sm font-semibold text-slate-900 dark:text-white truncate">
                    <?php echo isset($current_user) ? ($current_user['name'] ?? 'Admin User') : 'Admin User'; ?>
                </div>
                <div class="text-2xs text-slate-500 dark:text-slate-400 flex items-center space-x-1">
                    <span>Administrator</span>
                    <span class="w-1 h-1 bg-slate-300 dark:bg-slate-700 rounded-full"></span>
                    <span class="text-emerald-600 dark:text-emerald-400">Online</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto p-4 space-y-1 hide-scrollbar">
        <!-- Main Navigation -->
        <div class="mb-4">
            <div class="px-3 mb-2">
                <div class="text-2xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Main</div>
            </div>
            <div class="space-y-1">
                <a href="<?php echo ADMIN_URL; ?>dashboard.php" class="nav-link flex items-center space-x-3 px-3 py-2.5 rounded-xl <?php echo ($current_page == 'dashboard.php') ? 'bg-primary-100/50 dark:bg-primary-800/30 text-primary-700 dark:text-primary-300' : 'hover:bg-primary-100/50 dark:hover:bg-primary-800/30 text-slate-700 dark:text-slate-300'; ?> transition-colors group">
                    <i class="fas fa-chart-pie text-sm w-5"></i>
                    <span class="text-sm font-medium">Dashboard</span>
                </a>
                
                <a href="<?php echo ADMIN_URL; ?>products/" class="nav-link flex items-center space-x-3 px-3 py-2.5 rounded-xl <?php echo ($current_dir == 'products' || strpos($current_page, 'products') !== false) ? 'bg-primary-100/50 dark:bg-primary-800/30 text-primary-700 dark:text-primary-300' : 'hover:bg-primary-100/50 dark:hover:bg-primary-800/30 text-slate-700 dark:text-slate-300'; ?> transition-colors group">
                    <i class="fas fa-bread-slice text-sm w-5"></i>
                    <span class="text-sm font-medium">Products</span>
                </a>
                
                <a href="<?php echo ADMIN_URL; ?>orders/" class="nav-link flex items-center space-x-3 px-3 py-2.5 rounded-xl <?php echo ($current_dir == 'orders' || strpos($current_page, 'orders') !== false) ? 'bg-primary-100/50 dark:bg-primary-800/30 text-primary-700 dark:text-primary-300' : 'hover:bg-primary-100/50 dark:hover:bg-primary-800/30 text-slate-700 dark:text-slate-300'; ?> transition-colors group">
                    <i class="fas fa-truck text-sm w-5"></i>
                    <span class="text-sm font-medium">Orders</span>
                </a>
                
                <a href="<?php echo ADMIN_URL; ?>customers/" class="nav-link flex items-center space-x-3 px-3 py-2.5 rounded-xl <?php echo ($current_dir == 'customers' || strpos($current_page, 'customers') !== false) ? 'bg-primary-100/50 dark:bg-primary-800/30 text-primary-700 dark:text-primary-300' : 'hover:bg-primary-100/50 dark:hover:bg-primary-800/30 text-slate-700 dark:text-slate-300'; ?> transition-colors group">
                    <i class="fas fa-users text-sm w-5"></i>
                    <span class="text-sm font-medium">Customers</span>
                </a>
            </div>
        </div>

        <!-- Financial -->
        <div class="mb-4">
            <div class="px-3 mb-2">
                <div class="text-2xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Financial</div>
            </div>
            <div class="space-y-1">
                <a href="<?php echo ADMIN_URL; ?>ledger/accounts.php" class="nav-link flex items-center space-x-3 px-3 py-2.5 rounded-xl <?php echo ($current_page == 'accounts.php' || ($current_dir == 'ledger' && strpos($current_page, 'accounts') !== false)) ? 'bg-primary-100/50 dark:bg-primary-800/30 text-primary-700 dark:text-primary-300' : 'hover:bg-primary-100/50 dark:hover:bg-primary-800/30 text-slate-700 dark:text-slate-300'; ?> transition-colors group">
                    <i class="fas fa-book-open text-sm w-5"></i>
                    <span class="text-sm font-medium">Accounts</span>
                </a>
                
                <a href="<?php echo ADMIN_URL; ?>ledger/transactions.php" class="nav-link flex items-center space-x-3 px-3 py-2.5 rounded-xl <?php echo ($current_page == 'transactions.php' || ($current_dir == 'ledger' && strpos($current_page, 'transactions') !== false)) ? 'bg-primary-100/50 dark:bg-primary-800/30 text-primary-700 dark:text-primary-300' : 'hover:bg-primary-100/50 dark:hover:bg-primary-800/30 text-slate-700 dark:text-slate-300'; ?> transition-colors group">
                    <i class="fas fa-exchange-alt text-sm w-5"></i>
                    <span class="text-sm font-medium">Transactions</span>
                </a>
            </div>
        </div>

        <!-- Reports -->
        <div class="mb-4">
            <div class="px-3 mb-2">
                <div class="text-2xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Reports</div>
            </div>
            <div class="space-y-1">
                <a href="<?php echo ADMIN_URL; ?>reports/sales.php" class="nav-link flex items-center space-x-3 px-3 py-2.5 rounded-xl <?php echo ($current_page == 'sales.php' || ($current_dir == 'reports' && strpos($current_page, 'sales') !== false)) ? 'bg-primary-100/50 dark:bg-primary-800/30 text-primary-700 dark:text-primary-300' : 'hover:bg-primary-100/50 dark:hover:bg-primary-800/30 text-slate-700 dark:text-slate-300'; ?> transition-colors group">
                    <i class="fas fa-chart-line text-sm w-5"></i>
                    <span class="text-sm font-medium">Sales Report</span>
                </a>
                
                <a href="<?php echo ADMIN_URL; ?>reports/products.php" class="nav-link flex items-center space-x-3 px-3 py-2.5 rounded-xl <?php echo ($current_page == 'products.php' && $current_dir == 'reports') ? 'bg-primary-100/50 dark:bg-primary-800/30 text-primary-700 dark:text-primary-300' : 'hover:bg-primary-100/50 dark:hover:bg-primary-800/30 text-slate-700 dark:text-slate-300'; ?> transition-colors group">
                    <i class="fas fa-chart-bar text-sm w-5"></i>
                    <span class="text-sm font-medium">Product Performance</span>
                </a>
            </div>
        </div>

        <!-- Quick Stats Widget (Optional) -->
        <div class="mt-6 mx-2 p-4 bg-gradient-to-br from-primary-600 to-secondary-500 rounded-2xl text-white">
            <div class="text-center">
                <div class="flex items-center justify-center space-x-2 mb-2">
                    <i class="fas fa-store-alt text-2xl opacity-80"></i>
                </div>
                <div class="text-xs text-white/80 font-medium">BeriBakes Bakery</div>
                <div class="text-2xs text-white/60 mt-1">Fresh daily since 2024</div>
                <a href="<?php echo ADMIN_URL; ?>reports/sales.php" class="inline-block mt-3 text-xs text-white hover:text-white/80 transition-colors">
                    View Reports <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>
    </nav>

    <!-- Sidebar Footer -->
    <div class="p-4 border-t border-primary-200/50 dark:border-primary-800/50">
        <a href="<?php echo ADMIN_URL; ?>logout.php" class="w-full flex items-center justify-center space-x-2 px-3 py-2.5 bg-primary-100 dark:bg-primary-800 hover:bg-primary-200 dark:hover:bg-primary-700 rounded-xl text-primary-700 dark:text-primary-300 text-sm font-medium transition-colors group">
            <i class="fas fa-sign-out-alt text-sm"></i>
            <span>Sign Out</span>
        </a>
    </div>
</aside>