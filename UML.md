# POS Inventory System - UML Class Diagram

## Class Diagram

```mermaid
classDiagram
    class User {
        -int userId
        -string username
        -string password
        -string fullName
        -string role
        -datetime createdAt
        
        +login(username, password)
        +logout()
        +updateProfile()
        +isAdmin() boolean
        +isCashier() boolean
    }
    
    class Category {
        -int categoryId
        -string categoryName
        -string description
        -datetime createdAt
        
        +create()
        +update()
        +delete()
        +getProducts() Product[]
    }
    
    class Product {
        -int productId
        -string productCode
        -string productName
        -int categoryId
        -string cupSize
        -string description
        -decimal costPrice
        -decimal sellingPrice
        -int stockQuantity
        -int reorderLevel
        -string barcode
        -string imageUrl
        -string status
        -datetime createdAt
        -datetime updatedAt
        
        +create()
        +update()
        +delete()
        +updateStock(quantity, type)
        +isLowStock() boolean
        +getCupSizes() CoffeeCupSize[]
    }
    
    class CoffeeCupSize {
        -int id
        -int productId
        -string cupSize
        -decimal priceAdjustment
        -datetime createdAt
        
        +create()
        +update()
        +delete()
        +getAdjustedPrice() decimal
    }
    
    class Sale {
        -int saleId
        -string invoiceNumber
        -int userId
        -string customerName
        -string customerPhone
        -decimal subtotal
        -decimal tax
        -decimal discount
        -decimal totalAmount
        -decimal amountPaid
        -decimal changeAmount
        -string paymentMethod
        -datetime saleDate
        
        +create()
        +calculateTotal()
        +processPayment()
        +generateReceipt()
        +getSaleItems() SaleItem[]
    }
    
    class SaleItem {
        -int saleItemId
        -int saleId
        -int productId
        -string productName
        -string cupSize
        -int quantity
        -decimal unitPrice
        -decimal subtotal
        
        +create()
        +calculateSubtotal()
        +updateStock()
    }
    
    class StockMovement {
        -int movementId
        -int productId
        -string movementType
        -int quantity
        -string reference
        -string notes
        -int userId
        -datetime movementDate
        
        +create()
        +recordStockIn()
        +recordStockOut()
        +recordAdjustment()
    }
    
    class POSController {
        -User currentUser
        -Cart cart
        
        +login(username, password)
        +logout()
        +addToCart(productId, quantity)
        +removeFromCart(productId)
        +processSale()
        +generateReceipt()
    }
    
    class InventoryController {
        -Product[] products
        
        +addProduct()
        +updateProduct()
        +deleteProduct()
        +updateStock()
        +getLowStockProducts()
        +recordStockMovement()
    }
    
    class ReportController {
        -Sale[] sales
        
        +generateSalesReport(startDate, endDate)
        +generateInventoryReport()
        +generateStockMovementReport()
        +exportToPDF()
    }
    
    User ||--o{ Sale : "processes"
    User ||--o{ StockMovement : "records"
    Category ||--o{ Product : "contains"
    Product ||--o{ CoffeeCupSize : "has"
    Product ||--o{ SaleItem : "sold_in"
    Product ||--o{ StockMovement : "tracked_in"
    Sale ||--o{ SaleItem : "contains"
    
    POSController --> User
    POSController --> Sale
    POSController --> SaleItem
    InventoryController --> Product
    InventoryController --> StockMovement
    ReportController --> Sale
    ReportController --> Product
```

## Key Classes Description

### Core Entities
- **User**: Manages user authentication and roles
- **Category**: Product categorization
- **Product**: Main inventory entity with stock tracking
- **CoffeeCupSize**: Handles size-based pricing for coffee products

### Transaction Entities
- **Sale**: Complete sales transaction
- **SaleItem**: Individual items within a sale
- **StockMovement**: Inventory movement tracking

### Controller Classes
- **POSController**: Point of Sale operations
- **InventoryController**: Inventory management
- **ReportController**: Reporting and analytics

## Key Methods

### User Management
- Authentication (login/logout)
- Role-based access control

### Product Management
- CRUD operations
- Stock level tracking
- Low stock alerts

### Sales Processing
- Cart management
- Payment processing
- Receipt generation

### Reporting
- Sales analytics
- Inventory reports
- Export functionality
