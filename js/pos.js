// Cart data
let cart = [];

// Add product to cart
function addToCart(element) {
    const productId = element.dataset.id;
    const productCode = element.dataset.code;
    const productName = element.dataset.name;
    const productPrice = parseFloat(element.dataset.price);
    const productStock = parseInt(element.dataset.stock);
    
    if (productStock <= 0) {
        alert('Product is out of stock!');
        return;
    }
    
    // Check if product already in cart
    const existingItem = cart.find(item => item.id === productId);
    
    if (existingItem) {
        if (existingItem.quantity < productStock) {
            existingItem.quantity++;
            existingItem.subtotal = existingItem.quantity * existingItem.price;
        } else {
            alert('Cannot add more. Insufficient stock!');
            return;
        }
    } else {
        cart.push({
            id: productId,
            code: productCode,
            name: productName,
            price: productPrice,
            quantity: 1,
            stock: productStock,
            subtotal: productPrice
        });
    }
    
    updateCart();
}

// Update cart display
function updateCart() {
    const cartItemsDiv = document.getElementById('cartItems');
    
    if (cart.length === 0) {
        cartItemsDiv.innerHTML = '<p style="text-align: center; color: var(--text-secondary); padding: 40px 0;">Cart is empty</p>';
    } else {
        cartItemsDiv.innerHTML = cart.map((item, index) => `
            <div class="cart-item">
                <div class="cart-item-details">
                    <h4>${item.name}</h4>
                    <p>₱${item.price.toFixed(2)} × ${item.quantity}</p>
                </div>
                <div class="cart-item-actions">
                    <strong style="color: var(--primary);">₱${item.subtotal.toFixed(2)}</strong>
                    <div class="quantity-control">
                        <button class="quantity-btn" onclick="decreaseQuantity(${index})">-</button>
                        <span style="font-weight: 600; min-width: 30px; text-align: center;">${item.quantity}</span>
                        <button class="quantity-btn" onclick="increaseQuantity(${index})">+</button>
                    </div>
                    <button class="btn btn-danger btn-sm" onclick="removeFromCart(${index})" style="padding: 4px 8px;">Remove</button>
                </div>
            </div>
        `).join('');
    }
    
    updateTotals();
}

// Increase quantity
function increaseQuantity(index) {
    if (cart[index].quantity < cart[index].stock) {
        cart[index].quantity++;
        cart[index].subtotal = cart[index].quantity * cart[index].price;
        updateCart();
    } else {
        alert('Cannot add more. Insufficient stock!');
    }
}

// Decrease quantity
function decreaseQuantity(index) {
    if (cart[index].quantity > 1) {
        cart[index].quantity--;
        cart[index].subtotal = cart[index].quantity * cart[index].price;
        updateCart();
    }
}

// Remove from cart
function removeFromCart(index) {
    cart.splice(index, 1);
    updateCart();
}

// Clear cart
function clearCart() {
    if (cart.length === 0) return;
    
    if (confirm('Are you sure you want to clear the cart?')) {
        cart = [];
        updateCart();
    }
}

// Update totals
function updateTotals() {
    const subtotal = cart.reduce((sum, item) => sum + item.subtotal, 0);
    const tax = subtotal * 0.12; // 12% tax
    const discount = 0; // Will be calculated on checkout page
    const total = subtotal + tax - discount;
    
    document.getElementById('subtotal').textContent = '₱' + subtotal.toFixed(2);
    document.getElementById('tax').textContent = '₱' + tax.toFixed(2);
    document.getElementById('discount').textContent = '₱' + discount.toFixed(2);
    document.getElementById('grandTotal').textContent = '₱' + total.toFixed(2);
}

// Open checkout modal
function openCheckout() {
    if (cart.length === 0) {
        alert('Cart is empty!');
        return;
    }
    
    // Display cart items in modal
    displayModalCart();
    updateModalTotals();
    
    // Reset form
    document.getElementById('customerName').value = '';
    document.getElementById('discountAmount').value = '0';
    document.getElementById('amountPaid').value = '';
    document.getElementById('changeAmount').value = '';
    
    // Show modal
    document.getElementById('checkoutModal').classList.add('active');
}

