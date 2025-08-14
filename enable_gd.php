<?php
// This is a helper script to check GD extension status and provide guidance

// Security check - this should only be run locally
$client_ip = $_SERVER['REMOTE_ADDR'];
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    // X-Forwarded-For can be a comma-separated list; take the first one
    $forwarded_for = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    $client_ip = trim($forwarded_for);
}
if (!in_array($client_ip, ['127.0.0.1', '::1'])) {
    die("This script can only be run locally for security reasons.");
}

// Get PHP info
ob_start();
phpinfo(INFO_MODULES);
$phpinfo = ob_get_clean();

// Check if GD is enabled
$gd_enabled = extension_loaded('gd');

// Find php.ini location
$config_file = php_ini_loaded_file();

// Get server software info
$server_software = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
$is_xampp = (stripos($server_software, 'xampp') !== false);

// Get platform info
$is_windows = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');

// The page content
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP GD Extension Helper</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto max-w-4xl p-6">
        <div class="bg-white shadow-md rounded-lg p-6">
            <h1 class="text-2xl font-bold mb-6 text-blue-700">PHP GD Extension Helper</h1>
            
            <!-- Status Section -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold mb-4">GD Extension Status</h2>
                <div class="p-4 rounded-md <?php echo $gd_enabled ? 'bg-green-100' : 'bg-red-100'; ?>">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <?php if ($gd_enabled): ?>
                            <svg class="h-5 w-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <?php else: ?>
                            <svg class="h-5 w-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <?php endif; ?>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-lg font-medium <?php echo $gd_enabled ? 'text-green-800' : 'text-red-800'; ?>">
                                <?php echo $gd_enabled ? 'GD Extension is Enabled' : 'GD Extension is Not Enabled'; ?>
                            </h3>
                            <div class="mt-2 <?php echo $gd_enabled ? 'text-green-700' : 'text-red-700'; ?>">
                                <p>
                                    <?php if ($gd_enabled): ?>
                                        Good news! The GD extension is enabled. Your invoices should now be able to display logos properly.
                                    <?php else: ?>
                                        The GD extension is not enabled. This is required for image processing in your invoices.
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Information -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold mb-4">System Information</h2>
                <div class="bg-gray-50 p-4 rounded-md">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">PHP Version</dt>
                            <dd class="mt-1 text-sm text-gray-900"><?php echo phpversion(); ?></dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Server Software</dt>
                            <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($server_software); ?></dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Operating System</dt>
                            <dd class="mt-1 text-sm text-gray-900"><?php echo PHP_OS; ?></dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">PHP Configuration File</dt>
                            <dd class="mt-1 text-sm text-gray-900 overflow-hidden text-ellipsis"><?php echo $config_file ?: 'Not detected'; ?></dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Instructions -->
            <?php if (!$gd_enabled): ?>
            <div class="mb-8">
                <h2 class="text-xl font-semibold mb-4">How to Enable GD Extension</h2>
                <div class="border border-gray-200 rounded-md">
                    <?php if ($is_windows && $is_xampp): ?>
                    <!-- XAMPP on Windows Instructions -->
                    <div class="p-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-800">XAMPP on Windows Instructions</h3>
                        <ol class="mt-4 list-decimal list-inside space-y-2 text-gray-700">
                            <li>
                                <span class="font-medium">Edit php.ini:</span>
                                <div class="ml-6 mt-1">
                                    <p>Open your php.ini file located at:</p>
                                    <div class="p-2 bg-gray-100 rounded mt-1">
                                        <code><?php echo $config_file ?: 'C:\xampp\php\php.ini'; ?></code>
                                    </div>
                                    <p class="mt-2">You can open this with Notepad or any text editor.</p>
                                </div>
                            </li>
                            <li>
                                <span class="font-medium">Find and uncomment the GD extension line:</span>
                                <div class="ml-6 mt-1">
                                    <p>Search for the following line:</p>
                                    <div class="p-2 bg-gray-100 rounded mt-1">
                                        <code>;extension=gd</code>
                                    </div>
                                    <p class="mt-2">Remove the semicolon (;) at the beginning of the line to uncomment it:</p>
                                    <div class="p-2 bg-gray-100 rounded mt-1 text-green-700">
                                        <code>extension=gd</code>
                                    </div>
                                </div>
                            </li>
                            <li>
                                <span class="font-medium">Save the file and restart Apache:</span>
                                <div class="ml-6 mt-1">
                                    <p>Save the changes to php.ini, then open XAMPP Control Panel and:</p>
                                    <ul class="list-disc list-inside ml-4 mt-1">
                                        <li>Click "Stop" for Apache</li>
                                        <li>Once stopped, click "Start" for Apache</li>
                                    </ul>
                                </div>
                            </li>
                            <li>
                                <span class="font-medium">Verify:</span>
                                <div class="ml-6 mt-1">
                                    <p>Refresh this page to see if GD is now enabled.</p>
                                </div>
                            </li>
                        </ol>
                    </div>
                    <?php elseif ($is_windows): ?>
                    <!-- General Windows Instructions -->
                    <div class="p-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-800">Windows Instructions</h3>
                        <ol class="mt-4 list-decimal list-inside space-y-2 text-gray-700">
                            <li>Find and edit your php.ini file (located at: <?php echo $config_file ?: 'unknown location'; ?>)</li>
                            <li>Find the line <code>;extension=gd</code> and remove the semicolon</li>
                            <li>Save the file and restart your web server</li>
                        </ol>
                    </div>
                    <?php else: ?>
                    <!-- Linux/Unix Instructions -->
                    <div class="p-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-800">Linux/Unix Instructions</h3>
                        <ol class="mt-4 list-decimal list-inside space-y-2 text-gray-700">
                            <li>You may need to install the GD library and PHP GD extension:</li>
                            <div class="p-2 bg-gray-100 rounded mt-1">
                                <code>sudo apt-get install php-gd</code> (for Debian/Ubuntu)<br>
                                <code>sudo yum install php-gd</code> (for CentOS/RHEL)
                            </div>
                            <li>After installation, restart your web server:</li>
                            <div class="p-2 bg-gray-100 rounded mt-1">
                                <code>sudo systemctl restart apache2</code> (for Apache on Debian/Ubuntu)<br>
                                <code>sudo systemctl restart httpd</code> (for Apache on CentOS/RHEL)
                            </div>
                        </ol>
                    </div>
                    <?php endif; ?>
                    
                    <!-- General Instructions for All Platforms -->
                    <div class="p-4">
                        <h3 class="text-lg font-medium text-gray-800">Alternative Solution</h3>
                        <p class="mt-2 text-gray-700">If you're unable to enable the GD extension, you can still use the application without logos in your PDFs. The system has been updated to work without GD, but logos won't be displayed.</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Additional Resources -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold mb-4">Additional Resources</h2>
                <ul class="list-disc list-inside space-y-2 text-gray-700">
                    <li><a href="https://www.php.net/manual/en/image.installation.php" class="text-blue-600 hover:underline" target="_blank">PHP GD Installation Documentation</a></li>
                    <li><a href="https://www.php.net/manual/en/book.image.php" class="text-blue-600 hover:underline" target="_blank">PHP GD Functions Reference</a></li>
                    <?php if ($is_xampp): ?>
                    <li><a href="https://www.apachefriends.org/faq_windows.html" class="text-blue-600 hover:underline" target="_blank">XAMPP Windows FAQ</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <!-- Navigation Links -->
            <div class="mt-8 flex space-x-4">
                <a href="logo_test.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                    Go to Logo Test Tool
                </a>
                <a href="manage_companies.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gray-600 hover:bg-gray-700">
                    Return to Companies
                </a>
            </div>
        </div>
    </div>
</body>
</html>
