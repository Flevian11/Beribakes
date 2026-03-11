<?php
require_once '../includes/auth.php';
require_once __DIR__ . '/../../config/db.php';

// Handle new transaction
if (isset($_POST['add_transaction'])) {
    $account_id = filter_var($_POST['account_id'], FILTER_SANITIZE_NUMBER_INT);
    $transaction_type = filter_var($_POST['transaction_type'], FILTER_SANITIZE_STRING);
    $amount = filter_var($_POST['amount'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $reference_type = !empty($_POST['reference_type']) ? filter_var($_POST['reference_type'], FILTER_SANITIZE_STRING) : null;
    $reference_id = !empty($_POST['reference_id']) ? filter_var($_POST['reference_id'], FILTER_SANITIZE_NUMBER_INT) : null;
    $description = trim($_POST['description'] ?? '');
    
    $errors = [];
    
    if (!$account_id) $errors[] = "Please select an account";
    if (!in_array($transaction_type, ['debit', 'credit'])) $errors[] = "Invalid transaction type";
    if ($amount <= 0) $errors[] = "Amount must be greater than 0";
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("
                INSERT INTO ledger_transactions (account_id, transaction_type, amount, reference_type, reference_id, description) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$account_id, $transaction_type, $amount, $reference_type, $reference_id, $description]);
            
            // Get account name for log
            $stmt = $pdo->prepare("SELECT account_name FROM ledger_accounts WHERE id = ?");
            $stmt->execute([$account_id]);
            $account = $stmt->fetch();
            
            // Log activity
            $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user_id'] ?? 1, "Added $transaction_type of KES $amount to {$account['account_name']}"]);
            
            $pdo->commit();
            
            $_SESSION['success'] = "Transaction added successfully!";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Error adding transaction: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = implode(", ", $errors);
    }
    header("Location: transactions.php");
    exit;
}

// Handle transaction deletion
if (isset($_GET['delete'])) {
    $id = filter_var($_GET['delete'], FILTER_SANITIZE_NUMBER_INT);
    
    try {
        $pdo->beginTransaction();
        
        // Get transaction details for log
        $stmt = $pdo->prepare("SELECT t.*, a.account_name FROM ledger_transactions t JOIN ledger_accounts a ON t.account_id = a.id WHERE t.id = ?");
        $stmt->execute([$id]);
        $transaction = $stmt->fetch();
        
        $stmt = $pdo->prepare("DELETE FROM ledger_transactions WHERE id = ?");
        $stmt->execute([$id]);
        
        // Log activity
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'] ?? 1, "Deleted transaction #$id from {$transaction['account_name']}"]);
        
        $pdo->commit();
        
        $_SESSION['success'] = "Transaction deleted successfully!";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error deleting transaction: " . $e->getMessage();
    }
    header("Location: transactions.php");
    exit;
}

// Get filter parameters
$account_filter = isset($_GET['account_id']) ? filter_var($_GET['account_id'], FILTER_SANITIZE_NUMBER_INT) : null;
$type_filter = isset($_GET['type']) ? $_GET['type'] : 'all';
$date_filter = isset($_GET['date_range']) ? $_GET['date_range'] : 'month';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query with filters
$sql = "SELECT t.*, a.account_name, a.account_type 
        FROM ledger_transactions t
        JOIN ledger_accounts a ON t.account_id = a.id
        WHERE 1=1";
$params = [];

if ($account_filter) {
    $sql .= " AND t.account_id = ?";
    $params[] = $account_filter;
}

if ($type_filter != 'all') {
    $sql .= " AND t.transaction_type = ?";
    $params[] = $type_filter;
}

// Date filter
if ($date_filter == 'today') {
    $sql .= " AND DATE(t.created_at) = CURDATE()";
} elseif ($date_filter == 'week') {
    $sql .= " AND t.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
} elseif ($date_filter == 'month') {
    $sql .= " AND t.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
} elseif ($date_filter == 'year') {
    $sql .= " AND t.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
}

