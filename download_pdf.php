<?php
// Include the autoloader first
if (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
}

// Then include functions if needed
if (!function_exists('getInvoiceById')) {
    require_once 'includes/functions.php';
}

// Check if the dompdf library is available
if (!class_exists('Dompdf\\Dompdf')) {
    // Create a simple HTML version instead
    echo "<div style='background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px; font-family: Arial, sans-serif;'>";
    echo "<h2 style='color: #0d6efd;'>PDF Generation Not Available</h2>";
    echo "<p>The PDF generation feature requires the dompdf library, which is not installed.</p>";
    echo "<p><strong>Option 1:</strong> Install the library by running this command in the terminal:</p>";
    echo "<pre style='background-color: #f1f1f1; padding: 10px; border-radius: 4px;'>composer require dompdf/dompdf</pre>";
    echo "<p><strong>Option 2:</strong> Use the print function of your browser to print or save as PDF:</p>";
    echo "<p><a href='view_invoice.php?id=" . $_GET['id'] . "' style='display: inline-block; background-color: #0d6efd; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>View Printable Invoice</a></p>";
    echo "</div>";
    exit;
}

// Check for the GD extension which is required for image processing
if (!extension_loaded('gd')) {
    // We'll handle this in the logo section instead of showing an error message
    // This allows the PDF to be generated without the logo
    $gd_installed = false;
}

use Dompdf\Dompdf;
use Dompdf\Options;

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

// Create PDF options
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

// Create PDF document
$dompdf = new Dompdf($options);

