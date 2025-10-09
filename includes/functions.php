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

// Initialize JSON files if they don't exist
if (!function_exists('initializeDataFiles')) {
function initializeDataFiles() {
    if (!file_exists(DATA_DIR)) {
        mkdir(DATA_DIR, 0755, true);
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

// Get all invoices with optional pagination
if (!function_exists('getInvoices')) {
function getInvoices($orderDesc = false, $page = null, $perPage = 10) {
    initializeDataFiles();
    $data = file_get_contents(INVOICES_FILE);
    $invoices = json_decode($data, true) ?: [];
    
    if ($orderDesc) {
        // Sort invoices in descending order by ID (newest first)
        usort($invoices, function($a, $b) {
            return strcmp($b['id'], $a['id']);
        });
    }
    
    // If pagination is requested
    if ($page !== null) {
        $totalItems = count($invoices);
        $offset = ($page - 1) * $perPage;
        $invoices = array_slice($invoices, $offset, $perPage);
        
        return [
            'data' => $invoices,
            'total' => $totalItems,
            'perPage' => $perPage,
            'currentPage' => $page,
            'lastPage' => ceil($totalItems / $perPage)
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

// Initialize data files, but only if this is the first time the file is included
// This prevents double initialization when using composer's autoloader
if (!defined('DATA_INITIALIZED')) {
    define('DATA_INITIALIZED', true);
    initializeDataFiles();
}
