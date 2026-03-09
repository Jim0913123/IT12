// Cart data
let cart = [];
let selectedCupSize = {};

// Test if JavaScript is loading
console.log('POS JavaScript loaded successfully!');
alert('JavaScript is working!');

// Select cup size for drink
function selectCupSize(button, event) {
    event.stopPropagation();
    
    const productCard = button.closest('.product-card');
    const productId = productCard.dataset.id;
    const cupId = parseInt(button.dataset.cupId);
    const cupSize = button.dataset.cupSize;
    const price = parseFloat(button.dataset.price);
    
    // Clear previous selection for this product
    const cupButtons = productCard.querySelectorAll('.cup-btn');
    cupButtons.forEach(btn => btn.classList.remove('selected'));
    
    // Select current button
    button.classList.add('selected');
    
    // Store selected cup size with all details
    selectedCupSize[productId] = {
        cupId: cupId,
        cupSize: cupSize,
        price: price
    };
    
    // Update the displayed price on the product card
    const priceDiv = productCard.querySelector('.price');
    if (priceDiv) {
        priceDiv.textContent = '₱' + price.toFixed(2);
    }
    
    // Update the data-price attribute for addToCart
    productCard.dataset.price = price;
    
    console.log('Selected cup size:', productId, selectedCupSize[productId]);
    
    // Automatically add to cart after cup size selection
    setTimeout(() => {
        addToCart(productCard);
    }, 100);
}

// Handle product card click
function handleProductClick(element, event) {
    console.log('Product clicked:', element.dataset);
    const isDrink = element.dataset.isDrink === 'true';
    const hasCupSizes = element.dataset.cupSizes && element.dataset.cupSizes !== '[]';
    console.log('Is drink:', isDrink, 'Has cup sizes:', hasCupSizes);
    
    if (isDrink && hasCupSizes) {
        // For drinks with cup sizes, require cup size selection
        const productId = element.dataset.id;
        console.log('Product ID:', productId);
        console.log('Selected cup sizes:', selectedCupSize);
        
        if (!selectedCupSize[productId]) {
            alert('Please select a cup size for this drink!');
            return;
        }
        // If cup size is selected, add to cart
        addToCart(element);
    } else {
        // For non-drinks or drinks without cup sizes, add directly to cart
        addToCart(element);
    }
}

// Add product to cart
function addToCart(element) {
    console.log('addToCart called'); // Test if function is being called
    
    const productId = element.dataset.id;
    const productCode = element.dataset.code;
    const productName = element.dataset.name;
    const productStock = parseInt(element.dataset.stock);
    const isDrink = element.dataset.isDrink === 'true';
    
    console.log('Product details:', { productId, productCode, productName, productPrice, productStock, isDrink }); // Debug log
    
    // Check if it's a drink and cup size is selected
    if (isDrink && !selectedCupSize[productId]) {
        alert('Please select a cup size (12oz or 16oz) for this drink!');
        return;
    }
    
    if (productStock <= 0) {
        alert('Product is out of stock!');
        return;
    }
    
    // Get cup size for drinks
    const cupSize = isDrink ? selectedCupSize[productId] : 'none';
    
    // Create unique key for cart items (product + cup size)
    const cartKey = isDrink ? `${productId}_${cupSize}` : productId;
    
    // Check if product already in cart
    const existingItem = cart.find(item => item.cartKey === cartKey);
    
    if (existingItem) {
        console.log('Product already in cart, updating quantity'); // Debug log
        if (existingItem.quantity < productStock || isDrink) {
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
            cartKey: cartKey,
            id: productId,
            code: productCode,
            name: productName,
            price: productPrice,
            quantity: 1,
            stock: productStock,
            subtotal: productPrice,
            cupSize: cupSize,
            cupId: cupId,
            isDrink: isDrink
        };
        console.log('New item created:', newItem); // Debug log
        cart.push(newItem);
    }
    
    console.log('Cart after adding:', cart); // Debug log
    updateCart();
}

