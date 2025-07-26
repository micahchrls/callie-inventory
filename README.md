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
- [Implementation Guide: Product Categorization with Advanced Filters](#implementation-guide-product-categorization-with-advanced-filters)
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

## 🛠️ Implementation Guide: Product Categorization with Advanced Filters

### Overview
This guide provides a step-by-step process to implement Zalora-style filtering with categories, price ranges, stock status, and platform filters with session persistence.

### 📋 Implementation Steps

#### Step 1: Database Schema Updates

**1.1 Create Product Categories Migration**
```bash
php artisan make:migration add_category_to_products_table
```

```php
// Migration file
Schema::table('products', function (Blueprint $table) {
    $table->enum('category', ['earrings', 'necklaces', 'rings', 'bracelets'])
          ->after('name')
          ->index();
});
```

**1.2 Create Filter Preferences Table**
```bash
php artisan make:migration create_user_filter_preferences_table
```

```php
// Migration for session persistence
Schema::create('user_filter_preferences', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->json('filters'); // Store filter state as JSON
    $table->string('page_context')->default('products'); // products, dashboard, etc.
    $table->timestamps();
    
    $table->unique(['user_id', 'page_context']);
});
```

#### Step 2: Model Updates

**2.1 Update Product Model**
```php
// app/Models/Product.php
class Product extends Model
{
    protected $fillable = [
        'name', 'category', 'sku', 'price', 'stock_quantity', 
        'platform_id', 'description', 'image_url'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock_quantity' => 'integer',
    ];

    // Category scopes
    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock_quantity', '>', 0);
    }

    public function scopeLowStock($query, $threshold = 10)
    {
        return $query->where('stock_quantity', '<=', $threshold)
                    ->where('stock_quantity', '>', 0);
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('stock_quantity', 0);
    }

    public function scopePriceRange($query, $min, $max)
    {
        return $query->whereBetween('price', [$min, $max]);
    }

    // Category constants
    public static function getCategories(): array
    {
        return [
            'earrings' => 'Earrings',
            'necklaces' => 'Necklaces',
            'rings' => 'Rings',
            'bracelets' => 'Bracelets',
        ];
    }
}
```

**2.2 Create UserFilterPreference Model**
```php
// app/Models/UserFilterPreference.php
class UserFilterPreference extends Model
{
    protected $fillable = ['user_id', 'filters', 'page_context'];
    
    protected $casts = [
        'filters' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

#### Step 3: Filament Resource Updates

**3.1 Update ProductResource with Advanced Filters**
```php
// app/Filament/Resources/ProductResource.php
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;

class ProductResource extends Resource
{
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('category')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'earrings' => 'success',
                        'necklaces' => 'info',
                        'rings' => 'warning',
                        'bracelets' => 'danger',
                    }),
                TextColumn::make('sku')->searchable(),
                TextColumn::make('price')->money('USD')->sortable(),
                TextColumn::make('stock_quantity')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state > 10 => 'success',
                        $state > 0 => 'warning',
                        default => 'danger',
                    }),
                TextColumn::make('platform.name'),
            ])
            ->filters([
                // Category Filter
                SelectFilter::make('category')
                    ->label('Category')
                    ->options(Product::getCategories())
                    ->multiple()
                    ->preload(),

                // Platform Filter
                SelectFilter::make('platform')
                    ->relationship('platform', 'name')
                    ->multiple()
                    ->preload(),

                // Stock Status Filter
                SelectFilter::make('stock_status')
                    ->label('Stock Status')
                    ->options([
                        'in_stock' => 'In Stock',
                        'low_stock' => 'Low Stock (≤10)',
                        'out_of_stock' => 'Out of Stock',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            in_array('in_stock', $data['values'] ?? []),
                            fn (Builder $query): Builder => $query->orWhere('stock_quantity', '>', 10),
                        )->when(
                            in_array('low_stock', $data['values'] ?? []),
                            fn (Builder $query): Builder => $query->orWhere(function ($q) {
                                $q->where('stock_quantity', '<=', 10)
                                  ->where('stock_quantity', '>', 0);
                            }),
                        )->when(
                            in_array('out_of_stock', $data['values'] ?? []),
                            fn (Builder $query): Builder => $query->orWhere('stock_quantity', 0),
                        );
                    })
                    ->multiple(),

                // Price Range Filter
                Filter::make('price_range')
                    ->form([
                        Select::make('price_range')
                            ->label('Price Range')
                            ->options([
                                '0-25' => '$0 - $25',
                                '25-50' => '$25 - $50',
                                '50-100' => '$50 - $100',
                                '100-250' => '$100 - $250',
                                '250+' => '$250+',
                            ])
                            ->multiple(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $ranges = $data['price_range'] ?? [];
                        
                        return $query->where(function ($query) use ($ranges) {
                            foreach ($ranges as $range) {
                                match ($range) {
                                    '0-25' => $query->orWhereBetween('price', [0, 25]),
                                    '25-50' => $query->orWhereBetween('price', [25, 50]),
                                    '50-100' => $query->orWhereBetween('price', [50, 100]),
                                    '100-250' => $query->orWhereBetween('price', [100, 250]),
                                    '250+' => $query->orWhere('price', '>', 250),
                                };
                            }
                        });
                    }),
            ])
            ->persistFiltersInSession()
            ->filtersFormColumns(2)
            ->defaultSort('created_at', 'desc');
    }
}
```

#### Step 4: Quick Filter Shortcuts

**4.1 Create Custom Filter Shortcuts Widget**
```php
// app/Filament/Widgets/QuickFiltersWidget.php
use Filament\Widgets\Widget;

