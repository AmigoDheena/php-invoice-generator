<?php
// Check if functions are already loaded via composer autoloader
if (!function_exists('getInvoiceById')) {
    require_once 'includes/functions.php';
}

// Validate file parameter
if (!isset($_GET['file']) || empty($_GET['file'])) {
    header('HTTP/1.0 400 Bad Request');
    echo 'Missing file parameter';
    exit;
}

$filename = basename($_GET['file']);

// Security check: prevent directory traversal
if (strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
    header('HTTP/1.0 400 Bad Request');
    echo 'Invalid filename';
    exit;
}

// Define valid backup directories
$validDirectories = [
    'backups',
    'schemas',
    'exports'
];

// Find the file in one of the valid backup directories
$filePath = null;
foreach ($validDirectories as $dir) {
    $path = __DIR__ . '/data/' . $dir . '/' . $filename;
    if (file_exists($path)) {
        $filePath = $path;
        break;
    }
}

// If file not found
if (!$filePath || !file_exists($filePath)) {
    header('HTTP/1.0 404 Not Found');
    echo 'File not found';
    exit;
}

// Get file extension
$extension = pathinfo($filePath, PATHINFO_EXTENSION);

// Set content type based on file extension
switch ($extension) {
    case 'zip':
        $contentType = 'application/zip';
        break;
    case 'sql':
        $contentType = 'application/sql';
        break;
    case 'json':
        $contentType = 'application/json';
        break;
    default:
        $contentType = 'application/octet-stream';
}

// Send file to browser
header('Content-Type: ' . $contentType);
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filePath));
header('Pragma: no-cache');
header('Expires: 0');

// Output file content
readfile($filePath);
exit;