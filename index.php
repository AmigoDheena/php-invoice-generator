<?php
// Check if functions are already loaded via composer autoloader
if (!function_exists('getInvoiceById')) {
    require_once 'includes/functions.php';
}
$pageTitle = 'Invoice Generator';
$invoices = getInvoices();
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
    <div class="max-w-6xl mx-auto px-6 py-10 bg-transparent">
        <header class="mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Invoice Generator</h1>
            <p class="text-gray-600">Create and manage your invoices easily</p>
        </header>

        <div class="flex justify-between mb-6">
            <a href="create_invoice.php" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded">
                <i class="fas fa-plus mr-2"></i>Create New Invoice
            </a>
            <a href="manage_companies.php" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded">
                <i class="fas fa-building mr-2"></i>Manage Companies
            </a>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">Your Invoices</h2>
            
            <?php if (empty($invoices)): ?>
                <p class="text-gray-500">You haven't created any invoices yet.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="border p-2 text-left">Invoice #</th>
                                <th class="border p-2 text-left">Date</th>
                                <th class="border p-2 text-left">Client</th>
                                <th class="border p-2 text-left">Amount</th>
                                <th class="border p-2 text-left">Status</th>
                                <th class="border p-2 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invoices as $invoice): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="border p-2"><?php echo $invoice['id']; ?></td>
                                    <td class="border p-2"><?php echo $invoice['date']; ?></td>
                                    <td class="border p-2"><?php echo $invoice['client_name']; ?></td>
                                    <td class="border p-2">Rs.<?php echo number_format($invoice['total'], 2); ?></td>
                                    <td class="border p-2">
                                        <span class="px-2 py-1 rounded text-xs 
                                        <?php echo $invoice['status'] === 'Paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                            <?php echo $invoice['status']; ?>
                                        </span>
                                    </td>
                                    <td class="border p-2">
                                        <div class="flex space-x-2">
                                            <a href="view_invoice.php?id=<?php echo $invoice['id']; ?>" class="text-blue-500 hover:text-blue-700" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit_invoice.php?id=<?php echo $invoice['id']; ?>" class="text-yellow-500 hover:text-yellow-700" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="download_pdf.php?id=<?php echo $invoice['id']; ?>" class="text-green-500 hover:text-green-700" title="Download PDF">
                                                <i class="fas fa-file-pdf"></i>
                                            </a>
                                            <a href="delete_invoice.php?id=<?php echo $invoice['id']; ?>" class="text-red-500 hover:text-red-700" title="Delete" 
                                               onclick="return confirm('Are you sure you want to delete this invoice?');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>
