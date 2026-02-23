// Cart data
let cart = [];

// Add product to cart
function addToCart(element) {
    console.log('Adding product:', element.dataset); // Debug log
    
    const productId = element.dataset.id;
    const productCode = element.dataset.code;
    const productName = element.dataset.name;
    const productPrice = parseFloat(element.dataset.price);
    const productStock = parseInt(element.dataset.stock);
    
    console.log('Product details:', { productId, productCode, productName, productPrice, productStock }); // Debug log
    
    if (productStock <= 0) {
        alert('Product is out of stock!');
        return;
    }
    
    // Check if product already in cart
    const existingItem = cart.find(item => item.id === productId);
    
    if (existingItem) {
        console.log('Product already in cart, updating quantity'); // Debug log
        if (existingItem.quantity < productStock) {
            existingItem.quantity++;
            existingItem.subtotal = existingItem.quantity * existingItem.price;
            console.log('Updated existing item:', existingItem); // Debug log
        } else {
            alert('Cannot add more. Insufficient stock!');
            return;
        }
    } else {
        console.log('Adding new product to cart'); // Debug log
        const newItem = {
            id: productId,
            code: productCode,
            name: productName,
            price: productPrice,
            quantity: 1,
            stock: productStock,
            subtotal: productPrice
        };
        console.log('New item created:', newItem); // Debug log
        cart.push(newItem);
    }
    
    console.log('Cart after adding:', cart); // Debug log
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
                    <h4>${item.name}${item.cupSize && item.cupSize !== 'none' ? ` (${item.cupSize})` : ''}</h4>
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
    console.log('=== UPDATE TOTALS CALLED ==='); // Debug log
    console.log('Current cart:', cart); // Debug log
    console.log('Cart length:', cart.length); // Debug log
    
    if (!cart || cart.length === 0) {
        console.log('Cart is empty, setting totals to 0'); // Debug log
        document.getElementById('subtotal').textContent = '₱0.00';
        document.getElementById('tax').textContent = '₱0.00';
        document.getElementById('discount').textContent = '₱0.00';
        document.getElementById('grandTotal').textContent = '₱0.00';
        return;
    }
    
    let subtotal = 0;
    for (let i = 0; i < cart.length; i++) {
        console.log(`Item ${i}:`, cart[i]); // Debug log
        console.log(`Item ${i} subtotal:`, cart[i].subtotal); // Debug log
        subtotal += parseFloat(cart[i].subtotal) || 0;
    }
    
    console.log('Calculated subtotal:', subtotal); // Debug log
    
    const tax = subtotal * 0.12; // 12% tax
    const total = subtotal + tax;
    
    console.log('Final totals:', { subtotal, tax, total }); // Debug log
    
    document.getElementById('subtotal').textContent = '₱' + subtotal.toFixed(2);
    document.getElementById('tax').textContent = '₱' + tax.toFixed(2);
    document.getElementById('discount').textContent = '₱0.00';
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
    console.log('Cart items for modal total calculation:', cart); // Debug log
    const subtotal = cart.reduce((sum, item) => sum + item.subtotal, 0);
    const tax = subtotal * 0.12;
    const discount = parseFloat(document.getElementById('discountAmount').value || 0);
    const total = subtotal + tax - discount;
    
    console.log('Modal totals calculation:', { subtotal, tax, discount, total }); // Debug log
    
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
document.getElementById('checkoutForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    console.log('Checkout form submitted'); // Debug log
    
    const subtotal = cart.reduce((sum, item) => sum + item.subtotal, 0);
    const tax = subtotal * 0.12;
    const discount = parseFloat(document.getElementById('discountAmount').value || 0);
    const total = subtotal + tax - discount;
    const paid = parseFloat(document.getElementById('amountPaid').value);
    
    console.log('Calculations:', { subtotal, tax, discount, total, paid }); // Debug log
    
    if (paid < total) {
        alert('Amount paid is less than total!');
        return;
    }
    
    // Create hidden form for direct submission
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'process-sale.php';
    
    // Add all data as hidden fields
    const fields = {
        customer_name: document.getElementById('customerName').value,
        customer_phone: '',
        payment_method: document.getElementById('paymentMethod').value,
        subtotal: subtotal,
        tax: tax,
        discount: discount,
        total: total,
        paid: paid,
        change: paid - total,
        cart_items: JSON.stringify(cart)
    };
    
    for (const [key, value] of Object.entries(fields)) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = value;
        form.appendChild(input);
    }
    
    // Submit form
    document.body.appendChild(form);
    form.submit();
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
