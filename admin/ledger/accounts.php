<?php
require_once '../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';

// Handle account creation
if (isset($_POST['add_account'])) {
    $account_name = trim($_POST['account_name']);
    $account_type = filter_var($_POST['account_type'], FILTER_SANITIZE_STRING);
    
    if (!empty($account_name) && in_array($account_type, ['asset', 'liability', 'income', 'expense'])) {
        try {
            $stmt = $pdo->prepare("INSERT INTO ledger_accounts (account_name, account_type) VALUES (?, ?)");
            $stmt->execute([$account_name, $account_type]);
            
            // Log activity
            $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user_id'] ?? 1, "Created new account: $account_name"]);
            
            $_SESSION['success'] = "Account created successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error creating account: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Please fill all fields correctly";
    }
    header("Location: accounts.php");
    exit;
}

// Handle account update
if (isset($_POST['update_account'])) {
    $id = filter_var($_POST['account_id'], FILTER_SANITIZE_NUMBER_INT);
    $account_name = trim($_POST['account_name']);
    $account_type = filter_var($_POST['account_type'], FILTER_SANITIZE_STRING);
    
    if ($id && !empty($account_name) && in_array($account_type, ['asset', 'liability', 'income', 'expense'])) {
        try {
            $stmt = $pdo->prepare("UPDATE ledger_accounts SET account_name = ?, account_type = ? WHERE id = ?");
            $stmt->execute([$account_name, $account_type, $id]);
            
            // Log activity
            $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user_id'] ?? 1, "Updated account #$id"]);
            
            $_SESSION['success'] = "Account updated successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error updating account: " . $e->getMessage();
        }
    }
    header("Location: accounts.php");
    exit;
}

// Handle account deletion
if (isset($_GET['delete'])) {
    $id = filter_var($_GET['delete'], FILTER_SANITIZE_NUMBER_INT);
    
    try {
        // Check if account has transactions
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM ledger_transactions WHERE account_id = ?");
        $stmt->execute([$id]);
        $transaction_count = $stmt->fetchColumn();
        
        if ($transaction_count > 0) {
            $_SESSION['error'] = "Cannot delete account: It has associated transactions.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM ledger_accounts WHERE id = ?");
            $stmt->execute([$id]);
            
            // Log activity
            $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user_id'] ?? 1, "Deleted account #$id"]);
            
            $_SESSION['success'] = "Account deleted successfully!";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error deleting account: " . $e->getMessage();
    }
    header("Location: accounts.php");
    exit;
}

// Get all accounts with their balances
$stmt = $pdo->query("
    SELECT a.*, 
           COALESCE(SUM(CASE WHEN t.transaction_type = 'debit' THEN t.amount ELSE 0 END), 0) as total_debits,
           COALESCE(SUM(CASE WHEN t.transaction_type = 'credit' THEN t.amount ELSE 0 END), 0) as total_credits,
           COUNT(t.id) as transaction_count,
           COALESCE(SUM(CASE WHEN t.transaction_type = 'debit' THEN t.amount ELSE -t.amount END), 0) as balance
    FROM ledger_accounts a
    LEFT JOIN ledger_transactions t ON a.id = t.account_id
    GROUP BY a.id
    ORDER BY a.account_type, a.account_name
");
$accounts = $stmt->fetchAll();

// Calculate totals by account type
$type_totals = [
    'asset' => ['balance' => 0, 'count' => 0],
    'liability' => ['balance' => 0, 'count' => 0],
    'income' => ['balance' => 0, 'count' => 0],
    'expense' => ['balance' => 0, 'count' => 0]
];

foreach ($accounts as $account) {
    $type_totals[$account['account_type']]['balance'] += $account['balance'];
    $type_totals[$account['account_type']]['count']++;
}

// Account type colors and icons
$type_styles = [
    'asset' => ['bg' => 'emerald-100', 'text' => 'emerald-700', 'dark_bg' => 'emerald-900/30', 'dark_text' => 'emerald-300', 'icon' => 'piggy-bank', 'label' => 'Asset'],
    'liability' => ['bg' => 'amber-100', 'text' => 'amber-700', 'dark_bg' => 'amber-900/30', 'dark_text' => 'amber-300', 'icon' => 'credit-card', 'label' => 'Liability'],
    'income' => ['bg' => 'blue-100', 'text' => 'blue-700', 'dark_bg' => 'blue-900/30', 'dark_text' => 'blue-300', 'icon' => 'chart-line', 'label' => 'Income'],
    'expense' => ['bg' => 'rose-100', 'text' => 'rose-700', 'dark_bg' => 'rose-900/30', 'dark_text' => 'rose-300', 'icon' => 'shopping-cart', 'label' => 'Expense']
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
                        <i class="fas fa-book-open text-primary-500 mr-2"></i>
                        Ledger Accounts
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        <i class="far fa-credit-card mr-1"></i>
                        Manage your financial accounts
                    </p>
                </div>
            </div>
            <button onclick="openAddModal()" class="px-4 py-2 bg-gradient-to-r from-primary-600 to-secondary-500 text-white rounded-xl text-sm font-semibold hover:shadow-lg transition-all flex items-center">
                <i class="fas fa-plus-circle mr-2"></i>
                New Account
            </button>
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

        <!-- Account Type Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <?php foreach ($type_totals as $type => $data): 
                $style = $type_styles[$type];
            ?>
            <div class="bg-white dark:bg-slate-800 rounded-xl p-4 bakery-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-500"><?php echo ucfirst($type); ?> Accounts</p>
                        <h4 class="text-xl font-bold text-<?php echo $style['text']; ?>"><?php echo $data['count']; ?></h4>
                        <p class="text-xs text-slate-500 mt-1">Balance: KES <?php echo number_format($data['balance'], 2); ?></p>
                    </div>
                    <div class="w-10 h-10 bg-<?php echo $style['bg']; ?> dark:bg-<?php echo $style['dark_bg']; ?> rounded-lg flex items-center justify-center">
                        <i class="fas fa-<?php echo $style['icon']; ?> text-<?php echo $style['text']; ?>"></i>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Accounts Table -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 bakery-card">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center">
                <i class="fas fa-list text-primary-500 mr-2"></i>
                All Accounts
            </h3>

            <?php if (empty($accounts)): ?>
            <div class="text-center py-12">
                <div class="w-24 h-24 bg-primary-100 dark:bg-primary-800/30 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-book-open text-primary-500 text-3xl"></i>
                </div>
                <h4 class="text-base font-medium text-slate-900 dark:text-white mb-2">No Accounts Found</h4>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">Get started by creating your first ledger account</p>
                <button onclick="openAddModal()" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-primary-600 to-secondary-500 text-white rounded-xl text-sm font-semibold hover:shadow-lg transition-all">
                    <i class="fas fa-plus-circle mr-2"></i>
                    Create Account
                </button>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-xs text-slate-500 dark:text-slate-400 border-b border-primary-200 dark:border-primary-700">
                            <th class="text-left py-3 font-medium">Account Name</th>
                            <th class="text-left font-medium">Type</th>
                            <th class="text-right font-medium">Debits</th>
                            <th class="text-right font-medium">Credits</th>
                            <th class="text-right font-medium">Balance</th>
                            <th class="text-right font-medium">Transactions</th>
                            <th class="text-center font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-primary-100 dark:divide-primary-800">
                        <?php foreach ($accounts as $account): 
                            $style = $type_styles[$account['account_type']];
                            $balance_class = $account['balance'] >= 0 ? 'text-emerald-600' : 'text-rose-600';
                        ?>
                        <tr class="hover:bg-primary-50 dark:hover:bg-primary-800/30 transition-colors">
                            <td class="py-3 text-sm font-medium text-slate-900 dark:text-white">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-<?php echo $style['bg']; ?> dark:bg-<?php echo $style['dark_bg']; ?> rounded-full flex items-center justify-center mr-3">
                                        <i class="fas fa-<?php echo $style['icon']; ?> text-<?php echo $style['text']; ?> text-xs"></i>
                                    </div>
                                    <?php echo htmlspecialchars($account['account_name']); ?>
                                </div>
                            </td>
                            <td class="py-3">
                                <span class="px-2 py-1 bg-<?php echo $style['bg']; ?> dark:bg-<?php echo $style['dark_bg']; ?> text-<?php echo $style['text']; ?> dark:text-<?php echo $style['dark_text']; ?> rounded-full text-xs">
                                    <?php echo $style['label']; ?>
                                </span>
                            </td>
                            <td class="py-3 text-right text-sm text-slate-600 dark:text-slate-400">
                                KES <?php echo number_format($account['total_debits'], 2); ?>
                            </td>
                            <td class="py-3 text-right text-sm text-slate-600 dark:text-slate-400">
                                KES <?php echo number_format($account['total_credits'], 2); ?>
                            </td>
                            <td class="py-3 text-right text-sm font-semibold <?php echo $balance_class; ?>">
                                KES <?php echo number_format($account['balance'], 2); ?>
                            </td>
                            <td class="py-3 text-right text-sm text-slate-600 dark:text-slate-400">
                                <?php echo $account['transaction_count']; ?>
                            </td>
                          <td class="py-3 text-center">
    <div class="flex items-center justify-center gap-2">

        <button onclick="openEditModal(<?php echo $account['id']; ?>, '<?php echo htmlspecialchars($account['account_name']); ?>', '<?php echo $account['account_type']; ?>')" 
        class="px-2.5 py-1 text-[11px] font-semibold bg-primary-100 hover:bg-primary-200 dark:bg-primary-800/50 dark:hover:bg-primary-700 text-primary-700 dark:text-primary-300 rounded-full transition whitespace-nowrap">

        Edit

        </button>

        <a href="transactions.php?account_id=<?php echo $account['id']; ?>" 
        class="px-2.5 py-1 text-[11px] font-semibold bg-secondary-100 hover:bg-secondary-200 dark:bg-secondary-800 dark:hover:bg-secondary-700 text-secondary-800 dark:text-secondary-200 rounded-full transition whitespace-nowrap">

        Transactions

        </a>

        <?php if ($account['transaction_count'] == 0): ?>

        <button onclick="confirmDelete(<?php echo $account['id']; ?>, '<?php echo htmlspecialchars($account['account_name']); ?>')" 
        class="px-2.5 py-1 text-[11px] font-semibold bg-rose-100 hover:bg-rose-200 dark:bg-rose-900/40 dark:hover:bg-rose-800 text-rose-700 dark:text-rose-300 rounded-full transition whitespace-nowrap">

        Delete

        </button>

        <?php endif; ?>

    </div>
</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Add Account Modal -->
<div id="addModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-black/50 transition-opacity" onclick="closeAddModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        
        <div class="inline-block align-middle bg-white dark:bg-slate-800 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full">
            <form method="POST" action="accounts.php">
                <div class="bg-white dark:bg-slate-800 px-4 pt-5 pb-4 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Create New Account</h3>
                        <button type="button" onclick="closeAddModal()" class="p-2 hover:bg-primary-100 dark:hover:bg-primary-800 rounded-lg transition-colors">
                            <i class="fas fa-times text-slate-400"></i>
                        </button>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Account Name <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="account_name" required
                                   class="w-full px-4 py-3 border border-primary-200 dark:border-primary-700 rounded-xl bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                   placeholder="e.g., Cash, Sales, Rent Expense">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Account Type <span class="text-rose-500">*</span>
                            </label>
                            <select name="account_type" required
                                    class="w-full px-4 py-3 border border-primary-200 dark:border-primary-700 rounded-xl bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                                <option value="">Select Type</option>
                                <option value="asset">Asset</option>
                                <option value="liability">Liability</option>
                                <option value="income">Income</option>
                                <option value="expense">Expense</option>
                            </select>
                        </div>
                        
                        <div class="bg-primary-50 dark:bg-primary-800/30 p-4 rounded-xl">
                            <h4 class="text-sm font-semibold text-slate-900 dark:text-white mb-2">Account Types Guide:</h4>
                            <ul class="text-xs text-slate-600 dark:text-slate-400 space-y-1 list-disc list-inside">
                                <li><span class="font-medium text-emerald-600">Asset:</span> Cash, bank accounts, inventory</li>
                                <li><span class="font-medium text-amber-600">Liability:</span> Loans, accounts payable</li>
                                <li><span class="font-medium text-blue-600">Income:</span> Sales revenue, service income</li>
                                <li><span class="font-medium text-rose-600">Expense:</span> Rent, utilities, salaries</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="bg-slate-50 dark:bg-slate-900/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" name="add_account" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-gradient-to-r from-primary-600 to-secondary-500 text-base font-medium text-white hover:shadow-lg focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                        Create Account
                    </button>
                    <button type="button" onclick="closeAddModal()" class="mt-3 w-full inline-flex justify-center rounded-xl border border-primary-300 dark:border-primary-600 shadow-sm px-4 py-2 bg-white dark:bg-slate-800 text-base font-medium text-slate-700 dark:text-slate-300 hover:bg-primary-50 dark:hover:bg-primary-800/30 focus:outline-none sm:mt-0 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Account Modal -->
<div id="editModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-black/50 transition-opacity" onclick="closeEditModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        
        <div class="inline-block align-middle bg-white dark:bg-slate-800 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full">
            <form method="POST" action="accounts.php">
                <div class="bg-white dark:bg-slate-800 px-4 pt-5 pb-4 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Edit Account</h3>
                        <button type="button" onclick="closeEditModal()" class="p-2 hover:bg-primary-100 dark:hover:bg-primary-800 rounded-lg transition-colors">
                            <i class="fas fa-times text-slate-400"></i>
                        </button>
                    </div>
                    
                    <input type="hidden" name="account_id" id="edit_account_id">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Account Name <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="account_name" id="edit_account_name" required
                                   class="w-full px-4 py-3 border border-primary-200 dark:border-primary-700 rounded-xl bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Account Type <span class="text-rose-500">*</span>
                            </label>
                            <select name="account_type" id="edit_account_type" required
                                    class="w-full px-4 py-3 border border-primary-200 dark:border-primary-700 rounded-xl bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                                <option value="asset">Asset</option>
                                <option value="liability">Liability</option>
                                <option value="income">Income</option>
                                <option value="expense">Expense</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="bg-slate-50 dark:bg-slate-900/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" name="update_account" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-gradient-to-r from-primary-600 to-secondary-500 text-base font-medium text-white hover:shadow-lg focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                        Update Account
                    </button>
                    <button type="button" onclick="closeEditModal()" class="mt-3 w-full inline-flex justify-center rounded-xl border border-primary-300 dark:border-primary-600 shadow-sm px-4 py-2 bg-white dark:bg-slate-800 text-base font-medium text-slate-700 dark:text-slate-300 hover:bg-primary-50 dark:hover:bg-primary-800/30 focus:outline-none sm:mt-0 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </form>
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
                            Delete Account
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-slate-500 dark:text-slate-400" id="deleteModalMessage">
                                Are you sure you want to delete this account? This action cannot be undone.
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
function openAddModal() {
    document.getElementById('addModal').classList.remove('hidden');
}

function closeAddModal() {
    document.getElementById('addModal').classList.add('hidden');
}

function openEditModal(id, name, type) {
    document.getElementById('edit_account_id').value = id;
    document.getElementById('edit_account_name').value = name;
    document.getElementById('edit_account_type').value = type;
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

function confirmDelete(id, name) {
    document.getElementById('deleteModal').classList.remove('hidden');
    document.getElementById('deleteModalMessage').innerHTML = `Are you sure you want to delete <strong>${name}</strong>? This action cannot be undone.`;
    document.getElementById('confirmDeleteBtn').href = `accounts.php?delete=${id}`;
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}

// Close modals on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAddModal();
        closeEditModal();
        closeDeleteModal();
    }
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
</style>

<?php include '../includes/footer.php'; ?>