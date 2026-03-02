# Secure Admin-Authorized Void Function Implementation
**Date:** March 2, 2026  
**Status:** Complete  
**Version:** 1.0

---

## 📋 Implementation Summary

A complete secure void function has been implemented for the PHP POS system with the following features:
- Admin-authorized void operations with password verification
- Soft voiding with complete audit trail
- Frontend cart item voiding before checkout
- Backend database recording after checkout
- Security best practices (password_verify, prepared statements, session validation)

---

## 📁 Deliverables

### 1. **Database Migration**
**File:** `migrations/add_void_tracking.sql`

Adds four new fields to the `sale_items` table:
- `is_voided TINYINT(1)` - Soft void flag (0 = active, 1 = voided)
- `voided_by INT` - Admin user ID who authorized the void
- `void_reason TEXT` - Reason for voiding the item
- `voided_at DATETIME` - Timestamp when item was voided

Includes indexes for efficient querying and foreign key to `users` table.

**Migration Commands:**
```sql
-- Apply the migration
mysql -u root -p pos_inventory < migrations/add_void_tracking.sql

-- Verify the new columns
DESCRIBE sale_items;
```

---

### 2. **Backend Secure Endpoint**
**File:** `api/void_item.php`

**Functionality:**
- Validates POST requests with required fields
- Verifies user session exists
- Fetches all admin users from database
- Uses `password_verify()` for secure password authentication
- Updates sale_items with soft void marker
- Logs failed authorization attempts for security audit
- Returns JSON responses with success/error states

**Request Format:**
```json
{
    "sale_item_id": 123,
    "admin_password": "adminPassword123",
    "void_reason": "Wrong item scanned"
}
```

**Success Response:**
```json
{
    "success": true,
    "message": "Item voided successfully",
    "sale_item_id": 123,
    "voided_by": 1,
    "voided_at": "2026-03-02 10:30:45"
}
```

**Error Responses:**
```json
{
    "success": false,
    "error": "Invalid admin password"
}
```

**Security Features:**
- ✅ Prepared statements prevent SQL injection
- ✅ Password verification with password_verify()
- ✅ Session validation (requireLogin())
- ✅ No plaintext password storage
- ✅ Server-side void reason validation
- ✅ Failed attempt logging

---

### 3. **Frontend UI Update**
**File:** `pos.php`

**New Components:**

#### Cart Row Structure:
```html
<div class="cart-item">
    <!-- Item Name and Price -->
    <div class="cart-item-info">
        <div class="cart-item-name">Item Name [VOIDED badge if voided]</div>
        <div class="cart-item-price">₱99.99</div>
    </div>
    
    <!-- Quantity and Actions -->
    <div class="cart-item-actions">
        <input type="number" class="cart-item-qty" value="1">
        <strong>₱99.99</strong>
        <button class="btn-void" onclick="openVoidModal(index)">Void</button>
    </div>
</div>
```

#### Void Authorization Modal:
- Admin password input field (type="password")
- Void reason textarea (required, max 500 chars)
- Character counter for reason field
- Confirm/Cancel buttons
- Password and reason validation

---

### 4. **JavaScript Implementation**
**File:** `js/pos.js`

**New Functions:**

#### `updateCart()`
- Enhanced to display void button for each item
- Shows "VOIDED" badge for voided items
- Disables interactions for voided items
- Maintains cart structure with voided items

#### `openVoidModal(index)`
```javascript
openVoidModal(0); // Open void modal for cart item at index 0
```
Opens authorization modal with form fields ready for input.

#### `closeVoidModal()`
Closes the void modal and clears the form.

#### `voidItem()` Handler
```javascript
// Validates form
// Checks customer admin password
// Marks item as locally voided
// Stores voided state for checkout
```

**Character Counter:**
Monitors void reason field with real-time character count (max 500).

**Checkout Integration:**
- Filters out voided items before posting to database
- Recalculates totals (subtotal, tax, grand total) excluding voided items
- Shows voided items separately in checkout modal (read-only, struck through, reduced opacity)

---

### 5. **CSS Styling**
**File:** `pos.php` (inline styles)

**Void Button Styling:**
```css
.btn-void {
    background: #ff6b6b;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-weight: 600;
    text-transform: uppercase;
}

.btn-void:hover {
    background: #d32f2f;
}
```

**Voided Item Styling:**
```css
.voided-item {
    opacity: 0.6;
    background: #f5f5f5;
    border-color: #ddd;
}

.voided-item .cart-item-name {
    text-decoration: line-through;
    color: #999;
}

.voided-badge {
    display: inline-block;
    background: #d32f2f;
    color: white;
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 3px;
}
```

**Modal Styling:**
- Clean white modal with red header border
- Proper field styling matching POS theme
- Focus states for accessibility
- Smooth transitions and hover effects

---

## 🔐 Security Architecture

### Password Verification Flow:
```
User clicks "Void" → Modal opens → User enters admin password
                                 ↓
                         Backend fetches admin users
                                 ↓
                    password_verify(input, hash) for each admin
                                 ↓
              ✅ Match found → Update DB with soft void
              ❌ No match → Return 401 Unauthorized
```

### Prepared Statement Example:
```php
$void_stmt = $conn->prepare(
    "UPDATE sale_items SET is_voided = 1, voided_by = ?, 
     void_reason = ?, voided_at = ? WHERE sale_item_id = ?"
);
$void_stmt->bind_param('issi', $admin_id, $reason, $timestamp, $item_id);
$void_stmt->execute();
```

### Session Validation:
```php
requireLogin();  // Ensures user is authenticated
$user = getCurrentUser(); // Gets current user data
```

---

## 📊 Database Schema

