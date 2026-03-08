<?php
/**
 * Activity Logs Page - Admin Only
 * View all system activity with filtering and pagination (5 per page)
 */

require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/security.php';

setSecurityHeaders();
requireLogin();
checkPageAccess();
requirePermission('view_activity_logs');

$user = getCurrentUser();

// Filters
$filterAction = sanitize($_GET['action'] ?? '');
$filterUser = sanitizeInt($_GET['user'] ?? 0);
$filterDate = sanitize($_GET['date'] ?? '');
$page = max(1, sanitizeInt($_GET['page'] ?? 1));

// Build query
$where = [];
$params = [];

if ($filterAction) {
    $where[] = "al.action LIKE ?";
    $params[] = "%$filterAction%";
}
if ($filterUser) {
    $where[] = "al.user_id = ?";
    $params[] = $filterUser;
}
if ($filterDate) {
    $where[] = "DATE(al.created_at) = ?";
    $params[] = $filterDate;
}

$whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";

// Get total count
$countSql = "SELECT COUNT(*) as cnt FROM activity_logs al $whereClause";
$total = dbFetchOne($countSql, $params)['cnt'] ?? 0;
$totalPages = ceil($total / ITEMS_PER_PAGE);
$offset = ($page - 1) * ITEMS_PER_PAGE;

// Get logs
$sql = "SELECT al.*, u.full_name, u.username 
        FROM activity_logs al 
        LEFT JOIN users u ON al.user_id = u.user_id 
        $whereClause 
        ORDER BY al.created_at DESC 
        LIMIT " . ITEMS_PER_PAGE . " OFFSET $offset";
$logs = dbFetchAll($sql, $params);

// Get users for dropdown
$users = dbFetchAll("SELECT user_id, full_name, username FROM users ORDER BY full_name");

// Get action types for dropdown
$actionTypes = dbFetchAll("SELECT DISTINCT action FROM activity_logs ORDER BY action");