class QuickFiltersWidget extends Widget
{
    protected static string $view = 'filament.widgets.quick-filters';
    protected int | string | array $columnSpan = 'full';

    public function getViewData(): array
    {
        return [
            'categories' => Product::getCategories(),
            'stockCounts' => [
                'in_stock' => Product::inStock()->count(),
                'low_stock' => Product::lowStock()->count(),
                'out_of_stock' => Product::outOfStock()->count(),
            ],
        ];
    }
}
```

**4.2 Create Widget View**
```blade
{{-- resources/views/filament/widgets/quick-filters.blade.php --}}
<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Quick Filters
        </x-slot>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach($categories as $key => $label)
                <x-filament::button
                    color="gray"
                    size="sm"
                    wire:click="$dispatch('filter-products', { category: '{{ $key }}' })"
                >
                    {{ $label }}
                </x-filament::button>
            @endforeach
        </div>

        <div class="grid grid-cols-3 gap-4 mt-4">
            <x-filament::button
                color="success"
                size="sm"
                wire:click="$dispatch('filter-products', { stock_status: 'in_stock' })"
            >
                In Stock ({{ $stockCounts['in_stock'] }})
            </x-filament::button>
            
            <x-filament::button
                color="warning"
                size="sm"
                wire:click="$dispatch('filter-products', { stock_status: 'low_stock' })"
            >
                Low Stock ({{ $stockCounts['low_stock'] }})
            </x-filament::button>
            
            <x-filament::button
                color="danger"
                size="sm"
                wire:click="$dispatch('filter-products', { stock_status: 'out_of_stock' })"
            >
                Out of Stock ({{ $stockCounts['out_of_stock'] }})
            </x-filament::button>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
```

#### Step 5: Enhanced Session Persistence

**5.1 Create Filter Persistence Service**
```php
// app/Services/FilterPersistenceService.php
class FilterPersistenceService
{
    public function saveFilters(int $userId, array $filters, string $context = 'products'): void
    {
        UserFilterPreference::updateOrCreate(
            ['user_id' => $userId, 'page_context' => $context],
            ['filters' => $filters]
        );
    }

    public function getFilters(int $userId, string $context = 'products'): array
    {
        $preference = UserFilterPreference::where('user_id', $userId)
            ->where('page_context', $context)
            ->first();

        return $preference?->filters ?? [];
    }