// Update cart display with void buttons
function updateCart() {
    const cartItemsDiv = document.getElementById('cartItems');
    
    if (cart.length === 0) {
        cartItemsDiv.innerHTML = '<p style="text-align: center; color: var(--text-secondary); padding: 40px 0;">Cart is empty</p>';
    } else {
        cartItemsDiv.innerHTML = cart.map((item, index) => `
            <div class="cart-item" data-sale-item-id="${item.id || 'temp-' + index}">
                <div class="cart-item-details">
                    <h4>${item.name}</h4>
                    <p>₱${item.price.toFixed(2)} × ${item.quantity}</p>
                    ${item.voidReason ? `<p style="font-size: 11px; color: #666;">Reason: ${item.voidReason}</p>` : ''}
                </div>
                <div class="cart-item-actions">
                    <strong style="color: ${item.voided ? '#999' : 'var(--primary)'}; text-decoration: ${item.voided ? 'line-through' : 'none'};">₱${item.subtotal.toFixed(2)}</strong>
                    <div style="display: flex; gap: 5px; margin-top: 5px;">
                        ${!item.voided ? `
                        <div class="quantity-control">
                            <button class="quantity-btn" onclick="decreaseQuantity(${index})">-</button>
                            <span style="font-weight: 600; min-width: 30px; text-align: center;">${item.quantity}</span>
                            <button class="quantity-btn" onclick="increaseQuantity(${index})">+</button>
                        </div>
                        ` : '<span style="color: #999; font-size: 11px;">Voided</span>'}
                    </div>
                </div>
            </div>
        `}).join('');
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

// Update totals excluding voided items
function updateTotals() {
    console.log('=== UPDATE TOTALS CALLED ===');
    console.log('Current cart:', cart);
    
    const activeItems = cart.filter(item => !item.voided);
    console.log('Active items:', activeItems);
    
    if (activeItems.length === 0) {
        document.getElementById('subtotal').textContent = '₱0.00';
        document.getElementById('tax').textContent = '₱0.00';
        document.getElementById('discount').textContent = '₱0.00';
        document.getElementById('grandTotal').textContent = '₱0.00';
        return;
    }
    
    let subtotal = 0;
    for (let i = 0; i < activeItems.length; i++) {
        subtotal += parseFloat(activeItems[i].subtotal) || 0;
    }
    
    const tax = subtotal * 0.12;
    const total = subtotal + tax;
    
    console.log('Final totals:', { subtotal, tax, total });
    
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

// SALE VOID MODAL HELPERS
function openSaleVoidModal() {
    // Test if void modal exists
    const voidModal = document.getElementById('voidModal');
    if (!voidModal) {
        alert('Void modal not found!');
        return;
    }
    
    // Test if form exists
    const voidForm = document.getElementById('voidForm');
    if (!voidForm) {
        alert('Void form not found!');
        return;
    }
    
    document.getElementById('adminPassword').value = '';
    document.getElementById('voidReason').value = '';
    document.getElementById('charCount').textContent = '0';
    document.getElementById('voidModal').classList.add('active');
    setTimeout(() => document.getElementById('adminPassword').focus(), 100);
}

function closeSaleVoidModal() {
    document.getElementById('voidModal').classList.remove('active');
    document.getElementById('voidForm').reset();
}

// sale form char counter
document.getElementById('voidReason')?.addEventListener('input', function() {
    const cnt = this.value.length;
    document.getElementById('charCount').textContent = cnt;
    if (cnt > 500) {
        this.value = this.value.substring(0, 500);
        document.getElementById('charCount').textContent = '500';
    }
});

// handle sale void submission
document.getElementById('voidForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const reason = document.getElementById('voidReason').value.trim();
    
    if (!reason) {
        alert('Please enter a reason for voiding the sale');
        return;
    }
    const submitBtn = this.querySelector('button[type="submit"]');
    const orig = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Processing...';
    
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
        console.error('Item void error:', err);
        alert('Error voiding item: ' + err.message);
    } finally {
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
    
    console.log('Checkout form submitted');
    
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
    
    console.log('Calculations:', { subtotal, tax, discount, total, paid });
    
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
        console.error('Error processing sale:', error);
        alert('Error processing sale: ' + error.message);
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = origText;
    }
});

