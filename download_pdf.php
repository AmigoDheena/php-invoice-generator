<?php
// Include the autoloader first
if (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
}

// Then include functions if needed
if (!function_exists('getInvoiceById')) {
    require_once 'includes/functions.php';
}

// Check if the TCPDF library is available
if (!class_exists('TCPDF')) {
    echo "<div style='background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px; font-family: Arial, sans-serif;'>";
    echo "<h2 style='color: #0d6efd;'>PDF Generation Not Available</h2>";
    echo "<p>The TCPDF library is not installed.</p>";
    echo "<p><strong>Install it by running:</strong></p>";
    echo "<pre style='background-color: #f1f1f1; padding: 10px; border-radius: 4px;'>composer require tecnickcom/tcpdf</pre>";
    echo "<p><a href='view_invoice.php?id=" . htmlspecialchars($_GET['id'] ?? '') . "' style='display: inline-block; background-color: #0d6efd; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px;'>View Invoice</a></p>";
    echo "</div>";
    exit;
}

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

// Extend TCPDF with custom header/footer
class ModernInvoicePDF extends TCPDF {
    protected $company;
    protected $invoice;
    
    public function __construct($company, $invoice) {
        parent::__construct(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $this->company = $company;
        $this->invoice = $invoice;
    }
    
    // Custom Header
    public function Header() {
        // Set gradient background for header
        $this->SetFillColor(79, 70, 229); // #4f46e5
        $this->Rect(0, 0, $this->getPageWidth(), 55, 'F');
        
        // Company logo/name
        $this->SetXY(15, 12);
        $this->SetTextColor(255, 255, 255);
        
        // Check if company has a logo file and if the file exists on the server
        if (!empty($this->company['logo']) && file_exists($this->company['logo'])) {
            try {
                // Set fill color to white (RGB: 255, 255, 255) for the logo background
                $this->SetFillColor(255, 255, 255);
                
                // Draw a rounded rectangle as logo background
                // Parameters: X=15mm, Y=8mm, Width=52mm, Height=15mm, Radius=4mm, Corners='1111' (all rounded), Style='F' (filled)
                $this->RoundedRect(15, 8, 52, 15, 4, '1111', 'F');
                
                // Insert the company logo image into the PDF
                // Parameters: file path, X=18mm, Y=11mm, Width=46mm, Height=0 (auto), type, URL, align, resize=false, dpi=300, palign, resize2, fitbox=1, hidden=false, fitonpage=false, alt=false, altimgs=array()
                $this->Image($this->company['logo'], 18, 11, 46, 0, '', '', '', false, 300, '', false, false, 1, 'LT');
                
                // Set cursor position for next content below the logo (X=15mm, Y=28mm)
                $this->SetXY(15, 28);
            } catch (Exception $e) {
                // If logo loading fails, display company name as fallback
                // Set cursor position to X=15mm, Y=12mm
                $this->SetXY(15, 12);
                
                // Set font to Helvetica, Bold, size 22pt
                $this->SetFont('helvetica', 'B', 22);
                
                // Display company name as text cell
                // Parameters: Width=0 (full width), Height=0, Text, Border=0 (no border), Ln=1 (move to next line), Align='L' (left), Fill=0 (no fill), Link='', Stretch=0
                $this->Cell(0, 0, $this->company['name'], 0, 1, 'L', 0, '', 0);
                
                // Move cursor down for next content (X=15mm, Y=22mm)
                $this->SetXY(15, 22);
            }
        } else {
            // If no logo exists, display company name instead
            // Set font to Helvetica, Bold, size 22pt
            $this->SetFont('helvetica', 'B', 22);
            
            // Display company name as text cell (left-aligned, full width)
            $this->Cell(0, 0, $this->company['name'], 0, 1, 'L', 0, '', 0);
            
            // Position cursor for next element (X=15mm, Y=22mm)
            $this->SetXY(15, 22);
        }
        
        // Company details
        $this->SetFont('helvetica', '', 9);
        $this->Cell(0, 0, $this->company['email'], 0, 1, 'L', 0, '', 0);
        
        $this->SetXY(15, 32);
        $addressLines = explode("\n", $this->company['address']);
        $addressText = implode(', ', array_map('trim', $addressLines));
        $this->Cell(0, 0, $addressText, 0, 1, 'L', 0, '', 0);
        
        if (!empty($this->company['phone'])) {
            $this->SetXY(15, 37);
            $this->Cell(0, 0, $this->company['phone'], 0, 1, 'L', 0, '', 0);
        }
        
        // Invoice details (right side)
        $rightMargin = 15;
        $pageWidth = $this->getPageWidth();
        
        $this->SetXY($rightMargin, 12);
        $this->SetFont('helvetica', 'B', 26);
        $this->Cell($pageWidth - ($rightMargin * 2), 0, strtoupper($this->invoice['document_type'] ?? 'INVOICE'), 0, 1, 'R', 0, '', 0);
        
        // Invoice number badge
        $this->SetFillColor(99, 102, 241); // #6366f1
        $badgeWidth = 45;
        $badgeX = $pageWidth - $rightMargin - $badgeWidth;
        $this->RoundedRect($badgeX, 20, $badgeWidth, 8, 3, '1111', 'F');
        $this->SetXY($badgeX, 21.5);
        $this->SetFont('helvetica', 'B', 11);
        $this->SetTextColor(255, 255, 255);
        $this->Cell($badgeWidth, 0, $this->invoice['id'], 0, 1, 'C', 0, '', 0);
        
        $this->SetTextColor(255, 255, 255);
        $this->SetXY($rightMargin, 32);
        $this->SetFont('helvetica', '', 9);
        $this->Cell($pageWidth - ($rightMargin * 2), 0, 'Issue: ' . formatDate($this->invoice['date']), 0, 1, 'R', 0, '', 0);
        
        $this->SetXY($rightMargin, 37);
        $this->Cell($pageWidth - ($rightMargin * 2), 0, 'Due: ' . formatDate($this->invoice['due_date']), 0, 1, 'R', 0, '', 0);
        
        // Status badge
        $status = strtolower($this->invoice['status']);
        if ($status === 'paid') {
            $this->SetFillColor(16, 185, 129); // #10b981
        } elseif ($status === 'unpaid') {
            $this->SetFillColor(239, 68, 68); // #ef4444
        } else {
            $this->SetFillColor(245, 158, 11); // #f59e0b
        }
        $statusBadgeWidth = 35;
        $statusBadgeX = $pageWidth - $rightMargin - $statusBadgeWidth;
        $this->RoundedRect($statusBadgeX, 44, $statusBadgeWidth, 7, 3, '1111', 'F');
        $this->SetXY($statusBadgeX, 45.5);
        $this->SetFont('helvetica', 'B', 9);
        $this->SetTextColor(255, 255, 255);
        $this->Cell($statusBadgeWidth, 0, strtoupper($this->invoice['status']), 0, 1, 'C', 0, '', 0);
    }
    
    // Custom Footer
    public function Footer() {
        // Draw footer background
        $this->SetFillColor(248, 250, 252); // Light gray background
        $footerY = $this->getPageHeight() - 20;
        $this->Rect(0, $footerY, $this->getPageWidth(), 20, 'F');
        
        $this->SetY(-18);
        $this->SetFont('helvetica', '', 8);
        $this->SetTextColor(79, 70, 229); // Indigo color
        $this->Cell(0, 0, 'Thank you for your business!', 0, 0, 'C', 0, '', 0);
        $this->SetY(-13);
        $this->SetFont('helvetica', '', 7);
        $this->SetTextColor(100, 116, 139); // Gray color
        $this->Cell(0, 0, 'Generated on ' . date('F j, Y') . ' | Document ID: ' . $this->invoice['id'], 0, 0, 'C', 0, '', 0);
    }
}

// Create PDF instance
$pdf = new ModernInvoicePDF($company, $invoice);

// Set document information
$pdf->SetCreator('PHP Invoice Generator');
$pdf->SetAuthor($company['name']);
$pdf->SetTitle(($invoice['document_type'] ?? 'Invoice') . ' ' . $invoice['id']);
$pdf->SetSubject(($invoice['document_type'] ?? 'Invoice') . ' for ' . $invoice['client_name']);

// Set margins
$pdf->SetMargins(15, 65, 15); // left, top, right
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(15);
$pdf->SetAutoPageBreak(TRUE, 20);

// Add a page
$pdf->AddPage();

// Set default font
$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(30, 41, 59);

// Client Information Section
$pdf->Ln(2);
$clientY = $pdf->GetY();

// Draw client card background
$pdf->SetFillColor(248, 250, 252);
$pdf->SetDrawColor(79, 70, 229);
$pdf->SetLineWidth(0.5);
$pdf->RoundedRect(15, $clientY, 180, 28, 4, '1111', 'FD');

$pdf->SetXY(20, $clientY + 4);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetTextColor(79, 70, 229);
$pdf->Cell(0, 0, 'BILLED TO', 0, 1, 'L', 0, '', 0);

$pdf->SetXY(20, $clientY + 10);
$pdf->SetFont('helvetica', 'B', 13);
$pdf->SetTextColor(30, 41, 59);
$pdf->Cell(0, 0, $invoice['client_name'], 0, 1, 'L', 0, '', 0);

$pdf->SetXY(20, $clientY + 16);
$pdf->SetFont('helvetica', '', 9);
$pdf->SetTextColor(100, 116, 139);
$pdf->Cell(0, 0, $invoice['client_email'], 0, 1, 'L', 0, '', 0);

$pdf->SetXY(20, $clientY + 21);
$addressLines = explode("\n", $invoice['client_address']);
$addressText = implode(', ', array_map('trim', $addressLines));
$pdf->Cell(0, 0, $addressText, 0, 1, 'L', 0, '', 0);

// Items Table
$pdf->Ln(8);
$pdf->SetFillColor(79, 70, 229);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetDrawColor(79, 70, 229);
$pdf->SetLineWidth(0.3);

// Table header
$pdf->SetFont('helvetica', 'B', 9);
$header = array('DESCRIPTION', 'QTY', 'UNIT PRICE', 'AMOUNT');
$w = array(85, 25, 35, 35);

for($i = 0; $i < count($header); $i++) {
    $align = ($i == 0) ? 'L' : 'R';
    $pdf->Cell($w[$i], 10, $header[$i], 1, 0, $align, 1);
}
$pdf->Ln();

// Table data
$pdf->SetFillColor(248, 250, 252);
$pdf->SetTextColor(30, 41, 59);
$pdf->SetFont('helvetica', '', 9.5);
$pdf->SetDrawColor(226, 232, 240);

$fill = false;
foreach($invoice['items'] as $item) {
    $pdf->Cell($w[0], 9, $item['description'], 'LR', 0, 'L', $fill);
    $pdf->Cell($w[1], 9, number_format($item['quantity']), 'LR', 0, 'R', $fill);
    $pdf->Cell($w[2], 9, 'Rs. ' . number_format($item['price'], 2), 'LR', 0, 'R', $fill);
    
    $pdf->SetFont('helvetica', 'B', 9.5);
    $pdf->SetTextColor(4, 120, 87);
    $pdf->Cell($w[3], 9, 'Rs. ' . number_format($item['price'] * $item['quantity'], 2), 'LR', 0, 'R', $fill);
    $pdf->SetFont('helvetica', '', 9.5);
    $pdf->SetTextColor(30, 41, 59);
    
    $pdf->Ln();
    $fill = !$fill;
}

// Table bottom line
$pdf->Cell(array_sum($w), 0, '', 'T');

// Totals Section
$pdf->Ln(8);
$totalsX = 115;
$totalsY = $pdf->GetY();
$totalsWidth = 80;

// Draw totals card
$pdf->SetFillColor(255, 255, 255);
$pdf->SetDrawColor(226, 232, 240);
$pdf->SetLineWidth(0.5);
$pdf->RoundedRect($totalsX, $totalsY, $totalsWidth, 35, 4, '1111', 'D');

$pdf->SetXY($totalsX + 5, $totalsY + 5);
$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(100, 116, 139);
$pdf->Cell(40, 0, 'Subtotal:', 0, 0, 'L', 0, '', 0);
$pdf->SetXY($totalsX + 45, $totalsY + 5);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetTextColor(30, 41, 59);
$pdf->Cell(30, 0, 'Rs. ' . number_format($invoice['subtotal'], 2), 0, 1, 'R', 0, '', 0);

if ($invoice['apply_tax']) {
    $pdf->SetXY($totalsX + 5, $totalsY + 11);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->Cell(40, 0, 'Tax (18%):', 0, 0, 'L', 0, '', 0);
    $pdf->SetXY($totalsX + 45, $totalsY + 11);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetTextColor(30, 41, 59);
    $pdf->Cell(30, 0, 'Rs. ' . number_format($invoice['tax'], 2), 0, 1, 'R', 0, '', 0);
    $totalY = $totalsY + 20;
} else {
    $totalY = $totalsY + 15;
}

// Grand Total
$pdf->SetFillColor(79, 70, 229);
$pdf->RoundedRect($totalsX, $totalY, $totalsWidth, 12, 4, '1111', 'F');

$pdf->SetXY($totalsX + 5, $totalY + 3.5);
$pdf->SetFont('helvetica', 'B', 11);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(35, 0, 'TOTAL AMOUNT:', 0, 0, 'L', 0, '', 0);
$pdf->SetXY($totalsX + 5, $totalY + 3.5);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell($totalsWidth - 10, 0, 'Rs. ' . number_format($invoice['total'], 2), 0, 1, 'R', 0, '', 0);

// Notes Section
if (!empty($invoice['notes'])) {
    $pdf->Ln(12);
    $notesY = $pdf->GetY();
    
    // Calculate height based on content
    $pdf->SetFont('helvetica', '', 9);
    $notesHeight = max(22, $pdf->getStringHeight(160, $invoice['notes']) + 14);
    
    $pdf->SetFillColor(254, 252, 232);
    $pdf->SetDrawColor(245, 158, 11);
    $pdf->SetLineWidth(0.5);
    $pdf->RoundedRect(15, $notesY, 180, $notesHeight, 4, '1111', 'FD');
    
    $pdf->SetXY(20, $notesY + 4);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetTextColor(245, 158, 11);
    $pdf->Cell(0, 0, 'ADDITIONAL NOTES', 0, 1, 'L', 0, '', 0);
    
    $pdf->SetXY(20, $notesY + 10);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(113, 63, 18);
    $pdf->MultiCell(160, 4, $invoice['notes'], 0, 'L', 0, 1, '', '', true, 0, false, true, 0);
}

// Banking Details Section
if (!empty($company['banking_details'])) {
    $pdf->Ln(12);
    $bankY = $pdf->GetY();
    
    // Calculate height based on content
    $pdf->SetFont('helvetica', '', 9);
    $bankHeight = max(22, $pdf->getStringHeight(160, $company['banking_details']) + 14);
    
    $pdf->SetFillColor(236, 253, 245);
    $pdf->SetDrawColor(5, 150, 105);
    $pdf->SetLineWidth(0.5);
    $pdf->RoundedRect(15, $bankY, 180, $bankHeight, 4, '1111', 'FD');
    
    $pdf->SetXY(20, $bankY + 4);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetTextColor(5, 150, 105);
    $pdf->Cell(0, 0, 'PAYMENT INFORMATION', 0, 1, 'L', 0, '', 0);
    
    $pdf->SetXY(20, $bankY + 10);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(6, 78, 59);
    $pdf->MultiCell(160, 4, $company['banking_details'], 0, 'L', 0, 1, '', '', true, 0, false, true, 0);
}

// Generate filename
$documentType = $invoice['document_type'] ?? 'Invoice';
$clientName = preg_replace('/[^a-zA-Z0-9\s]/', '', $invoice['client_name']);
$clientName = preg_replace('/\s+/', '_', trim($clientName));
$invoiceNumber = str_replace(['INV-', ' '], ['', '_'], $invoice['id']);
$filename = $clientName . '_' . $documentType . '_' . $invoiceNumber . '.pdf';

// Output PDF
$pdf->Output($filename, 'D');
exit;
