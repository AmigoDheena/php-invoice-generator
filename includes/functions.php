<?php
// File path constants - only define if not already defined
if (!defined('DATA_DIR')) {
    define('DATA_DIR', __DIR__ . '/../data/');
}
if (!defined('INVOICES_FILE')) {
    define('INVOICES_FILE', DATA_DIR . 'invoices.json');
}
if (!defined('COMPANIES_FILE')) {
    define('COMPANIES_FILE', DATA_DIR . 'companies.json');
}
if (!defined('PRODUCTS_FILE')) {
    define('PRODUCTS_FILE', DATA_DIR . 'products.json');
}

// Additional data directories
if (!defined('BACKUPS_DIR')) {
    define('BACKUPS_DIR', DATA_DIR . 'backups/');
}
if (!defined('EXPORTS_DIR')) {
    define('EXPORTS_DIR', DATA_DIR . 'exports/');
}
if (!defined('SCHEMAS_DIR')) {
    define('SCHEMAS_DIR', DATA_DIR . 'schemas/');
}

// Initialize JSON files if they don't exist
if (!function_exists('initializeDataFiles')) {
function initializeDataFiles() {
    // Create main data directory if it doesn't exist
    if (!file_exists(DATA_DIR)) {
        mkdir(DATA_DIR, 0755, true);
    }
    
    // Create additional data directories
    $directories = [BACKUPS_DIR, EXPORTS_DIR, SCHEMAS_DIR];
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
    }
    
    if (!file_exists(INVOICES_FILE)) {
        file_put_contents(INVOICES_FILE, json_encode([]));
    }
    
    if (!file_exists(COMPANIES_FILE)) {
        $defaultCompany = [
            [
                'id' => 1,
                'name' => 'My Company',
                'email' => 'info@mycompany.com',
                'address' => '123 Business St, City, Country',
                'phone' => '123-456-7890',
                'banking_details' => '',
                'logo' => '' // Empty logo path
            ]
        ];
        file_put_contents(COMPANIES_FILE, json_encode($defaultCompany));
    }
    
    // Create logos directory if it doesn't exist
    $logosDir = __DIR__ . '/../uploads/logos';
    if (!file_exists($logosDir)) {
        mkdir($logosDir, 0755, true);
    }
    
    // Initialize products catalog file if it doesn't exist
    if (!file_exists(PRODUCTS_FILE)) {
        $initialProducts = [
            "products" => [],
            "last_id" => 0
        ];
        file_put_contents(PRODUCTS_FILE, json_encode($initialProducts, JSON_PRETTY_PRINT));
    }
}

}

// Get all invoices with optional filtering, sorting and pagination
if (!function_exists('getInvoices')) {
function getInvoices($orderDesc = false, $page = null, $perPage = 10, $filters = [], $sortBy = null, $sortOrder = 'desc') {
    initializeDataFiles();
    $data = file_get_contents(INVOICES_FILE);
    $invoices = json_decode($data, true) ?: [];
    
    // Apply filters if provided
    if (!empty($filters)) {
        $invoices = array_filter($invoices, function($invoice) use ($filters) {
            $match = true;
            
            // Filter by client name or email
            if (!empty($filters['client'])) {
                $clientSearch = strtolower($filters['client']);
                $clientName = strtolower($invoice['client_name'] ?? '');
                $clientEmail = strtolower($invoice['client_email'] ?? '');
                
                if (strpos($clientName, $clientSearch) === false && 
                    strpos($clientEmail, $clientSearch) === false) {
                    return false;
                }
            }
            
            // Filter by status
            if (!empty($filters['status']) && $invoice['status'] !== $filters['status']) {
                return false;
            }
            
            // Filter by document type
            if (!empty($filters['document_type']) && 
                ($invoice['document_type'] ?? 'Invoice') !== $filters['document_type']) {
                return false;
            }
            
            // Filter by minimum amount
            if (isset($filters['min_amount']) && $filters['min_amount'] !== '' && 
                $invoice['total'] < (float)$filters['min_amount']) {
                return false;
            }
            
            // Filter by maximum amount
            if (isset($filters['max_amount']) && $filters['max_amount'] !== '' && 
                $invoice['total'] > (float)$filters['max_amount']) {
                return false;
            }
            
            // Filter by start date
            if (!empty($filters['start_date']) && 
                strtotime($invoice['date']) < strtotime($filters['start_date'])) {
                return false;
            }
            
            // Filter by end date
            if (!empty($filters['end_date']) && 
                strtotime($invoice['date']) > strtotime($filters['end_date'])) {
                return false;
            }
            
            // Filter by invoice ID
            if (!empty($filters['invoice_id'])) {
                $idSearch = strtolower($filters['invoice_id']);
                $invoiceId = strtolower($invoice['id']);
                
                if (strpos($invoiceId, $idSearch) === false) {
                    return false;
                }
            }
            
            return $match;
        });
    }
    
    // Sort invoices
    if ($sortBy) {
        usort($invoices, function($a, $b) use ($sortBy, $sortOrder) {
            $valA = isset($a[$sortBy]) ? $a[$sortBy] : '';
            $valB = isset($b[$sortBy]) ? $b[$sortBy] : '';
            
            // Special case for date sorting
            if ($sortBy == 'date' || $sortBy == 'due_date') {
                $valA = strtotime($valA);
                $valB = strtotime($valB);
            }
            
            // For numeric values
            if (is_numeric($valA) && is_numeric($valB)) {
                $comparison = $valA <=> $valB;
            } else {
                $comparison = strcasecmp($valA, $valB);
            }
            
            return $sortOrder === 'asc' ? $comparison : -$comparison;
        });
    } elseif ($orderDesc) {
        // Sort invoices in descending order by ID (newest first)
        usort($invoices, function($a, $b) {
            return strcmp($b['id'], $a['id']);
        });
    }
    
    // If pagination is requested
    if ($page !== null) {
        $totalItems = count($invoices);
        $offset = ($page - 1) * $perPage;
        $paginatedInvoices = array_slice($invoices, $offset, $perPage);
        
        return [
            'data' => $paginatedInvoices,
            'total' => $totalItems,
            'perPage' => $perPage,
            'currentPage' => $page,
            'lastPage' => ceil($totalItems / $perPage),
            'allData' => $invoices // Include all filtered data (for export)
        ];
    }
    
    return $invoices;
}
}