// Close checkout modal
function closeCheckout() {
    document.getElementById('checkoutModal').classList.remove('active');
}

// Display cart items in modal
function displayModalCart() {
    const modalCartItems = document.getElementById('modalCartItems');
    
    if (cart.length === 0) {
        modalCartItems.innerHTML = '<p style="text-align: center; color: var(--text-secondary); padding: 20px;">Cart is empty</p>';
    } else {
        modalCartItems.innerHTML = cart.map(item => `
            <div class="cart-item">
                <div class="cart-item-details">
                    <h4>${item.name}</h4>
                    <p>₱${item.price.toFixed(2)} × ${item.quantity}</p>
                </div>
                <div class="cart-item-actions">
                    <strong style="color: var(--primary);">₱${item.subtotal.toFixed(2)}</strong>
                </div>
            </div>
        `).join('');
    }
}

// Update modal totals
function updateModalTotals() {
    const subtotal = cart.reduce((sum, item) => sum + item.subtotal, 0);
    const tax = subtotal * 0.12;
    const discount = parseFloat(document.getElementById('discountAmount').value || 0);
    const total = subtotal + tax - discount;
    
    document.getElementById('modalSubtotal').textContent = '₱' + subtotal.toFixed(2);
    document.getElementById('modalTax').textContent = '₱' + tax.toFixed(2);
    document.getElementById('modalDiscount').textContent = '₱' + discount.toFixed(2);
    document.getElementById('modalGrandTotal').textContent = '₱' + total.toFixed(2);
}

// Calculate change in modal
function calculateModalChange() {
    const subtotal = cart.reduce((sum, item) => sum + item.subtotal, 0);
    const tax = subtotal * 0.12;
    const discount = parseFloat(document.getElementById('discountAmount').value || 0);
    const total = subtotal + tax - discount;
    const paid = parseFloat(document.getElementById('amountPaid').value || 0);
    const change = paid - total;
    
    document.getElementById('changeAmount').value = change >= 0 ? '₱' + change.toFixed(2) : '₱0.00';
}

document.getElementById('amountPaid')?.addEventListener('input', calculateModalChange);

// Update discount in modal
document.getElementById('discountAmount')?.addEventListener('input', updateModalTotals);

// Handle checkout form submission
document.getElementById('checkoutForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const subtotal = cart.reduce((sum, item) => sum + item.subtotal, 0);
    const tax = subtotal * 0.12;
    const discount = parseFloat(document.getElementById('discountAmount').value || 0);
    const total = subtotal + tax - discount;
    const paid = parseFloat(document.getElementById('amountPaid').value);
    
    if (paid < total) {
        alert('Amount paid is less than total!');
        return;
    }
    
    const saleData = {
        customer_name: document.getElementById('customerName').value,
        payment_method: document.getElementById('paymentMethod').value,
        subtotal: subtotal,
        tax: tax,
        discount: discount,
        total: total,
        paid: paid,
        change: paid - total,
        items: cart
    };
    
    try {
        const response = await fetch('api/process-sale.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(saleData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Sale completed successfully!\nInvoice: ' + result.invoice);
            cart = [];
            updateCart();
            closeCheckout();
            
            // Optionally print receipt or redirect
            if (confirm('Would you like to view the receipt?')) {
                window.open('receipt.php?invoice=' + result.invoice, '_blank');
            }
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        alert('Error processing sale: ' + error.message);
    }
});

// Search products
document.getElementById('searchProduct')?.addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const products = document.querySelectorAll('.product-card');
    
    products.forEach(product => {
        const name = product.dataset.name.toLowerCase();
        const code = product.dataset.code.toLowerCase();
        
        if (name.includes(searchTerm) || code.includes(searchTerm)) {
            product.style.display = 'block';
        } else {
            product.style.display = 'none';
        }
    });
});

// Filter by category
document.getElementById('filterCategory')?.addEventListener('change', function() {
    const categoryId = this.value;
    const products = document.querySelectorAll('.product-card');
    
    products.forEach(product => {
        if (categoryId === '' || product.dataset.category === categoryId) {
            product.style.display = 'block';
        } else {
            product.style.display = 'none';
        }
    });
});

// Close modal on outside click
window.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        closeCheckout();
    }
});