### sale_items Table (Updated):
```sql
ALTER TABLE sale_items ADD COLUMN (
    is_voided TINYINT(1) DEFAULT 0,
    voided_by INT NULL,
    void_reason TEXT NULL,
    voided_at DATETIME NULL,
    FOREIGN KEY (voided_by) REFERENCES users(user_id)
);

-- Indexes for performance
CREATE INDEX idx_is_voided ON sale_items(is_voided);
CREATE INDEX idx_voided_by ON sale_items(voided_by);
CREATE INDEX idx_voided_at ON sale_items(voided_at);
```

---

## 🔄 Void Workflow

### For Items Not Yet Posted:
1. User adds products to cart
2. User clicks "Void" button on item
3. Modal opens requesting admin authorization
4. Cashier obtains admin password
5. Admin enters password and void reason
6. ✅ Item marked as voided locally (visually struck through)
7. ❌ Invalid password → Error message, retry
8. User proceeds to checkout without voided items
9. System calculates totals excluding voided items
10. Sale posted without voided item line

### For Items Already Posted:
1. Sale is completed and recorded
2. Item exists in sale_items table with sale_item_id
3. Admin/Supervisor can void item via separate interface (future)
4. void_item.php processes authorization
5. Item soft-deleted (is_voided = 1)
6. Audit trail preserved for reporting
7. Affects recalculation of sales reports

---

## 🧪 Testing Checklist

- [ ] Apply database migration: `mysql -u root -p < migrations/add_void_tracking.sql`
- [ ] Add item to cart in POS
- [ ] Click "Void" button
- [ ] Modal appears with password/reason fields
- [ ] Enter wrong password → "Invalid admin password" error
- [ ] Clear password, enter correct admin password (default: admin123)
- [ ] Enter void reason (required field)
- [ ] Click "Confirm Void"
- [ ] Item marked as voided (struck through, reduced opacity)
- [ ] "VOIDED" badge appears on item
- [ ] Void button disabled for voided item
- [ ] Item doesn't appear in checkout modal
- [ ] Totals exclude voided item amounts
- [ ] Complete sale with remaining items
- [ ] Check database: sale_items shows is_voided = 1, voided_by = admin_id, void_reason text

---

## 📝 Audit Logging

Void operations are logged in PHP error_log:

**Failed Attempt:**
```
SECURITY: Failed void authorization attempt by user_id=2 for sale_item_id=123 at 2026-03-02 10:23:15
```

**Successful Void:**
```
AUDIT: Item voided - sale_item_id=123, authorized_by_admin_id=1, 
requested_by_user_id=2, reason=Wrong item scanned... at 2026-03-02 10:24:30
```

Location: `php.ini` error_log directive (typically `/var/log/php-errors.log` or similar)

---

## 🛠️ Configuration & Customization

### Change Max Void Reason Length:
**File:** `api/void_item.php` (line ~60)
```php
if (strlen($void_reason) > 500) {  // Change 500 to desired length
    // Validator
}
```

### Adjust Void Item Styling:
**File:** `pos.php` (search for `.voided-item`)
```css
.voided-item {
    opacity: 0.6;           /* Change opacity percentage */
    background: #f5f5f5;    /* Change background color */
    border-color: #ddd;     /* Change border color */
}
```

### Modify Void Badge Appearance:
**File:** `pos.php` (search for `.voided-badge`)
```css
.voided-badge {
    background: #d32f2f;    /* Badge color */
    font-size: 10px;        /* Badge text size */
}
```

---

## 🔄 Integration with Existing Systems

### Sales Reports Query:
To exclude voided items from revenue reports:
```sql
SELECT 
    s.sale_id,
    SUM(si.subtotal) as total
FROM sales s
JOIN sale_items si ON s.sale_id = si.sale_id
WHERE si.is_voided = 0  -- Exclude voided items
GROUP BY s.sale_id;
```

### Audit Trail Query:
To view void operations:
```sql
SELECT 
    si.sale_item_id,
    si.product_name,
    si.void_reason,
    u.full_name as voided_by_admin,
    si.voided_at
FROM sale_items si
LEFT JOIN users u ON si.voided_by = u.user_id
WHERE si.is_voided = 1
ORDER BY si.voided_at DESC;
```

---

## 🚀 Future Enhancements

1. **Post-Sale Void Interface**
   - Separate page for supervisors to void completed sales
   - Search sale by invoice number
   - Display sale items with void button
   - Integrate with sales reports

2. **Void Analytics**
   - Dashboard showing void trends
   - Reasons for voids (categorized)
   - Abuse detection (alerts if user voids frequently)

3. **Approval Workflow**
   - Higher-level approval for voids over certain amount
   - Email notifications on void authorization
   - Configurable void thresholds by user role

4. **POS Permissions**
   - Restrict which users can void
   - Audit log of all void activities by user
   - Separate "Can Void" permission level

---

## 📞 Support & Troubleshooting

### Modal Not Opening:
- Check browser console for JavaScript errors
- Verify void_item.php endpoint exists
- Check for JavaScript conflicts with other libraries

### Password Always Invalid:
- Verify admin user password hash in database
- Check if password_verify() is available (PHP 5.5+)
- Test with default admin user (username: admin, password: admin123)

### Items Not Marking as Voided:
- Check cart.voided property is being set
- Verify updateCart() is called after void
- Check CSS classes are applied to voided-item

### Database Migration Fails:
- Ensure database exists: `USE pos_inventory;`
- Check sale_items table exists
- Verify users table has correct structure
- Try applying SQL statements individually

---

**Implementation Complete ✅**

All files have been created and updated. The system is ready for:
1. Database migration
2. Testing void functionality
3. Integration with existing POS operations
4. Audit trail monitoring

For questions or issues, refer to the security architecture section and troubleshooting guide.