// Get a specific invoice by ID
if (!function_exists('getInvoiceById')) {
function getInvoiceById($id) {
    $invoices = getInvoices();
    foreach ($invoices as $invoice) {
        if ($invoice['id'] == $id) {
            return $invoice;
        }
    }
    return null;
}
}

// Save invoice (create or update)
if (!function_exists('saveInvoice')) {
function saveInvoice($invoice) {
    $invoices = getInvoices();
    
    // If no ID provided, create a new one
    if (empty($invoice['id'])) {
        $invoice['id'] = generateInvoiceId();
        $invoices[] = $invoice;
    } else {
        // Update existing invoice
        $updated = false;
        foreach ($invoices as $key => $existingInvoice) {
            if ($existingInvoice['id'] == $invoice['id']) {
                $invoices[$key] = $invoice;
                $updated = true;
                break;
            }
        }
        
        // If not found, add as new
        if (!$updated) {
            $invoices[] = $invoice;
        }
    }
    
    file_put_contents(INVOICES_FILE, json_encode($invoices, JSON_PRETTY_PRINT));
    return $invoice['id'];
}
}

// Delete an invoice by ID
if (!function_exists('deleteInvoice')) {
function deleteInvoice($id) {
    $invoices = getInvoices();
    foreach ($invoices as $key => $invoice) {
        if ($invoice['id'] == $id) {
            unset($invoices[$key]);
            file_put_contents(INVOICES_FILE, json_encode(array_values($invoices), JSON_PRETTY_PRINT));
            return true;
        }
    }
    return false;
}
}

// Generate a new unique invoice ID
if (!function_exists('generateInvoiceId')) {
function generateInvoiceId() {
    $invoices = getInvoices();
    $maxId = 0;
    
    foreach ($invoices as $invoice) {
        $id = (int) str_replace('INV-', '', $invoice['id']);
        if ($id > $maxId) {
            $maxId = $id;
        }
    }
    
    $newId = $maxId + 1;
    return 'INV-' . str_pad($newId, 4, '0', STR_PAD_LEFT);
}
}

// Calculate invoice totals
if (!function_exists('calculateInvoiceTotals')) {
function calculateInvoiceTotals($items, $applyTax = true) {
    $subtotal = 0;
    $tax = 0;
    $total = 0;
    
    foreach ($items as $item) {
        $itemTotal = $item['price'] * $item['quantity'];
        $subtotal += $itemTotal;
    }
    
    if ($applyTax) {
        $tax = $subtotal * 0.18; // 18% tax
    }
    
    $total = $subtotal + $tax;
    
    return [
        'subtotal' => $subtotal,
        'tax' => $tax,
        'total' => $total
    ];
}
}

// Get all companies
if (!function_exists('getCompanies')) {
function getCompanies() {
    initializeDataFiles();
    $data = file_get_contents(COMPANIES_FILE);
    return json_decode($data, true) ?: [];
}
}

// Get company by ID
if (!function_exists('getCompanyById')) {
function getCompanyById($id) {
    $companies = getCompanies();
    foreach ($companies as $company) {
        if ($company['id'] == $id) {
            return $company;
        }
    }
    return null;
}
}

