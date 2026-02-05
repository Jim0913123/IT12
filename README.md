# POS & Inventory Management System

A complete Point of Sale and Inventory Management System built with PHP and MySQL.

## Features

### 🎯 Core Functionality
- **Point of Sale (POS)** - Fast and intuitive checkout process
- **Inventory Management** - Track stock levels and movements
- **Product Management** - Add, edit, and organize products
- **Sales History** - Complete transaction records
- **Categories** - Organize products by categories
- **User Management** - Admin and cashier roles
- **Reports** - Sales and inventory analytics

### 💡 Key Highlights
- Modern, responsive UI design
- Real-time stock updates
- Low stock alerts
- Receipt generation and printing
- Transaction search and filtering
- Stock movement tracking
- Multi-payment methods (Cash, Card, Online)
- Tax and discount calculations

## Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript
- **Design**: Custom CSS with modern aesthetics

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Web browser

### Step-by-Step Installation

1. **Download/Clone the Project**
   ```bash
   # Place all files in your web server directory
   # For XAMPP: C:/xampp/htdocs/pos-inventory-system
   # For WAMP: C:/wamp64/www/pos-inventory-system
   ```

2. **Create Database**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Click "New" to create a new database
   - Name it: `pos_inventory`
   - Click "Create"

3. **Import Database Schema**
   - Select the `pos_inventory` database
   - Click "Import" tab
   - Click "Choose File" and select `database.sql`
   - Click "Go" to import

4. **Configure Database Connection**
   - Open `includes/config.php`
   - Update the following if needed:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     define('DB_NAME', 'pos_inventory');
     ```

5. **Access the System**
   - Open your web browser
   - Navigate to: `http://localhost/pos-inventory-system`
   - You will be redirected to the login page

## Default Login Credentials

### Admin Account
- **Username**: admin
- **Password**: admin123
- **Access**: Full system access

### Cashier Account
- **Username**: cashier
- **Password**: admin123
- **Access**: POS and limited features

> ⚠️ **Important**: Change these default passwords after first login!

## System Structure

```
pos-inventory-system/
├── api/
│   ├── get-sale-details.php
│   └── process-sale.php
├── css/
│   └── style.css
├── includes/
│   ├── auth.php
│   ├── config.php
│   └── sidebar.php
├── js/
│   └── pos.js
├── categories.php
├── database.sql
├── index.php (Dashboard)
├── inventory.php
├── login.php
├── logout.php
├── pos.php
├── products.php
├── receipt.php
├── sales.php
└── README.md
```

## Usage Guide

### For Cashiers

1. **Login** with cashier credentials
2. **Navigate to POS** to start making sales
3. **Add products** by clicking on product cards
4. **Adjust quantities** using +/- buttons
5. **Checkout** when ready
6. **Enter payment details** and complete sale
7. **Print receipt** for customer

### For Administrators

1. **Dashboard** - View overview and statistics
2. **Products** - Add, edit, delete products
3. **Inventory** - Adjust stock levels
4. **Categories** - Manage product categories
5. **Sales History** - View all transactions
6. **Reports** - Generate sales reports (if implemented)
7. **Users** - Manage system users (if implemented)

## Database Schema

### Main Tables
- **users** - System users and authentication
- **categories** - Product categories
- **products** - Product information and stock
- **sales** - Sales transactions
- **sale_items** - Individual sale items
- **stock_movements** - Inventory movement history

## Security Features

- Password hashing using PHP's `password_hash()`
- Session-based authentication
- SQL injection prevention using prepared statements
- Role-based access control (Admin/Cashier)
- XSS protection using `htmlspecialchars()`

## Browser Compatibility

- Chrome (Recommended)
- Firefox
- Safari
- Edge
- Opera

## Troubleshooting

### Common Issues

**"Connection failed" error**
- Check if MySQL is running
- Verify database credentials in `config.php`
- Ensure database exists

**Login not working**
- Clear browser cache
- Check if sessions are enabled in PHP
- Verify user exists in database

**Products not showing**
- Check database connection
- Ensure products table has data
- Check product status is 'active'

**Receipt not printing**
- Enable pop-ups in browser
- Check printer settings
- Use print preview first

## Customization

### Changing Colors/Theme
Edit `css/style.css` and modify CSS variables:
```css
:root {
    --primary: #6366f1;
    --success: #10b981;
    --danger: #ef4444;
    /* ... */
}
```

### Adding New Features
The system is modular and easy to extend:
- Add new pages in root directory
- Create API endpoints in `api/` folder
- Update sidebar menu in `includes/sidebar.php`

## Support

For issues or questions:
1. Check the troubleshooting section
2. Review PHP error logs
3. Check browser console for JavaScript errors
4. Verify database integrity

## License

This project is provided as-is for educational and commercial use.

## Credits

Developed using modern web technologies and best practices.

---

**Version**: 1.0.0  
**Last Updated**: February 2026

## Next Steps After Installation

1. ✅ Login with default credentials
2. ✅ Change default passwords
3. ✅ Add your product categories
4. ✅ Add your products
5. ✅ Configure tax rates if needed
6. ✅ Start making sales!

Enjoy your new POS & Inventory System! 🎉
