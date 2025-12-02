# Green Grocers PHP Version

Complete PHP conversion of the React frontend with organized folder structure.

## Folder Structure

```
green-php/
├── website/
│   ├── pages/          # Website pages (landing, category, place-order, dashboard)
│   └── layouts/        # Website layouts (header.php, footer.php)
├── dashboard/
│   ├── pages/          # Dashboard pages (dashboard, products, packages, orders, featured-products)
│   └── layouts/        # Dashboard layouts (header.php, footer.php)
├── auth/               # Authentication pages (login, register, admin-login, forgot, email-verification)
├── config/             # Database configuration
├── includes/           # Helper functions (auth.php, functions.php, logout.php)
└── index.php           # Main router
```

## Setup

1. Configure database in `config/database.php`
2. Start PHP server: `php -S localhost:8000` (from green-php folder)
3. Access: `http://localhost:8000`

## Default Admin
- Email: `admin@greengrocers.com`
- Password: `admin123`

## Pages

### Website Pages
- `/` - Landing page
- `/category?name=CategoryName` - Category page
- `/cart/checkout.php` - Checkout (requires login)
- `/customer/dashboard.php` - Customer dashboard (requires login)

### Dashboard Pages
- `/dashboard/pages/dashboard.php` - Admin dashboard
- `/dashboard/pages/products.php` - Manage products
- `/dashboard/pages/packages.php` - Manage packages
- `/dashboard/pages/orders.php` - Manage orders
- `/dashboard/pages/featured-products.php` - Manage featured products

### Auth Pages
- `/auth/login.php` - Customer login
- `/auth/register.php` - Customer registration
- `/auth/admin-login.php` - Admin login
- `/auth/forgot.php` - Forgot password
- `/auth/verify.php` - Email verification


