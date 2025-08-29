<?php
// Check if functions are already loaded via composer autoloader
if (!function_exists('getInvoiceById')) {
    require_once 'includes/functions.php';
}

// Check if ID is provided
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$invoiceId = $_GET['id'];
$invoice = getInvoiceById($invoiceId);

// If invoice not found, redirect to index
if (!$invoice) {
    header('Location: index.php');
    exit;
}

$company = getCompanyById($invoice['company_id']);
$documentType = $invoice['document_type'] ?? 'Invoice';
$pageTitle = $documentType . ' ' . $invoice['id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-gray-100">
    <div class="max-w-6xl mx-auto px-6 py-10 bg-transparent" id="invoice-content">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-800"><?php echo $pageTitle; ?></h1>
                <p class="text-gray-600">
                    <?php echo $invoice['status'] === 'Paid' ? 'Paid' : 'Unpaid'; ?>
                    <span class="px-2 py-1 rounded text-xs ml-2 
                    <?php echo $invoice['status'] === 'Paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                        <?php echo $invoice['status']; ?>
                    </span>
                </p>
            </div>
            <div class="flex space-x-2">
                <a href="index.php" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded">
                    <i class="fas fa-arrow-left mr-2"></i>Back
                </a>
                <a href="edit_invoice.php?id=<?php echo $invoiceId; ?>" class="bg-yellow-500 hover:bg-yellow-600 text-white font-semibold py-2 px-4 rounded">
                    <i class="fas fa-edit mr-2"></i>Edit
                </a>
                <a href="download_pdf.php?id=<?php echo $invoiceId; ?>" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded">
                    <i class="fas fa-file-pdf mr-2"></i>PDF
                </a>
                <button onclick="window.print()" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded print-invoice">
                    <i class="fas fa-print mr-2"></i>Print
                </button>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6 mb-6" id="printable-area">
            <div class="flex justify-between mb-8">
                <div>
                    <h2 class="text-2xl font-bold mb-1"><?php echo $company['name']; ?></h2>
                    <p class="text-gray-600"><?php echo $company['email']; ?></p>
                    <p class="text-gray-600"><?php echo $company['address']; ?></p>
                    <?php if (!empty($company['phone'])): ?>
                        <p class="text-gray-600"><?php echo $company['phone']; ?></p>
                    <?php endif; ?>
                </div>
                <div class="text-right">
                    <h1 class="text-3xl font-bold mb-2"><?php echo strtoupper($invoice['document_type'] ?? 'INVOICE'); ?></h1>
                    <p class="text-gray-600"><?php echo $invoice['id']; ?></p>
                    <p class="text-gray-600">Date: <?php echo formatDate($invoice['date']); ?></p>
                    <p class="text-gray-600">Due: <?php echo formatDate($invoice['due_date']); ?></p>
                </div>
            </div>
            
            <div class="border-t border-b border-gray-200 py-4 mb-6">
                <h3 class="text-gray-600 mb-2">Billed To:</h3>
                <p class="font-semibold"><?php echo $invoice['client_name']; ?></p>
                <p><?php echo $invoice['client_email']; ?></p>
                <p><?php echo nl2br($invoice['client_address']); ?></p>
            </div>
            
            <table class="w-full mb-6">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="text-left p-3 border-b">Description</th>
                        <th class="text-right p-3 border-b">Quantity</th>
                        <th class="text-right p-3 border-b">Unit Price</th>
                        <th class="text-right p-3 border-b">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoice['items'] as $item): ?>
                        <tr>
                            <td class="text-left p-3 border-b"><?php echo $item['description']; ?></td>
                            <td class="text-right p-3 border-b"><?php echo number_format($item['quantity']); ?></td>
                            <td class="text-right p-3 border-b">Rs.<?php echo number_format($item['price'], 2); ?></td>
                            <td class="text-right p-3 border-b">Rs.<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-right p-3 font-semibold">Subtotal:</td>
                        <td class="text-right p-3">Rs.<?php echo number_format($invoice['subtotal'], 2); ?></td>
                    </tr>
                    <?php if ($invoice['apply_tax']): ?>
                        <tr>
                            <td colspan="3" class="text-right p-3 font-semibold">Tax (18%):</td>
                            <td class="text-right p-3">Rs.<?php echo number_format($invoice['tax'], 2); ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr class="bg-gray-50">
                        <td colspan="3" class="text-right p-3 font-bold">Grand Total:</td>
                        <td class="text-right p-3 font-bold">Rs.<?php echo number_format($invoice['total'], 2); ?></td>
                    </tr>
                </tfoot>
            </table>
            
            <?php if (!empty($invoice['notes'])): ?>
                <div class="border-t border-gray-200 pt-4 mb-4">
                    <h3 class="text-gray-600 mb-2">Notes:</h3>
                    <p><?php echo nl2br($invoice['notes']); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($company['banking_details'])): ?>
                <div class="border-t border-gray-200 pt-4 mb-4 bg-gray-50 p-4 rounded">
                    <h3 class="text-gray-600 mb-2 font-semibold">Payment Details:</h3>
                    <p class="text-gray-700 whitespace-pre-line"><?php echo nl2br(htmlspecialchars($company['banking_details'])); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="text-center text-gray-500 mt-8 pt-8 border-t">
                <p>Thank you for your business!</p>
            </div>
        </div>
    </div>
</body>
</html>