if ($search) {
    $sql .= " AND (t.description LIKE ? OR a.account_name LIKE ? OR t.reference_type LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$sql .= " ORDER BY t.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Get all accounts for dropdown
$stmt = $pdo->query("SELECT * FROM ledger_accounts ORDER BY account_type, account_name");
$accounts = $stmt->fetchAll();

// Calculate totals
$total_debits = 0;
$total_credits = 0;
foreach ($transactions as $t) {
    if ($t['transaction_type'] == 'debit') {
        $total_debits += $t['amount'];
    } else {
        $total_credits += $t['amount'];
    }
}
$net_balance = $total_credits - $total_debits;

// Get account if filtered
$selected_account = null;
if ($account_filter) {
    foreach ($accounts as $acc) {
        if ($acc['id'] == $account_filter) {
            $selected_account = $acc;
            break;
        }
    }
}

// Account type colors
$type_colors = [
    'asset' => 'emerald',
    'liability' => 'amber',
    'income' => 'blue',
    'expense' => 'rose'
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
                        <i class="fas fa-exchange-alt text-primary-500 mr-2"></i>
                        Ledger Transactions
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        <i class="far fa-clock mr-1"></i>
                        Track all financial movements
                    </p>
                </div>
            </div>
            <button onclick="openAddModal()" class="px-4 py-2 bg-gradient-to-r from-primary-600 to-secondary-500 text-white rounded-xl text-sm font-semibold hover:shadow-lg transition-all flex items-center">
                <i class="fas fa-plus-circle mr-2"></i>
                New Transaction
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

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="bg-white dark:bg-slate-800 rounded-xl p-5 bakery-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-500">Total Debits</p>
                        <h4 class="text-2xl font-bold text-rose-600">KES <?php echo number_format($total_debits, 2); ?></h4>
                    </div>
                    <div class="w-12 h-12 bg-rose-100 dark:bg-rose-900/30 rounded-xl flex items-center justify-center">
                        <i class="fas fa-arrow-down text-rose-600 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-slate-800 rounded-xl p-5 bakery-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-500">Total Credits</p>
                        <h4 class="text-2xl font-bold text-emerald-600">KES <?php echo number_format($total_credits, 2); ?></h4>
                    </div>
                    <div class="w-12 h-12 bg-emerald-100 dark:bg-emerald-900/30 rounded-xl flex items-center justify-center">
                        <i class="fas fa-arrow-up text-emerald-600 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-slate-800 rounded-xl p-5 bakery-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-500">Net Balance</p>
                        <h4 class="text-2xl font-bold <?php echo $net_balance >= 0 ? 'text-emerald-600' : 'text-rose-600'; ?>">
                            KES <?php echo number_format(abs($net_balance), 2); ?>
                        </h4>
                        <p class="text-xs text-slate-500 mt-1"><?php echo $net_balance >= 0 ? 'Credit' : 'Debit'; ?> balance</p>
                    </div>
                    <div class="w-12 h-12 bg-primary-100 dark:bg-primary-800/50 rounded-xl flex items-center justify-center">
                        <i class="fas fa-scale-balanced text-primary-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 bakery-card mb-6">
            <form method="GET" class="flex flex-wrap items-center gap-4">
                <div class="flex-1 min-w-[200px]">
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400"></i>
                        <input type="text" name="search" placeholder="Search by description or account..." 
                               value="<?php echo htmlspecialchars($search); ?>"
                               class="w-full pl-10 pr-4 py-2.5 border border-primary-200 dark:border-primary-700 rounded-lg bg-white dark:bg-slate-900 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    </div>
                </div>
                
                <div class="w-48">
                    <select name="account_id" class="w-full px-3 py-2.5 border border-primary-200 dark:border-primary-700 rounded-lg bg-white dark:bg-slate-900 text-sm focus:ring-2 focus:ring-primary-500">
                        <option value="">All Accounts</option>
                        <?php foreach ($accounts as $account): ?>
                        <option value="<?php echo $account['id']; ?>" <?php echo $account_filter == $account['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($account['account_name']); ?> (<?php echo ucfirst($account['account_type']); ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="w-40">
                    <select name="type" class="w-full px-3 py-2.5 border border-primary-200 dark:border-primary-700 rounded-lg bg-white dark:bg-slate-900 text-sm focus:ring-2 focus:ring-primary-500">
                        <option value="all" <?php echo $type_filter == 'all' ? 'selected' : ''; ?>>All Types</option>
                        <option value="debit" <?php echo $type_filter == 'debit' ? 'selected' : ''; ?>>Debits Only</option>
                        <option value="credit" <?php echo $type_filter == 'credit' ? 'selected' : ''; ?>>Credits Only</option>
                    </select>
                </div>
                
                <div class="w-40">
                    <select name="date_range" class="w-full px-3 py-2.5 border border-primary-200 dark:border-primary-700 rounded-lg bg-white dark:bg-slate-900 text-sm focus:ring-2 focus:ring-primary-500">
                        <option value="today" <?php echo $date_filter == 'today' ? 'selected' : ''; ?>>Today</option>
                        <option value="week" <?php echo $date_filter == 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                        <option value="month" <?php echo $date_filter == 'month' ? 'selected' : ''; ?>>Last 30 Days</option>
                        <option value="year" <?php echo $date_filter == 'year' ? 'selected' : ''; ?>>Last Year</option>
                        <option value="all" <?php echo $date_filter == 'all' ? 'selected' : ''; ?>>All Time</option>
                    </select>
                </div>
                
                <button type="submit" class="px-5 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium transition-colors">
                    <i class="fas fa-filter mr-2"></i>Apply Filters
                </button>
                
                <a href="transactions.php" class="px-5 py-2.5 border border-primary-300 dark:border-primary-600 text-primary-700 dark:text-primary-300 hover:bg-primary-50 dark:hover:bg-primary-800/30 rounded-lg text-sm font-medium transition-colors">
                    <i class="fas fa-redo mr-2"></i>Reset
                </a>
            </form>
        </div>

        <!-- Account Info if filtered -->
        <?php if ($selected_account): 
            $color = $type_colors[$selected_account['account_type']];
        ?>
        <div class="mb-4 p-4 bg-<?php echo $color; ?>-50 dark:bg-<?php echo $color; ?>-900/20 rounded-xl border border-<?php echo $color; ?>-200 dark:border-<?php echo $color; ?>-800">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-<?php echo $color; ?>-800 dark:text-<?php echo $color; ?>-300">
                        <i class="fas fa-filter mr-2"></i>
                        Filtering by: <?php echo htmlspecialchars($selected_account['account_name']); ?>
                    </h3>
                    <p class="text-xs text-<?php echo $color; ?>-600 dark:text-<?php echo $color; ?>-400 mt-1">
                        Account Type: <?php echo ucfirst($selected_account['account_type']); ?>
                    </p>
                </div>
                <a href="transactions.php" class="px-3 py-1.5 bg-white dark:bg-slate-800 text-<?php echo $color; ?>-600 rounded-lg text-xs hover:bg-<?php echo $color; ?>-100 transition-colors">
                    <i class="fas fa-times mr-1"></i>Clear Filter
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Transactions Table -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 bakery-card">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center">
                <i class="fas fa-list text-primary-500 mr-2"></i>
                Transaction History
                <span class="ml-auto text-sm font-normal text-slate-500"><?php echo count($transactions); ?> entries</span>
            </h3>

            <?php if (empty($transactions)): ?>
            <div class="text-center py-12">
                <div class="w-24 h-24 bg-primary-100 dark:bg-primary-800/30 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-exchange-alt text-primary-500 text-3xl"></i>
                </div>
                <h4 class="text-base font-medium text-slate-900 dark:text-white mb-2">No Transactions Found</h4>
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">No transactions match your current filters</p>
                <button onclick="openAddModal()" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-primary-600 to-secondary-500 text-white rounded-xl text-sm font-semibold hover:shadow-lg transition-all">
                    <i class="fas fa-plus-circle mr-2"></i>
                    Add Transaction
                </button>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-xs text-slate-500 dark:text-slate-400 border-b border-primary-200 dark:border-primary-700">
                            <th class="text-left py-3 font-medium">Date & Time</th>
                            <th class="text-left font-medium">Account</th>
                            <th class="text-left font-medium">Description</th>
                            <th class="text-left font-medium">Reference</th>
                            <th class="text-right font-medium">Debit</th>
                            <th class="text-right font-medium">Credit</th>
                            <th class="text-center font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-primary-100 dark:divide-primary-800">
                        <?php 
                        $running_balance = 0;
                        foreach ($transactions as $trans): 
                            $account_color = $type_colors[$trans['account_type']];
                            $running_balance += ($trans['transaction_type'] == 'credit' ? $trans['amount'] : -$trans['amount']);
                        ?>
                        <tr class="hover:bg-primary-50 dark:hover:bg-primary-800/30 transition-colors">
                            <td class="py-3 text-sm text-slate-600 dark:text-slate-400">
                                <?php echo date('M j, Y \a\t g:i A', strtotime($trans['created_at'])); ?>
                            </td>
                            <td class="py-3">
                                <div class="flex items-center">
                                    <div class="w-6 h-6 bg-<?php echo $account_color; ?>-100 dark:bg-<?php echo $account_color; ?>-900/30 rounded-full flex items-center justify-center mr-2">
                                        <i class="fas fa-circle text-<?php echo $account_color; ?>-500 text-2xs"></i>
                                    </div>
                                    <span class="text-sm font-medium text-slate-900 dark:text-white">
                                        <?php echo htmlspecialchars($trans['account_name']); ?>
                                    </span>
                                </div>
                            </td>
                            <td class="py-3 text-sm text-slate-600 dark:text-slate-400">
                                <?php echo htmlspecialchars($trans['description'] ?? '-'); ?>
                            </td>
                            <td class="py-3 text-sm">
                                <?php if ($trans['reference_type']): ?>
                                <span class="px-2 py-1 bg-primary-100 dark:bg-primary-800/30 text-primary-700 dark:text-primary-300 rounded-full text-xs">
                                    <?php echo ucfirst($trans['reference_type']); ?> #<?php echo $trans['reference_id']; ?>
                                </span>
                                <?php else: ?>
                                <span class="text-slate-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 text-right">
                                <?php if ($trans['transaction_type'] == 'debit'): ?>
                                <span class="text-sm font-semibold text-rose-600">
                                    KES <?php echo number_format($trans['amount'], 2); ?>
                                </span>
                                <?php else: ?>
                                <span class="text-sm text-slate-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 text-right">
                                <?php if ($trans['transaction_type'] == 'credit'): ?>
                                <span class="text-sm font-semibold text-emerald-600">
                                    KES <?php echo number_format($trans['amount'], 2); ?>
                                </span>
                                <?php else: ?>
                                <span class="text-sm text-slate-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 text-center">
                                <div class="flex items-center justify-center space-x-2">
                                    <a href="?account_id=<?php echo $trans['account_id']; ?>" 
                                       class="p-1.5 bg-primary-100 hover:bg-primary-200 dark:bg-primary-800/50 dark:hover:bg-primary-700 rounded-lg transition-colors tooltip" title="Show Account">
                                        <i class="fas fa-filter text-primary-600"></i>
                                        <span class="tooltip-text">Filter by Account</span>
                                    </a>
                                    <button onclick="confirmDelete(<?php echo $trans['id']; ?>)" 
                                            class="p-1.5 bg-rose-100 hover:bg-rose-200 dark:bg-rose-900/30 dark:hover:bg-rose-800/50 rounded-lg transition-colors tooltip" title="Delete">
                                        <i class="fas fa-trash-alt text-rose-600"></i>
                                        <span class="tooltip-text">Delete Transaction</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="border-t border-primary-200 dark:border-primary-700">
                        <tr class="font-semibold">
                            <td colspan="4" class="py-3 text-right text-sm text-slate-700 dark:text-slate-300">Totals:</td>
                            <td class="py-3 text-right text-sm text-rose-600">KES <?php echo number_format($total_debits, 2); ?></td>
                            <td class="py-3 text-right text-sm text-emerald-600">KES <?php echo number_format($total_credits, 2); ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Add Transaction Modal -->
<div id="addModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-black/50 transition-opacity" onclick="closeAddModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        
        <div class="inline-block align-middle bg-white dark:bg-slate-800 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full">
            <form method="POST" action="transactions.php">
                <div class="bg-white dark:bg-slate-800 px-4 pt-5 pb-4 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Add New Transaction</h3>
                        <button type="button" onclick="closeAddModal()" class="p-2 hover:bg-primary-100 dark:hover:bg-primary-800 rounded-lg transition-colors">
                            <i class="fas fa-times text-slate-400"></i>
                        </button>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Account <span class="text-rose-500">*</span>
                            </label>
                            <select name="account_id" required
                                    class="w-full px-4 py-3 border border-primary-200 dark:border-primary-700 rounded-xl bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                                <option value="">Select Account</option>
                                <?php foreach ($accounts as $account): ?>
                                <option value="<?php echo $account['id']; ?>" <?php echo $account_filter == $account['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($account['account_name']); ?> (<?php echo ucfirst($account['account_type']); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    Type <span class="text-rose-500">*</span>
                                </label>
                                <select name="transaction_type" required
                                        class="w-full px-4 py-3 border border-primary-200 dark:border-primary-700 rounded-xl bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                                    <option value="debit">Debit (Money Out)</option>
                                    <option value="credit">Credit (Money In)</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    Amount (KES) <span class="text-rose-500">*</span>
                                </label>
                                <input type="number" name="amount" step="0.01" min="0.01" required
                                       class="w-full px-4 py-3 border border-primary-200 dark:border-primary-700 rounded-xl bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500"
                                       placeholder="0.00">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Description
                            </label>
                            <textarea name="description" rows="2"
                                      class="w-full px-4 py-3 border border-primary-200 dark:border-primary-700 rounded-xl bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500"
                                      placeholder="Enter description for this transaction"></textarea>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    Reference Type
                                </label>
                                <select name="reference_type"
                                        class="w-full px-4 py-3 border border-primary-200 dark:border-primary-700 rounded-xl bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                                    <option value="">None</option>
                                    <option value="order">Order</option>
                                    <option value="payment">Payment</option>
                                    <option value="expense">Expense</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    Reference ID
                                </label>
                                <input type="number" name="reference_id" min="1"
                                       class="w-full px-4 py-3 border border-primary-200 dark:border-primary-700 rounded-xl bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary-500"
                                       placeholder="ID">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-slate-50 dark:bg-slate-900/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" name="add_transaction" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-gradient-to-r from-primary-600 to-secondary-500 text-base font-medium text-white hover:shadow-lg focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                        Add Transaction
                    </button>
                    <button type="button" onclick="closeAddModal()" class="mt-3 w-full inline-flex justify-center rounded-xl border border-primary-300 dark:border-primary-600 shadow-sm px-4 py-2 bg-white dark:bg-slate-800 text-base font-medium text-slate-700 dark:text-slate-300 hover:bg-primary-50 dark:hover:bg-primary-800/30 focus:outline-none sm:mt-0 sm:w-auto sm:text-sm">
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
                            Delete Transaction
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-slate-500 dark:text-slate-400" id="deleteModalMessage">
                                Are you sure you want to delete this transaction? This action cannot be undone.
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

function confirmDelete(id) {
    document.getElementById('deleteModal').classList.remove('hidden');
    document.getElementById('deleteModalMessage').innerHTML = `Are you sure you want to delete this transaction? This action cannot be undone.`;
    document.getElementById('confirmDeleteBtn').href = `transactions.php?delete=${id}`;
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}

// Close modals on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAddModal();
        closeDeleteModal();
    }
});

// Pre-fill account if coming from accounts page
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const accountId = urlParams.get('account_id');
    if (accountId && document.querySelector('select[name="account_id"]')) {
        document.querySelector('select[name="account_id"]').value = accountId;
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