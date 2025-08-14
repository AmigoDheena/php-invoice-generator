<?php
// Include functions if needed
if (!function_exists('getCompanyById')) {
    require_once 'includes/functions.php';
}

// Get all companies
$companies = getCompanies();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logo Test Tool</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        pre {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            overflow: auto;
        }
    </style>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow-md">
        <h1 class="text-2xl font-bold mb-6">Logo Troubleshooting Tool</h1>
        
        <div class="mb-6 p-4 bg-blue-50 border-l-4 border-blue-500">
            <p>This tool helps diagnose issues with company logos in PDF invoices.</p>
            <?php if (!extension_loaded('gd')): ?>
            <div class="mt-3 p-3 bg-yellow-100 border-l-4 border-yellow-500">
                <p class="font-bold">PHP GD Extension Not Installed</p>
                <p class="mt-1">The GD extension is required for image processing in your invoices.</p>
                
                <div class="mt-3 flex justify-between items-center">
                    <div>
                        <p class="font-semibold">How to enable in XAMPP:</p>
                        <ol class="list-decimal pl-6 mt-2">
                            <li>Open the php.ini file located at: <code>C:\xampp\php\php.ini</code></li>
                            <li>Find the line <code>;extension=gd</code> (it has a semicolon at the beginning)</li>
                            <li>Remove the semicolon to uncomment the line: <code>extension=gd</code></li>
                            <li>Save the file and restart Apache from the XAMPP Control Panel</li>
                        </ol>
                    </div>
                    <div class="ml-4">
                        <a href="enable_gd.php" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            GD Extension Helper
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <h2 class="text-xl font-semibold mb-4">System Information</h2>
        <div class="mb-6 grid grid-cols-2 gap-4">
            <div class="bg-gray-50 p-4 rounded">
                <h3 class="font-semibold">GD Extension</h3>
                <p><?php echo extension_loaded('gd') ? '<span class="text-green-600">Installed</span>' : '<span class="text-red-600">Not Installed</span>'; ?></p>
            </div>
            <div class="bg-gray-50 p-4 rounded">
                <h3 class="font-semibold">Logo Directory</h3>
                <p><?php 
                    $logoDir = 'uploads/logos/';
                    if (is_dir($logoDir)) {
                        echo '<span class="text-green-600">Exists</span>';
                        echo ' (' . (is_writable($logoDir) ? 'Writable' : 'Not Writable') . ')';
                    } else {
                        echo '<span class="text-red-600">Does Not Exist</span>';
                    }
                ?></p>
            </div>
        </div>
        
        <h2 class="text-xl font-semibold mb-4">Company Logos</h2>
        <div class="overflow-x-auto">
            <table class="w-full table-auto border-collapse">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="border p-2 text-left">Company</th>
                        <th class="border p-2 text-left">Logo Path</th>
                        <th class="border p-2 text-left">File Exists</th>
                        <th class="border p-2 text-left">File Readable</th>
                        <th class="border p-2 text-left">Preview</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($companies as $company): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="border p-2"><?php echo htmlspecialchars($company['name']); ?></td>
                        <td class="border p-2"><?php echo !empty($company['logo']) ? htmlspecialchars($company['logo']) : '<em>No logo</em>'; ?></td>
                        <td class="border p-2">
                            <?php 
                            if (!empty($company['logo'])) {
                                echo file_exists($company['logo']) 
                                    ? '<span class="text-green-600">Yes</span>' 
                                    : '<span class="text-red-600">No</span>';
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td class="border p-2">
                            <?php 
                            if (!empty($company['logo']) && file_exists($company['logo'])) {
                                echo is_readable($company['logo']) 
                                    ? '<span class="text-green-600">Yes</span>' 
                                    : '<span class="text-red-600">No</span>';
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td class="border p-2">
                            <?php 
                            if (!empty($company['logo']) && file_exists($company['logo'])) {
                                echo '<img src="' . htmlspecialchars($company['logo']) . '" alt="Logo" class="max-h-10">';
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <h2 class="text-xl font-semibold mt-8 mb-4">Troubleshooting Steps</h2>
        <div class="mb-6 bg-gray-50 p-4 rounded">
            <ol class="list-decimal pl-6 space-y-2">
                <li>
                    <strong>GD Extension:</strong> The GD extension is required for image processing. To enable it in XAMPP:
                    <ul class="list-disc pl-6 mt-1">
                        <li>Edit <code>C:\xampp\php\php.ini</code></li>
                        <li>Find <code>;extension=gd</code> and remove the semicolon</li>
                        <li>Save the file and restart Apache from the XAMPP Control Panel</li>
                    </ul>
                </li>
                <li>Check that the logo directory exists and has proper permissions (should be 755).</li>
                <li>Verify that logo files exist at the paths stored in the database.</li>
                <li>Make sure logo files are readable by the web server (permissions 644).</li>
                <li>If using Windows, backslashes in file paths may cause issues - ensure forward slashes are used.</li>
                <li>Try uploading a different image format (JPG, PNG, GIF) to see if the issue is format-specific.</li>
                <li>Check the web server error logs for any related errors (check <code>C:\xampp\apache\logs\error.log</code>).</li>
            </ol>
        </div>
        
        <div class="mt-6 text-center">
            <a href="manage_companies.php" class="inline-block bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded">
                Back to Companies
            </a>
        </div>
    </div>
</body>
</html>
