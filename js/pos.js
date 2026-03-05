// Cart data
let cart = [];
let selectedCupSize = {};

// Select cup size for drink
function selectCupSize(button, event) {
    event.stopPropagation();
    
    const productCard = button.closest('.product-card');
    const productId = productCard.dataset.id;
    const cupSize = button.dataset.cupSize;
    
    // Clear previous selection for this product
    const cupButtons = productCard.querySelectorAll('.cup-btn');
    cupButtons.forEach(btn => btn.classList.remove('selected'));
    
    // Select current button
    button.classList.add('selected');
    
    // Store selected cup size
    selectedCupSize[productId] = cupSize;
    
    console.log('Selected cup size:', productId, cupSize);
    
    // Automatically add to cart after cup size selection
    setTimeout(() => {
        addToCart(productCard);
    }, 100);
}

// Handle product card click
function handleProductClick(element, event) {
    console.log('Product clicked:', element.dataset);
    const isDrink = element.dataset.isDrink === 'true';
    console.log('Is drink:', isDrink);
    
    if (isDrink) {
        // For all drinks (including Hot Coffee), require cup size selection
        const productId = element.dataset.id;
        console.log('Product ID:', productId);
        console.log('Selected cup sizes:', selectedCupSize);
        
        if (!selectedCupSize[productId]) {
            alert('Please select a cup size (12oz or 16oz) for this drink!');
            return;
        }
        // If cup size is selected, add to cart
        addToCart(element);
    } else {
        // For non-drinks, add directly to cart
        addToCart(element);
    }
}

// Add product to cart
function addToCart(element) {
    console.log('addToCart called'); // Test if function is being called
    
    const productId = element.dataset.id;
    const productCode = element.dataset.code;
    const productName = element.dataset.name;
    const productPrice = parseFloat(element.dataset.price);
    const productStock = parseInt(element.dataset.stock);
    const categoryId = element.dataset.category;
    
    console.log('Product details:', { productId, productCode, productName, productPrice, productStock, categoryId }); // Debug log
    
    // Check if product is a drink based on category
    const isDrink = ['Coffee Drinks', 'Hot Coffee', 'Iced Coffee', 'Matcha', 'Other Beverages'].includes(categoryId);
    
    if (isDrink && !selectedCupSize[productId]) {
        alert('Please select a cup size for this drink');
        return;
    }
    
    // Create unique key for cart items (product + cup size)
    const cupSize = isDrink ? selectedCupSize[productId] : 'none';
    const cartKey = isDrink ? `${productId}_${cupSize}` : productId;
    
    // Check if product already in cart
    const existingItem = cart.find(item => item.cartKey === cartKey);
    
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
            cartKey: cartKey,
            id: productId,
            code: productCode,
            name: productName,
            price: productPrice,
            quantity: 1,
            stock: productStock,
            subtotal: productPrice,
            cupSize: cupSize,
            isDrink: isDrink
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
            <div class="cart-item" data-sale-item-id="${item.id || 'temp-' + index}">
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
                </div>
            </div>
        `).join('');
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
    const adminPassword = document.getElementById('adminPassword').value;
    const reason = document.getElementById('voidReason').value.trim();
    
    if (!reason) {
        alert('Please enter a reason for voiding the sale');
        return;
    }
    
    // Simple admin password check (use 'admin' as default)
    if (adminPassword !== 'admin') {
        alert('Invalid admin password');
        return;
    }
    
    // Clear cart locally without affecting inventory
    const submitBtn = this.querySelector('button[type="submit"]');
    const orig = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Processing...';
    
    try {
        // Mark all items as voided locally (for display purposes)
        cart.forEach(item => {
            item.voided = true;
            item.voidReason = reason;
        });
        
        // Clear cart after a short delay to show voided state
        setTimeout(() => {
            cart = [];
            updateCart();
            closeSaleVoidModal();
            alert('Sale voided successfully - inventory not affected');
        }, 1000);
        
    } catch(err) {
        console.error('Void error:', err);
        alert('Error voiding sale: ' + err.message);
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = orig;
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
    
    const submitBtn = this.querySelector('button[type="submit"]');
    const origText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Processing...';
    
    try {
        // Generate invoice number locally
        const invoiceNumber = 'INV-' + Date.now();
        const customerName = document.getElementById('customerName').value;
        const paymentMethod = document.getElementById('paymentMethod').value;
        const change = paid - total;
        const saleDate = new Date().toLocaleString();
        
        // Prepare receipt data
        const receiptData = {
            invoice: invoiceNumber,
            date: saleDate,
            customer: customerName || 'Walk-in Customer',
            paymentMethod: paymentMethod,
            items: activeItems,
            subtotal: subtotal,
            tax: tax,
            discount: discount,
            total: total,
            paid: paid,
            change: change
        };
        
        // Store receipt data in localStorage for the receipt page
        localStorage.setItem('currentReceipt', JSON.stringify(receiptData));
        
        // Clear cart
        cart = [];
        updateCart();
        closeCheckout();
        
        // Open receipt directly without alert
        const receiptWindow = window.open('receipt-local.php', '_blank', 'width=800,height=600');
        
        if (!receiptWindow) {
            // If popup blocked, show inline receipt
            showInlineReceipt(receiptData);
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
