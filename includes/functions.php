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
                'banking_details' => ''
            ]
        ];
        file_put_contents(COMPANIES_FILE, json_encode($defaultCompany));
    }
}

}

// Get all invoices
if (!function_exists('getInvoices')) {
function getInvoices() {
    initializeDataFiles();
    $data = file_get_contents(INVOICES_FILE);
    return json_decode($data, true) ?: [];
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

// Initialize data files, but only if this is the first time the file is included
// This prevents double initialization when using composer's autoloader
if (!defined('DATA_INITIALIZED')) {
    define('DATA_INITIALIZED', true);
    initializeDataFiles();
}
