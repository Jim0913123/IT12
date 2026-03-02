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
        cartItemsDiv.innerHTML = cart.map((item, index) => {
            const isVoided = item.voided || false;
            return `
                <div class="cart-item ${isVoided ? 'voided-item' : ''}">
                    <div class="cart-item-info">
                        <div class="cart-item-name">
                            ${item.name}
                            ${isVoided ? '<span class="voided-badge">VOIDED</span>' : ''}
                        </div>
                        <div class="cart-item-price">₱${item.price.toFixed(2)}</div>
                    </div>
                    <div class="cart-item-actions">
                        <div class="qty-wrapper">
                            <button class="quantity-btn" onclick="decreaseQuantity(${index})" ${isVoided ? 'disabled' : ''}>-</button>
                            <span style="font-weight:600; min-width: 30px; text-align:center;">${item.quantity}</span>
                            <button class="quantity-btn" onclick="increaseQuantity(${index})" ${isVoided ? 'disabled' : ''}>+</button>
                        </div>
                        <strong style="min-width: 70px; text-align: right;">₱${item.subtotal.toFixed(2)}</strong>
                
                    </div>
                </div>
            `;
        }).join('');
    }
    
    updateTotals();
}

// Update quantity
function updateQuantity(index, newQuantity) {
    const qty = parseInt(newQuantity);
    
    if (isNaN(qty) || qty < 1) {
        alert('Quantity must be at least 1');
        updateCart();
        return;
    }
    
    if (qty > cart[index].stock) {
        alert('Cannot add more. Insufficient stock!');
        updateCart();
        return;
    }
    
    cart[index].quantity = qty;
    cart[index].subtotal = cart[index].quantity * cart[index].price;
    updateCart();
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

// SALE VOID MODAL HELPERS
function openSaleVoidModal() {
    document.getElementById('saleAdminPassword').value = '';
    document.getElementById('saleVoidReason').value = '';
    document.getElementById('saleCharCount').textContent = '0';
    document.getElementById('saleVoidModal').classList.add('active');
    setTimeout(() => document.getElementById('saleAdminPassword').focus(), 100);
}

function closeSaleVoidModal() {
    document.getElementById('saleVoidModal').classList.remove('active');
    document.getElementById('saleVoidForm').reset();
}

// sale form char counter
document.getElementById('saleVoidReason')?.addEventListener('input', function() {
    const cnt = this.value.length;
    document.getElementById('saleCharCount').textContent = cnt;
    if (cnt > 500) {
        this.value = this.value.substring(0, 500);
        document.getElementById('saleCharCount').textContent = '500';
    }
});

// handle sale void submission
document.getElementById('saleVoidForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const adminPassword = document.getElementById('saleAdminPassword').value;
    const reason = document.getElementById('saleVoidReason').value.trim();
    if (!reason) {
        alert('Please enter a reason for voiding the sale');
        return;
    }
    const submitBtn = this.querySelector('button[type="submit"]');
    const orig = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Authorizing...';
    try {
        // send current cart along with request so server can audit what was cancelled
        const response = await fetch('api/void_item.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                sale_item_id: 0,
                admin_password: adminPassword,
                void_reason: reason,
                cart_items: cart
            })
        });
        const result = await response.json();
        submitBtn.disabled = false;
        submitBtn.textContent = orig;
        if (response.ok && result.success) {
            cart.forEach(i=>i.voided=true);
            updateCart();
            closeSaleVoidModal();
            alert('Sale cancelled and recorded (admin authorized)');
        } else if (response.status===401) {
            alert('Invalid admin password');
        } else {
            alert('Error: ' + (result.error||'Unable to void sale'));
        }
    } catch(err) {
        submitBtn.disabled = false;
        submitBtn.textContent = orig;
        alert('Error contacting server');
    }
});

// Display cart items in modal
function displayModalCart() {
    const modalCartItems = document.getElementById('modalCartItems');
    const activeItems = cart.filter(item => !item.voided);
    const voidedItems = cart.filter(item => item.voided);
    
    if (activeItems.length === 0 && voidedItems.length === 0) {
        modalCartItems.innerHTML = '<p style="text-align: center; color: var(--text-secondary); padding: 20px;">Cart is empty</p>';
    } else {
        let html = '';
        
        // Display active items
        if (activeItems.length > 0) {
            html += activeItems.map(item => `
                <div class="cart-item">
                    <div class="cart-item-info">
                        <div class="cart-item-name">${item.name}</div>
                        <div class="cart-item-price">₱${item.price.toFixed(2)}</div>
                    </div>
                    <div class="cart-item-actions">
                        <span style="min-width: 30px; text-align: center; font-weight: 600;">×${item.quantity}</span>
                        <strong style="min-width: 70px; text-align: right;">₱${item.subtotal.toFixed(2)}</strong>
                    </div>
                </div>
            `).join('');
        }
        
        // Display voided items (if any)
        if (voidedItems.length > 0) {
            html += '<div style="margin-top: 16px; padding-top: 16px; border-top: 2px solid #e8e8e8;">';
            html += '<p style="font-size: 12px; color: #999; margin: 0 0 8px 0; text-transform: uppercase; font-weight: 600;">Voided Items</p>';
            html += voidedItems.map(item => `
                <div class="cart-item voided-item">
                    <div class="cart-item-info">
                        <div class="cart-item-name">${item.name} <span class="voided-badge">VOIDED</span></div>
                        <div class="cart-item-price">₱${item.price.toFixed(2)}</div>
                    </div>
                    <div class="cart-item-actions">
                        <span style="min-width: 30px; text-align: center; font-weight: 600;">×${item.quantity}</span>
                        <strong style="min-width: 70px; text-align: right;">₱${item.subtotal.toFixed(2)}</strong>
                    </div>
                </div>
            `).join('');
            html += '</div>';
        }
        
        modalCartItems.innerHTML = html;
    }
}

// Update modal totals
function updateModalTotals() {
    const activeItems = cart.filter(item => !item.voided);
    const subtotal = activeItems.reduce((sum, item) => sum + item.subtotal, 0);
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
    const activeItems = cart.filter(item => !item.voided);
    const subtotal = activeItems.reduce((sum, item) => sum + item.subtotal, 0);
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
    
    // Filter out voided items
    const activeItems = cart.filter(item => !item.voided);
    
    if (activeItems.length === 0) {
        alert('No items to checkout. All items have been voided.');
        return;
    }
    
    const subtotal = activeItems.reduce((sum, item) => sum + item.subtotal, 0);
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
        items: activeItems
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
    const checkoutModal = document.getElementById('checkoutModal');
    const voidModal = document.getElementById('voidModal');
    const saleModal = document.getElementById('saleVoidModal');
    
    if (e.target === checkoutModal) {
        closeCheckout();
    }
    
    if (e.target === voidModal) {
        closeVoidModal();
    }
    
    if (e.target === saleModal) {
        closeSaleVoidModal();
    }
});
