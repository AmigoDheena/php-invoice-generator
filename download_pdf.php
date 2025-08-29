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
$options->set('isPhpEnabled', true);
$options->set('defaultMediaType', 'print');
$options->set('isFontSubsettingEnabled', true);

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
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 15px;
            color: #333;
            font-size: 12px;
            line-height: 1.5;
            background-color: #ffffff;
        }
        .invoice-header {
            width: 100%;
            margin-bottom: 25px;
            position: relative;
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
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
            color: #3b82f6; /* Modern blue */
            letter-spacing: 1px;
        }
        .company-info {
            text-align: left;
            width: 50%;
        }
        .company-info p {
            margin: 0;
            line-height: 1.4;
            font-size: 11px;
        }
        .invoice-info {
            text-align: right;
            width: 50%;
        }
        .invoice-info p {
            margin: 0;
            line-height: 1.4;
            font-size: 11px;
        }
        .invoice-id {
            font-size: 14px;
            font-weight: bold;
            color: #3b82f6; /* Modern blue */
            margin-bottom: 5px;
        }
        .client-info {
            margin-bottom: 25px;
            padding: 15px;
            border-radius: 6px;
            background-color: #f8fafc; /* Very light blue/gray */
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            border-left: 4px solid #3b82f6; /* Modern blue accent */
        }
        .client-info h3 {
            margin: 0 0 8px 0;
            font-size: 13px;
            color: #4b5563;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .client-info p {
            margin: 0;
            line-height: 1.4;
            font-size: 12px;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border-radius: 6px;
            overflow: hidden;
        }
        table.items th {
            background-color: #3b82f6; /* Modern blue */
            color: white;
            text-align: left;
            padding: 12px 10px;
            font-size: 12px;
            font-weight: 600;
            border: none;
        }
        table.items th:nth-child(1) { width: 50%; }
        table.items th:nth-child(2) { width: 15%; text-align: center; }
        table.items th:nth-child(3) { width: 17.5%; text-align: right; }
        table.items th:nth-child(4) { width: 17.5%; text-align: right; }
        table.items td {
            padding: 10px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 12px;
        }
        table.items tr:last-child td {
            border-bottom: none;
        }
        table.items tr:nth-child(even) {
            background-color: #f9fafb;
        }
        table.items td:nth-child(2) { text-align: center; }
        table.items td:nth-child(3), table.items td:nth-child(4) { text-align: right; }
        .totals {
            width: 100%;
            margin-top: 20px;
            font-size: 12px;
        }
        .totals table {
            width: 300px;
            margin-left: auto;
            border: none;
            margin-bottom: 0;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        .totals td {
            border: none;
            padding: 10px;
            font-size: 12px;
            background-color: #f9fafb;
        }
        .totals .label {
            text-align: left;
            width: 60%;
            font-weight: 500;
        }
        .totals .amount {
            text-align: right;
            width: 40%;
            font-weight: 500;
        }
        .grand-total {
            font-weight: 700;
            font-size: 14px;
            color: #3b82f6; /* Modern blue */
            background-color: #eff6ff !important; /* Very light blue */
        }
        .notes {
            margin-top: 25px;
            padding: 15px;
            background-color: #f8fafc;
            border-radius: 6px;
            font-size: 11px;
            border-left: 4px solid #93c5fd; /* Light blue */
        }
        .banking-details {
            margin-top: 25px;
            padding: 15px;
            background-color: #f0f9ff; /* Very light blue */
            border-radius: 6px;
            font-size: 11px;
            line-height: 1.4;
            border-left: 4px solid #38bdf8; /* Sky blue */
        }
        .banking-details-title {
            font-weight: 600;
            color: #0369a1; /* Darker blue */
            margin-bottom: 8px;
            font-size: 12px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            color: #6b7280;
            padding-top: 15px;
            font-size: 11px;
            border-top: 1px dashed #e5e7eb;
        }
    </style>
</head>
<body>
    <div class="invoice-header">
        <table>
            <tr>
                <td class="company-info">';

// For logo display, with detailed error handling
$html .= '<div style="margin-bottom: 15px;">';

// If GD extension is missing or not enabled, fall back to company name
// Check if GD extension is loaded
$gd_installed = extension_loaded('gd');
// If GD extension is missing or not enabled, fall back to company name
if (!$gd_installed) {
    $html .= '<div class="invoice-title">' . $company['name'] . '</div>';
    // Optionally add a small note about missing GD extension
    $html .= '<div style="font-size:8px; color:#999; margin-bottom:5px;">Note: Logo display requires PHP GD extension</div>';
} else {
    // Debug mode - uncomment the next line to see the logo path in the PDF
    // $html .= '<div style="font-size:8px; color:#999;">Logo path: ' . htmlspecialchars($company['logo'] ?? 'Not set') . '</div>';

    if (!empty($company['logo'])) {
        // Get absolute path to the logo file
        $logoPath = __DIR__ . '/' . $company['logo']; // Use direct server path with __DIR__
        
        // Alternative approach if above doesn't work:
        // $logoPath = $_SERVER['DOCUMENT_ROOT'] . parse_url($company['logo'], PHP_URL_PATH);
        
        // Debug - show actual path being used
        // $html .= '<div style="font-size:8px; color:#999;">Resolved path: ' . htmlspecialchars($logoPath) . '</div>';
        
        if (file_exists($company['logo'])) {
            // Try to read the file directly from stored path
            try {
                $logoData = file_get_contents($company['logo']);
                $logoType = pathinfo($company['logo'], PATHINFO_EXTENSION);
                
                if ($logoData !== false) {
                    // Convert to base64 and output
                    $base64Logo = base64_encode($logoData);
                    $imgSrc = 'data:image/' . $logoType . ';base64,' . $base64Logo;
                    
                    $html .= '<img src="' . $imgSrc . '" style="max-height: 60px; max-width: 200px;" alt="' . htmlspecialchars($company['name']) . '">';
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

$html .= '</div>';

$html .= '
                    <p>' . nl2br($company['address']) . '<br>';

// contact line: email and optional phone separated by " | "
$html .= htmlspecialchars($company['email']);
if (!empty($company['phone'])) {
    $html .= ' | ' . htmlspecialchars($company['phone']);
}

$html .= '
                    </p>
                </td>
                <td class="invoice-info">
                    <div class="invoice-title">' . ($invoice['document_type'] ?? 'INVOICE') . '</div>
                    <div class="invoice-id">' . $invoice['id'] . '</div>
                    <p>
                        Issue Date: ' . formatDate($invoice['date']) . '<br>
                        Due Date: ' . formatDate($invoice['due_date']) . '<br>
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
    
    <table class="items">
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
        <div class="banking-details-title">Notes</div>
        ' . nl2br($invoice['notes']) . '
    </div>';
}

// Add banking details if available
if (!empty($company['banking_details'])) {
    $html .= '
    <div class="banking-details">
        <div class="banking-details-title">Payment Details</div>
        ' . nl2br($company['banking_details']) . '
    </div>';
}

$html .= '
    <div class="footer">
        <p>Thank you for your business!</p>
        <p>Generated on ' . date('F j, Y') . '</p>
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
$documentType = $invoice['document_type'] ?? 'Invoice';
$filename = $documentType . '_' . str_replace(['INV-', ' '], ['', '_'], $invoice['id']) . '.pdf';

// Output the generated PDF
$dompdf->stream($filename, ['Attachment' => true]);
exit;
