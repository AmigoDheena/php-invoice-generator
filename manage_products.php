<?php
// Check if functions are already loaded via composer autoloader
if (!function_exists('getProducts')) {
    require_once 'includes/functions.php';
}

$pageTitle = 'Manage Products';
$editProduct = null;
$categories = getProductCategories();
$selectedCategory = $_GET['category'] ?? '';
$products = getProducts($selectedCategory);
$formMessage = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'save') {
            // Save or update product
            $product = [
                'id' => isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0,
                'name' => $_POST['name'] ?? '',
                'description' => $_POST['description'] ?? '',
                'price' => (float)($_POST['price'] ?? 0),
                'category' => $_POST['category'] ?? '',
                'sku' => $_POST['sku'] ?? '',
                'unit' => $_POST['unit'] ?? ''
            ];
            
            saveProduct($product);
            $formMessage = 'Product saved successfully.';
            
            // Redirect to clear form data
            header('Location: manage_products.php?message=' . urlencode($formMessage) . 
                  ($selectedCategory ? '&category=' . urlencode($selectedCategory) : ''));
            exit;
        } elseif ($_POST['action'] === 'delete' && isset($_POST['product_id'])) {
            // Delete product
            deleteProduct($_POST['product_id']);
            $formMessage = 'Product deleted successfully.';
            
            // Redirect to clear form data
            header('Location: manage_products.php?message=' . urlencode($formMessage) . 
                  ($selectedCategory ? '&category=' . urlencode($selectedCategory) : ''));
            exit;
        }
    }
}

// Check if we're editing a product
if (isset($_GET['edit']) && $_GET['edit']) {
    $editProduct = getProductById($_GET['edit']);
}

// Check for any passed messages
if (isset($_GET['message'])) {
    $formMessage = $_GET['message'];
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
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-800"><?php echo $pageTitle; ?></h1>
                <p class="text-gray-600">Create and manage your products/services catalog</p>
            </div>
            <div>
                <a href="index.php" class="text-gray-600 hover:text-gray-800">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
                </a>
            </div>
        </div>
        
        <?php if ($formMessage): ?>
        <div class="bg-green-100 border border-green-200 text-green-800 px-4 py-3 rounded mb-4">
            <?php echo $formMessage; ?>
        </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Product Form -->
            <div class="bg-white shadow-md rounded-lg p-6">
                <h2 class="text-xl font-semibold mb-4"><?php echo $editProduct ? 'Edit Product' : 'Add New Product'; ?></h2>
                
                <form method="post" action="manage_products.php<?php echo $selectedCategory ? '?category=' . urlencode($selectedCategory) : ''; ?>">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="product_id" value="<?php echo $editProduct ? $editProduct['id'] : ''; ?>">
                    
                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Product/Service Name *</label>
                        <input type="text" id="name" name="name" 
                               value="<?php echo $editProduct ? htmlspecialchars($editProduct['name']) : ''; ?>" 
                               class="w-full border-gray-300 rounded-md shadow-sm p-2 border focus:border-primary-color focus:ring focus:ring-primary-light" 
                               required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea id="description" name="description" rows="3" 
                                  class="w-full border-gray-300 rounded-md shadow-sm p-2 border focus:border-primary-color focus:ring focus:ring-primary-light"><?php echo $editProduct ? htmlspecialchars($editProduct['description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="price" class="block text-sm font-medium text-gray-700 mb-1">Price (Rs.) *</label>
                            <input type="number" id="price" name="price" step="0.01" min="0" 
                                   value="<?php echo $editProduct ? htmlspecialchars($editProduct['price']) : ''; ?>" 
                                   class="w-full border-gray-300 rounded-md shadow-sm p-2 border focus:border-primary-color focus:ring focus:ring-primary-light"
                                   required>
                        </div>
                        <div>
                            <label for="unit" class="block text-sm font-medium text-gray-700 mb-1">Unit</label>
                            <input type="text" id="unit" name="unit" 
                                   value="<?php echo $editProduct ? htmlspecialchars($editProduct['unit'] ?? '') : ''; ?>" 
                                   class="w-full border-gray-300 rounded-md shadow-sm p-2 border focus:border-primary-color focus:ring focus:ring-primary-light"
                                   placeholder="e.g., Hour, Item, Kg">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                            <input type="text" id="category" name="category" list="category-list"
                                   value="<?php echo $editProduct ? htmlspecialchars($editProduct['category'] ?? '') : ''; ?>" 
                                   class="w-full border-gray-300 rounded-md shadow-sm p-2 border focus:border-primary-color focus:ring focus:ring-primary-light">
                            <datalist id="category-list">
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category); ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div>
                            <label for="sku" class="block text-sm font-medium text-gray-700 mb-1">SKU/Code</label>
                            <input type="text" id="sku" name="sku" 
                                   value="<?php echo $editProduct ? htmlspecialchars($editProduct['sku'] ?? '') : ''; ?>" 
                                   class="w-full border-gray-300 rounded-md shadow-sm p-2 border focus:border-primary-color focus:ring focus:ring-primary-light">
                        </div>
                    </div>
                    
                    <div class="flex justify-end mt-6">
                        <a href="manage_products.php" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded mr-2">
                            Cancel
                        </a>
                        <button type="submit" class="text-white font-semibold py-2 px-4 rounded" style="background-color: var(--primary-color);">
                            <?php echo $editProduct ? 'Update Product' : 'Add Product'; ?>
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Products List -->
            <div class="md:col-span-2">
                <div class="bg-white shadow-md rounded-lg p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold">Your Products</h2>
                        <div>
                            <label for="category-filter" class="text-sm font-medium text-gray-700 mr-2">Filter by Category:</label>
                            <select id="category-filter" class="border-gray-300 rounded-md shadow-sm py-1 px-2 border focus:border-primary-color focus:ring focus:ring-primary-light">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category); ?>" <?php echo $selectedCategory === $category ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <?php if (empty($products)): ?>
                    <p class="text-gray-500 italic">No products found. Add your first product using the form.</p>
                    <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($products as $product): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($product['name']); ?></div>
                                        <?php if (!empty($product['description'])): ?>
                                        <div class="text-sm text-gray-500 truncate" style="max-width: 300px;" title="<?php echo htmlspecialchars($product['description']); ?>">
                                            <?php echo htmlspecialchars($product['description']); ?>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <div class="text-sm font-medium text-gray-900">Rs.<?php echo number_format($product['price'], 2); ?></div>
                                        <?php if (!empty($product['unit'])): ?>
                                        <div class="text-sm text-gray-500">per <?php echo htmlspecialchars($product['unit']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            <?php echo !empty($product['category']) ? htmlspecialchars($product['category']) : 'Uncategorized'; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="manage_products.php?edit=<?php echo $product['id']; ?><?php echo $selectedCategory ? '&category=' . urlencode($selectedCategory) : ''; ?>" class="text-yellow-500 hover:text-yellow-700 mr-3">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <form method="post" action="manage_products.php<?php echo $selectedCategory ? '?category=' . urlencode($selectedCategory) : ''; ?>" class="inline" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <button type="submit" class="text-red-500 hover:text-red-700 bg-transparent border-none p-0">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php include_once 'includes/footer.php'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle category filter change
        const categoryFilter = document.getElementById('category-filter');
        if (categoryFilter) {
            categoryFilter.addEventListener('change', function() {
                window.location.href = 'manage_products.php' + (this.value ? '?category=' + encodeURIComponent(this.value) : '');
            });
        }
    });
    </script>
</body>
</html>