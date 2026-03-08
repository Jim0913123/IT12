<?php
/**
 * Security Helper Functions - Enhanced Version
 * Provides CSRF protection, input sanitization, rate limiting, activity logging,
 * RBAC, secure session management, and inventory protection
 * 
 * Security Features:
 * - Role-Based Access Control (RBAC)
 * - Session Security with timeout
 * - CSRF Protection (database-backed)
 * - Rate Limiting with account lockout
 * - Activity Logging
 * - Input Validation/Sanitization
 * - Inventory Protection
 * - Admin Authorization for Voids
 * - Secure Error Handling
 * - Security Headers
 */

require_once __DIR__ . '/db.php';

// ============================================
// SECURITY CONFIGURATION
// ============================================
define('SECURITY_CONFIG', [
    'session_timeout' => 1800,           // 30 minutes inactivity timeout
    'max_login_attempts' => 5,           // Max failed login attempts
    'lockout_duration' => 900,           // 15 minutes lockout
    'csrf_token_expiry' => 3600,         // 1 hour CSRF token expiry
    'password_min_length' => 8,          // Minimum password length
    'secure_cookies' => true,            // Use secure cookies (HTTPS)
    'session_regenerate_interval' => 300 // Regenerate session every 5 minutes
]);

// ============================================
// SESSION SECURITY INITIALIZATION
// ============================================
function initSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        // Secure session configuration
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.use_strict_mode', 1);
        ini_set('session.use_only_cookies', 1);
        
        // Use secure cookies if HTTPS
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            ini_set('session.cookie_secure', 1);
        }
        
        session_start();
        
        // Check session timeout
        checkSessionTimeout();
        
        // Regenerate session ID periodically
        regenerateSessionPeriodically();
    }
}

/**
 * Check and enforce session timeout
 */
function checkSessionTimeout(): void {
    $timeout = SECURITY_CONFIG['session_timeout'];
    
    if (isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > $timeout) {
            // Session expired - destroy and redirect
            destroySecureSession();
            if (!headers_sent()) {
                header('Location: login.php?timeout=1');
                exit();
            }
        }
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
}

/**
 * Regenerate session ID periodically for security
 */
function regenerateSessionPeriodically(): void {
    $interval = SECURITY_CONFIG['session_regenerate_interval'];
    
    if (!isset($_SESSION['session_created'])) {
        $_SESSION['session_created'] = time();
    } elseif (time() - $_SESSION['session_created'] > $interval) {
        session_regenerate_id(true);
        $_SESSION['session_created'] = time();
    }
}

/**
 * Destroy session securely
 */