// Today's stats
$todayStats = dbFetchOne("
    SELECT 
        COUNT(*) as total,
        COUNT(DISTINCT user_id) as unique_users
    FROM activity_logs 
    WHERE DATE(created_at) = CURDATE()
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - POPRIE POS</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .filter-bar {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: flex-end;
            margin-bottom: 20px;
            padding: 16px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .filter-group label {
            font-size: 12px;
            font-weight: 600;
            color: #666;
        }
        
        .filter-group input,
        .filter-group select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            min-width: 160px;
        }
        
        .action-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .action-badge.login { background: #e3f2fd; color: #1565c0; }
        .action-badge.logout { background: #f3e5f5; color: #7b1fa2; }
        .action-badge.sale { background: #e8f5e9; color: #2e7d32; }
        .action-badge.void { background: #ffebee; color: #c62828; }
        .action-badge.restock { background: #e0f7fa; color: #00838f; }
        .action-badge.adjustment { background: #fff3e0; color: #ef6c00; }
        .action-badge.default { background: #f5f5f5; color: #616161; }
        
        .pagination-controls {
            display: flex;
            justify-content: center;
            gap: 4px;
            margin-top: 16px;
        }
        
        .pagination-controls a {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            text-decoration: none;
            color: #666;
            font-size: 13px;
        }
        
        .pagination-controls a:hover,
        .pagination-controls a.active {
            background: #d32f2f;
            color: white;
            border-color: #d32f2f;
        }
        
        .stat-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        
        .stat-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }
        
        .stat-card h3 {
            font-size: 36px;
            margin: 0;
            color: #d32f2f;
        }
        
        .stat-card p {
            margin: 8px 0 0;
            color: #666;
            font-size: 14px;
        }
        
        .details-column {
            max-width: 300px;
            word-wrap: break-word;
            font-size: 13px;
            color: #555;
        }
        
        .ip-badge {
            font-family: monospace;
            font-size: 11px;
            background: #f0f0f0;
            padding: 2px 6px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <div class="header">
                <div class="header-left">
                    <button class="hamburger-menu" onclick="toggleSidebar()">
                        <span></span><span></span><span></span>
                    </button>
                    <h1>Activity Logs</h1>
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
            
            <!-- Stats -->
            <div class="stat-cards">
                <div class="stat-card">
                    <h3><?php echo $total; ?></h3>
                    <p>Total Logs (Filtered)</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $todayStats['total'] ?? 0; ?></h3>
                    <p>Today's Activities</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $todayStats['unique_users'] ?? 0; ?></h3>
                    <p>Active Users Today</p>
                </div>
            </div>
            
            <!-- Filters -->
            <form method="GET" class="filter-bar">
                <div class="filter-group">
                    <label>Action Type</label>
                    <select name="action">
                        <option value="">All Actions</option>
                        <?php foreach ($actionTypes as $type): ?>
                            <option value="<?php echo htmlspecialchars($type['action']); ?>" <?php echo $filterAction === $type['action'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type['action']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>User</label>
                    <select name="user">
                        <option value="">All Users</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?php echo $u['user_id']; ?>" <?php echo $filterUser == $u['user_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($u['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Date</label>
                    <input type="date" name="date" value="<?php echo htmlspecialchars($filterDate); ?>">
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                <a href="activity_logs.php" class="btn btn-secondary btn-sm">Reset</a>
            </form>
            
            <!-- Logs Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Timestamp</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Details</th>
                                    <th>IP Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($logs)): ?>
                                    <?php foreach ($logs as $log): ?>
                                        <?php
                                        // Determine badge class
                                        $badgeClass = 'default';
                                        if (stripos($log['action'], 'login') !== false) $badgeClass = 'login';
                                        elseif (stripos($log['action'], 'logout') !== false) $badgeClass = 'logout';
                                        elseif (stripos($log['action'], 'sale') !== false) $badgeClass = 'sale';
                                        elseif (stripos($log['action'], 'void') !== false) $badgeClass = 'void';
                                        elseif (stripos($log['action'], 'restock') !== false) $badgeClass = 'restock';
                                        elseif (stripos($log['action'], 'adjust') !== false) $badgeClass = 'adjustment';
                                        ?>
                                        <tr>
                                            <td><?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?></td>
                                            <td>
                                                <?php if ($log['full_name']): ?>
                                                    <strong><?php echo htmlspecialchars($log['full_name']); ?></strong>
                                                    <div style="font-size: 11px; color: #888;">@<?php echo htmlspecialchars($log['username']); ?></div>
                                                <?php else: ?>
                                                    <span style="color: #999;">System</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="action-badge <?php echo $badgeClass; ?>">
                                                    <?php echo htmlspecialchars($log['action']); ?>
                                                </span>
                                            </td>
                                            <td class="details-column"><?php echo htmlspecialchars($log['details'] ?? '-'); ?></td>
                                            <td>
                                                <?php if ($log['ip_address']): ?>
                                                    <span class="ip-badge"><?php echo htmlspecialchars($log['ip_address']); ?></span>
                                                <?php else: ?>
                                                    <span style="color: #ccc;">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center; color: #999; padding: 40px;">
                                            No activity logs found
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination-controls">
                            <?php 
                            $queryParams = $_GET;
                            unset($queryParams['page']);
                            $baseUrl = 'activity_logs.php?' . http_build_query($queryParams) . '&page=';
                            ?>
                            
                            <?php if ($page > 1): ?>
                                <a href="<?php echo $baseUrl . ($page - 1); ?>">« Prev</a>
                            <?php endif; ?>
                            
                            <?php 
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            for ($i = $startPage; $i <= $endPage; $i++): 
                            ?>
                                <a href="<?php echo $baseUrl . $i; ?>" class="<?php echo $i === $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="<?php echo $baseUrl . ($page + 1); ?>">Next »</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="js/hamburger.js"></script>
</body>
</html>
