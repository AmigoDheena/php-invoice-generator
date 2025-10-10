<?php
// Check if functions are already loaded via composer autoloader
if (!function_exists('getInvoiceById')) {
    require_once 'includes/functions.php';
}

// Function to build sort URLs while maintaining existing filters
function buildSortUrl($column) {
    global $sortBy, $sortOrder, $filters;
    
    $newSortOrder = ($sortBy === $column && $sortOrder === 'asc') ? 'desc' : 'asc';
    $params = $_GET;
    $params['sort_by'] = $column;
    $params['sort_order'] = $newSortOrder;
    
    return '?' . http_build_query($params);
}
$pageTitle = 'Invoice Generator';

// Process filter parameters
$filters = [];
$validFilters = ['client', 'status', 'document_type', 'min_amount', 'max_amount', 'start_date', 'end_date', 'invoice_id'];

// Check if a saved filter is being loaded
if (isset($_GET['load_filter'])) {
    $savedFilters = getSavedFilters();
    foreach ($savedFilters as $savedFilter) {
        if ($savedFilter['id'] == $_GET['load_filter']) {
            // Set filter values from saved filter
            if (!empty($savedFilter['filters'])) {
                $filters = $savedFilter['filters'];
            }
            
            // Set sorting from saved filter
            $sortBy = $savedFilter['sort_by'] ?? 'date';
            $sortOrder = $savedFilter['sort_order'] ?? 'desc';
            break;
        }
    }
} else {
    // Get filter values from request
    foreach ($validFilters as $key) {
        if (isset($_GET[$key]) && $_GET[$key] !== '') {
            $filters[$key] = $_GET[$key];
        }
    }
    
    // Get sort parameters
    $sortBy = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'date';
    $sortOrder = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'desc';
}

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$perPage = 10;

// Get invoices with filters, sorting and pagination
$result = getInvoices(true, $page, $perPage, $filters, $sortBy, $sortOrder);
$invoices = $result['data'];
$totalInvoices = $result['total'];
$lastPage = $result['lastPage'];

// Get all unique clients for filter dropdown
$uniqueClients = getUniqueClients();

