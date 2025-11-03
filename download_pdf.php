<?php
// Robust loader: try Composer autoload, include helpers, then fall back to direct tcpdf.php require
$baseDir = __DIR__;

// 1) Try Composer autoloader if present
$autoloadPath = $baseDir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

// 2) Include project helper functions if not already available
if (!function_exists('getInvoiceById')) {
    $functionsPath = $baseDir . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'functions.php';
    if (file_exists($functionsPath)) {
        require_once $functionsPath;
    }
}

// 3) If TCPDF class not yet available, try the common manual paths where tcpdf.php might live
if (!class_exists('TCPDF')) {
    $tcpdfCandidates = [
        $baseDir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'tecnickcom' . DIRECTORY_SEPARATOR . 'tcpdf' . DIRECTORY_SEPARATOR . 'tcpdf.php',
        $baseDir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'tcpdf' . DIRECTORY_SEPARATOR . 'tcpdf.php',
    ];
    foreach ($tcpdfCandidates as $candidate) {
        if (file_exists($candidate)) {
            require_once $candidate;
            break;
        }
    }
}

// 4) If TCPDF is still unavailable, show a helpful diagnostic and exit
if (!class_exists('TCPDF')) {
    $checked = [];
    $checked[] = $autoloadPath;
    if (!empty($tcpdfCandidates)) {
        $checked = array_merge($checked, $tcpdfCandidates);
    }

    $checkedHtml = '';
    foreach ($checked as $p) {
        $real = @realpath($p);
        $exists = $real ? 'FOUND' : 'MISSING';
        $display = htmlspecialchars($real ?: $p, ENT_QUOTES, 'UTF-8');
        $checkedHtml .= "<li><code>{$display}</code> — <strong>{$exists}</strong></li>";
    }

    $fontsDir = $baseDir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'tecnickcom' . DIRECTORY_SEPARATOR . 'tcpdf' . DIRECTORY_SEPARATOR . 'fonts';
    $fontsStatus = is_dir($fontsDir) ? 'FOUND' : 'MISSING';

    echo "<div style='background:#fff; border:1px solid #eee; padding:20px; font-family:Arial,Helvetica,sans-serif;'>";
    echo "<h2 style='color:#d9534f; margin-top:0;'>PDF Generation Not Available</h2>";
    echo "<p>The TCPDF PHP class <code>TCPDF</code> could not be loaded.</p>";
    echo "<p>Paths checked:</p>";
    echo "<ul style='font-size:13px; color:#333;'>{$checkedHtml}</ul>";
    echo "<p>Fonts directory: <code>" . htmlspecialchars($fontsDir, ENT_QUOTES, 'UTF-8') . "</code> — <strong>" . $fontsStatus . "</strong></p>";
    echo "<p>Possible fixes:</p>";
    echo "<ul style='font-size:13px; color:#333;'>";
    echo "<li>Upload the full <code>vendor/</code> directory produced by Composer (recommended). It includes <code>vendor/autoload.php</code> and all dependencies.</li>";
    echo "<li>Or, if you can't run Composer on the server, ensure <code>vendor/tecnickcom/tcpdf/tcpdf.php</code> exists and is readable by PHP (same directory where this script lives).</li>";
    echo "<li>Check file permissions (PHP must be able to read the files) and that the path names/casing match your server (Linux hosts are case-sensitive).</li>";
    echo "<li>If you uploaded files via FTP, re-upload as a zip and extract on server (to preserve mode and avoid corruption), or run Composer locally and upload the whole <code>vendor/</code> folder.</li>";
    echo "</ul>";
    echo "<p>Quick test file (create <code>test_tcpdf.php</code> next to this script and open it in browser):</p>";
    echo "<pre style='background:#f5f5f5;padding:10px;'>&lt;?php\n// Try autoload or direct require\nif (file_exists(__DIR__ . '/vendor/autoload.php')) require_once __DIR__ . '/vendor/autoload.php';\nif (!class_exists('TCPDF') && file_exists(__DIR__ . '/vendor/tecnickcom/tcpdf/tcpdf.php')) require_once __DIR__ . '/vendor/tecnickcom/tcpdf/tcpdf.php';\nvar_dump(class_exists('TCPDF'));</pre>";
    echo "<p style='margin-top:12px;'><a href='view_invoice.php?id=" . htmlspecialchars($_GET['id'] ?? '') . "' style='display:inline-block;padding:8px 12px;background:#0d6efd;color:#fff;text-decoration:none;border-radius:4px;'>Back to Invoice</a></p>";
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
    // Calculate row height based on description length
    $descHeight = $pdf->getStringHeight($w[0] - 2, $item['description']);
    $rowHeight = max(9, $descHeight + 2);
    
    // Store current Y position
    $startY = $pdf->GetY();
    $startX = $pdf->GetX();
    
    // Description cell with MultiCell (allows text wrapping)
    $pdf->MultiCell($w[0], $rowHeight, $item['description'], 'LR', 'L', $fill, 0, '', '', true, 0, false, true, $rowHeight, 'M');
    
    // Move to same Y position for other columns
    $pdf->SetXY($startX + $w[0], $startY);
    
    // Quantity cell
    $pdf->Cell($w[1], $rowHeight, number_format($item['quantity']), 'LR', 0, 'R', $fill, '', 0, false, 'T', 'M');
    
    // Unit price cell
    $pdf->Cell($w[2], $rowHeight, 'Rs. ' . number_format($item['price'], 2), 'LR', 0, 'R', $fill, '', 0, false, 'T', 'M');
    
    // Amount cell (green, bold)
    $pdf->SetFont('helvetica', 'B', 9.5);
    $pdf->SetTextColor(4, 120, 87);
    $pdf->Cell($w[3], $rowHeight, 'Rs. ' . number_format($item['price'] * $item['quantity'], 2), 'LR', 0, 'R', $fill, '', 0, false, 'T', 'M');
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
