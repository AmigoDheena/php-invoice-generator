<?php
require_once 'includes/functions.php';

// This script purges old backups to prevent excessive storage usage
// It can be run via a cron job, e.g., weekly

// Check for security token to prevent unauthorized access
if (!isset($_GET['token']) || $_GET['token'] !== 'YOUR_SECURE_TOKEN_HERE') {
    http_response_code(403);
    echo 'Access denied';
    exit;
}

// Configuration
$maxBackupCount = 10;       // Maximum number of manual backups to keep
$maxAutoBackupCount = 5;    // Maximum number of automatic backups to keep
$backupsDir = DATA_DIR . 'backups';

// Function to purge old backups
function purgeOldBackups($pattern, $maxCount) {
    global $backupsDir;
    
    // Get all matching files
    $files = glob($backupsDir . '/' . $pattern);
    
    // Sort by modification time (newest first)
    usort($files, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    
    // Delete old files
    if (count($files) > $maxCount) {
        for ($i = $maxCount; $i < count($files); $i++) {
            if (file_exists($files[$i])) {
                unlink($files[$i]);
                echo "Deleted old backup: " . basename($files[$i]) . "\n";
            }
        }
    }
    
    return count($files) - $maxCount;
}

// Ensure backups directory exists
if (!is_dir($backupsDir)) {
    echo "Backups directory does not exist.\n";
    exit;
}

// Purge old manual backups
$deletedManual = purgeOldBackups('backup_*.zip', $maxBackupCount);
echo "Purged $deletedManual manual backups.\n";

// Purge old automatic backups
$deletedAuto = purgeOldBackups('auto_backup_*.zip', $maxAutoBackupCount);
echo "Purged $deletedAuto automatic backups.\n";

echo "Backup purging complete.\n";
?>