function destroySecureSession(): void {
    $_SESSION = [];
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

// Initialize secure session
initSecureSession();

// ============================================
// SECURITY HEADERS
// ============================================
function setSecurityHeaders(): void {
    if (!headers_sent()) {
        // Prevent clickjacking
        header('X-Frame-Options: DENY');
        
        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // XSS Protection
        header('X-XSS-Protection: 1; mode=block');
        
        // Referrer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Content Security Policy (basic)
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:;");
    }
}

// ============================================
// ROLE-BASED ACCESS CONTROL (RBAC)
// ============================================

/**
 * Define role permissions
 */
function getRolePermissions(): array {
    return [
        'admin' => [
            'manage_products',
            'manage_categories',
            'manage_inventory',
            'manage_users',
            'view_reports',
            'authorize_voids',
            'view_activity_logs',
            'create_sales',
            'process_payments',
            'view_products',
            'manage_cups',
            'manage_ingredients',
            'export_data'
        ],
        'cashier' => [
            'create_sales',
            'process_payments',
            'view_products',
            'request_void'  // Can request but not authorize
        ]
    ];
}

/**
 * Check if current user has a specific permission
 */
function hasPermission(string $permission): bool {
    $role = $_SESSION['role'] ?? null;
    if (!$role) return false;
    
    $permissions = getRolePermissions();
    return isset($permissions[$role]) && in_array($permission, $permissions[$role]);
}

/**
 * Require specific permission or redirect
 */
function requirePermission(string $permission, string $redirectTo = 'index.php'): void {
    if (!hasPermission($permission)) {
        logActivity('unauthorized_access', "Attempted to access: $permission without permission");
        $_SESSION['flash_message'] = 'You do not have permission to access this resource.';
        $_SESSION['flash_type'] = 'error';
        header("Location: $redirectTo");
        exit();
    }
}

/**
 * Check if user is cashier
 */
function isCashier(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'cashier';
}

/**
 * Get pages accessible by role
 */
function getAccessiblePages(string $role): array {
    $pages = [
        'admin' => [
            'index.php', 'pos.php', 'products.php', 'categories.php',
            'inventory.php', 'cup_inventory.php', 'ingredients.php',
            'sales.php', 'reports.php', 'users.php', 'activity_logs.php',
            'voids.php', 'receipt.php', 'logout.php'
        ],
        'cashier' => [
            'index.php', 'pos.php', 'receipt.php', 'logout.php'
        ]
    ];
    
    return $pages[$role] ?? [];
}

/**
 * Check if current page is accessible
 */
function checkPageAccess(): void {
    $currentPage = basename($_SERVER['PHP_SELF']);
    $role = $_SESSION['role'] ?? null;
    
    if (!$role) return;
    
    $accessiblePages = getAccessiblePages($role);
    
    // Check API endpoints
    if (strpos($currentPage, '.php') !== false && 
        strpos($_SERVER['PHP_SELF'], '/api/') !== false) {
        return; // API endpoints have their own access control
    }
    
    if (!in_array($currentPage, $accessiblePages)) {
        logActivity('unauthorized_page_access', "Attempted to access: $currentPage");
        $_SESSION['flash_message'] = 'You do not have access to this page.';
        $_SESSION['flash_type'] = 'error';
        header('Location: index.php');
        exit();
    }
}

// ============================================
// CSRF PROTECTION (Database-backed)
// ============================================

/**
 * Generate a CSRF token and store in database
 */
function generateCSRFToken(): string {
    $token = bin2hex(random_bytes(32));
    $userId = $_SESSION['user_id'] ?? null;
    $expiry = SECURITY_CONFIG['csrf_token_expiry'];
    
    // Also store in session for quick validation
    $_SESSION['csrf_token'] = $token;
    $_SESSION['csrf_token_time'] = time();
    
    // Store in database for persistence
    try {
        $pdo = getPDO();
        
        // Clean old tokens for this user
        if ($userId) {
            $cleanup = $pdo->prepare("DELETE FROM csrf_tokens WHERE user_id = ? OR expires_at < NOW()");
            $cleanup->execute([$userId]);
        } else {
            $cleanup = $pdo->prepare("DELETE FROM csrf_tokens WHERE expires_at < NOW()");
            $cleanup->execute();
        }
        
        // Insert new token
        $stmt = $pdo->prepare("INSERT INTO csrf_tokens (token, user_id, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND))");
        $stmt->execute([$token, $userId, $expiry]);
    } catch (Exception $e) {
        error_log("CSRF Token Generation Error: " . $e->getMessage());
    }
    
    return $token;
}

/**
 * Verify CSRF token from form submission
 */
function verifyCSRFToken(?string $token): bool {
    if (empty($token)) {
        return false;
    }
    
    // Quick session check first
    if (!empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
        // Check expiration
        if (isset($_SESSION['csrf_token_time'])) {
            $expiry = SECURITY_CONFIG['csrf_token_expiry'];
            if (time() - $_SESSION['csrf_token_time'] <= $expiry) {
                return true;
            }
        }
    }
    
    // Database verification as fallback
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare("SELECT * FROM csrf_tokens WHERE token = ? AND expires_at > NOW()");
        $stmt->execute([$token]);
        $result = $stmt->fetch();
        
        if ($result) {
            // Delete used token (one-time use)
            $delete = $pdo->prepare("DELETE FROM csrf_tokens WHERE token = ?");
            $delete->execute([$token]);
            return true;
        }
    } catch (Exception $e) {
        error_log("CSRF Token Verification Error: " . $e->getMessage());
    }
    
    return false;
}

