# POS Inventory System - Complete ERD Documentation

## Database Schema Overview

The POS Inventory System consists of 7 interconnected tables that manage users, products, sales, and inventory tracking.

---

## 1. USERS Table

**Purpose:** Manages user authentication and roles

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| user_id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique user identifier |
| username | VARCHAR(50) | UNIQUE, NOT NULL | Login username |
| password | VARCHAR(255) | NOT NULL | Encrypted password |
| full_name | VARCHAR(100) | NOT NULL | User's full name |
| role | ENUM('admin','cashier') | DEFAULT 'cashier' | User role/permissions |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Account creation date |

**Relationships:**
- One-to-Many with SALES (user_id)
- One-to-Many with STOCK_MOVEMENTS (user_id)

---

## 2. CATEGORIES Table

**Purpose:** Product categorization and organization

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| category_id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique category identifier |
| category_name | VARCHAR(100) | NOT NULL | Category name |
| description | TEXT | NULLABLE | Category description |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Category creation date |

**Relationships:**
- One-to-Many with PRODUCTS (category_id)

---

## 3. PRODUCTS Table

**Purpose:** Core product information and inventory

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| product_id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique product identifier |
| product_code | VARCHAR(50) | UNIQUE, NOT NULL | Product SKU/code |
| product_name | VARCHAR(200) | NOT NULL | Product name |
| category_id | INT | FOREIGN KEY → CATEGORIES | Product category |
| cup_size | ENUM('12oz','16oz','none') | DEFAULT 'none' | Cup size for coffee products |
| description | TEXT | NULLABLE | Product description |
| cost_price | DECIMAL(10,2) | NOT NULL | Purchase cost |
| selling_price | DECIMAL(10,2) | NOT NULL | Selling price |
| stock_quantity | INT | DEFAULT 0 | Current stock level |
| reorder_level | INT | DEFAULT 10 | Low stock threshold |
| barcode | VARCHAR(100) | NULLABLE | Product barcode |
| image_url | VARCHAR(255) | NULLABLE | Product image |
| status | ENUM('active','inactive') | DEFAULT 'active' | Product status |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Creation date |
| updated_at | TIMESTAMP | ON UPDATE CURRENT_TIMESTAMP | Last update |

**Relationships:**
- Many-to-One with CATEGORIES (category_id)
- One-to-Many with COFFEE_CUP_SIZES (product_id)
- One-to-Many with SALE_ITEMS (product_id)
- One-to-Many with STOCK_MOVEMENTS (product_id)

---

## 4. COFFEE_CUP_SIZES Table

**Purpose:** Size-based pricing for coffee products

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| product_id | INT | FOREIGN KEY → PRODUCTS | Related product |
| cup_size | ENUM('12oz','16oz') | NOT NULL | Cup size option |
| price_adjustment | DECIMAL(10,2) | DEFAULT 0.00 | Price difference from base |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Creation date |

**Relationships:**
- Many-to-One with PRODUCTS (product_id)

---

## 5. SALES Table

**Purpose:** Sales transaction records

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| sale_id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique sale identifier |
| invoice_number | VARCHAR(50) | UNIQUE, NOT NULL | Invoice number |
| user_id | INT | FOREIGN KEY → USERS | Cashier who processed sale |
| customer_name | VARCHAR(100) | NULLABLE | Customer name |
| customer_phone | VARCHAR(20) | NULLABLE | Customer phone |
| subtotal | DECIMAL(10,2) | NOT NULL | Subtotal before tax/discount |
| tax | DECIMAL(10,2) | DEFAULT 0.00 | Tax amount |
| discount | DECIMAL(10,2) | DEFAULT 0.00 | Discount amount |
| total_amount | DECIMAL(10,2) | NOT NULL | Final total |
| amount_paid | DECIMAL(10,2) | NOT NULL | Amount received |
| change_amount | DECIMAL(10,2) | DEFAULT 0.00 | Change given |
| payment_method | ENUM('cash','card','online') | DEFAULT 'cash' | Payment type |
| sale_date | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Transaction date |

