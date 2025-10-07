<?php
/**
 * Navigation Header template for the Invoice Generator
 * 
 * This file provides a consistent navigation header across all pages
 */

// Get the current page filename to highlight the active nav item
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Navigation Header -->
<header class="bg-white border-b shadow-sm mb-6" style="border-color: #fff; border-bottom-width: 3px;">
    <div class="max-w-6xl mx-auto px-4">
        <div class="flex justify-between items-center py-4">
            <!-- Logo/Brand -->
            <div>
                <a href="index.php" class="flex items-center">
                    <img src="assets/img/logo.png" alt="Invoice Generator Logo" class="h-12 mr-2">
                    <!-- <span class="text-2xl font-bold text-blue-600">Invoice Generator</span> -->
                </a>
            </div>

            <!-- Navigation Links -->
            <nav class="hidden md:flex space-x-6">
                <a href="index.php" class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home mr-1"></i> Dashboard
                </a>
                <a href="create_invoice.php" class="nav-link <?php echo $current_page === 'create_invoice.php' ? 'active' : ''; ?>">
                    <i class="fas fa-plus mr-1"></i> New Invoice
                </a>
                <a href="manage_companies.php" class="nav-link <?php echo $current_page === 'manage_companies.php' ? 'active' : ''; ?>">
                    <i class="fas fa-building mr-1"></i> Companies
                </a>
            </nav>

            <!-- Mobile Menu Button (only visible on small screens) -->
            <div class="md:hidden">
                <button id="mobile-menu-button" class="text-gray-500 hover:text-gray-700 focus:outline-none">
                    <i class="fas fa-bars text-xl"></i>
                </button>
            </div>
        </div>

        <!-- Mobile Menu (hidden by default) -->
        <div id="mobile-menu" class="md:hidden hidden py-4 border-t border-gray-200">
            <a href="index.php" class="mobile-nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>">
                <i class="fas fa-home mr-1"></i> Dashboard
            </a>
            <a href="create_invoice.php" class="mobile-nav-link <?php echo $current_page === 'create_invoice.php' ? 'active' : ''; ?>">
                <i class="fas fa-plus mr-1"></i> New Invoice
            </a>
            <a href="manage_companies.php" class="mobile-nav-link <?php echo $current_page === 'manage_companies.php' ? 'active' : ''; ?>">
                <i class="fas fa-building mr-1"></i> Companies
            </a>
        </div>
    </div>
</header>

<style>
.nav-link {
    display: inline-flex;
    align-items: center;
    padding: 0.5rem 0;
    color: #4b5563; /* text-gray-600 */
    font-weight: 500;
    transition: color 0.2s;
    position: relative;
}

.nav-link:hover {
    color: var(--primary-color); /* primary color */
}

.nav-link.active {
    color: var(--primary-color); /* primary color */
}

.nav-link.active:after {
    content: '';
    position: absolute;
    bottom: -4px;
    left: 0;
    right: 0;
    height: 2px;
    background-color: var(--primary-color); /* primary color */
}

.mobile-nav-link {
    display: block;
    padding: 0.75rem 0;
    color: #4b5563; /* text-gray-600 */
    font-weight: 500;
    border-bottom: 1px solid #f3f4f6; /* border-gray-100 */
}

.mobile-nav-link:hover {
    color: var(--primary-color); /* primary color */
}

.mobile-nav-link.active {
    color: var(--primary-color); /* primary color */
    font-weight: 600;
}
</style>

<script>
// Mobile menu toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');
    
    if (mobileMenuButton && mobileMenu) {
        mobileMenuButton.addEventListener('click', function() {
            mobileMenu.classList.toggle('hidden');
        });
    }
});
</script>