/**
 * Output a hidden CSRF field for forms
 */
function csrfField(): string {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Validate CSRF token from POST request (middleware)
 */
function validateCSRFRequest(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!verifyCSRFToken($token)) {
            logActivity('csrf_violation', 'Invalid CSRF token submitted');
            if (isAjaxRequest()) {
                jsonError('Invalid security token. Please refresh the page.', 403);
            } else {
                $_SESSION['flash_message'] = 'Security token expired. Please try again.';
                $_SESSION['flash_type'] = 'error';
                header('Location: ' . $_SERVER['HTTP_REFERER'] ?? 'index.php');
                exit();
            }
        }
    }
}

// ============================================
// RATE LIMITING & ACCOUNT LOCKOUT
// ============================================

/**
 * Check if IP/user is locked out
 */
function isLockedOut(string $identifier, string $type = 'login'): bool {
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare("
            SELECT locked_until 
            FROM login_attempts 
            WHERE identifier = ? AND attempt_type = ? AND locked_until > NOW()
            LIMIT 1
        ");
        $stmt->execute([$identifier, $type]);
        $result = $stmt->fetch();
        
        return $result !== false;
    } catch (Exception $e) {
        error_log("Lockout Check Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get remaining lockout time in seconds
 */
function getLockoutRemaining(string $identifier, string $type = 'login'): int {
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare("
            SELECT TIMESTAMPDIFF(SECOND, NOW(), locked_until) as remaining 
            FROM login_attempts 
            WHERE identifier = ? AND attempt_type = ? AND locked_until > NOW()
            LIMIT 1
        ");
        $stmt->execute([$identifier, $type]);
        $result = $stmt->fetch();
        
        return $result ? max(0, (int)$result['remaining']) : 0;
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Record a failed attempt and check for lockout
 */
function recordFailedAttempt(string $identifier, string $type = 'login'): array {
    $maxAttempts = SECURITY_CONFIG['max_login_attempts'];
    $lockoutDuration = SECURITY_CONFIG['lockout_duration'];
    
    try {
        $pdo = getPDO();
        $ip = getClientIP();
        
        // Check existing attempts
        $stmt = $pdo->prepare("
            SELECT id, attempts FROM login_attempts 
            WHERE identifier = ? AND attempt_type = ?
            LIMIT 1
        ");
        $stmt->execute([$identifier, $type]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            $newAttempts = $existing['attempts'] + 1;
            $lockedUntil = null;
            
            if ($newAttempts >= $maxAttempts) {
                $lockedUntil = date('Y-m-d H:i:s', time() + $lockoutDuration);
            }
            
            $update = $pdo->prepare("
                UPDATE login_attempts 
                SET attempts = ?, last_attempt = NOW(), locked_until = ?
                WHERE id = ?
            ");
            $update->execute([$newAttempts, $lockedUntil, $existing['id']]);
            
            $remaining = $maxAttempts - $newAttempts;
            return [
                'locked' => $newAttempts >= $maxAttempts,
                'attempts' => $newAttempts,
                'remaining' => max(0, $remaining),
                'lockout_seconds' => $lockedUntil ? $lockoutDuration : 0
            ];
        } else {
            // First attempt
            $insert = $pdo->prepare("
                INSERT INTO login_attempts (attempt_type, identifier, attempts, last_attempt)
                VALUES (?, ?, 1, NOW())
            ");
            $insert->execute([$type, $identifier]);
            
            return [
                'locked' => false,
                'attempts' => 1,
                'remaining' => $maxAttempts - 1,
                'lockout_seconds' => 0
            ];
        }
    } catch (Exception $e) {
        error_log("Record Failed Attempt Error: " . $e->getMessage());
        return ['locked' => false, 'attempts' => 0, 'remaining' => $maxAttempts];
    }
}

/**
 * Reset attempts on successful login
 */
function resetAttempts(string $identifier, string $type = 'login'): void {
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE identifier = ? AND attempt_type = ?");
        $stmt->execute([$identifier, $type]);
    } catch (Exception $e) {
        error_log("Reset Attempts Error: " . $e->getMessage());
    }
}

/**
 * Check rate limiting (legacy compatibility)
 */
function checkRateLimit(string $type = 'login', int $maxAttempts = 5, int $windowMinutes = 15): bool {
    $identifier = getClientIP();
    return !isLockedOut($identifier, $type);
}

/**
 * Record a login/void attempt (legacy compatibility)
 */
function recordAttempt(string $type, bool $success, ?string $username = null): bool {
    if (!$success) {
        $identifier = $username ?: getClientIP();
        recordFailedAttempt($identifier, $type);
    } else {
        if ($username) {
            resetAttempts($username, $type);
        }
        resetAttempts(getClientIP(), $type);
    }
    return true;
}

/**
 * Reset rate limit on successful authentication (legacy compatibility)
 */
function resetRateLimit(string $type = 'login'): void {
    resetAttempts(getClientIP(), $type);
}

// ============================================
// INPUT SANITIZATION
// ============================================

/**
 * Sanitize string input
 */
function sanitize(?string $input): string {
    if ($input === null) {
        return '';
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize integer input
 */
function sanitizeInt($input): int {
    return (int) filter_var($input, FILTER_SANITIZE_NUMBER_INT);
}

/**
 * Sanitize float/decimal input
 */
function sanitizeFloat($input): float {
    return (float) filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
}

/**
 * Sanitize email input
 */
function sanitizeEmail(?string $input): string {
    return filter_var(trim($input ?? ''), FILTER_SANITIZE_EMAIL);
}

/**
 * Validate email format
 */
function validateEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// ============================================
// ACTIVITY LOGGING (Enhanced)
// ============================================

/**
 * Log activity to the activity_logs table
 */
function logActivity(
    string $action,
    string $description,
    ?int $userId = null,
    ?string $referenceType = null,
    ?int $referenceId = null,
    ?array $oldValues = null,
    ?array $newValues = null
): bool {
    try {
        $pdo = getPDO();
        
        $sql = "INSERT INTO activity_logs 
                (user_id, action, description, ip_address, user_agent, reference_type, reference_id, old_values, new_values)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        
        $ipAddress = getClientIP();
        $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
        $oldValuesJson = $oldValues ? json_encode($oldValues) : null;
        $newValuesJson = $newValues ? json_encode($newValues) : null;
        
        // Use session user_id if not provided
        if ($userId === null && isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
        }
        
        return $stmt->execute([
            $userId,
            $action,
            $description,
            $ipAddress,
            $userAgent,
            $referenceType,
            $referenceId,
            $oldValuesJson,
            $newValuesJson
        ]);
    } catch (Exception $e) {
        error_log("Activity Log Error: " . $e->getMessage());
        return false;
    }
}

// ============================================
// VOID AUTHORIZATION
// ============================================

/**
 * Request void authorization from admin
 */
function requestVoidAuthorization(int $saleId, string $reason, int $requestedBy): array {
    if (!isAdmin()) {
        // Create pending void request
        logActivity('void_request', "Void requested for sale #$saleId: $reason", $requestedBy, 'sale', $saleId);
        return [
            'success' => true,
            'pending' => true,
            'message' => 'Void request submitted for admin approval'
        ];
    }
    
    return ['success' => true, 'pending' => false, 'authorized' => true];
}

/**
 * Authorize void operation (admin only)
 */
function authorizeVoid(int $saleId, int $adminId, string $adminPassword): array {
    // Verify admin credentials
    if (!verifyAdminCredentials($adminId, $adminPassword)) {
        logActivity('void_auth_failed', "Failed void authorization for sale #$saleId", $adminId, 'sale', $saleId);
        return ['success' => false, 'error' => 'Invalid admin credentials'];
    }
    
    logActivity('void_authorized', "Void authorized for sale #$saleId", $adminId, 'sale', $saleId);
    return ['success' => true, 'authorized' => true];
}

/**
 * Verify admin credentials for void authorization
 */
function verifyAdminCredentials(int $adminId, string $password): bool {
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ? AND role = 'admin'");
        $stmt->execute([$adminId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return false;
        }
        
        return password_verify($password, $user['password']);
    } catch (Exception $e) {
        error_log("Admin Verification Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Validate password strength
 */
function validatePassword(string $password): array {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    return $errors;
}

/**
 * Format currency for display
 */
function formatCurrency(float $amount): string {
    return '₱' . number_format($amount, 2);
}

/**
 * Redirect with optional flash message
 */
function redirect(string $url, ?string $message = null, string $type = 'success'): void {
    if ($message) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    header("Location: $url");
    exit();
}

/**
 * Get and clear flash message
 */
function getFlashMessage(): ?array {
    if (isset($_SESSION['flash_message'])) {
        $message = [
            'message' => $_SESSION['flash_message'],
            'type' => $_SESSION['flash_type'] ?? 'success'
        ];
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return $message;
    }
    return null;
}

/**
 * JSON response helper
 */
function jsonResponse(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

/**
 * JSON error response helper
 */
function jsonError(string $message, int $statusCode = 400): void {
    jsonResponse(['success' => false, 'error' => $message], $statusCode);
}

/**
 * JSON success response helper
 */
function jsonSuccess(array $data = [], string $message = 'Success'): void {
    jsonResponse(array_merge(['success' => true, 'message' => $message], $data));
}

/**
 * Get client IP address (handles proxies)
 */
function getClientIP(): string {
    $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    
    foreach ($ipKeys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = $_SERVER[$key];
            // If there are multiple IPs (proxy chain), get the first one
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    
    return '0.0.0.0';
}

/**
 * Check if request is AJAX
 */
function isAjaxRequest(): bool {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Secure error handler - logs to file, returns generic message
 */
function handleSecureError(Exception $e, string $context = ''): array {
    $errorId = uniqid('ERR_');
    $logMessage = sprintf(
        "[%s] %s | Context: %s | File: %s:%d | Message: %s | Trace: %s",
        $errorId,
        date('Y-m-d H:i:s'),
        $context,
        $e->getFile(),
        $e->getLine(),
        $e->getMessage(),
        $e->getTraceAsString()
    );
    
    error_log($logMessage);
    
    return [
        'success' => false,
        'error' => 'An error occurred. Reference: ' . $errorId,
        'error_id' => $errorId
    ];
}

/**
 * Validate required fields in array
 */
function validateRequired(array $data, array $requiredFields): array {
    $missing = [];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
            $missing[] = $field;
        }
    }
    
    if (!empty($missing)) {
        return [
            'valid' => false,
            'error' => 'Missing required fields: ' . implode(', ', $missing),
            'missing' => $missing
        ];
    }
    
    return ['valid' => true];
}

/**
 * API security middleware - validates CSRF and authentication
 */
function apiSecurityCheck(): void {
    // Check if user is authenticated
    if (!isset($_SESSION['user_id'])) {
        jsonError('Authentication required', 401);
    }
    
    // Check CSRF for POST/PUT/DELETE requests
    if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE'])) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!verifyCSRFToken($token)) {
            logActivity('api_csrf_violation', 'Invalid CSRF token in API request');
            jsonError('Invalid security token', 403);
        }
    }
    
    // Check session validity
    checkSessionTimeout();
}

/**
 * Get CSRF token for AJAX requests
 */
function getCSRFTokenForAjax(): string {
    if (empty($_SESSION['csrf_token'])) {
        return generateCSRFToken();
    }
    return $_SESSION['csrf_token'];
}
