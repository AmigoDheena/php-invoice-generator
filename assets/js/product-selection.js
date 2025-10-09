/**
 * Product selection and autocomplete functionality for invoices
 */
document.addEventListener('DOMContentLoaded', function() {
    let products = []; // Will store all products data
    
    // Fetch products from the server
    fetch('ajax/get_products.php')
        .then(response => response.json())
        .then(data => {
            products = data;
            initAutocomplete();
        })
        .catch(error => console.error('Error loading products:', error));
    
    // Initialize autocomplete on existing fields
    function initAutocomplete() {
        // Add autocomplete to existing product fields
        document.querySelectorAll('.product-search').forEach(input => {
            setupProductAutocomplete(input);
        });
    }
    
    // Setup product autocomplete on a specific input field
    function setupProductAutocomplete(input) {
        // Skip if already initialized
        if (input.dataset.autocompleteInitialized === 'true') return;
        
        // Create results container
        const resultsContainer = document.createElement('div');
        resultsContainer.className = 'autocomplete-results hidden';
        resultsContainer.style.position = 'absolute';
        resultsContainer.style.backgroundColor = 'white';
        resultsContainer.style.border = '1px solid #e5e7eb';
        resultsContainer.style.borderRadius = '0.375rem';
        resultsContainer.style.boxShadow = '0 4px 6px -1px rgba(0, 0, 0, 0.1)';
        resultsContainer.style.width = '100%';
        resultsContainer.style.maxHeight = '200px';
        resultsContainer.style.overflowY = 'auto';
        resultsContainer.style.zIndex = '50';
        
        // Add results container after the input
        input.parentNode.style.position = 'relative';
        input.parentNode.appendChild(resultsContainer);
        
        // Handle input changes
        input.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            if (query.length < 2) {
                resultsContainer.classList.add('hidden');
                return;
            }
            
            // Filter products based on query
            const matches = products.filter(product => 
                product.name.toLowerCase().includes(query) || 
                (product.description && product.description.toLowerCase().includes(query)) ||
                (product.sku && product.sku.toLowerCase().includes(query))
            );
            
            if (matches.length === 0) {
                resultsContainer.classList.add('hidden');
                return;
            }
            
            // Build results list
            resultsContainer.innerHTML = '';
            matches.slice(0, 6).forEach(product => {
                const resultItem = document.createElement('div');
                resultItem.className = 'p-2 hover:bg-gray-100 cursor-pointer flex justify-between items-center';
                
                // Name and category
                const nameEl = document.createElement('div');
                nameEl.innerHTML = `
                    <div class="font-medium">${product.name}</div>
                    ${product.category ? `<div class="text-xs text-gray-500">${product.category}</div>` : ''}
                `;
                
                // Price
                const priceEl = document.createElement('div');
                priceEl.className = 'text-right';
                priceEl.innerHTML = `
                    <div class="font-medium">Rs.${parseFloat(product.price).toFixed(2)}</div>
                    ${product.sku ? `<div class="text-xs text-gray-500">${product.sku}</div>` : ''}
                `;
                
                resultItem.appendChild(nameEl);
                resultItem.appendChild(priceEl);
                
                // Handle click on a result
                resultItem.addEventListener('click', function() {
                    selectProduct(product, input);
                    resultsContainer.classList.add('hidden');
                });
                
                resultsContainer.appendChild(resultItem);
            });
            
            resultsContainer.classList.remove('hidden');
        });
        
        // Handle input focus
        input.addEventListener('focus', function() {
            if (this.value.trim().length >= 2) {
                resultsContainer.classList.remove('hidden');
            }
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!input.contains(e.target) && !resultsContainer.contains(e.target)) {
                resultsContainer.classList.add('hidden');
            }
        });
        
        // Mark as initialized
        input.dataset.autocompleteInitialized = 'true';
    }
    
    // Select a product and fill related fields
    function selectProduct(product, input) {
        const row = input.closest('tr');
        
        // Fill description
        input.value = product.name;
        
        // Fill price
        const priceInput = row.querySelector('input[name="price[]"]');
        if (priceInput) priceInput.value = product.price;
        
        // Fill quantity (default to 1 if empty)
        const quantityInput = row.querySelector('input[name="quantity[]"]');
        if (quantityInput && quantityInput.value === '') quantityInput.value = 1;
        
        // Store product ID
        const productIdInput = row.querySelector('input[name="product_id[]"]');
        if (productIdInput) productIdInput.value = product.id;
        
        // Update total
        updateRowTotal(row);
    }
    
    // Add autocomplete to new rows
    document.addEventListener('DOMNodeInserted', function(e) {
        if (e.target.classList && e.target.classList.contains('item-row')) {
            const input = e.target.querySelector('.product-search');
            if (input) {
                setupProductAutocomplete(input);
            }
        }
    });
});

// Update row total based on price and quantity
function updateRowTotal(row) {
    const price = parseFloat(row.querySelector('input[name="price[]"]').value) || 0;
    const quantity = parseFloat(row.querySelector('input[name="quantity[]"]').value) || 0;
    const total = price * quantity;
    row.querySelector('.item-total').textContent = 'Rs.' + total.toFixed(2);
    
    updateInvoiceTotal();
}