$companies = getCompanies();
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
    <?php include_once 'includes/header.php'; ?>
    <div class="max-w-6xl mx-auto px-6 py-10 bg-transparent">
        <div class="mb-6 flex flex-wrap justify-between items-center">
            <div class="mb-2 md:mb-0">
                <button id="toggle-filter-form" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-filter mr-2"></i> Search & Filter
                </button>
                <a href="saved_filters.php" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 ml-2">
                    <i class="fas fa-bookmark mr-2"></i> Saved Filters
                </a>
                <?php if (!empty($filters)): ?>
                <a href="index.php" class="inline-flex items-center px-4 py-2 ml-2 text-sm font-medium text-red-700">
                    <i class="fas fa-times mr-1"></i> Clear Filters
                </a>
                <?php endif; ?>
            </div>
            <div>
                <a href="create_invoice.php" class="text-white font-semibold py-2 px-4 rounded" style="background-color: var(--primary-color); hover:background-color: var(--primary-dark);">
                    <i class="fas fa-plus mr-2"></i>Create New Invoice
                </a>
            </div>
        </div>
        
        <!-- Advanced Filter Form -->
        <div id="filter-form" class="bg-white shadow rounded-lg mb-6 <?php echo empty($filters) ? 'hidden' : ''; ?>">
            <div class="p-6">
                <h2 class="text-lg font-semibold mb-4 flex items-center">
                    <i class="fas fa-search mr-2"></i> Search & Filter Invoices
                </h2>
                
                <form action="index.php" method="get" id="invoice-filter-form">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Client Filter -->
                        <div class="mb-4">
                            <label for="client" class="block text-sm font-medium text-gray-700 mb-1">Client Name/Email</label>
                            <input type="text" name="client" id="client" class="w-full border-gray-300 rounded-md shadow-sm p-2 border focus:border-primary-color" value="<?php echo htmlspecialchars($filters['client'] ?? ''); ?>">
                        </div>
                        
                        <!-- Invoice ID Filter -->
                        <div class="mb-4">
                            <label for="invoice_id" class="block text-sm font-medium text-gray-700 mb-1">Invoice #</label>
                            <input type="text" name="invoice_id" id="invoice_id" class="w-full border-gray-300 rounded-md shadow-sm p-2 border focus:border-primary-color" value="<?php echo htmlspecialchars($filters['invoice_id'] ?? ''); ?>">
                        </div>
                        
                        <!-- Status Filter -->
                        <div class="mb-4">
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select name="status" id="status" class="w-full border-gray-300 rounded-md shadow-sm p-2 border focus:border-primary-color">
                                <option value="">All</option>
                                <option value="Paid" <?php echo (isset($filters['status']) && $filters['status'] === 'Paid') ? 'selected' : ''; ?>>Paid</option>
                                <option value="Unpaid" <?php echo (isset($filters['status']) && $filters['status'] === 'Unpaid') ? 'selected' : ''; ?>>Unpaid</option>
                            </select>
                        </div>
                        
                        <!-- Document Type Filter -->
                        <div class="mb-4">
                            <label for="document_type" class="block text-sm font-medium text-gray-700 mb-1">Document Type</label>
                            <select name="document_type" id="document_type" class="w-full border-gray-300 rounded-md shadow-sm p-2 border focus:border-primary-color">
                                <option value="">All</option>
                                <option value="Invoice" <?php echo (isset($filters['document_type']) && $filters['document_type'] === 'Invoice') ? 'selected' : ''; ?>>Invoice</option>
                                <option value="Quotation" <?php echo (isset($filters['document_type']) && $filters['document_type'] === 'Quotation') ? 'selected' : ''; ?>>Quotation</option>
                            </select>
                        </div>
                        
                        <!-- Min Amount Filter -->
                        <div class="mb-4">
                            <label for="min_amount" class="block text-sm font-medium text-gray-700 mb-1">Min Amount (Rs.)</label>
                            <input type="number" name="min_amount" id="min_amount" class="w-full border-gray-300 rounded-md shadow-sm p-2 border focus:border-primary-color" min="0" step="0.01" value="<?php echo htmlspecialchars($filters['min_amount'] ?? ''); ?>">
                        </div>
                        
                        <!-- Max Amount Filter -->
                        <div class="mb-4">
                            <label for="max_amount" class="block text-sm font-medium text-gray-700 mb-1">Max Amount (Rs.)</label>
                            <input type="number" name="max_amount" id="max_amount" class="w-full border-gray-300 rounded-md shadow-sm p-2 border focus:border-primary-color" min="0" step="0.01" value="<?php echo htmlspecialchars($filters['max_amount'] ?? ''); ?>">
                        </div>
                        
                        <!-- Start Date Filter -->
                        <div class="mb-4">
                            <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                            <input type="date" name="start_date" id="start_date" class="w-full border-gray-300 rounded-md shadow-sm p-2 border focus:border-primary-color" value="<?php echo htmlspecialchars($filters['start_date'] ?? ''); ?>">
                        </div>
                        
                        <!-- End Date Filter -->
                        <div class="mb-4">
                            <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                            <input type="date" name="end_date" id="end_date" class="w-full border-gray-300 rounded-md shadow-sm p-2 border focus:border-primary-color" value="<?php echo htmlspecialchars($filters['end_date'] ?? ''); ?>">
                        </div>
                        
                        <!-- Sort By Filter -->
                        <div class="mb-4">
                            <label for="sort_by" class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                            <select name="sort_by" id="sort_by" class="w-full border-gray-300 rounded-md shadow-sm p-2 border focus:border-primary-color">
                                <option value="date" <?php echo ($sortBy === 'date') ? 'selected' : ''; ?>>Date</option>
                                <option value="due_date" <?php echo ($sortBy === 'due_date') ? 'selected' : ''; ?>>Due Date</option>
                                <option value="id" <?php echo ($sortBy === 'id') ? 'selected' : ''; ?>>Invoice Number</option>
                                <option value="client_name" <?php echo ($sortBy === 'client_name') ? 'selected' : ''; ?>>Client Name</option>
                                <option value="total" <?php echo ($sortBy === 'total') ? 'selected' : ''; ?>>Amount</option>
                                <option value="status" <?php echo ($sortBy === 'status') ? 'selected' : ''; ?>>Status</option>
                            </select>
                        </div>
                        
                        <!-- Sort Order Filter -->
                        <div class="mb-4">
                            <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-1">Sort Order</label>
                            <select name="sort_order" id="sort_order" class="w-full border-gray-300 rounded-md shadow-sm p-2 border focus:border-primary-color">
                                <option value="desc" <?php echo ($sortOrder === 'desc') ? 'selected' : ''; ?>>Descending</option>
                                <option value="asc" <?php echo ($sortOrder === 'asc') ? 'selected' : ''; ?>>Ascending</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mt-4 flex justify-between">
                        <div>
                            <button type="submit" class="px-4 py-2 text-white font-semibold rounded" style="background-color: var(--primary-color);">
                                <i class="fas fa-search mr-2"></i>Apply Filters
                            </button>
                            <a href="index.php" class="px-4 py-2 bg-gray-300 text-gray-700 font-semibold rounded ml-2">
                                <i class="fas fa-times mr-2"></i>Reset
                            </a>
                        </div>
                        
                        <div class="flex items-center">
                            <!-- Save Filter Button (Opens Modal) -->
                            <button type="button" id="save-filter-btn" class="px-4 py-2 bg-gray-700 text-white font-medium rounded mr-2">
                                <i class="fas fa-save mr-2"></i>Save Filter
                            </button>
                            
                            <!-- Export CSV Button -->
                            <button type="button" id="export-csv-btn" class="px-4 py-2 bg-green-600 text-white font-medium rounded" onclick="exportToCSV();">
                                <i class="fas fa-file-csv mr-2"></i>Export CSV
                            </button>
                        </div>
                    </div>
                </form>
                
                <?php if (!empty($filters)): ?>
                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="text-sm text-gray-500">Active filters:</span>
                    <?php foreach ($filters as $key => $value): ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                            <?php 
                            switch($key) {
                                case 'client':
                                    echo "Client: $value";
                                    break;
                                case 'status':
                                    echo "Status: $value";
                                    break;
                                case 'document_type':
                                    echo "Type: $value";
                                    break;
                                case 'min_amount':
                                    echo "Min Amount: Rs.$value";
                                    break;
                                case 'max_amount':
                                    echo "Max Amount: Rs.$value";
                                    break;
                                case 'start_date':
                                    echo "From: $value";
                                    break;
                                case 'end_date':
                                    echo "To: $value";
                                    break;
                                case 'invoice_id':
                                    echo "Invoice ID: $value";
                                    break;
                            }
                            ?>
                        </span>
                    <?php endforeach; ?>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                        Sort: <?php echo $sortBy; ?> (<?php echo $sortOrder; ?>)
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Save Filter Modal -->
        <div id="save-filter-modal" class="fixed inset-0 z-10 overflow-y-auto hidden">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Save Filter</h3>
                                <div class="mt-2">
                                    <form id="save-filter-form" action="saved_filters.php" method="post">
                                        <input type="hidden" name="action" value="save">
                                        
                                        <div class="mb-4">
                                            <label for="filter_name" class="block text-sm font-medium text-gray-700">Filter Name</label>
                                            <input type="text" name="filter_name" id="filter_name" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:border-primary-color" placeholder="Enter a name for this filter" required>
                                        </div>
                                        
                                        <!-- Hidden fields to store filter data -->
                                        <div id="filter-data-container"></div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" id="confirm-save-filter" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 text-white font-medium sm:ml-3 sm:w-auto sm:text-sm" style="background-color: var(--primary-color);">
                            Save Filter
                        </button>
                        <button type="button" id="cancel-save-filter" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabbed Interface -->
        <div class="bg-white rounded-lg shadow-md">
            <!-- Tab Navigation -->
            <div class="border-b">
                <div class="flex">
                    <button class="tab-button py-3 px-6 border-b-2 border-primary font-medium text-primary bg-white" data-tab="invoices" style="border-color: var(--primary-color); color: var(--primary-color);">
                        <i class="fas fa-file-invoice mr-2"></i>Invoices
                    </button>
                    <button class="tab-button py-3 px-6 border-b-2 border-transparent font-medium text-gray-500 hover:text-gray-700 bg-white" data-tab="dashboard">
                        <i class="fas fa-chart-bar mr-2"></i>Dashboard
                    </button>
                </div>
            </div>

            <!-- Tab Contents -->
            <div class="tab-content" id="dashboard-tab" style="display: none;">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold">Analytics Dashboard</h2>
                        <button id="toggleCharts" class="text-sm text-gray-500 hover:text-gray-700">
                            <i class="fas fa-chevron-up"></i> Hide Charts
                        </button>
                    </div>
                    
                    <div id="chartsContainer" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Monthly Revenue Chart -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="flex justify-between items-center mb-2">
                                <h3 class="text-md font-medium">Monthly Revenue</h3>
                                <span class="text-sm text-gray-500" title="Shows paid vs. outstanding revenue over the last 6 months">
                                    <i class="fas fa-info-circle"></i>
                                </span>
                            </div>
                            <div class="h-64 chart-container">
                                <div class="loading-indicator">
                                    <i class="fas fa-spinner fa-spin"></i> Loading...
                                </div>
                                <canvas id="revenueChart"></canvas>
                            </div>
                        </div>
                        
                        <!-- Invoice Status Chart -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="flex justify-between items-center mb-2">
                                <h3 class="text-md font-medium">Invoice Status</h3>
                                <span class="text-sm text-gray-500" title="Distribution of paid vs. unpaid invoices">
                                    <i class="fas fa-info-circle"></i>
                                </span>
                            </div>
                            <div class="h-64 chart-container">
                                <div class="loading-indicator">
                                    <i class="fas fa-spinner fa-spin"></i> Loading...
                                </div>
                                <canvas id="statusChart"></canvas>
                            </div>
                        </div>
                        
                        <!-- Document Type Analysis -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="flex justify-between items-center mb-2">
                                <h3 class="text-md font-medium">Document Types</h3>
                                <span class="text-sm text-gray-500" title="Comparison of invoices vs. quotations">
                                    <i class="fas fa-info-circle"></i>
                                </span>
                            </div>
                            <div class="h-64 chart-container">
                                <div class="loading-indicator">
                                    <i class="fas fa-spinner fa-spin"></i> Loading...
                                </div>
                                <canvas id="documentTypeChart"></canvas>
                            </div>
                        </div>
                        
                        <!-- Top Clients Chart -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="flex justify-between items-center mb-2">
                                <h3 class="text-md font-medium">Top 5 Clients</h3>
                                <span class="text-sm text-gray-500" title="Clients with highest total invoice values">
                                    <i class="fas fa-info-circle"></i>
                                </span>
                            </div>
                            <div class="h-64 chart-container">
                                <div class="loading-indicator">
                                    <i class="fas fa-spinner fa-spin"></i> Loading...
                                </div>
                                <canvas id="clientChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Invoice List Tab (shown by default) -->
            <div class="tab-content" id="invoices-tab">
                <div class="p-6">
                    <h2 class="text-xl font-semibold mb-4">Your Invoices</h2>
                    
                    <?php if (empty($invoices)): ?>
                        <p class="text-gray-500">You haven't created any invoices yet.</p>
                    <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="border p-2 text-left">
                                    <a href="<?php echo buildSortUrl('id'); ?>" class="flex items-center">
                                        Doc # 
                                        <?php if ($sortBy === 'id'): ?>
                                            <i class="fas fa-sort-<?php echo $sortOrder === 'asc' ? 'up' : 'down'; ?> ml-1 text-gray-500"></i>
                                        <?php else: ?>
                                            <i class="fas fa-sort ml-1 text-gray-300"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th class="border p-2 text-left">
                                    <a href="<?php echo buildSortUrl('document_type'); ?>" class="flex items-center">
                                        Type 
                                        <?php if ($sortBy === 'document_type'): ?>
                                            <i class="fas fa-sort-<?php echo $sortOrder === 'asc' ? 'up' : 'down'; ?> ml-1 text-gray-500"></i>
                                        <?php else: ?>
                                            <i class="fas fa-sort ml-1 text-gray-300"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th class="border p-2 text-left">
                                    <a href="<?php echo buildSortUrl('date'); ?>" class="flex items-center">
                                        Date 
                                        <?php if ($sortBy === 'date'): ?>
                                            <i class="fas fa-sort-<?php echo $sortOrder === 'asc' ? 'up' : 'down'; ?> ml-1 text-gray-500"></i>
                                        <?php else: ?>
                                            <i class="fas fa-sort ml-1 text-gray-300"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th class="border p-2 text-left">
                                    <a href="<?php echo buildSortUrl('client_name'); ?>" class="flex items-center">
                                        Client 
                                        <?php if ($sortBy === 'client_name'): ?>
                                            <i class="fas fa-sort-<?php echo $sortOrder === 'asc' ? 'up' : 'down'; ?> ml-1 text-gray-500"></i>
                                        <?php else: ?>
                                            <i class="fas fa-sort ml-1 text-gray-300"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th class="border p-2 text-left">Company</th>
                                <th class="border p-2 text-left">
                                    <a href="<?php echo buildSortUrl('total'); ?>" class="flex items-center">
                                        Amount 
                                        <?php if ($sortBy === 'total'): ?>
                                            <i class="fas fa-sort-<?php echo $sortOrder === 'asc' ? 'up' : 'down'; ?> ml-1 text-gray-500"></i>
                                        <?php else: ?>
                                            <i class="fas fa-sort ml-1 text-gray-300"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th class="border p-2 text-left">
                                    <a href="<?php echo buildSortUrl('status'); ?>" class="flex items-center">
                                        Status 
                                        <?php if ($sortBy === 'status'): ?>
                                            <i class="fas fa-sort-<?php echo $sortOrder === 'asc' ? 'up' : 'down'; ?> ml-1 text-gray-500"></i>
                                        <?php else: ?>
                                            <i class="fas fa-sort ml-1 text-gray-300"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th class="border p-2 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invoices as $invoice): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="border p-2"><?php echo $invoice['id']; ?></td>
                                    <td class="border p-2"><?php echo $invoice['document_type'] ?? 'Invoice'; ?></td>
                                    <td class="border p-2"><?php echo $invoice['date']; ?></td>
                                    <td class="border p-2"><?php echo $invoice['client_name']; ?></td>
                                    <td class="border p-2">
                                        <?php 
                                        $companyName = "Unknown";
                                        foreach ($companies as $company) {
                                            if ($company['id'] == $invoice['company_id']) {
                                                $companyName = $company['name'];
                                                break;
                                            }
                                        }
                                        echo $companyName;
                                        ?>
                                    </td>
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
                    
                    <!-- Pagination -->
                    <?php if ($lastPage > 1): ?>
                    <div class="mt-6 flex justify-between items-center">
                        <div class="text-sm text-gray-500">
                            Showing <?php echo ($page-1)*$perPage+1; ?> to <?php echo min($page*$perPage, $totalInvoices); ?> of <?php echo $totalInvoices; ?> invoices
                        </div>
                        <div class="flex space-x-1">
                            <?php if ($page > 1): ?>
                            <a href="?page=1" class="px-3 py-1 rounded border bg-white hover:bg-gray-50">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                            <a href="?page=<?php echo $page-1; ?>" class="px-3 py-1 rounded border bg-white hover:bg-gray-50">
                                <i class="fas fa-angle-left"></i>
                            </a>
                            <?php endif; ?>
                            
                            <?php
                            // Calculate range of page numbers to show
                            $startPage = max(1, $page - 2);
                            $endPage = min($lastPage, $page + 2);
                            
                            // Always show at least 5 pages if available
                            if ($endPage - $startPage < 4 && $lastPage > 4) {
                                if ($startPage == 1) {
                                    $endPage = min($lastPage, 5);
                                } elseif ($endPage == $lastPage) {
                                    $startPage = max(1, $lastPage - 4);
                                }
                            }
                            
                            for ($i = $startPage; $i <= $endPage; $i++): 
                            ?>
                                <a href="?page=<?php echo $i; ?>" 
                                   class="px-3 py-1 rounded border <?php echo $i == $page ? 'text-white' : 'bg-white hover:bg-gray-50'; ?>" 
                                   style="<?php echo $i == $page ? 'background-color: var(--primary-color);' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $lastPage): ?>
                            <a href="?page=<?php echo $page+1; ?>" class="px-3 py-1 rounded border bg-white hover:bg-gray-50">
                                <i class="fas fa-angle-right"></i>
                            </a>
                            <a href="?page=<?php echo $lastPage; ?>" class="px-3 py-1 rounded border bg-white hover:bg-gray-50">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tab switching functionality
        const tabButtons = document.querySelectorAll('.tab-button');
        const tabContents = document.querySelectorAll('.tab-content');
        
        function switchTab(tabId) {
            // Hide all tab contents
            tabContents.forEach(content => {
                content.style.display = 'none';
            });
            
            // Remove active class from all buttons
            tabButtons.forEach(button => {
                button.classList.remove('text-gray-500');
                button.classList.add('border-transparent', 'text-gray-500');
                button.style.borderColor = '';
                button.style.color = '';
            });
            
            // Show the selected tab content
            document.getElementById(tabId + '-tab').style.display = 'block';
            
            // Add active class to the clicked button
            const activeTab = document.querySelector(`[data-tab="${tabId}"]`);
            activeTab.classList.remove('border-transparent', 'text-gray-500');
            activeTab.style.borderColor = 'var(--primary-color)';
            activeTab.style.color = 'var(--primary-color)';
            
            // If switching to dashboard, ensure charts are properly rendered
            if(tabId === 'dashboard') {
                window.dispatchEvent(new Event('resize'));
            }
        }
        
        // Add click event to tab buttons
        tabButtons.forEach(button => {
            button.addEventListener('click', () => {
                const tabId = button.getAttribute('data-tab');
                switchTab(tabId);
            });
        });
        
        // Set default tab to invoices
        switchTab('invoices');
        // Initial setup - hide loading indicators when charts are ready
        const hideLoading = (chartId) => {
            const container = document.querySelector(`#${chartId}`).closest('.chart-container');
            if (container) {
                const loader = container.querySelector('.loading-indicator');
                if (loader) {
                    loader.style.display = 'none';
                }
            }
        };

        // Chart color schemes
        const chartColors = {
            primary: getComputedStyle(document.documentElement).getPropertyValue('--primary-color').trim() || '#32c8a0',
            secondary: getComputedStyle(document.documentElement).getPropertyValue('--secondary-color').trim() || '#0d3155',
            blue: '#3B82F6',
            green: '#10B981',
            yellow: '#F59E0B',
            red: '#EF4444',
            purple: '#8B5CF6',
            gray: '#6B7280'
        };
        
        // Toggle charts visibility
        const toggleBtn = document.getElementById('toggleCharts');
        const chartsContainer = document.getElementById('chartsContainer');
        
        toggleBtn.addEventListener('click', function() {
            chartsContainer.classList.toggle('hidden');
            if (chartsContainer.classList.contains('hidden')) {
                toggleBtn.innerHTML = '<i class="fas fa-chevron-down"></i> Show Charts';
            } else {
                toggleBtn.innerHTML = '<i class="fas fa-chevron-up"></i> Hide Charts';
            }
        });
        
        // 1. Monthly Revenue Chart
        const revenueChartCtx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(revenueChartCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_keys(getMonthlyRevenueSummary())); ?>,
                datasets: [
                    {
                        label: 'Paid',
                        data: <?php 
                            $data = array_column(getMonthlyRevenueSummary(), 'paid');
                            echo json_encode(array_values($data)); 
                        ?>,
                        backgroundColor: chartColors.primary
                    },
                    {
                        label: 'Unpaid',
                        data: <?php 
                            $data = array_column(getMonthlyRevenueSummary(), 'unpaid');
                            echo json_encode(array_values($data)); 
                        ?>,
                        backgroundColor: chartColors.yellow
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += 'Rs.' + context.parsed.y.toFixed(2);
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        stacked: true,
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rs.' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
        hideLoading('revenueChart');
        
        // 2. Invoice Status Chart
        const statusData = <?php echo json_encode(getInvoiceStatusBreakdown()); ?>;
        const statusChartCtx = document.getElementById('statusChart').getContext('2d');
        const statusChart = new Chart(statusChartCtx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(statusData),
                datasets: [{
                    data: Object.values(statusData),
                    backgroundColor: [chartColors.green, chartColors.yellow],
                    borderColor: '#ffffff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label;
                                const value = context.raw;
                                const total = Object.values(statusData).reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                },
                cutout: '65%'
            }
        });
        hideLoading('statusChart');
        
        // 3. Document Type Analysis Chart
        const docTypeData = <?php echo json_encode(getDocumentTypeAnalysis()); ?>;
        const docTypeChartCtx = document.getElementById('documentTypeChart').getContext('2d');
        const docTypeChart = new Chart(docTypeChartCtx, {
            type: 'bar',
            data: {
                labels: Object.keys(docTypeData),
                datasets: [
                    {
                        label: 'Count',
                        data: Object.values(docTypeData).map(item => item.count),
                        backgroundColor: chartColors.blue,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Value',
                        data: Object.values(docTypeData).map(item => item.value),
                        backgroundColor: chartColors.purple,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.dataset.label === 'Value') {
                                    label += 'Rs.' + context.parsed.y.toFixed(2);
                                } else {
                                    label += context.parsed.y;
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Count'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false
                        },
                        title: {
                            display: true,
                            text: 'Value (Rs.)'
                        },
                        ticks: {
                            callback: function(value) {
                                return 'Rs.' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
        hideLoading('documentTypeChart');
        
        // 4. Top Clients Chart
        const clientData = <?php echo json_encode(getTopClientsByValue()); ?>;
        const clientLabels = Object.keys(clientData);
        const clientValues = Object.values(clientData);
        
        const clientChartCtx = document.getElementById('clientChart').getContext('2d');
        const clientChart = new Chart(clientChartCtx, {
            type: 'bar',
            data: {
                labels: clientLabels,
                datasets: [{
                    label: 'Total Invoice Value',
                    data: clientValues,
                    backgroundColor: clientLabels.map((_, i) => {
                        const colors = [chartColors.blue, chartColors.purple, chartColors.green, chartColors.yellow, chartColors.red];
                        return colors[i % colors.length];
                    }),
                    borderWidth: 0
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += 'Rs.' + context.parsed.x.toFixed(2);
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rs.' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
        hideLoading('clientChart');
    });
    </script>
    <style>
    .chart-container {
        position: relative;
    }
    .loading-indicator {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.8);
        z-index: 5;
    }
    .tab-button {
        position: relative;
        transition: all 0.3s ease;
    }
    .tab-button:hover {
        background-color: #f9fafb;
    }
    .tab-button.active {
        border-bottom-color: var(--primary-color);
        color: var(--primary-color);
    }
    .tab-content {
        display: none;
    }
    .tab-content.active {
        display: block;
        animation: fadeIn 0.5s;
    }
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    }
    /* Footer styles */
    .footer {
        margin-top: 3rem;
        padding: 1.5rem 0;
        border-top: 1px solid #e5e7eb;
        text-align: center;
        color: #6b7280;
    }
    .footer a {
        color: #3b82f6;
        text-decoration: none;
        transition: color 0.2s;
    }
    .footer a:hover {
        color: #2563eb;
        text-decoration: underline;
    }
    </style>

    <script src="assets/js/main.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle filter form visibility
            const toggleFilterBtn = document.getElementById('toggle-filter-form');
            const filterForm = document.getElementById('filter-form');
            
            if (toggleFilterBtn) {
                toggleFilterBtn.addEventListener('click', function() {
                    filterForm.classList.toggle('hidden');
                });
            }
            
            // Save filter functionality
            const saveFilterBtn = document.getElementById('save-filter-btn');
            const saveFilterModal = document.getElementById('save-filter-modal');
            const cancelSaveFilterBtn = document.getElementById('cancel-save-filter');
            const confirmSaveFilterBtn = document.getElementById('confirm-save-filter');
            const saveFilterForm = document.getElementById('save-filter-form');
            const filterDataContainer = document.getElementById('filter-data-container');
            
            if (saveFilterBtn) {
                saveFilterBtn.addEventListener('click', function() {
                    // Generate hidden fields for all filter values
                    const filterForm = document.getElementById('invoice-filter-form');
                    const formData = new FormData(filterForm);
                    
                    // Clear previous data
                    filterDataContainer.innerHTML = '';
                    
                    // Add hidden fields for each filter value
                    for (const [key, value] of formData.entries()) {
                        if (value !== '') {
                            const hiddenField = document.createElement('input');
                            hiddenField.type = 'hidden';
                            hiddenField.name = 'filters[' + key + ']';
                            hiddenField.value = value;
                            filterDataContainer.appendChild(hiddenField);
                        }
                    }
                    
                    // Add sort fields
                    const sortBy = document.getElementById('sort_by').value;
                    const sortOrder = document.getElementById('sort_order').value;
                    
                    const sortByField = document.createElement('input');
                    sortByField.type = 'hidden';
                    sortByField.name = 'sort_by';
                    sortByField.value = sortBy;
                    filterDataContainer.appendChild(sortByField);
                    
                    const sortOrderField = document.createElement('input');
                    sortOrderField.type = 'hidden';
                    sortOrderField.name = 'sort_order';
                    sortOrderField.value = sortOrder;
                    filterDataContainer.appendChild(sortOrderField);
                    
                    // Show the modal
                    saveFilterModal.classList.remove('hidden');
                });
            }
            
            if (cancelSaveFilterBtn) {
                cancelSaveFilterBtn.addEventListener('click', function() {
                    saveFilterModal.classList.add('hidden');
                });
            }
            
            if (confirmSaveFilterBtn) {
                confirmSaveFilterBtn.addEventListener('click', function() {
                    saveFilterForm.submit();
                });
            }
            
            // Export to CSV functionality
            window.exportToCSV = function() {
                const filterForm = document.getElementById('invoice-filter-form');
                const formData = new FormData(filterForm);
                
                // Create URL with current filter parameters
                let params = new URLSearchParams();
                for (const [key, value] of formData.entries()) {
                    if (value !== '') {
                        params.append(key, value);
                    }
                }
                
                // Redirect to export endpoint
                window.location.href = 'export_csv.php?' + params.toString();
            };
            
            // Handle pressing Enter in filter fields
            const filterInputs = document.querySelectorAll('#invoice-filter-form input');
            filterInputs.forEach(input => {
                input.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        document.getElementById('invoice-filter-form').submit();
                    }
                });
            });
        });
    </script>
    
    <?php include_once 'includes/footer.php'; ?>
</body>
</html>
