// Common JavaScript functionality for the invoice generator

document.addEventListener('DOMContentLoaded', function() {
    // Print invoice functionality
    const printButtons = document.querySelectorAll('.print-invoice');
    
    if (printButtons.length > 0) {
        printButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                window.print();
            });
        });
    }
    
    // Format currency inputs
    const formatCurrency = (input) => {
        if (!input) return;
        
        input.addEventListener('blur', function(e) {
            const value = parseFloat(this.value);
            if (!isNaN(value)) {
                this.value = value.toFixed(2);
            }
        });
    };
    
    document.querySelectorAll('.currency-input').forEach(formatCurrency);
});
