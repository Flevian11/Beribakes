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
    SELECT oi.*, p.product_name
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->execute([$id]);
$items = $stmt->fetchAll();

// Get system info
$stmt = $pdo->query("SELECT * FROM system_info WHERE id = 1");
$system = $stmt->fetch();

// Calculate totals
$subtotal = 0;
foreach ($items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
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
                        <i class="fas fa-file-invoice text-primary-500 mr-2"></i>
                        Invoice #<?php echo $order['id']; ?>
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        <i class="far fa-clock mr-1"></i>
                        Generate and print invoice for this order
                    </p>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <a href="view.php?id=<?php echo $order['id']; ?>" class="px-4 py-2 border border-primary-300 dark:border-primary-600 text-primary-700 dark:text-primary-300 rounded-xl text-sm font-semibold hover:bg-primary-50 dark:hover:bg-primary-800/30 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Order
                </a>
                <button onclick="generatePDF()" class="px-4 py-2 bg-gradient-to-r from-primary-600 to-secondary-500 text-white rounded-xl text-sm font-semibold hover:shadow-lg transition-all flex items-center">
                    <i class="fas fa-file-pdf mr-2"></i>
                    Download PDF
                </button>
                <button onclick="window.print()" class="px-4 py-2 bg-primary-100 hover:bg-primary-200 dark:bg-primary-800/50 dark:hover:bg-primary-700 text-primary-700 dark:text-primary-300 rounded-xl text-sm font-semibold transition-colors flex items-center">
                    <i class="fas fa-print mr-2"></i>
                    Print
                </button>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-1 p-6 bg-slate-50/50 dark:bg-primary-900/50">
        <!-- Invoice Preview Card -->
        <div class="max-w-4xl mx-auto">
            <!-- Loading Indicator -->
            <div id="loadingIndicator" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 text-center">
                    <div class="w-16 h-16 border-4 border-primary-200 border-t-primary-600 rounded-full animate-spin mx-auto mb-4"></div>
                    <p class="text-slate-600 dark:text-slate-400">Generating PDF...</p>
                </div>
            </div>

            <!-- Invoice Content -->
            <div id="invoiceContent" class="bg-white dark:bg-slate-800 rounded-2xl p-8 bakery-card print:shadow-none print:p-0" style="font-family: 'Inter', sans-serif;">
                <!-- Invoice Header -->
                <div class="flex justify-between items-start mb-8 print:mb-6">
                    <div>
                        <h1 class="text-3xl font-bold bg-gradient-to-r from-primary-600 to-secondary-500 bg-clip-text text-transparent mb-2">BeriBakes</h1>
                        <p class="text-sm text-slate-600 dark:text-slate-400">Fresh Bread, Cakes & Pastries</p>
                        <p class="text-sm text-slate-600 dark:text-slate-400 mt-2">
                            <?php echo htmlspecialchars($system['address'] ?? 'Nairobi, Kenya'); ?><br>
                            Tel: <?php echo htmlspecialchars($system['contact_phone'] ?? '+254700000000'); ?><br>
                            Email: <?php echo htmlspecialchars($system['contact_email'] ?? 'info@beribakes.com'); ?>
                        </p>
                    </div>
                    <div class="text-right">
                        <div class="bg-primary-100 dark:bg-primary-800/30 px-6 py-3 rounded-xl">
                            <p class="text-sm text-slate-600 dark:text-slate-400">INVOICE</p>
                            <p class="text-2xl font-bold text-primary-600">#<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></p>
                        </div>
                        <p class="text-sm text-slate-500 mt-2">
                            Date: <?php echo date('F j, Y', strtotime($order['order_date'])); ?><br>
                            Time: <?php echo date('g:i A', strtotime($order['order_date'])); ?>
                        </p>
                    </div>
                </div>

                <!-- Bill To & Order Info -->
                <div class="grid grid-cols-2 gap-6 mb-8">
                    <div class="p-4 bg-primary-50 dark:bg-primary-800/30 rounded-xl">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-3 flex items-center">
                            <i class="fas fa-user-circle text-primary-500 mr-2"></i>
                            Bill To:
                        </h3>
                        <p class="text-base font-semibold text-slate-800 dark:text-slate-200">
                            <?php echo htmlspecialchars($order['customer_name'] ?? 'Walk-in Customer'); ?>
                        </p>
                        <?php if ($order['customer_phone']): ?>
                        <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                            <i class="fas fa-phone-alt text-secondary-500 mr-2 w-4"></i>
                            <?php echo htmlspecialchars($order['customer_phone']); ?>
                        </p>
                        <?php endif; ?>
                        <?php if ($order['customer_email']): ?>
                        <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                            <i class="fas fa-envelope text-secondary-500 mr-2 w-4"></i>
                            <?php echo htmlspecialchars($order['customer_email']); ?>
                        </p>
                        <?php endif; ?>
                    </div>
                    <div class="p-4 bg-primary-50 dark:bg-primary-800/30 rounded-xl">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-3 flex items-center">
                            <i class="fas fa-info-circle text-primary-500 mr-2"></i>
                            Order Information:
                        </h3>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-sm text-slate-600 dark:text-slate-400">Order Status:</span>
                                <span class="text-sm font-semibold px-2 py-1 rounded-full 
                                    <?php echo $order['order_status'] == 'completed' ? 'bg-emerald-100 text-emerald-700' : 
                                          ($order['order_status'] == 'processing' ? 'bg-blue-100 text-blue-700' : 
                                          ($order['order_status'] == 'pending' ? 'bg-amber-100 text-amber-700' : 
                                          'bg-rose-100 text-rose-700')); ?>">
                                    <?php echo ucfirst($order['order_status']); ?>
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-slate-600 dark:text-slate-400">Payment Method:</span>
                                <span class="text-sm font-semibold flex items-center">
                                    <i class="fas fa-<?php echo $order['payment_method'] == 'cash' ? 'money-bill-wave' : ($order['payment_method'] == 'mpesa' ? 'mobile-alt' : 'credit-card'); ?> mr-2 text-<?php echo $order['payment_method'] == 'cash' ? 'emerald' : ($order['payment_method'] == 'mpesa' ? 'secondary' : 'blue'); ?>-500"></i>
                                    <?php echo ucfirst($order['payment_method'] ?? 'Not specified'); ?>
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-slate-600 dark:text-slate-400">Payment Status:</span>
                                <span class="text-sm font-semibold <?php echo $order['payment_status'] == 'paid' ? 'text-emerald-600' : 'text-amber-600'; ?>">
                                    <?php echo ucfirst($order['payment_status'] ?? 'pending'); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="mb-8">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-primary-100 dark:bg-primary-800/50">
                                <th class="text-left py-3 px-4 text-sm font-semibold text-slate-700 dark:text-slate-300 rounded-l-lg">Item</th>
                                <th class="text-center py-3 px-4 text-sm font-semibold text-slate-700 dark:text-slate-300">Quantity</th>
                                <th class="text-right py-3 px-4 text-sm font-semibold text-slate-700 dark:text-slate-300">Unit Price</th>
                                <th class="text-right py-3 px-4 text-sm font-semibold text-slate-700 dark:text-slate-300 rounded-r-lg">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-primary-200 dark:divide-primary-700">
                            <?php foreach ($items as $index => $item): ?>
                            <tr class="<?php echo $index % 2 == 0 ? 'bg-white dark:bg-slate-800' : 'bg-primary-50/50 dark:bg-primary-800/20'; ?>">
                                <td class="py-3 px-4 text-sm text-slate-800 dark:text-slate-200">
                                    <?php echo htmlspecialchars($item['product_name']); ?>
                                </td>
                                <td class="py-3 px-4 text-center text-sm text-slate-600 dark:text-slate-400">
                                    <?php echo $item['quantity']; ?>
                                </td>
                                <td class="py-3 px-4 text-right text-sm text-slate-600 dark:text-slate-400">
                                    KES <?php echo number_format($item['price'], 2); ?>
                                </td>
                                <td class="py-3 px-4 text-right text-sm font-semibold text-primary-600">
                                    KES <?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Summary & Totals -->
                <div class="flex justify-end mb-8">
                    <div class="w-72">
                        <div class="space-y-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-600 dark:text-slate-400">Subtotal:</span>
                                <span class="font-medium text-slate-800 dark:text-slate-200">KES <?php echo number_format($subtotal, 2); ?></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-600 dark:text-slate-400">Tax (16% VAT):</span>
                                <span class="font-medium text-slate-800 dark:text-slate-200">KES <?php echo number_format($subtotal * 0.16, 2); ?></span>
                            </div>
                            <div class="border-t border-primary-200 dark:border-primary-700 pt-3">
                                <div class="flex justify-between">
                                    <span class="text-base font-semibold text-slate-800 dark:text-slate-200">Total:</span>
                                    <span class="text-xl font-bold text-primary-600">KES <?php echo number_format($subtotal * 1.16, 2); ?></span>
                                </div>
                            </div>
                            <?php if ($order['payment_status'] == 'paid'): ?>
                            <div class="mt-3 p-2 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg text-center">
                                <i class="fas fa-check-circle text-emerald-600 mr-1"></i>
                                <span class="text-sm text-emerald-700 dark:text-emerald-300">Payment received on <?php echo date('M j, Y', strtotime($order['paid_at'])); ?></span>
                            </div>
                            <?php else: ?>
                            <div class="mt-3 p-2 bg-amber-50 dark:bg-amber-900/20 rounded-lg text-center">
                                <i class="fas fa-clock text-amber-600 mr-1"></i>
                                <span class="text-sm text-amber-700 dark:text-amber-300">Payment pending</span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Delivery Information -->
                <?php if ($order['delivery_address']): ?>
                <div class="mb-8 p-4 bg-primary-50 dark:bg-primary-800/30 rounded-xl">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white mb-2 flex items-center">
                        <i class="fas fa-truck text-primary-500 mr-2"></i>
                        Delivery Address:
                    </h3>
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        <?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?>
                    </p>
                </div>
                <?php endif; ?>

                <!-- Footer Notes -->
                <div class="text-center text-sm text-slate-500 dark:text-slate-400 border-t border-primary-200 dark:border-primary-700 pt-6">
                    <p class="mb-1">Thank you for choosing BeriBakes Bakery!</p>
                    <p class="text-xs">This is a computer generated invoice. No signature required.</p>
                    <p class="text-xs mt-2">Terms: Payment due immediately. All sales are final.</p>
                </div>
            </div>

            <!-- PDF Generation Scripts -->
            <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
            
            <script>
            function generatePDF() {
                // Show loading indicator
                document.getElementById('loadingIndicator').classList.remove('hidden');
                
                const invoiceContent = document.getElementById('invoiceContent');
                
                // Set temporary styles for better PDF capture
                invoiceContent.style.backgroundColor = '#ffffff';
                invoiceContent.style.padding = '20px';
                
                // Use html2canvas to capture the invoice
                html2canvas(invoiceContent, {
                    scale: 2,
                    backgroundColor: '#ffffff',
                    logging: false,
                    allowTaint: true,
                    useCORS: true
                }).then((canvas) => {
                    const imgData = canvas.toDataURL('image/png');
                    
                    // Calculate dimensions
                    const imgWidth = 210; // A4 width in mm
                    const pageHeight = 297; // A4 height in mm
                    const imgHeight = (canvas.height * imgWidth) / canvas.width;
                    let heightLeft = imgHeight;
                    
                    // Create PDF
                    const { jsPDF } = window.jspdf;
                    const pdf = new jsPDF({
                        orientation: 'portrait',
                        unit: 'mm',
                        format: 'a4'
                    });
                    
                    let position = 0;
                    
                    // Add first page
                    pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight, undefined, 'FAST');
                    heightLeft -= pageHeight;
                    
                    // Add additional pages if needed
                    while (heightLeft > 0) {
                        position = heightLeft - imgHeight;
                        pdf.addPage();
                        pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight, undefined, 'FAST');
                        heightLeft -= pageHeight;
                    }
                    
                    // Download PDF
                    pdf.save(`invoice_${<?php echo $order['id']; ?>}_${new Date().toISOString().slice(0,10)}.pdf`);
                    
                    // Hide loading indicator
                    document.getElementById('loadingIndicator').classList.add('hidden');
                    
                    // Reset styles
                    invoiceContent.style.backgroundColor = '';
                    invoiceContent.style.padding = '';
                }).catch(error => {
                    console.error('PDF generation failed:', error);
                    alert('Failed to generate PDF. Please try again.');
                    document.getElementById('loadingIndicator').classList.add('hidden');
                });
            }
            </script>

            <!-- Print Styles -->
            <style media="print">
                @page {
                    size: A4;
                    margin: 10mm;
                }
                body * {
                    visibility: hidden;
                }
                #invoiceContent, #invoiceContent * {
                    visibility: visible;
                }
                #invoiceContent {
                    position: absolute;
                    left: 0;
                    top: 0;
                    width: 100%;
                    background: white;
                    padding: 20px;
                }
                .no-print {
                    display: none;
                }
                .bakery-card {
                    box-shadow: none !important;
                    border: none !important;
                }
            </style>

            <!-- Additional styling for better print/PDF appearance -->
            <style>
                @media print {
                    header, footer, .sidebar, .no-print, button, .tooltip {
                        display: none !important;
                    }
                    .flex-1 {
                        margin-left: 0 !important;
                        padding: 0 !important;
                    }
                }
                
                /* Ensure good contrast in PDF */
                .invoice-content {
                    background: white;
                    color: #1e293b;
                }
                
                .invoice-content .bg-primary-50 {
                    background-color: #fef3e9 !important;
                }
                
                .invoice-content .text-primary-600 {
                    color: #b45309 !important;
                }
                
                .invoice-content .text-slate-800 {
                    color: #1e293b !important;
                }
                
                .invoice-content .text-slate-600 {
                    color: #475569 !important;
                }
                
                .invoice-content .border-primary-200 {
                    border-color: #fed7aa !important;
                }
            </style>
        </div>
    </main>
</div>

<!-- Quick Actions Toast (optional) -->
<div class="fixed bottom-6 left-1/2 transform -translate-x-1/2 z-50 no-print">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-primary-200 dark:border-primary-700 p-3 flex items-center space-x-4">
        <i class="fas fa-file-invoice text-primary-500 text-xl"></i>
        <span class="text-sm text-slate-600 dark:text-slate-400">Ready to generate invoice</span>
        <button onclick="generatePDF()" class="px-3 py-1.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-xs font-medium transition-colors">
            Download PDF
        </button>
        <button onclick="window.print()" class="px-3 py-1.5 bg-secondary-100 hover:bg-secondary-200 dark:bg-secondary-800/50 dark:hover:bg-secondary-700 text-secondary-700 dark:text-secondary-300 rounded-lg text-xs font-medium transition-colors">
            Print
        </button>
    </div>
</div>

<?php include '../includes/footer.php'; ?>