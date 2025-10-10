<?php
// Check if functions are already loaded via composer autoloader
if (!function_exists('getInvoiceById')) {
    require_once 'includes/functions.php';
}

// Process filter parameters
$filters = [];
$validFilters = ['client', 'status', 'document_type', 'min_amount', 'max_amount', 'start_date', 'end_date', 'invoice_id'];

foreach ($validFilters as $key) {
    if (isset($_GET[$key]) && $_GET[$key] !== '') {
        $filters[$key] = $_GET[$key];
    }
}

// Process sort parameters
$sortBy = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'date';
$sortOrder = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'desc';

// Get all invoices with filters (no pagination for export)
$result = getInvoices(false, null, null, $filters, $sortBy, $sortOrder);

// If we have paginated results, use allData property
if (is_array($result) && isset($result['allData'])) {
    $invoices = $result['allData'];
} else {
    $invoices = $result;
}

// Generate CSV content
$csv = exportInvoicesToCSV($invoices);

// Set headers for file download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=invoices-export-' . date('Y-m-d') . '.csv');

// Output CSV content
echo $csv;
exit;