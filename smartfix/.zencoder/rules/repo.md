# SmartFix - Comprehensive Service Management Platform

## Project Overview
SmartFix is a full-featured web-based service management platform built with PHP and MySQL. It provides a comprehensive solution for managing repair services, technician bookings, product sales, and customer interactions with multi-role user management.

## Project Structure

### Core Technologies
- **Backend**: PHP 7+ with MySQLi and PDO database connections
- **Database**: MySQL (Database: `smartfix`)
- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap
- **Session Management**: PHP Sessions with 2FA support
- **File Uploads**: Product images and user avatars

### Directory Structure
```
smartfix/
├── admin/              # Admin panel and management tools
├── user/              # User dashboard and features
├── technician/        # Technician-specific functionality
├── services/          # Service-related features
├── shop/              # E-commerce functionality
├── includes/          # Common PHP includes and utilities
├── img/               # Static images and assets
├── js/                # JavaScript files
├── uploads/           # User-uploaded files (product images)
└── *.php              # Main application files
```

## Key Features

### Multi-Role System
1. **Users/Customers**
   - Service request submission
   - Order placement and tracking
   - Message communication
   - Review and rating system

2. **Technicians**
   - Service request management
   - Profile and availability management
   - Customer communication
   - Work tracking and updates

3. **Administrators**
   - Complete system oversight
   - User and technician management
   - Product and inventory management
   - Reports and analytics
   - Emergency service coordination

### Core Functionality

#### Service Management
- Service request submission and tracking
- Technician assignment (manual and auto-assign)
- Emergency service requests
- Service status updates and notifications
- Customer-technician messaging system

#### E-Commerce Platform
- Product catalog management
- Shopping cart and checkout process
- Order processing and tracking
- Payment integration support
- Inventory management

#### Communication System
- Internal messaging between users, technicians, and admins
- Email notifications with 2FA codes
- Real-time notifications
- Contact form system

#### Security Features
- Password hashing with `password_hash()`
- Two-Factor Authentication (2FA) system
- Device fingerprinting for trusted devices
- SQL injection prevention with prepared statements
- Session-based authentication

## Database Schema

### Key Tables
- `users` - User accounts and profiles
- `technicians` - Technician profiles and specializations
- `service_requests` - Service bookings and requests
- `products` - Product catalog
- `orders` & `order_items` - E-commerce orders
- `messages` - Internal communication
- `notifications` - System notifications
- `reviews` - Customer reviews and ratings
- `two_factor_codes` - 2FA authentication codes
- `trusted_devices` - Device management for 2FA

## Configuration

### Database Connection
- Host: `localhost`
- Database: `smartfix`
- User: `root`
- Password: (empty by default)
- Connection: Both PDO and MySQLi for compatibility

### File Locations
- Database config: `includes/db.php`
- 2FA implementation: `includes/TwoFactorAuth.php`
- Admin documentation: `admin/README.md`

## Development Patterns

### Code Organization
- Consistent PHP session management
- Prepared statements for database security
- Modular include system for common functionality
- Responsive design with CSS custom properties
- Dark mode support

### Common Practices
- Error handling with try-catch blocks
- Form validation and sanitization
- File upload security measures
- Mobile-responsive design
- Cross-browser compatibility

## Setup and Deployment

### Initial Setup
1. Run `setup_database.php` to create the database and tables
2. Use `fix_database.php` for database repairs and updates
3. Configure `includes/db.php` with proper database credentials
4. Ensure `uploads/` directory has write permissions
5. Run admin setup scripts if needed:
   - `admin/add_status_column.php`
   - `admin/add_service_request_columns.php`

### Key Entry Points
- `index.php` - Main homepage
- `login.php` - User authentication
- `admin/admin_dashboard_new.php` - Admin panel
- `user/dashboard.php` - User dashboard
- `technician/dashboard.php` - Technician dashboard

## Common Issues and Solutions

### Database Issues
- Missing columns: Run appropriate update scripts in admin folder
- Connection issues: Check `includes/db.php` configuration
- Table creation: Use `fix_database.php` to recreate missing tables

### File Upload Issues
- Ensure `uploads/` directory exists with proper permissions
- Check PHP file upload settings in `php.ini`

### Authentication Issues
- 2FA problems: Check email configuration in `TwoFactorAuth.php`
- Session issues: Verify PHP session configuration

## Business Logic

### Service Request Flow
1. Customer submits service request
2. System creates request in database
3. Admin or system assigns technician
4. Technician receives notification
5. Service is performed with status updates
6. Customer receives completion notification
7. Customer can leave review

### Order Processing Flow
1. Customer browses products
2. Items added to cart
3. Checkout process with customer details
4. Order created and payment processed
5. Order tracking and status updates
6. Delivery coordination

## Contact Information
- Support: info@smartfixzed.com
- Admin: admin@smartfix.com (default admin account)
- System notifications: noreply@smartfix.com

## Development Notes
- Uses both MySQLi and PDO for database operations
- Implements proper security measures including 2FA
- Mobile-first responsive design
- Comprehensive error handling and logging
- Modular architecture for easy maintenance and updates