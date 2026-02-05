<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

requireLogin();
$user = getCurrentUser();

// Get cart from sessionStorage (passed via POST)
$cart = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cart = json_decode($_POST['cart'] ?? '[]', true);
} else {
    // For direct access, try to get from session storage via JavaScript
    $cart = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - POPRIE</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="main-wrapper">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="header">
                <h1>Checkout</h1>
                <div class="header-actions">
                    <div class="user-info">
                        <div class="user-avatar"><?php echo strtoupper(substr($user['full_name'], 0, 1)); ?></div>
                        <div class="user-details">
                            <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
                            <p><?php echo ucfirst($user['role']); ?></p>
                        </div>
                    </div>
                    <a href="pos.php" class="btn btn-secondary btn-sm">Back to POS</a>
                </div>
            </div>
            
            <div class="checkout-grid">
                <div class="checkout-items">
                    <div class="card">
                        <div class="card-header">
                            <h3>Order Summary</h3>
                        </div>
                        <div class="card-body">
                            <div id="cartItems">
                                <!-- Cart items will be populated by JavaScript -->
                            </div>
                            
                            <div class="cart-total">
                                <div class="total-row">
                                    <span>Subtotal:</span>
                                    <strong id="subtotal">₱0.00</strong>
                                </div>
                                <div class="total-row">
                                    <span>Tax (12%):</span>
                                    <strong id="tax">₱0.00</strong>
                                </div>
                                <div class="total-row">
                                    <span>Discount:</span>
                                    <strong id="discount">₱0.00</strong>
                                </div>
                                <div class="total-row grand-total">
                                    <span>Total:</span>
                                    <strong id="grandTotal">₱0.00</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="checkout-form">
                    <div class="card">
                        <div class="card-header">
                            <h3>Payment Details</h3>
                        </div>
                        <div class="card-body">
                            <form id="checkoutForm">
                                <div class="form-group">
                                    <label>Customer Name (Optional)</label>
                                    <input type="text" class="form-control" id="customerName" placeholder="Enter customer name">
                                </div>
                                
                                <div class="form-group">
                                    <label>Payment Method</label>
                                    <select class="form-control" id="paymentMethod" required>
                                        <option value="cash">Cash</option>
                                        <option value="card">Card</option>
                                        <option value="online">Online Payment</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>Discount Amount</label>
                                    <input type="number" class="form-control" id="discountAmount" value="0" min="0" step="0.01" placeholder="0.00">
                                </div>
                                
                                <div class="form-group">
                                    <label>Amount Paid</label>
                                    <input type="number" class="form-control" id="amountPaid" required min="0" step="0.01" placeholder="0.00">
                                </div>
                                
                                <div class="form-group">
                                    <label>Change</label>
                                    <input type="text" class="form-control" id="changeAmount" readonly placeholder="0.00">
                                </div>
                                
                                <div style="display: flex; gap: 8px; margin-top: 16px;">
                                    <button type="button" class="btn btn-secondary" onclick="history.back()" style="flex: 1;">Cancel</button>
                                    <button type="submit" class="btn btn-success" style="flex: 2;">Complete Sale</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Cart data from sessionStorage
        let cart = [];
        
        // Load cart from sessionStorage
        function loadCart() {
            const savedCart = sessionStorage.getItem('posCart');
            if (savedCart) {
                cart = JSON.parse(savedCart);
                displayCart();
                updateTotals();
            } else {
                // Redirect to POS if no cart
                window.location.href = 'pos.php';
            }
        }
        
        // Display cart items
        function displayCart() {
            const cartItemsDiv = document.getElementById('cartItems');
            
            if (cart.length === 0) {
                cartItemsDiv.innerHTML = '<p style="text-align: center; color: var(--text-secondary); padding: 20px;">Cart is empty</p>';
            } else {
                cartItemsDiv.innerHTML = cart.map(item => `
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
        
        // Update totals
        function updateTotals() {
            const subtotal = cart.reduce((sum, item) => sum + item.subtotal, 0);
            const tax = subtotal * 0.12;
            const discount = parseFloat(document.getElementById('discountAmount').value || 0);
            const total = subtotal + tax - discount;
            
            document.getElementById('subtotal').textContent = '₱' + subtotal.toFixed(2);
            document.getElementById('tax').textContent = '₱' + tax.toFixed(2);
            document.getElementById('discount').textContent = '₱' + discount.toFixed(2);
            document.getElementById('grandTotal').textContent = '₱' + total.toFixed(2);
            
            // Update change calculation
            calculateChange();
        }
        
        // Calculate change
        function calculateChange() {
            const subtotal = cart.reduce((sum, item) => sum + item.subtotal, 0);
            const tax = subtotal * 0.12;
            const discount = parseFloat(document.getElementById('discountAmount').value || 0);
            const total = subtotal + tax - discount;
            const paid = parseFloat(document.getElementById('amountPaid').value || 0);
            const change = paid - total;
            
            document.getElementById('changeAmount').value = change >= 0 ? '₱' + change.toFixed(2) : '₱0.00';
        }
        
        // Handle checkout form submission
        document.getElementById('checkoutForm').addEventListener('submit', async function(e) {
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
                    
                    // Clear cart from sessionStorage
                    sessionStorage.removeItem('posCart');
                    
                    // Redirect to POS or receipt
                    if (confirm('Would you like to view the receipt?')) {
                        window.open('receipt.php?invoice=' + result.invoice, '_blank');
                    }
                    window.location.href = 'pos.php';
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                alert('Error processing sale: ' + error.message);
            }
        });
        
        // Event listeners
        document.getElementById('discountAmount').addEventListener('input', updateTotals);
        document.getElementById('amountPaid').addEventListener('input', calculateChange);
        
        // Load cart when page loads
        document.addEventListener('DOMContentLoaded', loadCart);
    </script>
</body>
</html>
