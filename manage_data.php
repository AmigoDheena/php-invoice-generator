<?php
// Check if functions are already loaded via composer autoloader
if (!function_exists('getInvoiceById')) {
    require_once 'includes/functions.php';
}

// Include cloud providers if file exists
if (file_exists('includes/cloud/providers.php')) {
    require_once 'includes/cloud/providers.php';
}

$pageTitle = 'Data Management';

// Get current storage stats
$dataStats = getDataStorageStats();

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'export_all_data':
                // Generate ZIP archive with all data
                $zipPath = exportAllData();
                if ($zipPath) {
                    // Redirect to download the ZIP file
                    header("Location: download_backup.php?file=" . basename($zipPath));
                    exit;
                } else {
                    $message = 'Failed to create data export. Please check file permissions.';
                    $messageType = 'error';
                }
                break;
                
            case 'import_data':
                // Handle data import from ZIP archive
                if (isset($_FILES['import_file']) && $_FILES['import_file']['error'] === UPLOAD_ERR_OK) {
                    $result = importAllData($_FILES['import_file']['tmp_name']);
                    if ($result === true) {
                        $message = 'Data successfully imported!';
                        $messageType = 'success';
                    } else {
                        $message = 'Import failed: ' . $result;
                        $messageType = 'error';
                    }
                } else {
                    $message = 'Please select a valid backup file to import.';
                    $messageType = 'error';
                }
                break;
                
            case 'setup_cloud_backup':
                // Save cloud backup settings
                $provider = $_POST['cloud_provider'] ?? '';
                $apiKey = $_POST['api_key'] ?? '';
                $folder = $_POST['folder_path'] ?? '';
                $schedule = $_POST['backup_schedule'] ?? '';
                $autoBackup = isset($_POST['auto_backup']) && $_POST['auto_backup'] === 'on';
                
                $settings = [
                    'provider' => $provider,
                    'api_key' => $apiKey,
                    'folder' => $folder,
                    'schedule' => $schedule,
                    'active' => true,
                    'auto_backup' => $autoBackup,
                    'last_sync' => null
                ];
                
                // Validate settings
                if (!validateCloudBackupSettings($settings)) {
                    $message = 'Invalid cloud backup settings. Provider and API key are required.';
                    $messageType = 'error';
                    break;
                }
                
                // Test connection if providers are available
                if (function_exists('testCloudBackupConnection')) {
                    $testResult = testCloudBackupConnection($settings);
                    if ($testResult !== true) {
                        $message = 'Cloud connection test failed: ' . $testResult;
                        $messageType = 'error';
                        break;
                    }
                }
                
                $result = saveCloudBackupSettings($settings);
                
                if ($result) {
                    $message = 'Cloud backup settings saved successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to save cloud backup settings.';
                    $messageType = 'error';
                }
                break;
                
            case 'disable_cloud_backup':
                // Disable cloud backup
                $result = disableCloudBackup();
                if ($result) {
                    $message = 'Cloud backups have been disabled.';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to disable cloud backups.';
                    $messageType = 'error';
                }
                break;
                
            case 'trigger_cloud_backup':
                // Manually trigger cloud backup
                $cloudSettings = getCloudBackupSettings();
                if (!$cloudSettings || !$cloudSettings['active']) {
                    $message = 'Cloud backup is not configured or not active.';
                    $messageType = 'error';
                } else {
                    // Create a backup file
                    $backupFile = createBackupArchive(false);
                    if ($backupFile) {
                        $success = false;
                        $uploadDetails = '';
                        
                        // Try to upload to cloud if providers are available
                        if (class_exists('CloudStorageFactory')) {
                            $provider = $cloudSettings['provider'];
                            $apiKey = $cloudSettings['api_key'];
                            $folder = $cloudSettings['folder'] ?: '/backups';
                            $remotePath = $folder . '/' . basename($backupFile);
                            
                            $credentials = [
                                'api_key' => $apiKey,
                                // Add other credentials as needed
                            ];
                            
                            $cloudStorage = CloudStorageFactory::create($provider, $credentials);
                            if ($cloudStorage && $cloudStorage->authenticate($credentials)) {
                                $result = $cloudStorage->uploadFile($backupFile, $remotePath);
                                if ($result === true) {
                                    $success = true;
                                    $uploadDetails = " and uploaded to " . ucfirst($provider);
                                } else {
                                    $uploadDetails = " but upload failed: $result";
                                }
                            } else {
                                $uploadDetails = " but could not authenticate with " . ucfirst($provider);
                            }
                        } else {
                            $uploadDetails = ". Cloud provider integration not available.";
                        }
                        
                        $message = 'Backup created successfully' . $uploadDetails;
                        $messageType = $success ? 'success' : 'warning';
                        
                        // Update last sync timestamp
                        $cloudSettings['last_sync'] = time();
                        saveCloudBackupSettings($cloudSettings);
                    } else {
                        $message = 'Failed to create backup for cloud upload.';
                        $messageType = 'error';
                    }
                }
                break;
                
            case 'create_manual_backup':
                // Create a manual backup
                $zipPath = createBackupArchive();
                if ($zipPath) {
                    $message = 'Manual backup created successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to create backup. Please check file permissions.';
                    $messageType = 'error';
                }
                break;
                
            case 'generate_mysql_schema':
                // Generate MySQL schema file
                $schemaPath = generateMySQLSchema();
                if ($schemaPath) {
                    header("Location: download_backup.php?file=" . basename($schemaPath));
                    exit;
                } else {
                    $message = 'Failed to generate MySQL schema.';
                    $messageType = 'error';
                }
                break;
                
            case 'migrate_to_mysql':
                // Handle MySQL migration
                $host = $_POST['mysql_host'] ?? '';
                $dbname = $_POST['mysql_dbname'] ?? '';
                $username = $_POST['mysql_username'] ?? '';
                $password = $_POST['mysql_password'] ?? '';
                
                if (empty($host) || empty($dbname) || empty($username)) {
                    $message = 'Please fill in all MySQL connection details.';
                    $messageType = 'error';
                } else {
                    $result = migrateToMySQL($host, $dbname, $username, $password);
                    if ($result === true) {
                        $message = 'Successfully migrated data to MySQL!';
                        $messageType = 'success';
                    } else {
                        $message = 'Migration failed: ' . $result;
                        $messageType = 'error';
                    }
                }
                break;
        }
    }
}

