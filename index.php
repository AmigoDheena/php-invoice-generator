<?php
// Check if functions are already loaded via composer autoloader
if (!function_exists('getInvoiceById')) {
    require_once 'includes/functions.php';
}
$pageTitle = 'Invoice Generator';

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$perPage = 10;

// Get invoices in descending order with pagination
$result = getInvoices(true, $page, $perPage);
$invoices = $result['data'];
$totalInvoices = $result['total'];
$lastPage = $result['lastPage'];

$companies = getCompanies();
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
    <div class="max-w-6xl mx-auto px-6 py-10 bg-transparent">
        <header class="mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Invoice Generator</h1>
            <p class="text-gray-600">Create and manage your invoices easily</p>
        </header>

        <div class="flex justify-between mb-6">
            <a href="create_invoice.php" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded">
                <i class="fas fa-plus mr-2"></i>Create New Invoice
            </a>
            <a href="manage_companies.php" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded">
                <i class="fas fa-building mr-2"></i>Manage Companies
            </a>
        </div>

        <!-- Tabbed Interface -->
        <div class="bg-white rounded-lg shadow-md">
            <!-- Tab Navigation -->
            <div class="border-b">
                <div class="flex">
                    <button class="tab-button py-3 px-6 border-b-2 border-blue-500 font-medium text-blue-600 bg-white" data-tab="invoices">
                        <i class="fas fa-file-invoice mr-2"></i>Invoices
                    </button>
                    <button class="tab-button py-3 px-6 border-b-2 border-transparent font-medium text-gray-500 hover:text-gray-700 bg-white" data-tab="dashboard">
                        <i class="fas fa-chart-bar mr-2"></i>Dashboard
                    </button>
                </div>
            </div>

            <!-- Tab Contents -->
            <div class="tab-content" id="dashboard-tab" style="display: none;">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold">Analytics Dashboard</h2>
                        <button id="toggleCharts" class="text-sm text-gray-500 hover:text-gray-700">
                            <i class="fas fa-chevron-up"></i> Hide Charts
                        </button>
                    </div>
                    
                    <div id="chartsContainer" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Monthly Revenue Chart -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="flex justify-between items-center mb-2">
                                <h3 class="text-md font-medium">Monthly Revenue</h3>
                                <span class="text-sm text-gray-500" title="Shows paid vs. outstanding revenue over the last 6 months">
                                    <i class="fas fa-info-circle"></i>
                                </span>
                            </div>
                            <div class="h-64 chart-container">
                                <div class="loading-indicator">
                                    <i class="fas fa-spinner fa-spin"></i> Loading...
                                </div>
                                <canvas id="revenueChart"></canvas>
                            </div>
                        </div>
                        
                        <!-- Invoice Status Chart -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="flex justify-between items-center mb-2">
                                <h3 class="text-md font-medium">Invoice Status</h3>
                                <span class="text-sm text-gray-500" title="Distribution of paid vs. unpaid invoices">
                                    <i class="fas fa-info-circle"></i>
                                </span>
                            </div>
                            <div class="h-64 chart-container">
                                <div class="loading-indicator">
                                    <i class="fas fa-spinner fa-spin"></i> Loading...
                                </div>
                                <canvas id="statusChart"></canvas>
                            </div>
                        </div>
                        
                        <!-- Document Type Analysis -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="flex justify-between items-center mb-2">
                                <h3 class="text-md font-medium">Document Types</h3>
                                <span class="text-sm text-gray-500" title="Comparison of invoices vs. quotations">
                                    <i class="fas fa-info-circle"></i>
                                </span>
                            </div>
                            <div class="h-64 chart-container">
                                <div class="loading-indicator">
                                    <i class="fas fa-spinner fa-spin"></i> Loading...
                                </div>
                                <canvas id="documentTypeChart"></canvas>
                            </div>
                        </div>
                        
                        <!-- Top Clients Chart -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="flex justify-between items-center mb-2">
                                <h3 class="text-md font-medium">Top 5 Clients</h3>
                                <span class="text-sm text-gray-500" title="Clients with highest total invoice values">
                                    <i class="fas fa-info-circle"></i>
                                </span>
                            </div>
                            <div class="h-64 chart-container">
                                <div class="loading-indicator">
                                    <i class="fas fa-spinner fa-spin"></i> Loading...
                                </div>
                                <canvas id="clientChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Invoice List Tab (shown by default) -->
            <div class="tab-content" id="invoices-tab">
                <div class="p-6">
                    <h2 class="text-xl font-semibold mb-4">Your Invoices</h2>
                    
                    <?php if (empty($invoices)): ?>
                        <p class="text-gray-500">You haven't created any invoices yet.</p>
                    <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="border p-2 text-left">Doc #</th>
                                <th class="border p-2 text-left">Type</th>
                                <th class="border p-2 text-left">Date</th>
                                <th class="border p-2 text-left">Client</th>
                                <th class="border p-2 text-left">Company</th>
                                <th class="border p-2 text-left">Amount</th>
                                <th class="border p-2 text-left">Status</th>
                                <th class="border p-2 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invoices as $invoice): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="border p-2"><?php echo $invoice['id']; ?></td>
                                    <td class="border p-2"><?php echo $invoice['document_type'] ?? 'Invoice'; ?></td>
                                    <td class="border p-2"><?php echo $invoice['date']; ?></td>
                                    <td class="border p-2"><?php echo $invoice['client_name']; ?></td>
                                    <td class="border p-2">
                                        <?php 
                                        $companyName = "Unknown";
                                        foreach ($companies as $company) {
                                            if ($company['id'] == $invoice['company_id']) {
                                                $companyName = $company['name'];
                                                break;
                                            }
                                        }
                                        echo $companyName;
                                        ?>
                                    </td>
                                    <td class="border p-2">Rs.<?php echo number_format($invoice['total'], 2); ?></td>
                                    <td class="border p-2">
                                        <span class="px-2 py-1 rounded text-xs 
                                        <?php echo $invoice['status'] === 'Paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                            <?php echo $invoice['status']; ?>
                                        </span>
                                    </td>
                                    <td class="border p-2">
                                        <div class="flex space-x-2">
                                            <a href="view_invoice.php?id=<?php echo $invoice['id']; ?>" class="text-blue-500 hover:text-blue-700" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit_invoice.php?id=<?php echo $invoice['id']; ?>" class="text-yellow-500 hover:text-yellow-700" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="download_pdf.php?id=<?php echo $invoice['id']; ?>" class="text-green-500 hover:text-green-700" title="Download PDF">
                                                <i class="fas fa-file-pdf"></i>
                                            </a>
                                            <a href="delete_invoice.php?id=<?php echo $invoice['id']; ?>" class="text-red-500 hover:text-red-700" title="Delete" 
                                               onclick="return confirm('Are you sure you want to delete this invoice?');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <!-- Pagination -->
                    <?php if ($lastPage > 1): ?>
                    <div class="mt-6 flex justify-between items-center">
                        <div class="text-sm text-gray-500">
                            Showing <?php echo ($page-1)*$perPage+1; ?> to <?php echo min($page*$perPage, $totalInvoices); ?> of <?php echo $totalInvoices; ?> invoices
                        </div>
                        <div class="flex space-x-1">
                            <?php if ($page > 1): ?>
                            <a href="?page=1" class="px-3 py-1 rounded border bg-white hover:bg-gray-50">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                            <a href="?page=<?php echo $page-1; ?>" class="px-3 py-1 rounded border bg-white hover:bg-gray-50">
                                <i class="fas fa-angle-left"></i>
                            </a>
                            <?php endif; ?>
                            
                            <?php
                            // Calculate range of page numbers to show
                            $startPage = max(1, $page - 2);
                            $endPage = min($lastPage, $page + 2);
                            
                            // Always show at least 5 pages if available
                            if ($endPage - $startPage < 4 && $lastPage > 4) {
                                if ($startPage == 1) {
                                    $endPage = min($lastPage, 5);
                                } elseif ($endPage == $lastPage) {
                                    $startPage = max(1, $lastPage - 4);
                                }
                            }
                            
                            for ($i = $startPage; $i <= $endPage; $i++): 
                            ?>
                                <a href="?page=<?php echo $i; ?>" 
                                   class="px-3 py-1 rounded border <?php echo $i == $page ? 'bg-blue-500 text-white' : 'bg-white hover:bg-gray-50'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $lastPage): ?>
                            <a href="?page=<?php echo $page+1; ?>" class="px-3 py-1 rounded border bg-white hover:bg-gray-50">
                                <i class="fas fa-angle-right"></i>
                            </a>
                            <a href="?page=<?php echo $lastPage; ?>" class="px-3 py-1 rounded border bg-white hover:bg-gray-50">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tab switching functionality
        const tabButtons = document.querySelectorAll('.tab-button');
        const tabContents = document.querySelectorAll('.tab-content');
        
        function switchTab(tabId) {
            // Hide all tab contents
            tabContents.forEach(content => {
                content.style.display = 'none';
            });
            
            // Remove active class from all buttons
            tabButtons.forEach(button => {
                button.classList.remove('border-blue-500', 'text-blue-600');
                button.classList.add('border-transparent', 'text-gray-500');
            });
            
            // Show the selected tab content
            document.getElementById(tabId + '-tab').style.display = 'block';
            
            // Add active class to the clicked button
            document.querySelector(`[data-tab="${tabId}"]`).classList.remove('border-transparent', 'text-gray-500');
            document.querySelector(`[data-tab="${tabId}"]`).classList.add('border-blue-500', 'text-blue-600');
            
            // If switching to dashboard, ensure charts are properly rendered
            if(tabId === 'dashboard') {
                window.dispatchEvent(new Event('resize'));
            }
        }
        
        // Add click event to tab buttons
        tabButtons.forEach(button => {
            button.addEventListener('click', () => {
                const tabId = button.getAttribute('data-tab');
                switchTab(tabId);
            });
        });
        
        // Set default tab to invoices
        switchTab('invoices');
        // Initial setup - hide loading indicators when charts are ready
        const hideLoading = (chartId) => {
            const container = document.querySelector(`#${chartId}`).closest('.chart-container');
            if (container) {
                const loader = container.querySelector('.loading-indicator');
                if (loader) {
                    loader.style.display = 'none';
                }
            }
        };

        // Chart color schemes
        const chartColors = {
            blue: '#3B82F6',
            green: '#10B981',
            yellow: '#F59E0B',
            red: '#EF4444',
            purple: '#8B5CF6',
            gray: '#6B7280'
        };
        
        // Toggle charts visibility
        const toggleBtn = document.getElementById('toggleCharts');
        const chartsContainer = document.getElementById('chartsContainer');
        
        toggleBtn.addEventListener('click', function() {
            chartsContainer.classList.toggle('hidden');
            if (chartsContainer.classList.contains('hidden')) {
                toggleBtn.innerHTML = '<i class="fas fa-chevron-down"></i> Show Charts';
            } else {
                toggleBtn.innerHTML = '<i class="fas fa-chevron-up"></i> Hide Charts';
            }
        });
        
        // 1. Monthly Revenue Chart
        const revenueChartCtx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(revenueChartCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_keys(getMonthlyRevenueSummary())); ?>,
                datasets: [
                    {
                        label: 'Paid',
                        data: <?php 
                            $data = array_column(getMonthlyRevenueSummary(), 'paid');
                            echo json_encode(array_values($data)); 
                        ?>,
                        backgroundColor: chartColors.green
                    },
                    {
                        label: 'Unpaid',
                        data: <?php 
                            $data = array_column(getMonthlyRevenueSummary(), 'unpaid');
                            echo json_encode(array_values($data)); 
                        ?>,
                        backgroundColor: chartColors.yellow
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += 'Rs.' + context.parsed.y.toFixed(2);
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        stacked: true,
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rs.' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
        hideLoading('revenueChart');
        
        // 2. Invoice Status Chart
        const statusData = <?php echo json_encode(getInvoiceStatusBreakdown()); ?>;
        const statusChartCtx = document.getElementById('statusChart').getContext('2d');
        const statusChart = new Chart(statusChartCtx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(statusData),
                datasets: [{
                    data: Object.values(statusData),
                    backgroundColor: [chartColors.green, chartColors.yellow],
                    borderColor: '#ffffff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label;
                                const value = context.raw;
                                const total = Object.values(statusData).reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                },
                cutout: '65%'
            }
        });
        hideLoading('statusChart');
        
        // 3. Document Type Analysis Chart
        const docTypeData = <?php echo json_encode(getDocumentTypeAnalysis()); ?>;
        const docTypeChartCtx = document.getElementById('documentTypeChart').getContext('2d');
        const docTypeChart = new Chart(docTypeChartCtx, {
            type: 'bar',
            data: {
                labels: Object.keys(docTypeData),
                datasets: [
                    {
                        label: 'Count',
                        data: Object.values(docTypeData).map(item => item.count),
                        backgroundColor: chartColors.blue,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Value',
                        data: Object.values(docTypeData).map(item => item.value),
                        backgroundColor: chartColors.purple,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.dataset.label === 'Value') {
                                    label += 'Rs.' + context.parsed.y.toFixed(2);
                                } else {
                                    label += context.parsed.y;
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Count'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false
                        },
                        title: {
                            display: true,
                            text: 'Value (Rs.)'
                        },
                        ticks: {
                            callback: function(value) {
                                return 'Rs.' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
        hideLoading('documentTypeChart');
        
        // 4. Top Clients Chart
        const clientData = <?php echo json_encode(getTopClientsByValue()); ?>;
        const clientLabels = Object.keys(clientData);
        const clientValues = Object.values(clientData);
        
        const clientChartCtx = document.getElementById('clientChart').getContext('2d');
        const clientChart = new Chart(clientChartCtx, {
            type: 'bar',
            data: {
                labels: clientLabels,
                datasets: [{
                    label: 'Total Invoice Value',
                    data: clientValues,
                    backgroundColor: clientLabels.map((_, i) => {
                        const colors = [chartColors.blue, chartColors.purple, chartColors.green, chartColors.yellow, chartColors.red];
                        return colors[i % colors.length];
                    }),
                    borderWidth: 0
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += 'Rs.' + context.parsed.x.toFixed(2);
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rs.' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
        hideLoading('clientChart');
    });
    </script>
    <style>
    .chart-container {
        position: relative;
    }
    .loading-indicator {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.8);
        z-index: 5;
    }
    .tab-button {
        position: relative;
        transition: all 0.3s ease;
    }
    .tab-button:hover {
        background-color: #f9fafb;
    }
    .tab-button.active {
        border-bottom-color: #3B82F6;
        color: #2563EB;
    }
    .tab-content {
        display: none;
    }
    .tab-content.active {
        display: block;
        animation: fadeIn 0.5s;
    }
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    </style>

    <script src="assets/js/main.js"></script>
</body>
</html>
