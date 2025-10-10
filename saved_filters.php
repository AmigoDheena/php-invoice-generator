<?php
// Check if functions are already loaded via composer autoloader
if (!function_exists('getSavedFilters')) {
    require_once 'includes/functions.php';
}

// Process save, update or delete requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'save') {
            // Save new filter
            $filterData = [
                'name' => $_POST['filter_name'],
                'filters' => $_POST['filters'] ?? [],
                'sort_by' => $_POST['sort_by'] ?? 'date',
                'sort_order' => $_POST['sort_order'] ?? 'desc'
            ];
            saveFilter($filterData);
            header('Location: saved_filters.php?saved=1');
            exit;
        } 
        elseif ($action === 'delete' && isset($_POST['id'])) {
            // Delete filter
            deleteFilter($_POST['id']);
            header('Location: saved_filters.php?deleted=1');
            exit;
        }
    }
}

$filters = getSavedFilters();
$pageTitle = 'Saved Filters';
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
            <p class="text-gray-600">Manage your saved filters and search presets</p>
        </header>
        
        <a href="index.php" class="inline-block mb-4 text-blue-500 hover:text-blue-700">
            <i class="fas fa-arrow-left mr-2"></i>Back to Invoices
        </a>
        
        <?php if (isset($_GET['saved'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <span class="font-bold">Success!</span> Your filter has been saved.
        </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['deleted'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <span class="font-bold">Success!</span> The filter has been deleted.
        </div>
        <?php endif; ?>
        
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="p-6">
                <h2 class="text-xl font-semibold mb-4">Your Saved Filters</h2>
                
                <?php if (empty($filters)): ?>
                    <p class="text-gray-500">You haven't saved any filters yet.</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="border p-2 text-left">Name</th>
                                    <th class="border p-2 text-left">Filters</th>
                                    <th class="border p-2 text-left">Sort</th>
                                    <th class="border p-2 text-left">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($filters as $filter): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="border p-2"><?php echo htmlspecialchars($filter['name']); ?></td>
                                        <td class="border p-2">
                                            <?php 
                                            $filterDescriptions = [];
                                            if (!empty($filter['filters'])) {
                                                foreach ($filter['filters'] as $key => $value) {
                                                    switch($key) {
                                                        case 'client':
                                                            $filterDescriptions[] = "Client: $value";
                                                            break;
                                                        case 'status':
                                                            $filterDescriptions[] = "Status: $value";
                                                            break;
                                                        case 'document_type':
                                                            $filterDescriptions[] = "Type: $value";
                                                            break;
                                                        case 'min_amount':
                                                            $filterDescriptions[] = "Min Amount: Rs.$value";
                                                            break;
                                                        case 'max_amount':
                                                            $filterDescriptions[] = "Max Amount: Rs.$value";
                                                            break;
                                                        case 'start_date':
                                                            $filterDescriptions[] = "From: $value";
                                                            break;
                                                        case 'end_date':
                                                            $filterDescriptions[] = "To: $value";
                                                            break;
                                                        case 'invoice_id':
                                                            $filterDescriptions[] = "Invoice ID: $value";
                                                            break;
                                                    }
                                                }
                                            }
                                            echo !empty($filterDescriptions) ? implode(', ', $filterDescriptions) : 'No filters';
                                            ?>
                                        </td>
                                        <td class="border p-2">
                                            <?php 
                                            $sortBy = $filter['sort_by'] ?? 'date';
                                            $sortOrder = $filter['sort_order'] ?? 'desc';
                                            echo "$sortBy ($sortOrder)";
                                            ?>
                                        </td>
                                        <td class="border p-2">
                                            <div class="flex space-x-2">
                                                <a href="index.php?load_filter=<?php echo $filter['id']; ?>" class="text-blue-500 hover:text-blue-700" title="Apply Filter">
                                                    <i class="fas fa-filter"></i>
                                                </a>
                                                <form method="post" class="inline" onsubmit="return confirm('Are you sure you want to delete this filter?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?php echo $filter['id']; ?>">
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
    </div>
    
    <?php include_once 'includes/footer.php'; ?>
</body>
</html>