// Get cloud backup settings
$cloudSettings = getCloudBackupSettings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-gray-100">
    <?php include_once 'includes/header.php'; ?>
    <div class="max-w-6xl mx-auto px-6 py-10 bg-transparent">
        <header class="mb-6">
            <h1 class="text-3xl font-bold text-gray-800"><?php echo $pageTitle; ?></h1>
            <p class="text-gray-600">Backup, import/export, and database migration tools</p>
        </header>
        
        <a href="index.php" class="inline-block mb-4 text-blue-500 hover:text-blue-700">
            <i class="fas fa-arrow-left mr-2"></i>Back to Invoices
        </a>
        
        <?php if (!empty($message)): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <!-- Data Storage Stats -->
        <div class="bg-white rounded-lg shadow-md mb-6">
            <div class="p-6">
                <h2 class="text-xl font-semibold mb-4">Data Storage Statistics</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="text-lg font-medium mb-2">Invoices</h3>
                        <p class="text-gray-600">Count: <span class="font-semibold"><?php echo $dataStats['invoices']['count']; ?></span></p>
                        <p class="text-gray-600">Size: <span class="font-semibold"><?php echo $dataStats['invoices']['size']; ?></span></p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="text-lg font-medium mb-2">Companies</h3>
                        <p class="text-gray-600">Count: <span class="font-semibold"><?php echo $dataStats['companies']['count']; ?></span></p>
                        <p class="text-gray-600">Size: <span class="font-semibold"><?php echo $dataStats['companies']['size']; ?></span></p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="text-lg font-medium mb-2">Products</h3>
                        <p class="text-gray-600">Count: <span class="font-semibold"><?php echo $dataStats['products']['count']; ?></span></p>
                        <p class="text-gray-600">Size: <span class="font-semibold"><?php echo $dataStats['products']['size']; ?></span></p>
                    </div>
                </div>
                <div class="mt-4">
                    <p class="text-gray-600">Total Data Size: <span class="font-semibold"><?php echo $dataStats['total_size']; ?></span></p>
                    <p class="text-gray-600">Last Backup: <span class="font-semibold"><?php echo $dataStats['last_backup'] ? date('Y-m-d H:i:s', $dataStats['last_backup']) : 'Never'; ?></span></p>
                </div>
            </div>
        </div>
        
        <!-- Tabs -->
        <div class="bg-white rounded-lg shadow-md">
            <div class="border-b border-gray-200">
                <nav class="flex -mb-px">
                    <a href="#" class="data-tab border-primary-color text-primary-color border-b-2 py-4 px-6 font-medium text-sm" data-tab="backup">
                        <i class="fas fa-cloud-upload-alt mr-2"></i>Backup & Export
                    </a>
                    <a href="#" class="data-tab border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 border-b-2 py-4 px-6 font-medium text-sm" data-tab="import">
                        <i class="fas fa-file-import mr-2"></i>Data Import
                    </a>
                    <a href="#" class="data-tab border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 border-b-2 py-4 px-6 font-medium text-sm" data-tab="database">
                        <i class="fas fa-database mr-2"></i>MySQL Migration
                    </a>
                </nav>
            </div>
            
            <!-- Backup & Export Tab -->
            <div id="backup-tab" class="data-tab-content p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Manual Backup -->
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Manual Backup</h3>
                        <p class="mb-4 text-gray-600">Create and download a complete backup of all your invoice data.</p>
                        
                        <form method="post" action="">
                            <input type="hidden" name="action" value="export_all_data">
                            <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded">
                                <i class="fas fa-download mr-2"></i>Export All Data (ZIP)
                            </button>
                        </form>
                        
                        <div class="mt-4">
                            <form method="post" action="">
                                <input type="hidden" name="action" value="create_manual_backup">
                                <button type="submit" class="w-full bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded">
                                    <i class="fas fa-save mr-2"></i>Create Backup (Server Only)
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Cloud Backup -->
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Cloud Backup</h3>
                        <p class="mb-4 text-gray-600">Configure automatic backups to cloud storage services.</p>
                        
                        <?php if (empty($cloudSettings) || !$cloudSettings['active']): ?>
                            <form method="post" action="" class="space-y-4">
                                <input type="hidden" name="action" value="setup_cloud_backup">
                                <div>
                                    <label for="cloud_provider" class="block text-sm font-medium text-gray-700 mb-1">Cloud Provider</label>
                                    <select name="cloud_provider" id="cloud_provider" class="w-full border-gray-300 rounded-md shadow-sm p-2 border focus:border-primary-color">
                                        <option value="dropbox">Dropbox</option>
                                        <option value="google_drive">Google Drive</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label for="api_key" class="block text-sm font-medium text-gray-700 mb-1">API Key/Token</label>
                                    <input type="text" name="api_key" id="api_key" class="w-full border-gray-300 rounded-md shadow-sm p-2 border focus:border-primary-color" required>
                                    <p class="text-xs text-gray-500 mt-1">Your API key or access token for the selected cloud service.</p>
                                </div>
                                
                                <div>
                                    <label for="folder_path" class="block text-sm font-medium text-gray-700 mb-1">Folder Path</label>
                                    <input type="text" name="folder_path" id="folder_path" class="w-full border-gray-300 rounded-md shadow-sm p-2 border focus:border-primary-color" placeholder="/backups">
                                    <p class="text-xs text-gray-500 mt-1">Optional: Destination folder for your backups.</p>
                                </div>
                                
                                <div>
                                    <label for="backup_schedule" class="block text-sm font-medium text-gray-700 mb-1">Backup Schedule</label>
                                    <select name="backup_schedule" id="backup_schedule" class="w-full border-gray-300 rounded-md shadow-sm p-2 border focus:border-primary-color">
                                        <option value="daily">Daily</option>
                                        <option value="weekly">Weekly</option>
                                        <option value="monthly">Monthly</option>
                                    </select>
                                </div>
                                
                                <div class="flex items-center mt-2">
                                    <input type="checkbox" name="auto_backup" id="auto_backup" class="h-4 w-4 text-blue-600 border-gray-300 rounded" checked>
                                    <label for="auto_backup" class="ml-2 block text-sm text-gray-700">
                                        Enable automatic backups
                                    </label>
                                </div>
                                
                                <div>
                                    <button type="submit" class="w-full bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded">
                                        <i class="fas fa-cloud-upload-alt mr-2"></i>Configure Cloud Backup
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="bg-gray-50 p-4 rounded-lg mb-4">
                                <h4 class="font-medium">Current Cloud Backup Settings</h4>
                                <p class="text-gray-600">Provider: <?php echo ucfirst($cloudSettings['provider']); ?></p>
                                <p class="text-gray-600">Schedule: <?php echo ucfirst($cloudSettings['schedule']); ?></p>
                                <p class="text-gray-600">Folder: <?php echo $cloudSettings['folder'] ?: 'Default'; ?></p>
                                <p class="text-gray-600">Auto-Backup: <?php echo isset($cloudSettings['auto_backup']) && $cloudSettings['auto_backup'] ? 'Enabled' : 'Disabled'; ?></p>
                                <p class="text-gray-600">Last Sync: <?php echo isset($cloudSettings['last_sync']) && $cloudSettings['last_sync'] ? date('Y-m-d H:i:s', $cloudSettings['last_sync']) : 'Never'; ?></p>
                            </div>
                            
                            <div class="flex space-x-2">
                                <form method="post" action="" class="w-1/2">
                                    <input type="hidden" name="action" value="trigger_cloud_backup">
                                    <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded">
                                        <i class="fas fa-cloud-upload-alt mr-2"></i>Run Now
                                    </button>
                                </form>
                                
                                <form method="post" action="" class="w-1/2">
                                    <input type="hidden" name="action" value="disable_cloud_backup">
                                    <button type="submit" class="w-full bg-red-500 hover:bg-red-600 text-white font-semibold py-2 px-4 rounded">
                                        <i class="fas fa-times mr-2"></i>Disable
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Backup History -->
                <div class="mt-8">
                    <h3 class="text-lg font-semibold mb-4">Backup History</h3>
                    <?php if (empty($dataStats['backup_history'])): ?>
                        <p class="text-gray-600">No backup history available.</p>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse">
                                <thead>
                                    <tr class="bg-gray-100">
                                        <th class="border p-2 text-left">Date</th>
                                        <th class="border p-2 text-left">Type</th>
                                        <th class="border p-2 text-left">Size</th>
                                        <th class="border p-2 text-left">Status</th>
                                        <th class="border p-2 text-left">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dataStats['backup_history'] as $backup): ?>
                                        <tr>
                                            <td class="border p-2"><?php echo date('Y-m-d H:i:s', $backup['timestamp']); ?></td>
                                            <td class="border p-2"><?php echo ucfirst($backup['type']); ?></td>
                                            <td class="border p-2"><?php echo $backup['size']; ?></td>
                                            <td class="border p-2">
                                                <span class="px-2 py-1 rounded text-xs <?php echo $backup['status'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                    <?php echo ucfirst($backup['status']); ?>
                                                </span>
                                            </td>
                                            <td class="border p-2">
                                                <?php if ($backup['status'] === 'success' && file_exists($backup['file'])): ?>
                                                    <a href="download_backup.php?file=<?php echo basename($backup['file']); ?>" class="text-blue-500 hover:text-blue-700">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Data Import Tab -->
            <div id="import-tab" class="data-tab-content p-6 hidden">
                <div class="max-w-lg mx-auto">
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    <strong>Warning:</strong> Importing data will replace all existing data. Make sure to create a backup first.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <form method="post" action="" enctype="multipart/form-data" class="bg-gray-50 p-6 rounded-lg">
                        <input type="hidden" name="action" value="import_data">
                        
                        <div class="mb-6">
                            <label for="import_file" class="block text-sm font-medium text-gray-700 mb-2">Import Backup File</label>
                            <input type="file" name="import_file" id="import_file" accept=".zip" class="w-full p-2 border border-gray-300 rounded-md" required>
                            <p class="text-xs text-gray-500 mt-2">Upload a valid backup ZIP file that was generated using the Export function.</p>
                        </div>
                        
                        <div class="flex items-center">
                            <input type="checkbox" name="confirm_import" id="confirm_import" class="h-4 w-4 text-blue-600 border-gray-300 rounded" required>
                            <label for="confirm_import" class="ml-2 block text-sm text-gray-700">
                                I understand this will replace all existing data
                            </label>
                        </div>
                        
                        <div class="mt-6">
                            <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-2 px-4 rounded">
                                <i class="fas fa-file-import mr-2"></i>Import Data
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- MySQL Migration Tab -->
            <div id="database-tab" class="data-tab-content p-6 hidden">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- MySQL Schema -->
                    <div>
                        <h3 class="text-lg font-semibold mb-4">MySQL Schema</h3>
                        <p class="mb-4 text-gray-600">Generate a MySQL schema file that can be used to create the required database tables.</p>
                        
                        <form method="post" action="">
                            <input type="hidden" name="action" value="generate_mysql_schema">
                            <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded">
                                <i class="fas fa-file-code mr-2"></i>Generate MySQL Schema
                            </button>
                        </form>
                    </div>
                    
                    <!-- Migration Tool -->
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Migrate to MySQL Database</h3>
                        <p class="mb-4 text-gray-600">Migrate all your data to a MySQL database for larger installations.</p>
                        
                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-yellow-700">
                                        <strong>Warning:</strong> Make sure the database exists and is empty before proceeding.
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <form method="post" action="" class="space-y-4">
                            <input type="hidden" name="action" value="migrate_to_mysql">
                            
                            <div>
                                <label for="mysql_host" class="block text-sm font-medium text-gray-700 mb-1">MySQL Host</label>
                                <input type="text" name="mysql_host" id="mysql_host" class="w-full border-gray-300 rounded-md shadow-sm p-2 border focus:border-primary-color" placeholder="localhost" required>
                            </div>
                            
                            <div>
                                <label for="mysql_dbname" class="block text-sm font-medium text-gray-700 mb-1">Database Name</label>
                                <input type="text" name="mysql_dbname" id="mysql_dbname" class="w-full border-gray-300 rounded-md shadow-sm p-2 border focus:border-primary-color" required>
                            </div>
                            
                            <div>
                                <label for="mysql_username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                                <input type="text" name="mysql_username" id="mysql_username" class="w-full border-gray-300 rounded-md shadow-sm p-2 border focus:border-primary-color" required>
                            </div>
                            
                            <div>
                                <label for="mysql_password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                                <input type="password" name="mysql_password" id="mysql_password" class="w-full border-gray-300 rounded-md shadow-sm p-2 border focus:border-primary-color">
                            </div>
                            
                            <div class="flex items-center">
                                <input type="checkbox" name="confirm_migration" id="confirm_migration" class="h-4 w-4 text-blue-600 border-gray-300 rounded" required>
                                <label for="confirm_migration" class="ml-2 block text-sm text-gray-700">
                                    I understand this will migrate all data to MySQL
                                </label>
                            </div>
                            
                            <div>
                                <button type="submit" class="w-full bg-purple-500 hover:bg-purple-600 text-white font-semibold py-2 px-4 rounded">
                                    <i class="fas fa-database mr-2"></i>Migrate Data to MySQL
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="mt-8">
                    <h3 class="text-lg font-semibold mb-4">Using MySQL with the Application</h3>
                    <p class="text-gray-600 mb-4">After migration, you'll need to configure the application to use MySQL instead of JSON files.</p>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-sm font-mono mb-2">1. Create a <code>config.php</code> file in the root directory with database credentials:</p>
                        <pre class="bg-gray-800 text-green-400 p-3 rounded overflow-x-auto text-sm">
&lt;?php
define('USE_DATABASE', true);
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
?&gt;</pre>
                        <p class="text-sm mt-4">2. After creating this file, the system will automatically use MySQL instead of JSON files.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tab switching functionality
            const tabs = document.querySelectorAll('.data-tab');
            const tabContents = document.querySelectorAll('.data-tab-content');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Get the tab ID
                    const tabId = this.getAttribute('data-tab');
                    
                    // Remove active class from all tabs
                    tabs.forEach(t => {
                        t.classList.remove('text-primary-color', 'border-primary-color');
                        t.classList.add('text-gray-500', 'border-transparent');
                    });
                    
                    // Add active class to selected tab
                    this.classList.remove('text-gray-500', 'border-transparent');
                    this.classList.add('text-primary-color', 'border-primary-color');
                    
                    // Hide all tab contents
                    tabContents.forEach(content => {
                        content.classList.add('hidden');
                    });
                    
                    // Show selected tab content
                    document.getElementById(tabId + '-tab').classList.remove('hidden');
                });
            });
        });
    </script>

    <?php include_once 'includes/footer.php'; ?>
</body>
</html>