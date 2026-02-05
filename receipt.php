<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

requireLogin();

$invoice_number = $_GET['invoice'] ?? '';

if (empty($invoice_number)) {
    die('Invalid invoice number');
}

// Get sale details
$sale_result = $conn->query("
    SELECT s.*, u.full_name 
    FROM sales s 
    LEFT JOIN users u ON s.user_id = u.user_id 
    WHERE s.invoice_number = '$invoice_number'
");

if ($sale_result->num_rows === 0) {
    die('Invoice not found');
}

$sale = $sale_result->fetch_assoc();

// Get sale items
$items = $conn->query("
    SELECT * FROM sale_items WHERE sale_id = {$sale['sale_id']}
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - <?php echo $invoice_number; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background: #f5f5f5;
            padding: 20px;
        }
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .receipt-header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .receipt-header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        .receipt-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        .info-block h4 {
            font-size: 12px;
            text-transform: uppercase;
            color: #666;
            margin-bottom: 8px;
        }
        .info-block p {
            margin: 4px 0;
        }
        .items-table {
            width: 100%;
            margin-bottom: 30px;
        }
        .items-table th {
            background: #f5f5f5;
            padding: 12px;
            text-align: left;
            font-size: 12px;
            text-transform: uppercase;
        }
        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        .totals {
            margin-left: auto;
            width: 300px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
        }
        .total-row.grand {
            border-top: 2px solid #333;
            margin-top: 12px;
            padding-top: 12px;
            font-size: 20px;
            font-weight: bold;
        }
        .receipt-footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #333;
        }
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .receipt-container {
                box-shadow: none;
                padding: 20px;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="receipt-header">
            <h1>🛒 POS & Inventory System</h1>
            <p>Sales Receipt</p>
        </div>
        
        <div class="receipt-info">
            <div class="info-block">
                <h4>Invoice Information</h4>
                <p><strong>Invoice #:</strong> <?php echo $invoice_number; ?></p>
                <p><strong>Date:</strong> <?php echo date('F d, Y h:i A', strtotime($sale['sale_date'])); ?></p>
                <p><strong>Payment Method:</strong> <?php echo ucfirst($sale['payment_method']); ?></p>
            </div>
            
            <div class="info-block">
                <h4>Customer Information</h4>
                <p><strong>Name:</strong> <?php echo $sale['customer_name'] ?: 'Walk-in Customer'; ?></p>
                <?php if ($sale['customer_phone']): ?>
                    <p><strong>Phone:</strong> <?php echo $sale['customer_phone']; ?></p>
                <?php endif; ?>
                <p><strong>Served by:</strong> <?php echo $sale['full_name']; ?></p>
            </div>
        </div>
        
        <table class="items-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($item = $items->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td>₱<?php echo number_format($item['unit_price'], 2); ?></td>
                        <td>₱<?php echo number_format($item['subtotal'], 2); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <div class="totals">
            <div class="total-row">
                <span>Subtotal:</span>
                <strong>₱<?php echo number_format($sale['subtotal'], 2); ?></strong>
            </div>
            <div class="total-row">
                <span>Tax (12%):</span>
                <strong>₱<?php echo number_format($sale['tax'], 2); ?></strong>
            </div>
            <?php if ($sale['discount'] > 0): ?>
                <div class="total-row">
                    <span>Discount:</span>
                    <strong>-₱<?php echo number_format($sale['discount'], 2); ?></strong>
                </div>
            <?php endif; ?>
            <div class="total-row grand">
                <span>Grand Total:</span>
                <strong>₱<?php echo number_format($sale['total_amount'], 2); ?></strong>
            </div>
            <div class="total-row">
                <span>Amount Paid:</span>
                <strong>₱<?php echo number_format($sale['amount_paid'], 2); ?></strong>
            </div>
            <div class="total-row">
                <span>Change:</span>
                <strong>₱<?php echo number_format($sale['change_amount'], 2); ?></strong>
            </div>
        </div>
        
        <div class="receipt-footer">
            <p><strong>Thank you for your purchase!</strong></p>
            <p style="font-size: 12px; color: #666; margin-top: 8px;">
                This is a computer-generated receipt and is valid without signature
            </p>
        </div>
        
        <div class="no-print" style="text-align: center; margin-top: 30px;">
            <button class="btn btn-primary" onclick="window.print()">Print Receipt</button>
            <button class="btn btn-secondary" onclick="window.close()">Close</button>
        </div>
    </div>
</body>
</html>
