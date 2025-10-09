<?php
/**
 * AJAX endpoint to get all products
 */

// Check if functions are already loaded via composer autoloader
if (!function_exists('getProducts')) {
    require_once '../includes/functions.php';
}

// Set content type to JSON
header('Content-Type: application/json');

// Get all products
$products = getProducts();

// Return as JSON
echo json_encode($products);