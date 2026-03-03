<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

requireLogin();
$user = getCurrentUser();

// Handle cup inventory updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['restock_cups'])) {
        $cup_size = $_POST['cup_size'];
        $quantity = intval($_POST['quantity']);
        $notes = $_POST['notes'] ?? '';
        
        // Update cup inventory
        $stmt = $conn->prepare("UPDATE cup_inventory SET stock_quantity = stock_quantity + ?, last_updated = NOW() WHERE cup_size = ?");
        $stmt->bind_param("is", $quantity, $cup_size);
        $stmt->execute();
        
        // Record movement
        $stmt = $conn->prepare("INSERT INTO cup_movements (cup_size, movement_type, quantity, notes, user_id) VALUES (?, 'restock', ?, ?, ?)");
        $stmt->bind_param("sisi", $cup_size, $quantity, $notes, $user['user_id']);
        $stmt->execute();
        
        header('Location: cup_inventory.php?success=restocked');
        exit();
    }
    
    if (isset($_POST['adjust_cups'])) {
        $cup_size = $_POST['cup_size'];
        $quantity = intval($_POST['quantity']);
        $notes = $_POST['notes'] ?? '';
        $movement_type = $_POST['movement_type']; // 'adjustment' or 'waste'
        
        // Update cup inventory
        $stmt = $conn->prepare("UPDATE cup_inventory SET stock_quantity = stock_quantity - ?, last_updated = NOW() WHERE cup_size = ?");
        $stmt->bind_param("is", $quantity, $cup_size);
        $stmt->execute();
        
        // Record movement
        $stmt = $conn->prepare("INSERT INTO cup_movements (cup_size, movement_type, quantity, notes, user_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssisi", $cup_size, $movement_type, $quantity, $notes, $user['user_id']);
        $stmt->execute();
        
        header('Location: cup_inventory.php?success=adjusted');
        exit();
    }
}

// Get current cup inventory
$cup_inventory = $conn->query("SELECT * FROM cup_inventory ORDER BY cup_size");

