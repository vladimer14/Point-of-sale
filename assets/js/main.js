// Point of Sale Version 1 - JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips and form enhancements
    initQuantityControls();
    initSearch();
    initCheckoutCalculations();
});

// Quantity increment/decrement for cart items
function initQuantityControls() {
    document.querySelectorAll('.qty-input').forEach(input => {
        input.addEventListener('change', function() {
            const row = this.closest('.cart-item');
            const productId = row.dataset.productId;
            const qty = parseInt(this.value) || 1;
            
            // Update via AJAX or form submit could go here
            updateCartItem(productId, qty);
        });
    });
}

function updateCartItem(productId, qty) {
    // Could send AJAX to update_cart.php
    console.log('Updating product ' + productId + ' to qty ' + qty);
}

// Product search filtering on POS page
function initSearch() {
    const searchInput = document.querySelector('.search-box input');
    if (!searchInput) return;

    searchInput.addEventListener('input', function() {
        const term = this.value.toLowerCase();
        const cards = document.querySelectorAll('.product-card');
        
        cards.forEach(card => {
            const name = card.querySelector('.product-name').textContent.toLowerCase();
            const sku = card.querySelector('.product-stock') ? card.dataset.sku || '' : '';
            
            if (name.includes(term)) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    });
}

// Checkout payment/change calculation
function initCheckoutCalculations() {
    const paymentInput = document.querySelector('input[name="payment_amount"]');
    const changeDisplay = document.querySelector('.change-display');
    const totalDisplay = document.querySelector('.cart-total-value');
    
    if (!paymentInput || !changeDisplay || !totalDisplay) return;

    const total = parseFloat(totalDisplay.textContent.replace(/[^0-9.]/g, '')) || 0;

    paymentInput.addEventListener('input', function() {
        const paid = parseFloat(this.value) || 0;
        const change = paid - total;
        changeDisplay.textContent = (change >= 0 ? '₱' : 'Short: ₱') + change.toFixed(2);
        changeDisplay.style.color = change >= 0 ? '#10b981' : '#ef4444';
    });
}

// Confirm delete
function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this item?');
}

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
}