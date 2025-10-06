<?php
/**
 * Footer template for the Invoice Generator
 */
?>
<!-- Footer -->
<footer class="footer">
    <div class="container mx-auto px-4">
        <p>© <?php echo date('Y'); ?> <a href="https://github.com/AmigoDheena/php-invoice-generator" target="_blank" rel="noopener noreferrer">PHP Invoice Generator</a> | Developed by <a href="https://github.com/AmigoDheena" target="_blank" rel="noopener">AmigoDheena</a></p>
        <p class="text-sm mt-2">An open-source project for simple invoice management without a database</p>
    </div>
</footer>

<style>
/* Footer styles */
.footer {
    margin-top: 3rem;
    padding: 1.5rem 0;
    border-top: 1px solid #e5e7eb;
    text-align: center;
    color: #6b7280;
}
.footer a {
    color: #3b82f6;
    text-decoration: none;
    transition: color 0.2s;
}
.footer a:hover {
    color: #2563eb;
    text-decoration: underline;
}
</style>