// Invoice HTML content
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice ' . $invoice['id'] . '</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 15px;
            color: #333;
            font-size: 12px;
            line-height: 1.3;
        }
        .logo-container {
            background: white;
            padding: 5px;
            display: inline-block;
            border-radius: 3px;
            margin-bottom: 5px;
        }
        .invoice-header {
            width: 100%;
            margin-bottom: 15px;
        }
        .invoice-header table {
            width: 100%;
            border: none;
            margin-bottom: 0;
        }
        .invoice-header td {
            border: none;
            padding: 0;
            vertical-align: top;
        }
        .invoice-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 3px;
        }
        .company-info {
            text-align: left;
            width: 50%;
        }
        .company-info p {
            margin: 0;
            line-height: 1.2;
            font-size: 11px;
        }
        .invoice-info {
            text-align: right;
            width: 50%;
        }
        .invoice-info p {
            margin: 0;
            line-height: 1.2;
            font-size: 11px;
            text-align: right;
        }
        .invoice-info .invoice-title {
            text-align: right;
        }
        .invoice-info strong {
            text-align: right;
        }
        .client-info {
            margin-bottom: 15px;
            padding: 8px 0;
            border-top: 1px solid #c6e7ff97;
            border-bottom: 1px solid #c6e7ff97;
        }
        .client-info h3 {
            margin: 0 0 5px 0;
            font-size: 12px;
            color: #666;
        }
        .client-info p {
            margin: 0;
            line-height: 1.2;
            font-size: 11px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 11px;
        }
        th {
            background-color: #c6e7ff97;
            text-align: left;
            padding: 6px 8px;
            border-bottom: 1px solid #c6e7ff97;
            font-size: 11px;
            font-weight: bold;
        }
        th:nth-child(1) { width: 50%; }
        th:nth-child(2) { width: 15%; text-align: center; }
        th:nth-child(3) { width: 17.5%; text-align: right; }
        th:nth-child(4) { width: 17.5%; text-align: right; }
        td {
            padding: 6px 8px;
            border-bottom: 1px solid #c6e7ff97;
            font-size: 11px;
        }
        td:nth-child(2) { text-align: center; }
        td:nth-child(3), td:nth-child(4) { text-align: right; }
        .totals {
            width: 100%;
            margin-top: 10px;
            font-size: 11px;
        }
        .totals table {
            width: 250px;
            margin-left: auto;
            border: none;
            margin-bottom: 0;
        }
        .totals td {
            border: none;
            padding: 3px 0;
            font-size: 11px;
        }
        .totals .label {
            text-align: left;
            width: 60%;
        }
        .totals .amount {
            text-align: right;
            width: 40%;
        }
        .grand-total {
            font-weight: bold;
            border-top: 1px solid #c6e7ff97;
            padding-top: 3px;
            font-size: 12px;
        }
        .notes {
            margin-top: 15px;
            border-top: 1px solid #c6e7ff97;
            padding-top: 8px;
            font-size: 10px;
        }
        .banking-details {
            margin-top: 15px;
            border-top: 1px solid #c6e7ff97;
            padding: 8px;
            background-color: #c6e7ff97;
            border-radius: 3px;
            font-size: 10px;
            line-height: 1.3;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            color: #1046aa;
            border-top: 1px solid #c6e7ff97;
            padding-top: 10px;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="invoice-header">
        <table>
            <tr>
                <td class="company-info">
                    <div style="margin-bottom: 10px;">';

// For logo display, with detailed error handling
// Check if GD extension is loaded
$gd_installed = extension_loaded('gd');
// If GD extension is missing or not enabled, fall back to company name
if (!$gd_installed) {
    $html .= '<div class="invoice-title">' . $company['name'] . ' (install GD)</div>';
} else {
    if (!empty($company['logo'])) {
        if (file_exists($company['logo'])) {
            try {
                $logoData = file_get_contents($company['logo']);
                $logoType = pathinfo($company['logo'], PATHINFO_EXTENSION);
                
                if ($logoData !== false) {
                    // Convert to base64 and output
                    $base64Logo = base64_encode($logoData);
                    $imgSrc = 'data:image/' . $logoType . ';base64,' . $base64Logo;
                    
                    $html .= '<div class="logo-container"><img src="' . $imgSrc . '" style="max-height: 50px; max-width: 180px;" alt="' . htmlspecialchars($company['name']) . '"></div>';
                } else {
                    $html .= '<div class="invoice-title">' . $company['name'] . '</div>';
                }
            } catch (Exception $e) {
                // If any error occurs, show company name instead
                $html .= '<div class="invoice-title">' . $company['name'] . '</div>';
            }
        } else {
            // File doesn't exist at the path specified
            $html .= '<div class="invoice-title">' . $company['name'] . '</div>';
        }
    } else {
        // No logo specified in company record
        $html .= '<div class="invoice-title">' . $company['name'] . '</div>';
    }
}

$html .= '</div>
                    <p>' . $company['email'] . '<br>' . nl2br($company['address']);
            
if (!empty($company['phone'])) {
    $html .= '<br>' . $company['phone'];
}

$html .= '
                    </p>
                </td>
                <td class="invoice-info">
                    <div class="invoice-title" style="text-align: right;">' . ($invoice['document_type'] ?? 'INVOICE') . '</div>
                    <p style="text-align: right;">
                        ' . $invoice['id'] . '<br>
                        Date: ' . formatDate($invoice['date']) . '<br>
                        Due: ' . formatDate($invoice['due_date']) . '<br>
                        Status: <strong>' . $invoice['status'] . '</strong>
                    </p>
                </td>
            </tr>
        </table>
    </div>
    
    <div class="client-info">
        <h3>Billed To:</h3>
        <p><strong>' . $invoice['client_name'] . '</strong><br>
        ' . $invoice['client_email'] . '<br>
        ' . nl2br($invoice['client_address']) . '</p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>';
        
foreach ($invoice['items'] as $item) {
    $html .= '
            <tr>
                <td>' . $item['description'] . '</td>
                <td>' . number_format($item['quantity']) . '</td>
                <td>Rs.' . number_format($item['price'], 2) . '</td>
                <td>Rs.' . number_format($item['price'] * $item['quantity'], 2) . '</td>
            </tr>';
}

$html .= '
        </tbody>
    </table>
    
    <div class="totals">
        <table>
            <tr>
                <td class="label">Subtotal:</td>
                <td class="amount">Rs.' . number_format($invoice['subtotal'], 2) . '</td>
            </tr>';

if ($invoice['apply_tax']) {
    $html .= '
            <tr>
                <td class="label">Tax (18%):</td>
                <td class="amount">Rs.' . number_format($invoice['tax'], 2) . '</td>
            </tr>';
}

$html .= '
            <tr class="grand-total">
                <td class="label">Grand Total:</td>
                <td class="amount">Rs.' . number_format($invoice['total'], 2) . '</td>
            </tr>
        </table>
    </div>';

if (!empty($invoice['notes'])) {
    $html .= '
    <div class="notes">
        <strong>Notes:</strong><br>
        ' . nl2br($invoice['notes']) . '
    </div>';
}

// Add banking details if available
if (!empty($company['banking_details'])) {
    $html .= '
    <div class="banking-details">
        <strong>Payment Details:</strong><br>
        ' . nl2br($company['banking_details']) . '
    </div>';
}

$html .= '
    <div class="footer">
        <p>Thank you for your business!</p>
    </div>
</body>
</html>';

// Load HTML content
$dompdf->loadHtml($html);

// Set paper size and orientation
$dompdf->setPaper('A4', 'portrait');

// Render the HTML as PDF
$dompdf->render();

// Set the PDF filename with client name
$documentType = $invoice['document_type'] ?? 'Invoice';
$clientName = preg_replace('/[^a-zA-Z0-9\s]/', '', $invoice['client_name']); // Remove special characters
$clientName = preg_replace('/\s+/', '_', trim($clientName)); // Replace spaces with underscores
$invoiceNumber = str_replace(['INV-', ' '], ['', '_'], $invoice['id']);
$filename = $clientName . '_' . $documentType . '_' . $invoiceNumber . '.pdf';

// Output the generated PDF
$dompdf->stream($filename, ['Attachment' => true]);
exit;