// Save company (create or update)
if (!function_exists('saveCompany')) {
function saveCompany($company) {
    $companies = getCompanies();
    
    // If no ID provided, create a new one
    if (empty($company['id'])) {
        $company['id'] = count($companies) + 1;
        $companies[] = $company;
    } else {
        // Update existing company
        $updated = false;
        foreach ($companies as $key => $existingCompany) {
            if ($existingCompany['id'] == $company['id']) {
                // Preserve logo if not updated
                if (!isset($company['logo']) && isset($existingCompany['logo'])) {
                    $company['logo'] = $existingCompany['logo'];
                }
                $companies[$key] = $company;
                $updated = true;
                break;
            }
        }
        
        // If not found, add as new
        if (!$updated) {
            $companies[] = $company;
        }
    }
    
    file_put_contents(COMPANIES_FILE, json_encode($companies, JSON_PRETTY_PRINT));
    return $company['id'];
}
}

// Delete a company by ID
if (!function_exists('deleteCompany')) {
function deleteCompany($id) {
    $companies = getCompanies();
    foreach ($companies as $key => $company) {
        if ($company['id'] == $id) {
            unset($companies[$key]);
            file_put_contents(COMPANIES_FILE, json_encode(array_values($companies), JSON_PRETTY_PRINT));
            return true;
        }
    }
    return false;
}
}

// Format currency
if (!function_exists('formatCurrency')) {
function formatCurrency($amount) {
    return number_format($amount, 2);
}
}

// Format date for display
if (!function_exists('formatDate')) {
function formatDate($date) {
    return date('F j, Y', strtotime($date));
}
}

// Get total count of invoices
if (!function_exists('getInvoiceCount')) {
function getInvoiceCount() {
    initializeDataFiles();
    $data = file_get_contents(INVOICES_FILE);
    $invoices = json_decode($data, true) ?: [];
    return count($invoices);
}
}

