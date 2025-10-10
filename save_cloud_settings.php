<?php
require_once 'includes/functions.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection is already handled in manage_data.php
    // This file is just a direct endpoint in case needed separately
    
    // Get cloud service data
    $cloudService = isset($_POST['cloud_provider']) ? $_POST['cloud_provider'] : '';
    $apiKey = isset($_POST['api_key']) ? $_POST['api_key'] : '';
    $folderPath = isset($_POST['folder_path']) ? $_POST['folder_path'] : '';
    $frequency = isset($_POST['backup_schedule']) ? $_POST['backup_schedule'] : 'daily';
    $autoBackup = isset($_POST['auto_backup']) && $_POST['auto_backup'] === 'on';
    
    // Validate required fields
    if (empty($cloudService) || empty($apiKey)) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode([
            'success' => false,
            'message' => 'Cloud service and API key are required'
        ]);
        exit;
    }
    
    // Save settings
    $settings = [
        'active' => true,
        'cloud_service' => $cloudService,
        'api_key' => $apiKey,
        'folder_path' => $folderPath,
        'frequency' => $frequency,
        'auto_backup' => $autoBackup,
        'last_sync' => null
    ];
    
    $result = saveCloudBackupSettings($settings);
    
    if ($result) {
        // Create an initial backup if auto backup is enabled
        if ($autoBackup) {
            $backupFile = createBackupArchive(true);
            if ($backupFile) {
                // For demonstration purposes, we'll just report success
                // In a real application, we would upload this to the cloud service
                echo json_encode([
                    'success' => true,
                    'message' => 'Cloud backup settings saved and initial backup created',
                    'backup_file' => basename($backupFile)
                ]);
                exit;
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Cloud backup settings saved successfully'
        ]);
    } else {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode([
            'success' => false,
            'message' => 'Failed to save cloud backup settings'
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