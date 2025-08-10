<?php
// Check if functions are already loaded via composer autoloader
if (!function_exists('getInvoiceById')) {
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
    <div class="container mx-auto px-4 py-8">
        <header class="mb-6">
            <h1 class="text-3xl font-bold text-gray-800"><?php echo $pageTitle; ?></h1>
            <p class="text-gray-600">Add and manage your company profiles for invoices</p>
        </header>
        
        <a href="index.php" class="inline-block mb-4 text-blue-500 hover:text-blue-700">
            <i class="fas fa-arrow-left mr-2"></i>Back to Invoices
        </a>
        
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
                    
                    <form action="manage_companies.php" method="post">
                        <input type="hidden" name="action" value="<?php echo $editCompany ? 'update' : 'add'; ?>">
                        <?php if ($editCompany): ?>
                            <input type="hidden" name="company_id" value="<?php echo $editCompany['id']; ?>">
                        <?php endif; ?>
                        
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
                                <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white font-semibold py-2 px-4 rounded">
                                    Update Company
                                </button>
                            <?php else: ?>
                                <div></div>
                                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded">
                                    Add Company
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
