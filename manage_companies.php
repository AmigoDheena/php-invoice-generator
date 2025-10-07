<?php
// Check if functions are already loaded via composer autoloader
if (!function_exists('get        <div class="flex justify-between mb-4">
            <a href="index.php" class="inline-block" style="color: var(--primary-color);">
                <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
            </a>iceById')) {
    require_once 'includes/functions.php';
}
$pageTitle = 'Manage Companies';
$companies = getCompanies();

$editCompany = null;
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $editCompany = getCompanyById($_GET['edit']);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add' || $_POST['action'] === 'update') {
            $company = [
                'id' => $_POST['company_id'] ?? '',
                'name' => $_POST['name'],
                'email' => $_POST['email'],
                'address' => $_POST['address'],
                'phone' => $_POST['phone'],
                'banking_details' => $_POST['banking_details'] ?? ''
            ];
            
            // Handle logo upload
            if (!empty($_FILES['logo']['name'])) {
                // Make sure the logo directory exists with correct permissions
                $logoDir = 'uploads/logos/';
                if (!is_dir($logoDir)) {
                    mkdir($logoDir, 0755, true); // Sufficient permissions for server, more secure
                }
                
                // Clean the filename to avoid any special characters
                $logoName = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '', basename($_FILES['logo']['name']));
                $logoPath = $logoDir . $logoName;
                
                // Check if the file is an image
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                $fileExt = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
                
                if (in_array($fileExt, $allowedExtensions) && $_FILES['logo']['error'] === 0) {
                    // Move the uploaded file to the logos directory
                    if (move_uploaded_file($_FILES['logo']['tmp_name'], $logoPath)) {
                        // Set file permissions to be readable by everyone
                        chmod($logoPath, 0644);
                        
                        // Store the path in the company record
                        $company['logo'] = $logoPath;
                        
                        // Debug message - uncomment if needed
                        // echo "Logo uploaded to: " . $logoPath;
                    }
                }
            } elseif (isset($_POST['remove_logo']) && $_POST['remove_logo'] === '1') {
                // User wants to remove the logo - first delete the file if it exists
                if (!empty($editCompany['logo']) && file_exists($editCompany['logo'])) {
                    if (is_writable($editCompany['logo'])) {
                        if (!unlink($editCompany['logo'])) {
                            error_log("Failed to delete logo file: " . $editCompany['logo']);
                        }
                    } else {
                        error_log("Logo file is not writable and cannot be deleted: " . $editCompany['logo']);
                    }
                }
                $company['logo'] = '';
            }
            
            saveCompany($company);
            header('Location: manage_companies.php');
            exit;
        } elseif ($_POST['action'] === 'delete' && isset($_POST['company_id'])) {
            deleteCompany($_POST['company_id']);
            header('Location: manage_companies.php');
            exit;
        }
    }
}
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
            <p class="text-gray-600">Add and manage your company profiles for invoices</p>
        </header>
        
        <div class="flex justify-between mb-4">
            <a href="index.php" class="inline-block text-blue-500 hover:text-blue-700">
                <i class="fas fa-arrow-left mr-2"></i>Back to Invoices
            </a>
            <div>
                <a href="logo_test.php" class="inline-block text-blue-500 hover:text-blue-700 mr-4">
                    <i class="fas fa-wrench mr-2"></i>Logo Troubleshooting Tool
                </a>
                <?php if (!extension_loaded('gd')): ?>
                <a href="enable_gd.php" class="inline-block text-red-500 hover:text-red-700">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Enable GD Extension
                </a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h2 class="text-xl font-semibold mb-4">Your Companies</h2>
                    
                    <?php if (empty($companies)): ?>
                        <p class="text-gray-500">You haven't added any companies yet.</p>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse">
                                <thead>
                                    <tr class="bg-gray-100">
                                        <th class="border p-2 text-left">Name</th>
                                        <th class="border p-2 text-left">Email</th>
                                        <th class="border p-2 text-left">Phone</th>
                                        <th class="border p-2 text-left">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($companies as $company): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="border p-2"><?php echo $company['name']; ?></td>
                                            <td class="border p-2"><?php echo $company['email']; ?></td>
                                            <td class="border p-2"><?php echo $company['phone']; ?></td>
                                            <td class="border p-2">
                                                <div class="flex space-x-2">
                                                    <a href="manage_companies.php?edit=<?php echo $company['id']; ?>" class="text-yellow-500 hover:text-yellow-700" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="manage_companies.php" method="post" class="inline" onsubmit="return confirm('Are you sure you want to delete this company?');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="company_id" value="<?php echo $company['id']; ?>">
                                                        <button type="submit" class="text-red-500 hover:text-red-700" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div>
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4">
                        <?php echo $editCompany ? 'Edit Company' : 'Add New Company'; ?>
                    </h2>
                    
                    <form action="manage_companies.php" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="<?php echo $editCompany ? 'update' : 'add'; ?>">
                        <?php if ($editCompany): ?>
                            <input type="hidden" name="company_id" value="<?php echo $editCompany['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-4">
                            <label for="logo" class="block text-gray-700 font-medium mb-2">Company Logo</label>
                            <input type="file" id="logo" name="logo" 
                                   class="w-full border-gray-300 rounded-md shadow-sm p-2 border focus:border-blue-500 focus:ring focus:ring-blue-200" 
                                   accept="image/png, image/jpeg, image/gif"
                                   <?php echo !extension_loaded('gd') ? 'disabled' : ''; ?>>
                            <small class="text-gray-500 block mt-1">Recommended size: 300x100px. Logo will replace company name in PDF invoices.</small>
                            
                            <?php if (!extension_loaded('gd')): ?>
                                <div class="mt-2 p-2 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded">
                                    <strong>Note:</strong> PHP GD extension is not installed. Logo functionality is disabled.
                                    Contact your server administrator to install the GD extension for image support.
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($editCompany && !empty($editCompany['logo']) && file_exists($editCompany['logo'])): ?>
                                <div class="mt-3 border rounded p-3 bg-gray-50">
                                    <p class="mb-2 text-sm">Current logo:</p>
                                    <div class="flex items-center">
                                        <img src="<?php echo $editCompany['logo']; ?>" alt="Company Logo" class="h-12 mr-3 border">
                                        <label class="inline-flex items-center">
                                            <input type="checkbox" name="remove_logo" value="1" class="mr-2">
                                            Remove logo
                                        </label>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-4">
                            <label for="name" class="block text-gray-700 font-medium mb-2">Company Name</label>
                            <input type="text" id="name" name="name" value="<?php echo $editCompany ? $editCompany['name'] : ''; ?>" 
                                   class="w-full border-gray-300 rounded-md shadow-sm p-2 border focus:border-blue-500 focus:ring focus:ring-blue-200" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="email" class="block text-gray-700 font-medium mb-2">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo $editCompany ? $editCompany['email'] : ''; ?>" 
                                   class="w-full border-gray-300 rounded-md shadow-sm p-2 border focus:border-blue-500 focus:ring focus:ring-blue-200" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="address" class="block text-gray-700 font-medium mb-2">Address</label>
                            <textarea id="address" name="address" rows="3" 
                                      class="w-full border-gray-300 rounded-md shadow-sm p-2 border focus:border-blue-500 focus:ring focus:ring-blue-200" required><?php echo $editCompany ? $editCompany['address'] : ''; ?></textarea>
                        </div>
                        
                        <div class="mb-4">
                            <label for="phone" class="block text-gray-700 font-medium mb-2">Phone</label>
                            <input type="text" id="phone" name="phone" value="<?php echo $editCompany ? $editCompany['phone'] : ''; ?>" 
                                   class="w-full border-gray-300 rounded-md shadow-sm p-2 border focus:border-blue-500 focus:ring focus:ring-blue-200">
                        </div>
                        
                        <div class="mb-4">
                            <label for="banking_details" class="block text-gray-700 font-medium mb-2">Banking Details</label>
                            <textarea id="banking_details" name="banking_details" rows="8" 
                                      placeholder="Name: Your Company Name&#10;Bank: Your Bank Name&#10;Branch: Your Branch&#10;Acc num: 1234567890&#10;IFSC Code: ABCD0123456&#10;Account: Savings&#10;PAN: ABCDE1234F&#10;Gpay and PhonePe: 9876543210"
                                      class="w-full border-gray-300 rounded-md shadow-sm p-2 border focus:border-blue-500 focus:ring focus:ring-blue-200"><?php echo $editCompany ? ($editCompany['banking_details'] ?? '') : ''; ?></textarea>
                            <small class="text-gray-500">Enter your banking details for payment information on invoices</small>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <?php if ($editCompany): ?>
                                <a href="manage_companies.php" class="text-blue-500 hover:text-blue-700">
                                    Cancel
                                </a>
                                <button type="submit" class="text-white font-semibold py-2 px-4 rounded" style="background-color: var(--secondary-color); hover:background-color: var(--secondary-dark);">
                                    Update Company
                                </button>
                            <?php else: ?>
                                <div></div>
                                <button type="submit" class="text-white font-semibold py-2 px-4 rounded" style="background-color: var(--primary-color); hover:background-color: var(--primary-dark);">
                                    Add Company
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <?php include_once 'includes/footer.php'; ?>
</body>
</html>