// Get recent cup movements
$movements = $conn->query("
    SELECT cm.*, u.full_name 
    FROM cup_movements cm 
    LEFT JOIN users u ON cm.user_id = u.user_id 
    ORDER BY cm.movement_date DESC 
    LIMIT 20
");

// Get today's cup usage
$today_usage = $conn->query("
    SELECT cup_size, SUM(quantity) as total_used
    FROM cup_movements 
    WHERE DATE(movement_date) = CURDATE() AND movement_type = 'sale'
    GROUP BY cup_size
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cup Inventory - POS & Inventory System</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .cup-card {
            background: linear-gradient(135deg, #FFFFFF 0%, #F8F8F8 100%);
            border: 1px solid #e8e8e8;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
        }
        
        .cup-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
        }
        
        .cup-size-badge {
            display: inline-block;
            padding: 8px 16px;
            background: var(--primary);
            color: white;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 16px;
        }
        
        .stock-display {
            font-size: 48px;
            font-weight: 700;
            color: var(--primary);
            margin: 16px 0;
        }
        
        .stock-display.low-stock {
            color: #ff9800;
        }
        
        .stock-display.critical-stock {
            color: #f44336;
        }
        
        .movement-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        
        .movement-item:last-child {
            border-bottom: none;
        }
        
        .movement-type {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .movement-type.sale { background: #ffebee; color: #c62828; }
        .movement-type.restock { background: #e8f5e8; color: #2e7d32; }
        .movement-type.adjustment { background: #fff3e0; color: #f57c00; }
        .movement-type.waste { background: #fce4ec; color: #c2185b; }
        
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="header">
                <div class="header-left">
                    <!-- Hamburger Menu Button -->
                    <button class="hamburger-menu" onclick="toggleSidebar()">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                    <h1>🥤 Cup Inventory</h1>
                </div>
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
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success" style="margin-bottom: 24px; background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7;">
                    Cup inventory <?php echo htmlspecialchars($_GET['success']); ?> successfully!
                </div>
            <?php endif; ?>
            
            <!-- Current Cup Inventory -->
            <div class="stats-grid">
                <?php while ($cup = $cup_inventory->fetch_assoc()): ?>
                    <?php
                    $stock_class = '';
                    if ($cup['stock_quantity'] <= $cup['reorder_level'] / 2) {
                        $stock_class = 'critical-stock';
                    } elseif ($cup['stock_quantity'] <= $cup['reorder_level']) {
                        $stock_class = 'low-stock';
                    }
                    ?>
                    <div class="cup-card">
                        <div class="cup-size-badge"><?php echo htmlspecialchars($cup['cup_size']); ?></div>
                        <div class="stock-display <?php echo $stock_class; ?>">
                            <?php echo $cup['stock_quantity']; ?>
                        </div>
                        <div style="color: #666; margin-bottom: 16px;">
                            Reorder Level: <?php echo $cup['reorder_level']; ?>
                        </div>
                        
                        <!-- Quick Actions -->
                        <div style="display: flex; gap: 8px;">
                            <button class="btn btn-primary btn-sm" onclick="openRestockModal('<?php echo $cup['cup_size']; ?>')">
                                + Restock
                            </button>
                            <button class="btn btn-warning btn-sm" onclick="openAdjustModal('<?php echo $cup['cup_size']; ?>')">
                                Adjust
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            
            <!-- Today's Usage -->
            <div class="card">
                <div class="card-header">
                    <h3>Today's Cup Usage</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Cup Size</th>
                                    <th>Cups Used Today</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($today_usage->num_rows > 0): ?>
                                    <?php while ($usage = $today_usage->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($usage['cup_size']); ?></strong></td>
                                            <td><?php echo $usage['total_used']; ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="2" style="text-align: center; color: var(--text-secondary);">No cup usage today</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Recent Movements -->
            <div class="card" style="margin-top: 24px;">
                <div class="card-header">
                    <h3>Recent Cup Movements</h3>
                </div>
                <div class="card-body">
                    <div class="movement-list">
                        <?php if ($movements->num_rows > 0): ?>
                            <?php while ($movement = $movements->fetch_assoc()): ?>
                                <div class="movement-item">
                                    <div>
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <span class="movement-type <?php echo $movement['movement_type']; ?>">
                                                <?php echo $movement['movement_type']; ?>
                                            </span>
                                            <strong><?php echo htmlspecialchars($movement['cup_size']); ?></strong>
                                            <span style="color: #666;">
                                                <?php echo $movement['quantity']; ?> cups
                                            </span>
                                        </div>
                                        <?php if ($movement['notes']): ?>
                                            <div style="font-size: 12px; color: #999; margin-top: 4px;">
                                                <?php echo htmlspecialchars($movement['notes']); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div style="font-size: 12px; color: #999; margin-top: 4px;">
                                            <?php echo date('M j, Y g:i A', strtotime($movement['movement_date'])); ?>
                                            <?php if ($movement['full_name']): ?>
                                                by <?php echo htmlspecialchars($movement['full_name']); ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div style="text-align: center; color: var(--text-secondary); padding: 24px;">
                                No cup movements recorded yet
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Restock Modal -->
    <div id="restockModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Restock Cups</h2>
                <button class="modal-close" onclick="closeRestockModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <input type="hidden" name="cup_size" id="restockCupSize">
                    
                    <div class="form-group">
                        <label>Cup Size</label>
                        <input type="text" id="restockCupSizeDisplay" class="form-control" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label>Quantity to Add</label>
                        <input type="number" name="quantity" class="form-control" required min="1" placeholder="Enter quantity">
                    </div>
                    
                    <div class="form-group">
                        <label>Notes (Optional)</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Add notes about this restock..."></textarea>
                    </div>
                    
                    <button type="submit" name="restock_cups" class="btn btn-primary" style="width: 100%;">Restock Cups</button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Adjust Modal -->
    <div id="adjustModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Adjust Cup Inventory</h2>
                <button class="modal-close" onclick="closeAdjustModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <input type="hidden" name="cup_size" id="adjustCupSize">
                    
                    <div class="form-group">
                        <label>Cup Size</label>
                        <input type="text" id="adjustCupSizeDisplay" class="form-control" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label>Adjustment Type</label>
                        <select name="movement_type" class="form-control" required>
                            <option value="adjustment">Manual Adjustment</option>
                            <option value="waste">Waste/Damaged</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Quantity to Remove</label>
                        <input type="number" name="quantity" class="form-control" required min="1" placeholder="Enter quantity">
                    </div>
                    
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" class="form-control" rows="3" required placeholder="Explain reason for adjustment..."></textarea>
                    </div>
                    
                    <button type="submit" name="adjust_cups" class="btn btn-warning" style="width: 100%;">Adjust Inventory</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function openRestockModal(cupSize) {
            document.getElementById('restockCupSize').value = cupSize;
            document.getElementById('restockCupSizeDisplay').value = cupSize;
            document.getElementById('restockModal').classList.add('active');
        }
        
        function closeRestockModal() {
            document.getElementById('restockModal').classList.remove('active');
        }
        
        function openAdjustModal(cupSize) {
            document.getElementById('adjustCupSize').value = cupSize;
            document.getElementById('adjustCupSizeDisplay').value = cupSize;
            document.getElementById('adjustModal').classList.add('active');
        }
        
        function closeAdjustModal() {
            document.getElementById('adjustModal').classList.remove('active');
        }
        
        window.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                closeRestockModal();
                closeAdjustModal();
            }
        });
    </script>
    <script src="js/hamburger.js"></script>
</body>
</html>
