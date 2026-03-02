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
    <style>
        <?php if ($user['role'] === 'cashier'): ?>
        /* CASHIER-ONLY LAYOUT */
        .main-wrapper {
            display: flex;
            height: 100vh;
            flex-direction: column;
            background: #E8E4C9;
        }

        .sidebar {
            display: none !important;
        }

        .main-content {
            width: 100%;
            margin-left: 0;
            display: flex;
            flex-direction: column;
            height: 100vh;
        }

        .header {
            padding: 30px 40px;
            background: linear-gradient(135deg, #FFFFFF 0%, #F8F8F8 100%);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12), 0 4px 12px rgba(0, 0, 0, 0.08);
            flex-shrink: 0;
            margin: 20px 20px 20px 20px;
            border-radius: 12px;
            position: relative;
        }

        .header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #d32f2f;
            margin: 0 0 12px 0;
        }

        .header-actions {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 20px;
            margin-top: 12px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #d32f2f 0%, #c62828 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
        }

        .user-details h4 {
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin: 0;
        }

        .user-details p {
            font-size: 12px;
            color: #999;
            margin: 0;
        }

        .pos-grid {
            display: flex;
            flex: 1;
            gap: 20px;
            overflow: visible;
            padding: 20px;
        }

        .pos-products {
            flex: 1;
            width: 70%;
            display: flex;
            flex-direction: column;
            border-right: none;
            background: #FFFFFF;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 14px 40px rgba(0, 0, 0, 0.08), 0 6px 16px rgba(0, 0, 0, 0.05);
            position: relative;
            transition: all 0.3s ease;
        }

        .pos-products:hover {
            box-shadow: 0 20px 48px rgba(211, 47, 47, 0.1), 0 10px 24px rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
        }

        .pos-cart {
            width: 30%;
            display: flex;
            flex-direction: column;
            background: linear-gradient(135deg, #FFFFFF 0%, #FAFAFA 100%);
            box-shadow: 0 14px 40px rgba(0, 0, 0, 0.08), 0 6px 16px rgba(0, 0, 0, 0.05);
            border-radius: 12px;
            position: relative;
            transition: all 0.3s ease;
            overflow-y: auto;
        }

        .pos-cart:hover {
            box-shadow: 0 20px 48px rgba(211, 47, 47, 0.12), 0 10px 24px rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
        }

        .pos-products > div:first-child {
            padding: 24px;
            border-bottom: 2px solid #d32f2f;
            background: linear-gradient(135deg, #FFFFFF 0%, #F5F5F5 100%);
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06);
        }

        .product-grid {
            flex: 1;
            overflow-y: auto;
            padding: 10px 0;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 20px;
        }

        .product-card {
            background: linear-gradient(135deg, #FFFFFF 0%, #F8F8F8 100%);
            border: 1px solid #e8e8e8;
            border-radius: 12px;
            padding: 18px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06);
            position: relative;
            overflow: hidden;
        }

        .product-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #d32f2f 0%, #ff6b6b 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .product-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.12), 0 8px 20px rgba(0, 0, 0, 0.08);
            border-color: #d32f2f;
            background: linear-gradient(135deg, #FFFFFF 0%, #FFF5F5 100%);
        }

        .product-card h4 {
            font-size: 13px;
            font-weight: 600;
            color: #333;
            margin: 0 0 8px 0;
            word-break: break-word;
        }

        .product-card .price {
            font-size: 14px;
            font-weight: 700;
            color: #d32f2f;
            margin: 6px 0;
        }

        .product-card .stock {
            font-size: 11px;
            color: #999;
        }

        .pos-cart h3 {
            padding: 28px 28px 0 28px;
            font-size: 16px;
            font-weight: 800;
            color: #d32f2f;
            margin-bottom: 16px;
            border-bottom: 3px solid #d32f2f;
            padding-bottom: 16px;
            letter-spacing: 0.3px;
            text-transform: uppercase;
            text-align: center; /* center heading */
        }

        .cart-items {
            flex: 1;
            overflow-y: auto;
            margin-bottom: 24px;
            border-top: none;
            padding: 20px 28px;
        }

        .cart-item {
            background: linear-gradient(135deg, #F8F8F8 0%, #FFFFFF 100%);
            border: 1px solid #e8e8e8;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .cart-item:hover {
            box-shadow: 0 6px 16px rgba(211, 47, 47, 0.1);
            transform: translateX(4px);
        }

        .cart-item-info {
            flex: 1;
        }

        .cart-item-name {
            font-weight: 600;
            font-size: 13px;
            color: #333;
            margin-bottom: 4px;
        }

        .cart-item-price {
            font-size: 12px;
            color: #d32f2f;
        }

        .cart-item-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .cart-item-qty {
            width: 30px;
            padding: 4px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            text-align: center;
            font-size: 12px;
        }

        .cart-total {
            background: linear-gradient(135deg, #FFFFFF 0%, #FFF9F9 100%);
            padding: 24px;
            border-radius: 12px;
            border-bottom: 2px solid #d32f2f;
            margin: 0 28px 20px 28px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08), 0 4px 10px rgba(0, 0, 0, 0.05);
            position: relative;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .total-row:last-child {
            font-weight: 700;
            font-size: 16px;
            color: #d32f2f;
            border-top: 1px solid #ddd;
            padding-top: 10px;
            margin-bottom: 0;
        }

        .cart-actions {
            display: flex;
            gap: 12px;
            padding: 0 28px 28px 28px;
        }

        .cart-actions button {
            flex: 1;
            padding: 14px;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 13px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .cart-actions .btn-clear {
            background: linear-gradient(135deg, #E8E8E8 0%, #E0E0E0 100%);
            color: #444;
            border: 1px solid #d0d0d0;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
        }

        .cart-actions .btn-clear:hover {
            background: linear-gradient(135deg, #D8D8D8 0%, #D0D0D0 100%);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }

        .cart-actions .btn-checkout {
            background: linear-gradient(135deg, #d32f2f 0%, #c62828 100%);
            color: white;
            box-shadow: 0 6px 20px rgba(211, 47, 47, 0.25);
            position: relative;
            overflow: hidden;
        }

        .cart-actions .btn-checkout::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.2);
            transition: left 0.3s ease;
        }

        .cart-actions .btn-checkout:hover {
            box-shadow: 0 10px 28px rgba(211, 47, 47, 0.35);
            transform: translateY(-3px);
        }

        .cart-actions .btn-checkout:hover::before {
            left: 100%;
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            font-size: 13px;
            font-family: inherit;
            margin-bottom: 10px;
        }

        .form-control:focus {
            outline: none;
            border-color: #d32f2f;
            box-shadow: 0 0 0 3px rgba(211, 47, 47, 0.1);
        }

        .btn-logout {
            background: #DC3545;
            color: #FFFFFF;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn-logout:hover {
            background: #C82333;
            color: #FFFFFF;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(220, 53, 69, 0.3);
        }

        /* additional standout box styles */
        .search-filter-container {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .header-actions .user-info {
            background: #fff;
            padding: 8px 12px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        /* SCROLLBAR STYLING */
        .cart-items::-webkit-scrollbar,
        .product-grid::-webkit-scrollbar {
            width: 6px;
        }

        .cart-items::-webkit-scrollbar-track,
        .product-grid::-webkit-scrollbar-track {
            background: #f0f0f0;
        }

        .cart-items::-webkit-scrollbar-thumb,
        .product-grid::-webkit-scrollbar-thumb {
            background: #ddd;
            border-radius: 3px;
        }

        .cart-items::-webkit-scrollbar-thumb:hover,
        .product-grid::-webkit-scrollbar-thumb:hover {
            background: #ccc;
        }

        @media (max-width: 1024px) {
            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
                gap: 12px;
                padding: 16px;
            }
        }

        @media (max-width: 768px) {
            .pos-grid {
                flex-direction: column;
            }

            .pos-products {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid #e0e0e0;
            }

            .pos-cart {
                width: 100%;
                min-height: 300px;
            }

            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
                gap: 10px;
                padding: 12px;
            }
        }
        <?php endif; ?>
    </style>
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
                <div class="search-filter-container" style="margin-bottom: 16px;">
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
