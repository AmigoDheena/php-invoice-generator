<?php
// Check if functions are already loaded via composer autoloader
if (!function_exists('getInvoiceById')) {
    require_once 'includes/functions.php';
}
$pageTitle = 'Create Invoice';
$companies = getCompanies();

// Default values
$invoice = [
    'id' => '',
    'date' => date('Y-m-d'),
    'due_date' => date('Y-m-d', strtotime('+30 days')),
    'client_name' => '',
    'client_email' => '',
    'client_address' => '',
    'company_id' => $companies[0]['id'] ?? '',
    'apply_tax' => true,
    'document_type' => 'Invoice',
    'items' => [
        [
            'description' => '',
            'quantity' => 1,
            'price' => 0
        ]
    ],
    'notes' => '',
    'status' => 'Unpaid'
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $invoice = [
        'id' => $_POST['invoice_id'],
        'date' => $_POST['invoice_date'],
        'due_date' => $_POST['due_date'],
        'client_name' => $_POST['client_name'],
        'client_email' => $_POST['client_email'],
        'client_address' => $_POST['client_address'],
        'company_id' => $_POST['company_id'],
        'apply_tax' => isset($_POST['apply_tax']),
        'document_type' => $_POST['document_type'],
        'items' => [],
        'notes' => $_POST['notes'],
        'status' => $_POST['status']
    ];
    
    // Process invoice items
    $descriptions = $_POST['description'];
    $quantities = $_POST['quantity'];
    $prices = $_POST['price'];
    
    for ($i = 0; $i < count($descriptions); $i++) {
        if (!empty($descriptions[$i])) {
            $invoice['items'][] = [
                'description' => $descriptions[$i],
                'quantity' => (float) $quantities[$i],
                'price' => (float) $prices[$i]
            ];
        }
    }
    
    // Calculate totals
    $totals = calculateInvoiceTotals($invoice['items'], $invoice['apply_tax']);
    $invoice['subtotal'] = $totals['subtotal'];
    $invoice['tax'] = $totals['tax'];
    $invoice['total'] = $totals['total'];
    
    // Save invoice
    $invoiceId = saveInvoice($invoice);
    
    // Redirect to view invoice
    header("Location: view_invoice.php?id=$invoiceId");
    exit;
}
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
    <div class="container mx-auto px-4 py-8">
        <header class="mb-6">
            <h1 class="text-3xl font-bold text-gray-800"><?php echo $pageTitle; ?></h1>
            <p class="text-gray-600">Fill out the form to create a new invoice</p>
        </header>
        
        <a href="index.php" class="inline-block mb-4 text-blue-500 hover:text-blue-700">
            <i class="fas fa-arrow-left mr-2"></i>Back to Invoices
        </a>
        
        <form action="create_invoice.php" method="post" class="bg-white rounded-lg shadow-md p-6">
            <input type="hidden" name="invoice_id" value="<?php echo $invoice['id']; ?>">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <h2 class="text-xl font-semibold mb-4">Client Information</h2>
                    
                    <div class="mb-4">
                        <label for="client_name" class="block text-gray-700 font-medium mb-2">Company Name (Invoice To)</label>
                        <input type="text" id="client_name" name="client_name" value="<?php echo $invoice['client_name']; ?>" 
                               class="w-full border-gray-300 rounded-md shadow-sm p-2 border focus:border-blue-500 focus:ring focus:ring-blue-200" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="client_email" class="block text-gray-700 font-medium mb-2">Email (Email To)</label>
                        <input type="email" id="client_email" name="client_email" value="<?php echo $invoice['client_email']; ?>" 
                               class="w-full border-gray-300 rounded-md shadow-sm p-2 border focus:border-blue-500 focus:ring focus:ring-blue-200" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="client_address" class="block text-gray-700 font-medium mb-2">Address (Address To)</label>
                        <textarea id="client_address" name="client_address" rows="3" 
                                  class="w-full border-gray-300 rounded-md shadow-sm p-2 border focus:border-blue-500 focus:ring focus:ring-blue-200" required><?php echo $invoice['client_address']; ?></textarea>
                    </div>
                </div>
                
                <div>
                    <h2 class="text-xl font-semibold mb-4">Invoice Details</h2>
                    
                    <div class="mb-4">
                        <label for="invoice_date" class="block text-gray-700 font-medium mb-2">Invoice Date</label>
                        <input type="date" id="invoice_date" name="invoice_date" value="<?php echo $invoice['date']; ?>" 
                               class="w-full border-gray-300 rounded-md shadow-sm p-2 border focus:border-blue-500 focus:ring focus:ring-blue-200" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="due_date" class="block text-gray-700 font-medium mb-2">Due Date</label>
                        <input type="date" id="due_date" name="due_date" value="<?php echo $invoice['due_date']; ?>" 
                               class="w-full border-gray-300 rounded-md shadow-sm p-2 border focus:border-blue-500 focus:ring focus:ring-blue-200" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="company_id" class="block text-gray-700 font-medium mb-2">Invoice From (Your Company)</label>
                        <select id="company_id" name="company_id" class="w-full border-gray-300 rounded-md shadow-sm p-2 border focus:border-blue-500 focus:ring focus:ring-blue-200" required>
                            <?php foreach ($companies as $company): ?>
                                <option value="<?php echo $company['id']; ?>" <?php echo $company['id'] == $invoice['company_id'] ? 'selected' : ''; ?>>
                                    <?php echo $company['name']; ?> (<?php echo $company['email']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="mt-2">
                            <a href="manage_companies.php" class="text-blue-500 hover:text-blue-700 text-sm">
                                <i class="fas fa-plus mr-1"></i>Add New Company
                            </a>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="document_type" class="block text-gray-700 font-medium mb-2">Document Type</label>
                        <select id="document_type" name="document_type" class="w-full border-gray-300 rounded-md shadow-sm p-2 border focus:border-blue-500 focus:ring focus:ring-blue-200" required>
                            <option value="Invoice" <?php echo $invoice['document_type'] === 'Invoice' ? 'selected' : ''; ?>>Invoice</option>
                            <option value="Quotation" <?php echo $invoice['document_type'] === 'Quotation' ? 'selected' : ''; ?>>Quotation</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="status" class="block text-gray-700 font-medium mb-2">Status</label>
                        <select id="status" name="status" class="w-full border-gray-300 rounded-md shadow-sm p-2 border focus:border-blue-500 focus:ring focus:ring-blue-200" required>
                            <option value="Unpaid" <?php echo $invoice['status'] === 'Unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                            <option value="Paid" <?php echo $invoice['status'] === 'Paid' ? 'selected' : ''; ?>>Paid</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="flex items-center">
                            <input type="checkbox" name="apply_tax" value="1" <?php echo $invoice['apply_tax'] ? 'checked' : ''; ?> 
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <span class="ml-2">Apply Tax (18%)</span>
                        </label>
                    </div>
                </div>
            </div>
            
            <h2 class="text-xl font-semibold mb-4">Invoice Items</h2>
            
            <div class="overflow-x-auto mb-4">
                <table class="w-full border-collapse" id="items-table">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border p-2 text-left">Description</th>
                            <th class="border p-2 text-left">Quantity</th>
                            <th class="border p-2 text-left">Price</th>
                            <th class="border p-2 text-left">Total</th>
                            <th class="border p-2 text-left">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invoice['items'] as $index => $item): ?>
                            <tr class="item-row">
                                <td class="border p-2">
                                    <input type="text" name="description[]" value="<?php echo $item['description']; ?>" 
                                           class="w-full border-gray-300 rounded p-2 border focus:border-blue-500" required>
                                </td>
                                <td class="border p-2">
                                    <input type="number" name="quantity[]" value="<?php echo $item['quantity']; ?>" min="1" step="1" 
                                           class="w-full border-gray-300 rounded p-2 border focus:border-blue-500 item-quantity" required>
                                </td>
                                <td class="border p-2">
                                    <input type="number" name="price[]" value="<?php echo $item['price']; ?>" min="0" step="0.01" 
                                           class="w-full border-gray-300 rounded p-2 border focus:border-blue-500 item-price" required>
                                </td>
                                <td class="border p-2">
                                    <span class="item-total">Rs.0.00</span>
                                </td>
                                <td class="border p-2">
                                    <button type="button" class="text-red-500 hover:text-red-700 delete-row">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="mb-6">
                <button type="button" id="add-item" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-1 px-3 rounded text-sm">
                    <i class="fas fa-plus mr-1"></i>Add Item
                </button>
            </div>
            
            <div class="flex flex-col items-end mb-6">
                <div class="w-full md:w-64">
                    <div class="flex justify-between py-2">
                        <span class="font-medium">Subtotal:</span>
                        <span id="subtotal">Rs.0.00</span>
                    </div>
                    <div class="flex justify-between py-2" id="tax-row">
                        <span class="font-medium">Tax (18%):</span>
                        <span id="tax">Rs.0.00</span>
                    </div>
                    <div class="flex justify-between py-2 text-lg font-bold">
                        <span>Grand Total:</span>
                        <span id="grand-total">Rs.0.00</span>
                    </div>
                </div>
            </div>
            
            <div class="mb-6">
                <label for="notes" class="block text-gray-700 font-medium mb-2">Notes / Terms</label>
                <textarea id="notes" name="notes" rows="3" 
                          class="w-full border-gray-300 rounded-md shadow-sm p-2 border focus:border-blue-500 focus:ring focus:ring-blue-200"><?php echo $invoice['notes']; ?></textarea>
            </div>
            
            <div class="flex justify-end">
                <a href="index.php" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded mr-2">
                    Cancel
                </a>
                <button type="submit" id="submit-button" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded">
                    Create Invoice
                </button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add item row
            document.getElementById('add-item').addEventListener('click', function() {
                const tbody = document.querySelector('#items-table tbody');
                const newRow = document.createElement('tr');
                newRow.className = 'item-row';
                newRow.innerHTML = `
                    <td class="border p-2">
                        <input type="text" name="description[]" class="w-full border-gray-300 rounded p-2 border focus:border-blue-500" required>
                    </td>
                    <td class="border p-2">
                        <input type="number" name="quantity[]" value="1" min="1" step="1" 
                               class="w-full border-gray-300 rounded p-2 border focus:border-blue-500 item-quantity" required>
                    </td>
                    <td class="border p-2">
                        <input type="number" name="price[]" value="0" min="0" step="0.01" 
                               class="w-full border-gray-300 rounded p-2 border focus:border-blue-500 item-price" required>
                    </td>
                    <td class="border p-2">
                        <span class="item-total">Rs.0.00</span>
                    </td>
                    <td class="border p-2">
                        <button type="button" class="text-red-500 hover:text-red-700 delete-row">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(newRow);
                
                // Add event listeners to new row
                attachRowListeners(newRow);
                calculateTotals();
            });
            
            // Delete row
            function attachRowListeners(row) {
                const deleteButton = row.querySelector('.delete-row');
                const quantityInput = row.querySelector('.item-quantity');
                const priceInput = row.querySelector('.item-price');
                
                if (deleteButton) {
                    deleteButton.addEventListener('click', function() {
                        if (document.querySelectorAll('.item-row').length > 1) {
                            row.remove();
                            calculateTotals();
                        } else {
                            alert('Cannot remove the last row. You must have at least one item.');
                        }
                    });
                }
                
                if (quantityInput) {
                    quantityInput.addEventListener('input', calculateTotals);
                }
                
                if (priceInput) {
                    priceInput.addEventListener('input', calculateTotals);
                }
            }
            
            // Calculate row and invoice totals
            function calculateTotals() {
                const rows = document.querySelectorAll('.item-row');
                let subtotal = 0;
                
                rows.forEach(function(row) {
                    const quantity = parseFloat(row.querySelector('.item-quantity').value) || 0;
                    const price = parseFloat(row.querySelector('.item-price').value) || 0;
                    const total = quantity * price;
                    
                    row.querySelector('.item-total').textContent = 'Rs.' + total.toFixed(2);
                    subtotal += total;
                });
                
                document.getElementById('subtotal').textContent = 'Rs.' + subtotal.toFixed(2);
                
                const applyTax = document.querySelector('input[name="apply_tax"]').checked;
                const taxRow = document.getElementById('tax-row');
                
                let tax = 0;
                if (applyTax) {
                    tax = subtotal * 0.18;
                    taxRow.style.display = 'flex';
                } else {
                    taxRow.style.display = 'none';
                }
                
                document.getElementById('tax').textContent = 'Rs.' + tax.toFixed(2);
                document.getElementById('grand-total').textContent = 'Rs.' + (subtotal + tax).toFixed(2);
            }
            
            // Toggle tax calculation
            document.querySelector('input[name="apply_tax"]').addEventListener('change', calculateTotals);
            
            // Update button text based on document type selection
            const documentTypeSelect = document.getElementById('document_type');
            const submitButton = document.getElementById('submit-button');
            
            function updateButtonText() {
                submitButton.textContent = 'Create ' + documentTypeSelect.value;
            }
            
            documentTypeSelect.addEventListener('change', updateButtonText);
            updateButtonText(); // Initialize button text
            
            // Attach listeners to existing rows
            document.querySelectorAll('.item-row').forEach(function(row) {
                attachRowListeners(row);
            });
            
            // Initial calculation
            calculateTotals();
        });
    </script>
</body>
</html>