**Relationships:**
- Many-to-One with USERS (user_id)
- One-to-Many with SALE_ITEMS (sale_id)

---

## 6. SALE_ITEMS Table

**Purpose:** Individual items within each sale

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| sale_item_id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique line item ID |
| sale_id | INT | FOREIGN KEY → SALES | Parent sale |
| product_id | INT | FOREIGN KEY → PRODUCTS | Product sold |
| product_name | VARCHAR(200) | NOT NULL | Product name snapshot |
| cup_size | ENUM('12oz','16oz','none') | DEFAULT 'none' | Cup size sold |
| quantity | INT | NOT NULL | Quantity sold |
| unit_price | DECIMAL(10,2) | NOT NULL | Price per unit |
| subtotal | DECIMAL(10,2) | NOT NULL | Line item total |

**Relationships:**
- Many-to-One with SALES (sale_id)
- Many-to-One with PRODUCTS (product_id)

---

## 7. STOCK_MOVEMENTS Table

**Purpose:** Inventory movement tracking

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| movement_id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique movement ID |
| product_id | INT | FOREIGN KEY → PRODUCTS | Affected product |
| movement_type | ENUM('in','out','adjustment') | NOT NULL | Movement type |
| quantity | INT | NOT NULL | Quantity moved |
| reference | VARCHAR(100) | NULLABLE | Reference number |
| notes | TEXT | NULLABLE | Movement notes |
| user_id | INT | FOREIGN KEY → USERS | User who recorded |
| movement_date | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Movement date |

**Relationships:**
- Many-to-One with PRODUCTS (product_id)
- Many-to-One with USERS (user_id)

---

## Entity Relationship Summary

### Primary Relationships
1. **USERS** ↔ **SALES**: One user processes many sales
2. **USERS** ↔ **STOCK_MOVEMENTS**: One user records many stock movements
3. **CATEGORIES** ↔ **PRODUCTS**: One category contains many products
4. **PRODUCTS** ↔ **COFFEE_CUP_SIZES**: One product has multiple cup size prices
5. **PRODUCTS** ↔ **SALE_ITEMS**: One product sold in many sale items
6. **PRODUCTS** ↔ **STOCK_MOVEMENTS**: One product tracked in many movements
7. **SALES** ↔ **SALE_ITEMS**: One sale contains many items

### Data Flow
1. **Product Management**: Categories → Products → Cup Sizes
2. **Sales Process**: Users → Sales → Sale Items → Products
3. **Inventory Tracking**: Products ↔ Stock Movements ↔ Users

### Key Constraints
- **Referential Integrity**: All foreign keys maintain data consistency
- **Data Validation**: Enums restrict values to valid options
- **Audit Trail**: Timestamps track creation and updates
- **Uniqueness**: Product codes and invoice numbers are unique

---

## Business Logic Rules

1. **Stock Management**
   - Stock quantity cannot be negative
   - Low stock alerts when quantity < reorder_level
   - All stock changes must be recorded in STOCK_MOVEMENTS

2. **Sales Validation**
   - Sale items must reference valid products
   - Total amount = subtotal + tax - discount
   - Change amount = amount_paid - total_amount

3. **User Permissions**
   - Admin: Full system access
   - Cashier: Sales and basic inventory view only

4. **Product Pricing**
   - Coffee products can have multiple cup sizes
   - Cup size pricing = base price + price_adjustment
   - Selling price must be >= cost_price

---

## Index Recommendations

```sql
-- Performance indexes
CREATE INDEX idx_products_category ON products(category_id);
CREATE INDEX idx_products_status ON products(status);
CREATE INDEX idx_sales_date ON sales(sale_date);
CREATE INDEX idx_sales_user ON sales(user_id);
CREATE INDEX idx_sale_items_sale ON sale_items(sale_id);
CREATE INDEX idx_sale_items_product ON sale_items(product_id);
CREATE INDEX idx_stock_movements_product ON stock_movements(product_id);
CREATE INDEX idx_stock_movements_date ON stock_movements(movement_date);
```

This complete ERD provides the foundation for a robust POS and inventory management system.
