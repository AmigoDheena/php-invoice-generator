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
            padding: 20px;
            color: #333;
        }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
        }
        .invoice-title {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .company-info {
            text-align: left;
        }
        .invoice-info {
            text-align: right;
        }
        .client-info {
            margin-bottom: 30px;
            padding: 10px 0;
            border-top: 1px solid #ddd;
            border-bottom: 1px solid #ddd;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th {
            background-color: #f2f2f2;
            text-align: left;
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        .text-right {
            text-align: right;
        }
        .totals {
            width: 300px;
            margin-left: auto;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
        }
        .grand-total {
            font-weight: bold;
            border-top: 1px solid #ddd;
            padding-top: 5px;
        }
        .notes {
            margin-top: 30px;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            color: #777;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <div class="invoice-header">
        <div class="company-info">
            <div class="invoice-title">' . $company['name'] . '</div>
            <p>' . $company['email'] . '<br>' . nl2br($company['address']);
            
if (!empty($company['phone'])) {
    $html .= '<br>' . $company['phone'];
}

$html .= '
            </p>
        </div>
        <div class="invoice-info">
            <div class="invoice-title">INVOICE</div>
            <p>
                ' . $invoice['id'] . '<br>
                Date: ' . formatDate($invoice['date']) . '<br>
                Due: ' . formatDate($invoice['due_date']) . '
            </p>
        </div>
    </div>
    
    <div class="client-info">
        <strong>Billed To:</strong><br>
        ' . $invoice['client_name'] . '<br>
        ' . $invoice['client_email'] . '<br>
        ' . nl2br($invoice['client_address']) . '
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th>Quantity</th>
                <th>Unit Price</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>';
        
foreach ($invoice['items'] as $item) {
    $html .= '
            <tr>
                <td>' . $item['description'] . '</td>
                <td>' . number_format($item['quantity']) . '</td>
                <td class="text-right">$' . number_format($item['price'], 2) . '</td>
                <td class="text-right">$' . number_format($item['price'] * $item['quantity'], 2) . '</td>
            </tr>';
}

$html .= '
        </tbody>
    </table>
    
    <div class="totals">
        <div class="total-row">
            <span>Subtotal:</span>
            <span>$' . number_format($invoice['subtotal'], 2) . '</span>
        </div>';

if ($invoice['apply_tax']) {
    $html .= '
        <div class="total-row">
            <span>Tax (18%):</span>
            <span>$' . number_format($invoice['tax'], 2) . '</span>
        </div>';
}

$html .= '
        <div class="total-row grand-total">
            <span>Grand Total:</span>
            <span>$' . number_format($invoice['total'], 2) . '</span>
        </div>
    </div>';

if (!empty($invoice['notes'])) {
    $html .= '
    <div class="notes">
        <strong>Notes:</strong><br>
        ' . nl2br($invoice['notes']) . '
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

// Set the PDF filename
$filename = 'Invoice_' . str_replace(['INV-', ' '], ['', '_'], $invoice['id']) . '.pdf';

// Output the generated PDF
$dompdf->stream($filename, ['Attachment' => true]);
exit;
