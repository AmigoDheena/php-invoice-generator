<?php
require_once 'includes/functions.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header('HTTP/1.1 403 Forbidden');
        echo json_encode([
            'success' => false,
            'message' => 'Invalid CSRF token'
        ]);
        exit;
    }
    
    // Check if file was uploaded
    if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
        $errorMessage = 'No file uploaded';
        if (isset($_FILES['import_file']['error'])) {
            switch ($_FILES['import_file']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $errorMessage = 'File size exceeds the maximum limit';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $errorMessage = 'File was only partially uploaded';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $errorMessage = 'No file was uploaded';
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $errorMessage = 'Missing temporary folder';
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $errorMessage = 'Failed to write file to disk';
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $errorMessage = 'File upload stopped by extension';
                    break;
                default:
                    $errorMessage = 'Unknown upload error';
                    break;
            }
        }
        
        header('HTTP/1.1 400 Bad Request');
        echo json_encode([
            'success' => false,
            'message' => $errorMessage
        ]);
        exit;
    }
    
    // Validate file type
    $fileTmpPath = $_FILES['import_file']['tmp_name'];
    $fileName = $_FILES['import_file']['name'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    if ($fileExtension !== 'zip') {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode([
            'success' => false,
            'message' => 'Only ZIP files are allowed'
        ]);
        exit;
    }
    
    // Import data
    $result = importAllData($fileTmpPath);
    
    if ($result === true) {
        echo json_encode([
            'success' => true,
            'message' => 'Data imported successfully'
        ]);
    } else {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode([
            'success' => false,
            'message' => $result
        ]);
    }
    exit;
}

// If not POST, return error
header('HTTP/1.1 405 Method Not Allowed');
echo json_encode([
    'success' => false,
    'message' => 'Method not allowed'
]);
exit;
?>