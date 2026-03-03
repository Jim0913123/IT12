# POS Inventory System - ERD Diagram

## Entity Relationship Diagram

```mermaid
erDiagram
    USERS {
        INT user_id PK
        VARCHAR(50) username UK
        VARCHAR(255) password
        VARCHAR(100) full_name
        ENUM role
        TIMESTAMP created_at
    }
    
    CATEGORIES {
        INT category_id PK
        VARCHAR(100) category_name
        TEXT description
        TIMESTAMP created_at
    }
    
    PRODUCTS {
        INT product_id PK
        VARCHAR(50) product_code UK
        VARCHAR(200) product_name
        INT category_id FK
        ENUM cup_size
        TEXT description
        DECIMAL(10,2) cost_price
        DECIMAL(10,2) selling_price
        INT stock_quantity
        INT reorder_level
        VARCHAR(100) barcode
        VARCHAR(255) image_url
        ENUM status
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }
    
    COFFEE_CUP_SIZES {
        INT id PK
        INT product_id FK
        ENUM cup_size
        DECIMAL(10,2) price_adjustment
        TIMESTAMP created_at
    }
    
    SALES {
        INT sale_id PK
        VARCHAR(50) invoice_number UK
        INT user_id FK
        VARCHAR(100) customer_name
        VARCHAR(20) customer_phone
        DECIMAL(10,2) subtotal
        DECIMAL(10,2) tax
        DECIMAL(10,2) discount
        DECIMAL(10,2) total_amount
        DECIMAL(10,2) amount_paid
        DECIMAL(10,2) change_amount
        ENUM payment_method
        TIMESTAMP sale_date
    }
    
    SALE_ITEMS {
        INT sale_item_id PK
        INT sale_id FK
        INT product_id FK
        VARCHAR(200) product_name
        ENUM cup_size
        INT quantity
        DECIMAL(10,2) unit_price
        DECIMAL(10,2) subtotal
    }
    
    STOCK_MOVEMENTS {
        INT movement_id PK
        INT product_id FK
        ENUM movement_type
        INT quantity
        VARCHAR(100) reference
        TEXT notes
        INT user_id FK
        TIMESTAMP movement_date
    }

    USERS ||--o{ SALES : "processes"
    USERS ||--o{ STOCK_MOVEMENTS : "records"
    CATEGORIES ||--o{ PRODUCTS : "contains"
    PRODUCTS ||--o{ COFFEE_CUP_SIZES : "has"
    PRODUCTS ||--o{ SALE_ITEMS : "sold_in"
    PRODUCTS ||--o{ STOCK_MOVEMENTS : "tracked_in"
    SALES ||--o{ SALE_ITEMS : "contains"
```

## Relationships

1. **USERS → SALES**: One-to-Many (One user can process many sales)
2. **USERS → STOCK_MOVEMENTS**: One-to-Many (One user can record many stock movements)
3. **CATEGORIES → PRODUCTS**: One-to-Many (One category can have many products)
4. **PRODUCTS → COFFEE_CUP_SIZES**: One-to-Many (One product can have multiple cup size prices)
5. **PRODUCTS → SALE_ITEMS**: One-to-Many (One product can be sold in many sale items)
6. **PRODUCTS → STOCK_MOVEMENTS**: One-to-Many (One product can have many stock movements)
7. **SALES → SALE_ITEMS**: One-to-Many (One sale can have many sale items)

## Key Constraints

- **Primary Keys**: All tables have auto-increment primary keys
- **Foreign Keys**: Referential integrity maintained
- **Unique Constraints**: product_code, invoice_number
- **Enums**: role, cup_size, movement_type, payment_method, status
- **Timestamps**: created_at, updated_at for tracking
