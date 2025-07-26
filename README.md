# Callie Jewelry Inventory Management System

<p align="center">
<img src="https://img.shields.io/badge/Laravel-12.0-FF2D20?style=for-the-badge&logo=laravel" alt="Laravel 12.0">
<img src="https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php" alt="PHP 8.2+">
<img src="https://img.shields.io/badge/Filament-3.3-F59E0B?style=for-the-badge" alt="Filament 3.3">
<img src="https://img.shields.io/badge/Status-In_Development-yellow?style=for-the-badge" alt="Development Status">
</p>

## 📋 Table of Contents
- [Overview](#overview)
- [Current Implementation Status](#current-implementation-status)
- [Missing Core Features](#missing-core-features)
- [User Stories & Requirements](#user-stories--requirements)
- [Technical Stack](#technical-stack)
- [Database Schema](#database-schema)
- [Installation & Setup](#installation--setup)
- [Role-Based Access Control](#role-based-access-control)
- [API Documentation](#api-documentation)
- [Development Roadmap](#development-roadmap)
- [Contributing](#contributing)

## 🎯 Overview

Callie is a comprehensive **Jewelry Inventory Management System** designed specifically for businesses selling on multiple platforms like TikTok Shop and Shopee. The system provides advanced inventory tracking, multi-platform sales management, automated reorder suggestions, and seamless Excel integration.

### Key Business Objectives
- **Multi-Platform Inventory Tracking** - Manage stock across TikTok Shop and Shopee
- **Smart Reorder Management** - AI-driven suggestions based on sales velocity
- **Role-Based Access Control** - Owner and Staff permission levels
- **Excel Integration** - Bulk import/export with validation
- **Mobile-First Design** - Responsive UI for on-the-go management

## ✅ Current Implementation Status

### Implemented Features
- [x] **Laravel Framework** (v12.0) with modern PHP 8.2+ support
- [x] **Filament Admin Panel** (v3.3) for beautiful UI
- [x] **User Authentication** with Laravel's built-in system
- [x] **Basic Role System** using Spatie Laravel Permission
- [x] **Excel Package Integration** (Maatwebsite Excel v3.1)
- [x] **Database Foundation** with users, cache, and job tables

### Current Database Structure
```sql
-- Existing Tables
users (id, name, email, password, timestamps)
roles (id, name, guard_name, timestamps)
permissions (id, name, guard_name, timestamps)
role_has_permissions (permission_id, role_id)
model_has_roles (role_id, model_type, model_id)
```

### Current Permissions
```php
// Existing Permissions in RolePermissionSeeder
'view inventory'    // View inventory items
'edit inventory'    // Modify inventory items
'manage users'      // User management (admin only)
'import inventory'  // Import Excel files (admin only)

// Current Roles
'admin' - Full access to all permissions
'staff' - Limited to view and edit inventory
```

## 🚀 Missing Core Features

### 1. **Product & Inventory Management**
- [ ] Product model with SKU, barcode, pricing
- [ ] Category management (earrings, necklaces, rings)
- [ ] Stock level tracking with real-time updates
- [ ] Discontinued item flagging and management
- [ ] Product variants (color, size, material)

### 2. **Multi-Platform Integration**
- [ ] Platform model (TikTok Shop, Shopee)
- [ ] Platform-specific product listings
- [ ] Cross-platform inventory synchronization
- [ ] Platform-specific pricing management
- [ ] Order import from platforms

### 3. **SKU & Barcode System**
- [ ] Automatic SKU generation (EAR-001, RNG-002-BLK)
- [ ] Barcode generation and printing
- [ ] QR code support for mobile scanning
- [ ] Batch barcode printing functionality
- [ ] SKU validation and uniqueness checks

### 4. **Stock Alert & Notification System**
- [ ] Configurable minimum stock thresholds
- [ ] Email notifications for low stock
- [ ] Dashboard alert widgets
- [ ] SMS notifications (optional)
- [ ] Automated reorder suggestions

### 5. **Sales Velocity & Analytics**
- [ ] Sales tracking and velocity calculation
- [ ] Demand forecasting algorithms
- [ ] Seasonal trend analysis
- [ ] Platform performance comparison
- [ ] Revenue analytics dashboard

### 6. **Advanced Filtering & Search**
- [ ] Zalora-style filter interface
- [ ] Multi-criteria filtering (category + platform + stock)
- [ ] Advanced search with autocomplete
- [ ] Saved filter presets
- [ ] Quick action filters

### 7. **Excel Import/Export Enhancement**
- [ ] Template generation for imports
- [ ] Data validation with detailed error reporting
- [ ] Preview before import confirmation
- [ ] Audit trail for all imports
- [ ] Scheduled automated exports

### 8. **Report Generation**
- [ ] TikTok Shop format compatibility
- [ ] PDF/Word export capabilities
- [ ] Custom report builder
- [ ] Scheduled report delivery
- [ ] Print-optimized layouts

### 9. **Mobile & UX Improvements**
- [ ] Progressive Web App (PWA) capabilities
- [ ] Mobile barcode scanning
- [ ] Offline data synchronization
- [ ] Touch-optimized interfaces
- [ ] Dark mode support

## 📚 User Stories & Requirements

### 1. **Multi-Platform Inventory Tracking**
**User Story:** As a business owner, I want to filter inventory by sales platform (TikTok Shop or Shopee) to track stock movement per platform.

**Acceptance Criteria:**
- Inventory records include source field (TikTok/Shopee)
- UI filters for platform-specific views
- Export reports reflect platform distinction
- Cross-platform stock synchronization

### 2. **Product Categorization with Advanced Filters**
**User Story:** As a user, I want Zalora-style filtering by category, price, stock status, and platform.

**Acceptance Criteria:**
- Product categories: earrings, necklaces, rings, bracelets
- Combinable filters (Category + Platform + Stock Level)
- Filter persistence across sessions
- Quick filter shortcuts

### 3. **Low Stock Alert System**
**User Story:** As a business owner, I want alerts when stock falls below thresholds.

**Acceptance Criteria:**
- Configurable minimum stock per product
- Email and dashboard notifications
- Special low-stock dashboard view
- Snooze functionality for alerts

### 4. **Reorder Suggestions**
**User Story:** As a store manager, I want AI-driven reorder suggestions based on sales data.

**Acceptance Criteria:**
- Sales velocity calculation (daily/weekly)
- "Reorder Suggested" status display
- Manual threshold adjustment per product
- Seasonal demand consideration

### 5. **Discontinued Item Management**
**User Story:** As a business owner, I want to mark items as discontinued to exclude them from active operations.

**Acceptance Criteria:**
- Discontinued flag in database
- Hidden from active inventory views
- Visible in historical reports
- Manual reactivation capability

### 6. **SKU & Barcode Generation**
**User Story:** As a system user, I want automatic SKU and barcode generation with scanning support.

**Acceptance Criteria:**
- Auto-generated unique SKUs (EAR-001, RNG-002-BLK)
- Printable barcodes from SKU
- Variant SKU handling with suffixes
- Manual SKU override capability

### 7. **Excel Bulk Operations**
**User Story:** As inventory staff, I want to bulk update via Excel upload with validation.

**Acceptance Criteria:**
- Accept .xlsx/.csv with predefined format
- Validation before import (mandatory fields, valid SKUs)
- Partial import support (skip invalid rows)
- Preview step before finalizing
- Detailed audit trail logging

### 8. **Report Generation & Export**
**User Story:** As a business owner, I want to export data in Excel, Word, and PDF formats.

**Acceptance Criteria:**
- Multiple export formats (.xlsx, .docx, .pdf)
- Include: SKU, Name, Category, Stock, Price, Source, Status
- Export filtered views
- Scheduled automated reports

### 9. **TikTok Shop Compatible Reports**
**User Story:** As staff, I want printable reports matching TikTok Shop format.

**Acceptance Criteria:**
- Reports include: Order ID, Product Name, SKU, Quantity, Source
- Layout mirrors TikTok's format
- Print-optimized styling
- Batch printing support

### 10. **Enhanced Role-Based Access**
**User Story:** As an owner, I want granular permission control for different user types.

**Roles & Permissions:**
- **Owner**: Full system access, user management, all reports
- **Staff**: Product updates, Excel imports, limited reporting
- **Viewer**: Read-only access to inventory data

## 🛠 Technical Stack

### Backend
- **Laravel Framework** 12.0
- **PHP** 8.2+
- **MySQL** 8.0+ or PostgreSQL 13+
- **Redis** (caching and queues)

### Frontend & UI
- **Filament** 3.3 (Admin Panel)
- **Alpine.js** (JavaScript framework)
- **Tailwind CSS** (Styling)
- **Livewire** (Real-time updates)

### Packages & Dependencies
```json
{
    "filament/filament": "^3.3",
    "spatie/laravel-permission": "^6.21",
    "maatwebsite/excel": "^3.1",
    "barryvdh/laravel-dompdf": "^2.0",
    "milon/barcode": "^10.0",
    "laravel/horizon": "^5.24",
    "spatie/laravel-backup": "^8.8"
}
```

## 🗄 Database Schema

### Required Tables & Relationships

```sql
-- Products Table
CREATE TABLE products (
    id BIGINT UNSIGNED PRIMARY KEY,
    sku VARCHAR(50) UNIQUE NOT NULL,
    barcode VARCHAR(100) UNIQUE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category_id BIGINT UNSIGNED,
    current_stock INT DEFAULT 0,
    min_stock_level INT DEFAULT 0,
    price DECIMAL(10,2),
    cost_price DECIMAL(10,2),
    is_discontinued BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Categories Table
CREATE TABLE categories (
    id BIGINT UNSIGNED PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE,
    description TEXT,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Platforms Table
CREATE TABLE platforms (
    id BIGINT UNSIGNED PRIMARY KEY,
    name VARCHAR(50) NOT NULL, -- TikTok Shop, Shopee
    slug VARCHAR(50) UNIQUE,
    api_endpoint VARCHAR(255),
    configuration JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Product Platform Mapping
CREATE TABLE product_platforms (
    id BIGINT UNSIGNED PRIMARY KEY,
    product_id BIGINT UNSIGNED,
    platform_id BIGINT UNSIGNED,
    platform_sku VARCHAR(100),
    platform_price DECIMAL(10,2),
    is_active BOOLEAN DEFAULT TRUE,
    
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (platform_id) REFERENCES platforms(id)
);

-- Stock Movements Table
CREATE TABLE stock_movements (
    id BIGINT UNSIGNED PRIMARY KEY,
    product_id BIGINT UNSIGNED,
    platform_id BIGINT UNSIGNED NULL,
    movement_type ENUM('sale', 'restock', 'adjustment', 'return'),
    quantity_change INT NOT NULL, -- Positive for additions, negative for sales
    reference_number VARCHAR(100), -- Order ID, PO number, etc.
    notes TEXT,
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP,
    
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (platform_id) REFERENCES platforms(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Sales Data for Velocity Calculation
CREATE TABLE sales_data (
    id BIGINT UNSIGNED PRIMARY KEY,
    product_id BIGINT UNSIGNED,
    platform_id BIGINT UNSIGNED,
    sale_date DATE,
    quantity_sold INT,
    revenue DECIMAL(10,2),
    
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (platform_id) REFERENCES platforms(id),
    
    UNIQUE KEY unique_daily_sale (product_id, platform_id, sale_date)
);

-- Stock Alerts Table
CREATE TABLE stock_alerts (
    id BIGINT UNSIGNED PRIMARY KEY,
    product_id BIGINT UNSIGNED,
    alert_type ENUM('low_stock', 'out_of_stock', 'reorder_suggested'),
    threshold_value INT,
    current_stock INT,
    is_acknowledged BOOLEAN DEFAULT FALSE,
    acknowledged_by BIGINT UNSIGNED NULL,
    acknowledged_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (acknowledged_by) REFERENCES users(id)
);
```

## ⚙ Installation & Setup

### Prerequisites
- PHP 8.2 or higher
- Composer
- Node.js & NPM
- MySQL 8.0+ or PostgreSQL 13+
- Redis (optional, for caching)

### Installation Steps

```bash
# Clone the repository
git clone https://github.com/your-username/callie-inventory.git
cd callie-inventory

# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Configure database in .env file
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=callie_inventory
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Run migrations and seeders
php artisan migrate
php artisan db:seed

# Build frontend assets
npm run build

# Create storage symlink
php artisan storage:link

# Start the development server
php artisan serve
```

### Default Admin User
After seeding, you can log in with:
- **Email**: admin@callie.com
- **Password**: password
- **Role**: Owner (full access)

## 🔐 Role-Based Access Control

### Permission Structure

```php
// Core Permissions
'products.view'         // View products
'products.create'       // Create new products
'products.edit'         // Edit product details
'products.delete'       // Delete products
'products.import'       // Import from Excel
'products.export'       // Export to Excel/PDF

// Category Management
'categories.manage'     // Manage product categories

// Platform Management
'platforms.manage'      // Manage sales platforms

// Stock Management
'stock.view'           // View stock levels
'stock.adjust'         // Adjust stock levels
'stock.alerts'         // Manage stock alerts

// Reporting
'reports.view'         // View reports
'reports.export'       // Export reports
'reports.schedule'     // Schedule automated reports

// User Management
'users.manage'         // Manage user accounts
'roles.manage'         // Manage roles and permissions

// System
'system.settings'      // System configuration
'system.backup'        // Database backup/restore
```

### Role Definitions

#### Owner Role
- Complete system access
- User and role management
- System configuration
- All product and inventory operations
- Advanced reporting and analytics

#### Staff Role
- Product view and edit (cannot delete)
- Stock adjustments and imports
- Basic reporting
- No user management access

#### Viewer Role (Future)
- Read-only access to inventory
- Basic reporting only
- No modification capabilities

## 📖 API Documentation

### RESTful API Endpoints

```php
// Products API
GET    /api/products              // List products with filtering
POST   /api/products              // Create new product
GET    /api/products/{id}         // Get product details
PUT    /api/products/{id}         // Update product
DELETE /api/products/{id}         // Delete product

// Stock Management API
GET    /api/products/{id}/stock   // Get stock history
POST   /api/products/{id}/stock   // Adjust stock level

// Categories API
GET    /api/categories            // List categories
POST   /api/categories            // Create category
PUT    /api/categories/{id}       // Update category

// Platforms API
GET    /api/platforms             // List platforms
POST   /api/platforms             // Create platform

// Reports API
GET    /api/reports/inventory     // Inventory report
GET    /api/reports/sales         // Sales report
GET    /api/reports/low-stock     // Low stock report

// Import/Export API
POST   /api/import/products       // Import products from Excel
GET    /api/export/products       // Export products to Excel
```

## 🛣 Development Roadmap

### Phase 1: Core Foundation (Weeks 1-2)
- [ ] Create Product, Category, Platform models
- [ ] Implement database migrations
- [ ] Update permission system
- [ ] Basic CRUD operations in Filament

### Phase 2: Inventory Management (Weeks 3-4)
- [ ] SKU generation service
- [ ] Stock movement tracking
- [ ] Barcode generation
- [ ] Basic filtering and search

### Phase 3: Business Logic (Weeks 5-6)
- [ ] Stock alert system
- [ ] Sales velocity calculations
- [ ] Reorder suggestions
- [ ] Multi-platform synchronization

### Phase 4: Advanced Features (Weeks 7-8)
- [ ] Excel import/export with validation
- [ ] Advanced filtering (Zalora-style)
- [ ] Report generation system
- [ ] Email notification system

### Phase 5: UI/UX Enhancement (Weeks 9-10)
- [ ] Mobile responsiveness
- [ ] Dashboard widgets
- [ ] Print-friendly reports
- [ ] Performance optimization

### Phase 6: Integration & Testing (Weeks 11-12)
- [ ] Platform API integrations
- [ ] Comprehensive testing
- [ ] Documentation completion
- [ ] Deployment preparation

## 🤝 Contributing

### Development Guidelines
1. Follow PSR-12 coding standards
2. Write comprehensive tests for new features
3. Update documentation for any API changes
4. Use conventional commit messages
5. Create feature branches from `develop`

### Testing
```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature

# Generate coverage report
php artisan test --coverage
```

### Code Quality
```bash
# PHP CS Fixer
composer fix-style

# PHPStan analysis
composer analyse

# Laravel Pint
./vendor/bin/pint
```

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 📞 Support

For support and questions:
- **Documentation**: [Wiki](https://github.com/your-username/callie-inventory/wiki)
- **Issues**: [GitHub Issues](https://github.com/your-username/callie-inventory/issues)
- **Discussions**: [GitHub Discussions](https://github.com/your-username/callie-inventory/discussions)

---

**Last Updated**: January 2025  
**Version**: 1.0.0-dev  
**Status**: In Active Development