    public function clearFilters(int $userId, string $context = 'products'): void
    {
        UserFilterPreference::where('user_id', $userId)
            ->where('page_context', $context)
            ->delete();
    }
}
```

#### Step 6: Testing Implementation

**6.1 Create Feature Tests**
```php
// tests/Feature/ProductFilterTest.php
class ProductFilterTest extends TestCase
{
    public function test_can_filter_products_by_category()
    {
        // Create test products
        Product::factory()->create(['category' => 'earrings']);
        Product::factory()->create(['category' => 'necklaces']);

        // Test filtering
        $response = $this->get('/admin/products?tableFilters[category][values][0]=earrings');
        $response->assertStatus(200);
    }

    public function test_can_combine_multiple_filters()
    {
        // Test combinable filters
        $product = Product::factory()->create([
            'category' => 'rings',
            'price' => 75,
            'stock_quantity' => 5
        ]);

        $response = $this->get('/admin/products?' . http_build_query([
            'tableFilters' => [
                'category' => ['values' => ['rings']],
                'price_range' => ['price_range' => ['50-100']],
                'stock_status' => ['values' => ['low_stock']]
            ]
        ]));

        $response->assertStatus(200);
    }

    public function test_filter_persistence_across_sessions()
    {
        $user = User::factory()->create();

        // Save filter preferences
        $service = new FilterPersistenceService();
        $filters = ['category' => ['values' => ['earrings']]];
        $service->saveFilters($user->id, $filters);

        // Retrieve and verify
        $retrieved = $service->getFilters($user->id);
        $this->assertEquals($filters, $retrieved);
    }
}
```

#### Step 7: Performance Optimization

**7.1 Add Database Indexes**
```php
// In migration file
Schema::table('products', function (Blueprint $table) {
    $table->index(['category', 'stock_quantity']);
    $table->index(['price', 'platform_id']);
    $table->index(['created_at', 'category']);
});
```

**7.2 Add Caching for Filter Counts**
```php
// In ProductResource or service
use Illuminate\Support\Facades\Cache;

public function getCachedFilterCounts(): array
{
    return Cache::remember('product_filter_counts', 300, function () {
        return [
            'categories' => Product::selectRaw('category, COUNT(*) as count')
                ->groupBy('category')
                ->pluck('count', 'category')
                ->toArray(),
            'stock_status' => [
                'in_stock' => Product::inStock()->count(),
                'low_stock' => Product::lowStock()->count(),
                'out_of_stock' => Product::outOfStock()->count(),
            ],
        ];
    });
}
```

### 🎯 Implementation Checklist

- [ ] **Database Setup**
  - [ ] Run category migration
  - [ ] Run filter preferences migration
  - [ ] Add database indexes

- [ ] **Model Updates**
  - [ ] Update Product model with scopes
  - [ ] Create UserFilterPreference model
  - [ ] Add relationships

- [ ] **Filament Integration**
  - [ ] Update ProductResource with filters
  - [ ] Create QuickFiltersWidget
  - [ ] Enable filter persistence

- [ ] **Advanced Features**
  - [ ] Implement FilterPersistenceService
  - [ ] Create filter shortcuts
  - [ ] Add caching optimization

- [ ] **Testing**
  - [ ] Write feature tests
  - [ ] Test filter combinations
  - [ ] Verify session persistence

- [ ] **UI/UX Enhancements**
  - [ ] Style filter badges
  - [ ] Add filter count indicators
  - [ ] Implement mobile-responsive design

### 📋 Expected Results

After implementation, users will have:
- ✅ **Category Filtering**: Filter by earrings, necklaces, rings, bracelets
- ✅ **Combinable Filters**: Mix category + platform + stock + price filters
- ✅ **Session Persistence**: Filters maintained across browser sessions
- ✅ **Quick Shortcuts**: One-click filter buttons for common searches
- ✅ **Performance**: Optimized queries with proper indexing
- ✅ **Mobile-Friendly**: Responsive filter interface

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