// Function to show receipt inline if popup blocked
function showInlineReceipt(data) {
    const receiptHTML = `
        <div id="inlineReceipt" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; overflow: auto;">
            <div style="max-width: 600px; margin: 20px auto; background: white; padding: 30px; border-radius: 8px;">
                <div style="text-align: center; margin-bottom: 20px; border-bottom: 2px dashed #ccc; padding-bottom: 20px;">
                    <h2 style="margin: 0; color: #d32f2f;">SALES RECEIPT</h2>
                    <p style="font-size: 12px; color: #666; margin: 5px 0;">POPRIE Coffee Shop</p>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <p><strong>Invoice:</strong> ${data.invoice}</p>
                    <p><strong>Date:</strong> ${data.date}</p>
                    <p><strong>Customer:</strong> ${data.customer}</p>
                    <p><strong>Payment:</strong> ${data.paymentMethod}</p>
                </div>
                
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                    <thead>
                        <tr style="background: #f5f5f5;">
                            <th style="padding: 10px; text-align: left;">Item</th>
                            <th style="padding: 10px; text-align: center;">Qty</th>
                            <th style="padding: 10px; text-align: right;">Price</th>
                            <th style="padding: 10px; text-align: right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${data.items.map(item => `
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 10px;">${item.name}</td>
                                <td style="padding: 10px; text-align: center;">${item.quantity}</td>
                                <td style="padding: 10px; text-align: right;">₱${item.price.toFixed(2)}</td>
                                <td style="padding: 10px; text-align: right;">₱${item.subtotal.toFixed(2)}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
                
                <div style="border-top: 2px solid #333; padding-top: 15px;">
                    <div style="display: flex; justify-content: space-between; margin: 5px 0;">
                        <span>Subtotal:</span>
                        <strong>₱${data.subtotal.toFixed(2)}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin: 5px 0;">
                        <span>Tax (12%):</span>
                        <strong>₱${data.tax.toFixed(2)}</strong>
                    </div>
                    ${data.discount > 0 ? `
                    <div style="display: flex; justify-content: space-between; margin: 5px 0;">
                        <span>Discount:</span>
                        <strong>-₱${data.discount.toFixed(2)}</strong>
                    </div>
                    ` : ''}
                    <div style="display: flex; justify-content: space-between; margin: 10px 0; font-size: 18px; font-weight: bold; border-top: 1px solid #ccc; padding-top: 10px;">
                        <span>Grand Total:</span>
                        <span>₱${data.total.toFixed(2)}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin: 5px 0;">
                        <span>Amount Paid:</span>
                        <strong>₱${data.paid.toFixed(2)}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin: 5px 0;">
                        <span>Change:</span>
                        <strong>₱${data.change.toFixed(2)}</strong>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 2px dashed #ccc;">
                    <p style="font-weight: bold;">Thank you for your purchase!</p>
                    <p style="font-size: 11px; color: #666;">This is a computer-generated receipt</p>
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <button onclick="printInlineReceipt()" style="background: #d32f2f; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; margin: 5px;">Print Receipt</button>
                    <button onclick="closeInlineReceipt()" style="background: #666; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; margin: 5px;">Close</button>
                </div>
            </div>
        </div>
        
        <script>
            function printInlineReceipt() {
                const content = document.getElementById('inlineReceipt').innerHTML;
                const printWindow = window.open('', '_blank');
                printWindow.document.write('<html><head><title>Receipt</title></head><body>' + content + '</body></html>');
                printWindow.document.close();
                printWindow.print();
            }
            
            function closeInlineReceipt() {
                document.getElementById('inlineReceipt').remove();
            }
        </script>
    `;
    
    document.body.insertAdjacentHTML('beforeend', receiptHTML);
}

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
    const saleModal = document.getElementById('saleVoidModal');
    
    if (e.target === checkoutModal) {
        closeCheckout();
    }
    
    if (e.target === saleModal) {
        closeSaleVoidModal();
    }
});