// Get all unique clients from invoices for reuse
if (!function_exists('getUniqueClients')) {
function getUniqueClients() {
    $invoices = getInvoices();
    $clients = [];
    $clientEmails = []; // Track emails to avoid duplicates
    
    foreach ($invoices as $invoice) {
        // Skip if no email or if we've already added this client
        if (empty($invoice['client_email']) || in_array($invoice['client_email'], $clientEmails)) {
            continue;
        }
        
        // Add to unique clients list
        $clients[] = [
            'name' => $invoice['client_name'],
            'email' => $invoice['client_email'],
            'address' => $invoice['client_address']
        ];
        
        // Add to emails tracker
        $clientEmails[] = $invoice['client_email'];
    }
    
    // Sort by client name alphabetically
    usort($clients, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
    
    return $clients;
}
}

// Get monthly revenue data for charts
if (!function_exists('getMonthlyRevenueSummary')) {
function getMonthlyRevenueSummary($limit = 6) {
    $invoices = getInvoices();
    $monthly = [];
    
    // Sort invoices by date (oldest first)
    usort($invoices, function($a, $b) {
        return strcmp($a['date'], $b['date']);
    });
    
    foreach ($invoices as $invoice) {
        $month = date('M Y', strtotime($invoice['date']));
        
        if (!isset($monthly[$month])) {
            $monthly[$month] = ['paid' => 0, 'unpaid' => 0];
        }
        
        if ($invoice['status'] === 'Paid') {
            $monthly[$month]['paid'] += $invoice['total'];
        } else {
            $monthly[$month]['unpaid'] += $invoice['total'];
        }
    }
    
    // Return the last X months
    if (count($monthly) > $limit) {
        $monthly = array_slice($monthly, -$limit, $limit, true);
    }
    
    return $monthly;
}
}

// Get invoice status distribution for pie chart
if (!function_exists('getInvoiceStatusBreakdown')) {
function getInvoiceStatusBreakdown() {
    $invoices = getInvoices();
    $status = ['Paid' => 0, 'Unpaid' => 0];
    
    foreach ($invoices as $invoice) {
        $status[$invoice['status']] += 1;
    }
    
    return $status;
}
}

// Get document type analysis (invoice vs quotation)
if (!function_exists('getDocumentTypeAnalysis')) {
function getDocumentTypeAnalysis() {
    $invoices = getInvoices();
    $types = [
        'Invoice' => ['count' => 0, 'value' => 0],
        'Quotation' => ['count' => 0, 'value' => 0]
    ];
    
    foreach ($invoices as $invoice) {
        $type = isset($invoice['document_type']) ? $invoice['document_type'] : 'Invoice';
        $types[$type]['count'] += 1;
        $types[$type]['value'] += $invoice['total'];
    }
    
    return $types;
}
}

// Get top clients by invoice value
if (!function_exists('getTopClientsByValue')) {
function getTopClientsByValue($limit = 5) {
    $invoices = getInvoices();
    $clients = [];
    
    foreach ($invoices as $invoice) {
        $clientName = $invoice['client_name'];
        if (!isset($clients[$clientName])) {
            $clients[$clientName] = 0;
        }
        $clients[$clientName] += $invoice['total'];
    }
    
    // Sort by value (highest first)
    arsort($clients);
    
    // Take top X clients
    return array_slice($clients, 0, $limit, true);
}
}

// Product Management Functions

// Get all products
if (!function_exists('getProducts')) {
function getProducts($categoryFilter = null) {
    initializeDataFiles();
    $data = file_get_contents(PRODUCTS_FILE);
    $productsData = json_decode($data, true) ?: ['products' => [], 'last_id' => 0];
    $products = $productsData['products'];
    
    // If category filter is provided, filter products
    if ($categoryFilter) {
        $products = array_filter($products, function($product) use ($categoryFilter) {
            return $product['category'] === $categoryFilter;
        });
    }
    
    // Sort products by name
    usort($products, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
    
    return $products;
}
}

// Get product by ID
if (!function_exists('getProductById')) {
function getProductById($id) {
    $products = getProducts();
    foreach ($products as $product) {
        if ($product['id'] == $id) {
            return $product;
        }
    }
    return null;
}
}

// Save product (create or update)
if (!function_exists('saveProduct')) {
function saveProduct($product) {
    initializeDataFiles();
    $data = file_get_contents(PRODUCTS_FILE);
    $productsData = json_decode($data, true) ?: ['products' => [], 'last_id' => 0];
    $products = $productsData['products'];
    $lastId = $productsData['last_id'];
    
    // If no ID provided or ID is 0, create a new product
    if (empty($product['id'])) {
        $lastId++;
        $product['id'] = $lastId;
        $products[] = $product;
    } else {
        // Update existing product
        $updated = false;
        foreach ($products as $key => $existingProduct) {
            if ($existingProduct['id'] == $product['id']) {
                $products[$key] = $product;
                $updated = true;
                break;
            }
        }
        
        // If not found, add as new
        if (!$updated) {
            $products[] = $product;
        }
    }
    
    // Update and save file
    $productsData['products'] = $products;
    $productsData['last_id'] = $lastId;
    file_put_contents(PRODUCTS_FILE, json_encode($productsData, JSON_PRETTY_PRINT));
    
    return $product['id'];
}
}

// Delete a product by ID
if (!function_exists('deleteProduct')) {
function deleteProduct($id) {
    initializeDataFiles();
    $data = file_get_contents(PRODUCTS_FILE);
    $productsData = json_decode($data, true) ?: ['products' => [], 'last_id' => 0];
    $products = $productsData['products'];
    
    foreach ($products as $key => $product) {
        if ($product['id'] == $id) {
            unset($products[$key]);
            $productsData['products'] = array_values($products);
            file_put_contents(PRODUCTS_FILE, json_encode($productsData, JSON_PRETTY_PRINT));
            return true;
        }
    }
    return false;
}
}

// Get all unique product categories
if (!function_exists('getProductCategories')) {
function getProductCategories() {
    $products = getProducts();
    $categories = [];
    
    foreach ($products as $product) {
        if (!empty($product['category']) && !in_array($product['category'], $categories)) {
            $categories[] = $product['category'];
        }
    }
    
    sort($categories);
    return $categories;
}
}

// Get all saved filters
if (!function_exists('getSavedFilters')) {
function getSavedFilters() {
    $filtersFile = DATA_DIR . 'saved_filters.json';
    
    if (!file_exists($filtersFile)) {
        file_put_contents($filtersFile, json_encode([]));
        return [];
    }
    
    $data = file_get_contents($filtersFile);
    return json_decode($data, true) ?: [];
}
}

// Save a filter preset
if (!function_exists('saveFilter')) {
function saveFilter($filterData) {
    $filtersFile = DATA_DIR . 'saved_filters.json';
    $filters = getSavedFilters();
    
    // Generate ID if not provided
    if (empty($filterData['id'])) {
        $maxId = 0;
        foreach ($filters as $filter) {
            if ($filter['id'] > $maxId) {
                $maxId = $filter['id'];
            }
        }
        $filterData['id'] = $maxId + 1;
        $filters[] = $filterData;
    } else {
        // Update existing filter
        $updated = false;
        foreach ($filters as $key => $filter) {
            if ($filter['id'] == $filterData['id']) {
                $filters[$key] = $filterData;
                $updated = true;
                break;
            }
        }
        
        if (!$updated) {
            $filters[] = $filterData;
        }
    }
    
    file_put_contents($filtersFile, json_encode($filters, JSON_PRETTY_PRINT));
    return $filterData['id'];
}
}

// Delete a saved filter
if (!function_exists('deleteFilter')) {
function deleteFilter($id) {
    $filtersFile = DATA_DIR . 'saved_filters.json';
    $filters = getSavedFilters();
    
    foreach ($filters as $key => $filter) {
        if ($filter['id'] == $id) {
            unset($filters[$key]);
            file_put_contents($filtersFile, json_encode(array_values($filters), JSON_PRETTY_PRINT));
            return true;
        }
    }
    return false;
}
}

// Export invoices to CSV
if (!function_exists('exportInvoicesToCSV')) {
function exportInvoicesToCSV($invoices) {
    // Define CSV headers
    $headers = [
        'Invoice ID',
        'Document Type',
        'Date',
        'Due Date',
        'Client Name',
        'Client Email',
        'Status',
        'Subtotal',
        'Tax',
        'Total'
    ];
    
    // Create a temporary file
    $temp = fopen('php://temp', 'r+');
    
    // Add BOM for Excel UTF-8 compatibility
    fputs($temp, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Add headers
    fputcsv($temp, $headers);
    
    // Add data rows
    foreach ($invoices as $invoice) {
        $row = [
            $invoice['id'],
            $invoice['document_type'] ?? 'Invoice',
            $invoice['date'],
            $invoice['due_date'],
            $invoice['client_name'],
            $invoice['client_email'],
            $invoice['status'],
            number_format($invoice['subtotal'], 2),
            number_format($invoice['tax'], 2),
            number_format($invoice['total'], 2)
        ];
        fputcsv($temp, $row);
    }
    
    // Reset pointer to beginning of file
    rewind($temp);
    
    // Get the contents
    $csv = stream_get_contents($temp);
    fclose($temp);
    
    return $csv;
}
}

// Data Management Functions

// Get storage statistics for all data files
if (!function_exists('getDataStorageStats')) {
function getDataStorageStats() {
    $stats = [
        'invoices' => [
            'count' => 0,
            'size' => '0 KB'
        ],
        'companies' => [
            'count' => 0,
            'size' => '0 KB'
        ],
        'products' => [
            'count' => 0,
            'size' => '0 KB'
        ],
        'total_size' => '0 KB',
        'last_backup' => null,
        'backup_history' => []
    ];
    
    // Invoices stats
    if (file_exists(INVOICES_FILE)) {
        $invoices = getInvoices();
        $stats['invoices']['count'] = count($invoices);
        $stats['invoices']['size'] = formatFileSize(filesize(INVOICES_FILE));
    }
    
    // Companies stats
    if (file_exists(COMPANIES_FILE)) {
        $companies = getCompanies();
        $stats['companies']['count'] = count($companies);
        $stats['companies']['size'] = formatFileSize(filesize(COMPANIES_FILE));
    }
    
    // Products stats
    if (file_exists(PRODUCTS_FILE)) {
        $productsData = json_decode(file_get_contents(PRODUCTS_FILE), true) ?: ['products' => []];
        $stats['products']['count'] = count($productsData['products'] ?? []);
        $stats['products']['size'] = formatFileSize(filesize(PRODUCTS_FILE));
    }
    
    // Calculate total size
    $totalBytes = filesize(INVOICES_FILE) + filesize(COMPANIES_FILE);
    if (file_exists(PRODUCTS_FILE)) {
        $totalBytes += filesize(PRODUCTS_FILE);
    }
    if (file_exists(DATA_DIR . 'saved_filters.json')) {
        $totalBytes += filesize(DATA_DIR . 'saved_filters.json');
    }
    
    $stats['total_size'] = formatFileSize($totalBytes);
    
    // Get backup history
    $backupsDir = DATA_DIR . 'backups';
    if (is_dir($backupsDir)) {
        $backupFiles = glob($backupsDir . '/*.zip');
        $backupHistory = [];
        
        foreach ($backupFiles as $file) {
            $filename = basename($file);
            if (preg_match('/backup_(\d{8})_(\d{6})\.zip/', $filename, $matches)) {
                $dateStr = $matches[1] . '_' . $matches[2];
                $timestamp = strtotime(
                    substr($matches[1], 0, 4) . '-' . 
                    substr($matches[1], 4, 2) . '-' . 
                    substr($matches[1], 6, 2) . ' ' .
                    substr($matches[2], 0, 2) . ':' . 
                    substr($matches[2], 2, 2) . ':' . 
                    substr($matches[2], 4, 2)
                );
                
                $backupHistory[] = [
                    'file' => $file,
                    'timestamp' => $timestamp,
                    'type' => strpos($filename, 'auto_') !== false ? 'automatic' : 'manual',
                    'size' => formatFileSize(filesize($file)),
                    'status' => 'success'
                ];
            }
        }
        
        // Sort by timestamp (newest first)
        usort($backupHistory, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });
        
        $stats['backup_history'] = $backupHistory;
        
        // Get last backup timestamp
        if (!empty($backupHistory)) {
            $stats['last_backup'] = $backupHistory[0]['timestamp'];
        }
    }
    
    return $stats;
}
}

// Format file size in human-readable format
if (!function_exists('formatFileSize')) {
function formatFileSize($bytes) {
    if ($bytes > 1048576) {
        return round($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes > 1024) {
        return round($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
}

// Create a backup archive of all data files
if (!function_exists('createBackupArchive')) {
function createBackupArchive($autoBackup = false) {
    // Create backups directory if it doesn't exist
    $backupsDir = DATA_DIR . 'backups';
    if (!file_exists($backupsDir)) {
        if (!mkdir($backupsDir, 0755, true)) {
            return false;
        }
    }
    
    // Generate backup filename with timestamp
    $timestamp = date('Ymd_His');
    $prefix = $autoBackup ? 'auto_backup_' : 'backup_';
    $backupFile = $backupsDir . '/' . $prefix . $timestamp . '.zip';
    
    // Create ZIP archive
    $zip = new ZipArchive();
    if ($zip->open($backupFile, ZipArchive::CREATE) !== true) {
        return false;
    }
    
    // Add data files to the archive
    $dataFiles = [
        'invoices.json' => INVOICES_FILE,
        'companies.json' => COMPANIES_FILE,
        'products.json' => PRODUCTS_FILE
    ];
    
    foreach ($dataFiles as $filename => $filepath) {
        if (file_exists($filepath)) {
            $zip->addFile($filepath, $filename);
        }
    }
    
    // Add saved filters if exists
    if (file_exists(DATA_DIR . 'saved_filters.json')) {
        $zip->addFile(DATA_DIR . 'saved_filters.json', 'saved_filters.json');
    }
    
    // Close the archive
    $zip->close();
    
    return $backupFile;
}
}

// Export all data to a ZIP archive
if (!function_exists('exportAllData')) {
function exportAllData() {
    // Create exports directory if it doesn't exist
    $exportsDir = DATA_DIR . 'exports';
    if (!file_exists($exportsDir)) {
        if (!mkdir($exportsDir, 0755, true)) {
            return false;
        }
    }
    
    // Generate export filename with timestamp
    $timestamp = date('Ymd_His');
    $exportFile = $exportsDir . '/export_' . $timestamp . '.zip';
    
    // Create ZIP archive
    $zip = new ZipArchive();
    if ($zip->open($exportFile, ZipArchive::CREATE) !== true) {
        return false;
    }
    
    // Add data files to the archive
    $dataFiles = [
        'invoices.json' => INVOICES_FILE,
        'companies.json' => COMPANIES_FILE,
        'products.json' => PRODUCTS_FILE
    ];
    
    foreach ($dataFiles as $filename => $filepath) {
        if (file_exists($filepath)) {
            $zip->addFile($filepath, $filename);
        }
    }
    
    // Add saved filters if exists
    if (file_exists(DATA_DIR . 'saved_filters.json')) {
        $zip->addFile(DATA_DIR . 'saved_filters.json', 'saved_filters.json');
    }
    
    // Close the archive
    $zip->close();
    
    return $exportFile;
}
}

// Import data from a ZIP archive
if (!function_exists('importAllData')) {
function importAllData($zipFile) {
    // Temporary directory for extraction
    $tempDir = sys_get_temp_dir() . '/invoice_import_' . uniqid();
    if (!mkdir($tempDir, 0755, true)) {
        return "Failed to create temporary directory";
    }
    
    // Open the ZIP archive
    $zip = new ZipArchive();
    if ($zip->open($zipFile) !== true) {
        return "Failed to open ZIP archive";
    }
    
    // Extract to temporary directory
    $zip->extractTo($tempDir);
    $zip->close();
    
    // Validate required files
    $requiredFiles = ['invoices.json', 'companies.json'];
    foreach ($requiredFiles as $file) {
        if (!file_exists($tempDir . '/' . $file)) {
            return "Invalid backup archive: missing $file";
        }
    }
    
    // Import data files
    $dataFiles = [
        'invoices.json' => INVOICES_FILE,
        'companies.json' => COMPANIES_FILE,
        'products.json' => PRODUCTS_FILE,
        'saved_filters.json' => DATA_DIR . 'saved_filters.json'
    ];
    
    foreach ($dataFiles as $filename => $destination) {
        if (file_exists($tempDir . '/' . $filename)) {
            // Validate JSON structure
            $content = file_get_contents($tempDir . '/' . $filename);
            $json = json_decode($content, true);
            if ($json === null && json_last_error() !== JSON_ERROR_NONE) {
                return "Invalid JSON data in $filename";
            }
            
            // Create a backup of existing file
            if (file_exists($destination)) {
                copy($destination, $destination . '.bak');
            }
            
            // Copy imported file
            if (!copy($tempDir . '/' . $filename, $destination)) {
                return "Failed to import $filename";
            }
        }
    }
    
    // Clean up temp directory
    array_map('unlink', glob($tempDir . '/*'));
    rmdir($tempDir);
    
    return true;
}
}

// Get cloud backup settings
if (!function_exists('getCloudBackupSettings')) {
function getCloudBackupSettings() {
    $settingsFile = DATA_DIR . 'cloud_backup_settings.json';
    
    if (!file_exists($settingsFile)) {
        return null;
    }
    
    $settings = json_decode(file_get_contents($settingsFile), true);
    return $settings;
}
}

// Save cloud backup settings
if (!function_exists('saveCloudBackupSettings')) {
function saveCloudBackupSettings($settings) {
    $settingsFile = DATA_DIR . 'cloud_backup_settings.json';
    $result = file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT));
    return $result !== false;
}
}

// Disable cloud backup
if (!function_exists('disableCloudBackup')) {
function disableCloudBackup() {
    $settings = getCloudBackupSettings();
    if ($settings) {
        $settings['active'] = false;
        return saveCloudBackupSettings($settings);
    }
    return false;
}
}

// Validate cloud backup settings
if (!function_exists('validateCloudBackupSettings')) {
function validateCloudBackupSettings($settings) {
    $requiredFields = ['provider', 'api_key'];
    foreach ($requiredFields as $field) {
        if (!isset($settings[$field]) || empty($settings[$field])) {
            return false;
        }
    }
    return true;
}
}

// Test cloud backup connection
if (!function_exists('testCloudBackupConnection')) {
function testCloudBackupConnection($settings) {
    // Check if cloud providers file exists
    if (!file_exists(__DIR__ . '/cloud/providers.php')) {
        return "Cloud provider integration not available";
    }
    
    require_once __DIR__ . '/cloud/providers.php';
    
    if (!validateCloudBackupSettings($settings)) {
        return "Invalid cloud backup settings";
    }
    
    $provider = $settings['provider'];
    $apiKey = $settings['api_key'];
    
    $credentials = [
        'api_key' => $apiKey,
        // Add other credentials as needed
    ];
    
    $cloudStorage = CloudStorageFactory::create($provider, $credentials);
    if (!$cloudStorage) {
        return "Unsupported cloud provider: " . ucfirst($provider);
    }
    
    if (!$cloudStorage->authenticate($credentials)) {
        return "Failed to authenticate with " . ucfirst($provider);
    }
    
    return true;
}
}

// Generate MySQL schema
if (!function_exists('generateMySQLSchema')) {
function generateMySQLSchema() {
    // Create schemas directory if it doesn't exist
    $schemasDir = DATA_DIR . 'schemas';
    if (!file_exists($schemasDir)) {
        if (!mkdir($schemasDir, 0755, true)) {
            return false;
        }
    }
    
    // Generate schema filename with timestamp
    $timestamp = date('Ymd_His');
    $schemaFile = $schemasDir . '/mysql_schema_' . $timestamp . '.sql';
    
    // Create schema SQL
    $schema = "-- MySQL Schema for Invoice Generator\n";
    $schema .= "-- Generated on " . date('Y-m-d H:i:s') . "\n\n";
    
    // Invoices table
    $schema .= "CREATE TABLE IF NOT EXISTS `invoices` (\n";
    $schema .= "  `id` varchar(20) NOT NULL,\n";
    $schema .= "  `date` date NOT NULL,\n";
    $schema .= "  `due_date` date NOT NULL,\n";
    $schema .= "  `client_name` varchar(255) NOT NULL,\n";
    $schema .= "  `client_email` varchar(255) NOT NULL,\n";
    $schema .= "  `client_address` text NOT NULL,\n";
    $schema .= "  `company_id` int(11) NOT NULL,\n";
    $schema .= "  `subtotal` decimal(10,2) NOT NULL,\n";
    $schema .= "  `tax` decimal(10,2) NOT NULL,\n";
    $schema .= "  `total` decimal(10,2) NOT NULL,\n";
    $schema .= "  `apply_tax` tinyint(1) NOT NULL DEFAULT '1',\n";
    $schema .= "  `document_type` varchar(20) NOT NULL DEFAULT 'Invoice',\n";
    $schema .= "  `status` varchar(20) NOT NULL DEFAULT 'Unpaid',\n";
    $schema .= "  `notes` text,\n";
    $schema .= "  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,\n";
    $schema .= "  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n";
    $schema .= "  PRIMARY KEY (`id`)\n";
    $schema .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n\n";
    
    // Invoice items table
    $schema .= "CREATE TABLE IF NOT EXISTS `invoice_items` (\n";
    $schema .= "  `id` int(11) NOT NULL AUTO_INCREMENT,\n";
    $schema .= "  `invoice_id` varchar(20) NOT NULL,\n";
    $schema .= "  `product_id` int(11) DEFAULT NULL,\n";
    $schema .= "  `description` varchar(255) NOT NULL,\n";
    $schema .= "  `quantity` decimal(10,2) NOT NULL,\n";
    $schema .= "  `price` decimal(10,2) NOT NULL,\n";
    $schema .= "  PRIMARY KEY (`id`),\n";
    $schema .= "  KEY `invoice_id` (`invoice_id`),\n";
    $schema .= "  CONSTRAINT `invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE\n";
    $schema .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n\n";
    
    // Companies table
    $schema .= "CREATE TABLE IF NOT EXISTS `companies` (\n";
    $schema .= "  `id` int(11) NOT NULL AUTO_INCREMENT,\n";
    $schema .= "  `name` varchar(255) NOT NULL,\n";
    $schema .= "  `email` varchar(255) NOT NULL,\n";
    $schema .= "  `address` text NOT NULL,\n";
    $schema .= "  `phone` varchar(50) DEFAULT NULL,\n";
    $schema .= "  `banking_details` text,\n";
    $schema .= "  `logo` varchar(255) DEFAULT NULL,\n";
    $schema .= "  PRIMARY KEY (`id`)\n";
    $schema .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n\n";
    
    // Products table
    $schema .= "CREATE TABLE IF NOT EXISTS `products` (\n";
    $schema .= "  `id` int(11) NOT NULL AUTO_INCREMENT,\n";
    $schema .= "  `name` varchar(255) NOT NULL,\n";
    $schema .= "  `description` text,\n";
    $schema .= "  `price` decimal(10,2) NOT NULL,\n";
    $schema .= "  `sku` varchar(50) DEFAULT NULL,\n";
    $schema .= "  `category` varchar(100) DEFAULT NULL,\n";
    $schema .= "  PRIMARY KEY (`id`)\n";
    $schema .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n\n";
    
    // Saved filters table
    $schema .= "CREATE TABLE IF NOT EXISTS `saved_filters` (\n";
    $schema .= "  `id` int(11) NOT NULL AUTO_INCREMENT,\n";
    $schema .= "  `name` varchar(255) NOT NULL,\n";
    $schema .= "  `filters` text NOT NULL,\n";
    $schema .= "  `sort_by` varchar(50) DEFAULT NULL,\n";
    $schema .= "  `sort_order` varchar(4) DEFAULT 'desc',\n";
    $schema .= "  PRIMARY KEY (`id`)\n";
    $schema .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n\n";
    
    // Write schema to file
    if (file_put_contents($schemaFile, $schema) === false) {
        return false;
    }
    
    return $schemaFile;
}
}

// Migrate data to MySQL
if (!function_exists('migrateToMySQL')) {
function migrateToMySQL($host, $dbname, $username, $password) {
    try {
        // Connect to MySQL server
        $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $pdo = new PDO($dsn, $username, $password, $options);
        
        // Create database schema
        $schemaFile = generateMySQLSchema();
        if (!$schemaFile) {
            return "Failed to generate schema";
        }
        
        $schema = file_get_contents($schemaFile);
        $statements = explode(';', $schema);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
        
        // Migrate companies data
        $companies = getCompanies();
        $stmt = $pdo->prepare("INSERT INTO companies (id, name, email, address, phone, banking_details, logo) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($companies as $company) {
            $stmt->execute([
                $company['id'],
                $company['name'],
                $company['email'],
                $company['address'],
                $company['phone'] ?? null,
                $company['banking_details'] ?? null,
                $company['logo'] ?? null
            ]);
        }
        
        // Migrate products data
        $products = getProducts();
        $stmt = $pdo->prepare("INSERT INTO products (id, name, description, price, sku, category) VALUES (?, ?, ?, ?, ?, ?)");
        
        foreach ($products as $product) {
            $stmt->execute([
                $product['id'],
                $product['name'],
                $product['description'] ?? null,
                $product['price'],
                $product['sku'] ?? null,
                $product['category'] ?? null
            ]);
        }
        
        // Migrate invoices and invoice items data
        $invoices = getInvoices();
        $invoiceStmt = $pdo->prepare("INSERT INTO invoices (id, date, due_date, client_name, client_email, client_address, company_id, subtotal, tax, total, apply_tax, document_type, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $itemStmt = $pdo->prepare("INSERT INTO invoice_items (invoice_id, product_id, description, quantity, price) VALUES (?, ?, ?, ?, ?)");
        
        foreach ($invoices as $invoice) {
            $invoiceStmt->execute([
                $invoice['id'],
                $invoice['date'],
                $invoice['due_date'],
                $invoice['client_name'],
                $invoice['client_email'],
                $invoice['client_address'],
                $invoice['company_id'],
                $invoice['subtotal'],
                $invoice['tax'],
                $invoice['total'],
                $invoice['apply_tax'] ? 1 : 0,
                $invoice['document_type'] ?? 'Invoice',
                $invoice['status'],
                $invoice['notes'] ?? null
            ]);
            
            foreach ($invoice['items'] as $item) {
                $itemStmt->execute([
                    $invoice['id'],
                    $item['product_id'] ?? null,
                    $item['description'],
                    $item['quantity'],
                    $item['price']
                ]);
            }
        }
        
        // Migrate saved filters data if exists
        if (file_exists(DATA_DIR . 'saved_filters.json')) {
            $filters = getSavedFilters();
            $stmt = $pdo->prepare("INSERT INTO saved_filters (id, name, filters, sort_by, sort_order) VALUES (?, ?, ?, ?, ?)");
            
            foreach ($filters as $filter) {
                $stmt->execute([
                    $filter['id'],
                    $filter['name'],
                    json_encode($filter['filters'] ?? []),
                    $filter['sort_by'] ?? 'date',
                    $filter['sort_order'] ?? 'desc'
                ]);
            }
        }
        
        return true;
        
    } catch (PDOException $e) {
        return "Database error: " . $e->getMessage();
    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}
}

// Initialize data files, but only if this is the first time the file is included
// This prevents double initialization when using composer's autoloader
if (!defined('DATA_INITIALIZED')) {
    define('DATA_INITIALIZED', true);
    initializeDataFiles();
}
