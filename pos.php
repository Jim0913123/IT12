<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

requireLogin();
$user = getCurrentUser();

// Get all active products with categories
$products = $conn->query("
    SELECT p.*, c.category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.category_id 
    WHERE p.status = 'active' 
    ORDER BY p.product_name ASC
");

// Get categories for filter
$categories = $conn->query("SELECT * FROM categories ORDER BY category_name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Point of Sale - POS & Inventory System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="main-wrapper">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="header">
            <h1>Point of Sale</h1>
            <div class="header-actions">
                <div class="user-info">
                    <div class="user-avatar"><?php echo strtoupper(substr($user['full_name'], 0, 1)); ?></div>
                    <div class="user-details">
                        <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
                        <p><?php echo ucfirst($user['role']); ?></p>
                    </div>
                </div>
                <a href="logout.php" class="btn btn-logout btn-sm">Logout</a>
            </div>
        </div>
        
        <div class="pos-grid">
            <!-- PRODUCTS -->
            <div class="pos-products">
                <div style="margin-bottom: 16px;">
                    <input type="text" id="searchProduct" class="form-control" placeholder="Search products..." style="margin-bottom: 12px;">
                    
                    <select id="filterCategory" class="form-control">
                        <option value="">All Categories</option>
                        <?php 
                        $categories->data_seek(0);
                        while ($cat = $categories->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $cat['category_id']; ?>">
                                <?php echo htmlspecialchars($cat['category_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="product-grid">
                    <?php while ($product = $products->fetch_assoc()): ?>
                        <div class="product-card"
                             data-id="<?php echo $product['product_id']; ?>"
                             data-code="<?php echo htmlspecialchars($product['product_code']); ?>"
                             data-name="<?php echo htmlspecialchars($product['product_name']); ?>"
                             data-price="<?php echo $product['selling_price']; ?>"
                             data-stock="<?php echo $product['stock_quantity']; ?>"
                             data-category="<?php echo $product['category_id']; ?>"
                             onclick="addToCart(this)">
                             
                            <h4><?php echo htmlspecialchars($product['product_name']); ?></h4>
                            <div class="price">₱<?php echo number_format($product['selling_price'], 2); ?></div>
                            <div class="stock">Stock: <?php echo $product['stock_quantity']; ?></div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
            
            <!-- CART -->
            <div class="pos-cart">
                <h3 style="margin-bottom: 16px;">Shopping Cart</h3>
                
                <div class="cart-items" id="cartItems">
                    <p style="text-align: center; padding: 40px 0;">Cart is empty</p>
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
                
                <div style="margin-top: 16px; display: flex; gap: 8px;">
                    <button class="btn btn-danger" onclick="clearCart()" style="flex:1;">Clear</button>
                    <button class="btn btn-success" onclick="openCheckout()" style="flex:2;">Complete Sale</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SINGLE CLEAN CHECKOUT MODAL -->
<div id="checkoutModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Complete Sale</h2>
            <button class="modal-close" onclick="closeCheckout()">&times;</button>
        </div>
        
        <div class="modal-body">
            <div class="checkout-modal-grid">
                
                <!-- ORDER SUMMARY -->
                <div class="order-summary">
                    <h3>Order Summary</h3>
                    <div id="modalCartItems"></div>
                    
                    <div class="modal-cart-total">
                        <div class="total-row">
                            <span>Subtotal:</span>
                            <strong id="modalSubtotal">₱0.00</strong>
                        </div>
                        <div class="total-row">
                            <span>Tax (12%):</span>
                            <strong id="modalTax">₱0.00</strong>
                        </div>
                        <div class="total-row">
                            <span>Discount:</span>
                            <strong id="modalDiscount">₱0.00</strong>
                        </div>
                        <div class="total-row grand-total">
                            <span>Total:</span>
                            <strong id="modalGrandTotal">₱0.00</strong>
                        </div>
                    </div>
                </div>

                <!-- PAYMENT FORM -->
                <div class="payment-details">
                    <h3>Payment Details</h3>
                    
                    <form id="checkoutForm">
                        <div class="form-group">
                            <label>Customer Name (Optional)</label>
                            <input type="text" class="form-control" id="customerName">
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
                            <input type="number" class="form-control" id="discountAmount" value="0" min="0" step="0.01">
                        </div>

                        <div class="form-group">
                            <label>Amount Paid</label>
                            <input type="number" class="form-control" id="amountPaid" required min="0" step="0.01">
                        </div>

                        <div class="form-group">
                            <label>Change</label>
                            <input type="text" class="form-control" id="changeAmount" readonly>
                        </div>

                        <div style="display:flex; gap:8px; margin-top:16px;">
                            <button type="button" class="btn btn-secondary" onclick="closeCheckout()" style="flex:1;">Cancel</button>
                            <button type="submit" class="btn btn-success" style="flex:2;">Complete Sale</button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="js/pos.js"></script>
</body>
</html>
