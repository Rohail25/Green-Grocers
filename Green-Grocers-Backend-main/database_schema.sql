-- =====================================================
-- MySQL Database Schema for Green Grocers E-commerce
-- All tables in one database
-- Generated from Prisma schema files
-- =====================================================

-- Create database (uncomment if needed)
-- CREATE DATABASE IF NOT EXISTS green_grocers;
-- USE green_grocers;

-- =====================================================
-- USER SERVICE TABLES
-- =====================================================

-- Table: users (from user-service)
CREATE TABLE IF NOT EXISTS `users` (
  `id` VARCHAR(36) NOT NULL PRIMARY KEY,
  `email` VARCHAR(255) NULL,
  `phone` VARCHAR(50) NULL,
  `password` VARCHAR(255) NOT NULL,
  `platform` VARCHAR(50) NOT NULL,
  `role` VARCHAR(50) NOT NULL DEFAULT 'customer',
  `parentLogistic` VARCHAR(255) NULL,
  `isEmailConfirmed` BOOLEAN NOT NULL DEFAULT FALSE,
  `emailVerificationToken` VARCHAR(255) NULL,
  `emailVerificationExpires` DATETIME NULL,
  `verificationDocuments` JSON NOT NULL DEFAULT ('[]'),
  `isVerified` BOOLEAN NOT NULL DEFAULT FALSE,
  `documentsUploaded` BOOLEAN NOT NULL DEFAULT FALSE,
  `isAvailable` BOOLEAN NOT NULL DEFAULT FALSE,
  `firstName` VARCHAR(255) NULL,
  `lastName` VARCHAR(255) NULL,
  `profileImage` VARCHAR(500) NULL,
  `preferredVendors` JSON NOT NULL DEFAULT ('[]'),
  `addresses` JSON NOT NULL DEFAULT ('[]'),
  `vendorId` VARCHAR(255) NULL,
  `clientId` VARCHAR(255) NULL,
  `googleId` VARCHAR(255) NULL,
  `facebookId` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `users_email_platform_key` (`email`, `platform`),
  KEY `users_email_idx` (`email`),
  KEY `users_platform_idx` (`platform`),
  KEY `users_role_idx` (`role`),
  KEY `users_parentLogistic_idx` (`parentLogistic`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: vehicles (from user-service)
CREATE TABLE IF NOT EXISTS `vehicles` (
  `id` VARCHAR(36) NOT NULL PRIMARY KEY,
  `userId` VARCHAR(255) NOT NULL,
  `vehicleType` VARCHAR(255) NOT NULL,
  `vehicleModel` VARCHAR(255) NOT NULL,
  `vehicleColor` VARCHAR(255) NOT NULL,
  `plateNumber` VARCHAR(255) NOT NULL UNIQUE,
  `workHours` VARCHAR(255) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `vehicles_userId_idx` (`userId`),
  KEY `vehicles_plateNumber_idx` (`plateNumber`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- PRODUCT SERVICE TABLES
-- =====================================================

-- Table: brands (from product-service)
CREATE TABLE IF NOT EXISTS `brands` (
  `id` VARCHAR(36) NOT NULL PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `image` VARCHAR(500) NOT NULL,
  `description` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `brands_title_idx` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: categories (from product-service)
CREATE TABLE IF NOT EXISTS `categories` (
  `id` VARCHAR(36) NOT NULL PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `image` VARCHAR(500) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `categories_title_idx` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: products (from product-service)
CREATE TABLE IF NOT EXISTS `products` (
  `id` VARCHAR(36) NOT NULL PRIMARY KEY,
  `vendorId` VARCHAR(255) NULL,
  `brandId` VARCHAR(255) NULL,
  `categoryId` VARCHAR(255) NULL,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `itemSize` VARCHAR(255) NULL,
  `totalQuantityInStock` INT NOT NULL DEFAULT 0,
  `images` JSON NOT NULL DEFAULT ('[]'),
  `variants` JSON NOT NULL DEFAULT ('[]'),
  `brand` VARCHAR(255) NULL,
  `category` VARCHAR(255) NULL,
  `gender` VARCHAR(50) NULL,
  `collection` VARCHAR(255) NULL,
  `tags` JSON NOT NULL DEFAULT ('[]'),
  `retailPrice` DECIMAL(10, 2) NULL,
  `wholesalePrice` DECIMAL(10, 2) NULL,
  `minWholesaleQty` INT NULL,
  `preSalePrice` DECIMAL(10, 2) NULL,
  `preSalePeriod` JSON NULL,
  `discount` JSON NULL,
  `appliedCoupons` JSON NOT NULL DEFAULT ('[]'),
  `status` VARCHAR(50) NOT NULL DEFAULT 'active',
  `isFeatured` BOOLEAN NOT NULL DEFAULT FALSE,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `products_vendorId_idx` (`vendorId`),
  KEY `products_brandId_idx` (`brandId`),
  KEY `products_categoryId_idx` (`categoryId`),
  KEY `products_status_idx` (`status`),
  KEY `products_isFeatured_idx` (`isFeatured`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: packages (from product-service)
CREATE TABLE IF NOT EXISTS `packages` (
  `id` VARCHAR(36) NOT NULL PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `image` VARCHAR(500) NULL,
  `packageDay` VARCHAR(50) NOT NULL,
  `items` JSON NOT NULL DEFAULT ('[]'),
  `retailPrice` DECIMAL(10, 2) NOT NULL,
  `discount` JSON NOT NULL DEFAULT ('{"type":"percentage","value":0}'),
  `status` VARCHAR(50) NOT NULL DEFAULT 'active',
  `isFeatured` BOOLEAN NOT NULL DEFAULT FALSE,
  `tags` JSON NOT NULL DEFAULT ('[]'),
  `category` VARCHAR(255) NULL,
  `rating` DECIMAL(3, 2) NOT NULL DEFAULT 0,
  `totalOrders` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `packages_packageDay_idx` (`packageDay`),
  KEY `packages_status_idx` (`status`),
  KEY `packages_isFeatured_idx` (`isFeatured`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: review_ratings (from product-service)
CREATE TABLE IF NOT EXISTS `review_ratings` (
  `id` VARCHAR(36) NOT NULL PRIMARY KEY,
  `productId` VARCHAR(255) NOT NULL,
  `user` JSON NOT NULL,
  `rating` INT NOT NULL,
  `review` TEXT NOT NULL,
  `date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `review_ratings_productId_idx` (`productId`),
  KEY `review_ratings_date_idx` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ORDER SERVICE TABLES
-- =====================================================

-- Table: orders (from order-service)
CREATE TABLE IF NOT EXISTS `orders` (
  `id` VARCHAR(36) NOT NULL PRIMARY KEY,
  `userId` VARCHAR(255) NOT NULL,
  `vendorId` VARCHAR(255) NOT NULL,
  `logisticsId` VARCHAR(255) NULL,
  `items` JSON NOT NULL DEFAULT ('[]'),
  `authenticationCode` VARCHAR(255) NULL,
  `deliveryTimeline` VARCHAR(255) NULL,
  `shippingAddress` JSON NULL,
  `customerName` VARCHAR(255) NULL,
  `totalAmount` DECIMAL(10, 2) NOT NULL,
  `discountAmount` DECIMAL(10, 2) NOT NULL DEFAULT 0,
  `deliveryCharges` DECIMAL(10, 2) NOT NULL DEFAULT 0,
  `couponCode` VARCHAR(255) NULL,
  `paymentMethod` VARCHAR(50) NOT NULL DEFAULT 'COD',
  `paymentStatus` VARCHAR(50) NOT NULL DEFAULT 'PENDING',
  `transactionId` VARCHAR(255) NULL,
  `purchaseDate` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expectedDeliveryDate` DATETIME NULL,
  `actualDeliveryDate` DATETIME NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'inprogress',
  `deliveryStatus` VARCHAR(50) NOT NULL DEFAULT 'Pending',
  `orderProgress` VARCHAR(255) NOT NULL DEFAULT 'Awaiting Confirmation',
  `notes` TEXT NULL,
  `vendorNotes` TEXT NULL,
  `statusHistory` JSON NOT NULL DEFAULT ('[]'),
  `platform` VARCHAR(50) NOT NULL DEFAULT 'Web',
  `isDeleted` BOOLEAN NOT NULL DEFAULT FALSE,
  `isReturnRequested` BOOLEAN NOT NULL DEFAULT FALSE,
  `returnRequest` JSON NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `orders_userId_idx` (`userId`),
  KEY `orders_vendorId_idx` (`vendorId`),
  KEY `orders_logisticsId_idx` (`logisticsId`),
  KEY `orders_status_idx` (`status`),
  KEY `orders_paymentStatus_idx` (`paymentStatus`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: carts (from order-service)
CREATE TABLE IF NOT EXISTS `carts` (
  `id` VARCHAR(36) NOT NULL PRIMARY KEY,
  `userId` VARCHAR(255) NOT NULL UNIQUE,
  `items` JSON NOT NULL DEFAULT ('[]'),
  `totalPrice` DECIMAL(10, 2) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `carts_userId_idx` (`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: order_users (from order-service - renamed to avoid conflict with user-service users table)
-- Note: This is the User model from order-service, renamed to order_users to avoid table name conflict
CREATE TABLE IF NOT EXISTS `order_users` (
  `id` VARCHAR(36) NOT NULL PRIMARY KEY,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(50) NULL,
  `password` VARCHAR(255) NULL,
  `vendorId` VARCHAR(255) NULL UNIQUE,
  `clientId` VARCHAR(255) NULL UNIQUE,
  `platform` VARCHAR(50) NOT NULL,
  `googleId` VARCHAR(255) NULL,
  `facebookId` VARCHAR(255) NULL,
  `name` VARCHAR(255) NULL,
  `role` VARCHAR(50) NOT NULL DEFAULT 'user',
  `firstName` VARCHAR(255) NULL,
  `lastName` VARCHAR(255) NULL,
  `addresses` JSON NOT NULL DEFAULT ('[]'),
  `profileImage` VARCHAR(500) NULL,
  `extraDetails` JSON NOT NULL DEFAULT ('[]'),
  `emailConfirmation` JSON NULL,
  `isEmailConfirmed` BOOLEAN NOT NULL DEFAULT FALSE,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `order_users_email_idx` (`email`),
  KEY `order_users_platform_idx` (`platform`),
  KEY `order_users_vendorId_idx` (`vendorId`),
  KEY `order_users_clientId_idx` (`clientId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- VENDOR SERVICE TABLES
-- =====================================================

-- Table: vendors (from vendor-service)
CREATE TABLE IF NOT EXISTS `vendors` (
  `id` VARCHAR(36) NOT NULL PRIMARY KEY,
  `vendorId` VARCHAR(255) NOT NULL UNIQUE,
  `userId` VARCHAR(255) NOT NULL,
  `storeName` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(50) NULL,
  `email` VARCHAR(255) NULL,
  `address` TEXT NULL,
  `vendorProfileImage` VARCHAR(500) NULL,
  `vendorBannerImage` VARCHAR(500) NULL,
  `inventoryCount` INT NOT NULL DEFAULT 0,
  `categories` JSON NOT NULL DEFAULT ('[]'),
  `description` TEXT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
  `storeEnabled` BOOLEAN NOT NULL DEFAULT TRUE,
  `coupons` JSON NOT NULL DEFAULT ('[]'),
  `storeCurrency` VARCHAR(10) NULL,
  `timezone` VARCHAR(100) NULL,
  `workHours` VARCHAR(255) NULL,
  `state` VARCHAR(255) NULL,
  `city` VARCHAR(255) NULL,
  `localGovernment` VARCHAR(255) NULL,
  `country` VARCHAR(255) NULL,
  `storeIndustries` JSON NOT NULL DEFAULT ('[]'),
  `storeAddress` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `vendors_vendorId_idx` (`vendorId`),
  KEY `vendors_userId_idx` (`userId`),
  KEY `vendors_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- CLIENT SERVICE TABLES
-- =====================================================

-- Table: clients (from client-service)
CREATE TABLE IF NOT EXISTS `clients` (
  `id` VARCHAR(36) NOT NULL PRIMARY KEY,
  `userId` VARCHAR(255) NOT NULL,
  `clientId` VARCHAR(255) NOT NULL UNIQUE,
  `fullName` VARCHAR(255) NULL,
  `email` VARCHAR(255) NULL,
  `phone` VARCHAR(50) NULL,
  `favoriteProducts` JSON NOT NULL DEFAULT ('[]'),
  `ratingHistory` JSON NOT NULL DEFAULT ('[]'),
  `logisticRatings` JSON NOT NULL DEFAULT ('[]'),
  `referral` JSON NOT NULL DEFAULT ('{"code":"","referredBy":null,"totalReferrals":0,"totalPoints":0,"history":[]}'),
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `clients_userId_idx` (`userId`),
  KEY `clients_clientId_idx` (`clientId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: wallets (from client-service)
CREATE TABLE IF NOT EXISTS `wallets` (
  `id` VARCHAR(36) NOT NULL PRIMARY KEY,
  `userId` VARCHAR(255) NOT NULL UNIQUE,
  `balance` DECIMAL(10, 2) NOT NULL DEFAULT 0,
  `transactions` JSON NOT NULL DEFAULT ('[]'),
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `wallets_userId_idx` (`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- LOGISTIC SERVICE TABLES
-- =====================================================

-- Table: delivery_assignments (from logistic-service)
CREATE TABLE IF NOT EXISTS `delivery_assignments` (
  `id` VARCHAR(36) NOT NULL PRIMARY KEY,
  `parcelNo` VARCHAR(255) NOT NULL,
  `teamMemberId` VARCHAR(255) NOT NULL,
  `orderId` VARCHAR(255) NOT NULL,
  `vendorLocation` JSON NULL,
  `travelDistance` DECIMAL(10, 2) NULL,
  `estimatedTime` INT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'assigned',
  `assignedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `startedAt` DATETIME NULL,
  `completedAt` DATETIME NULL,
  `authenticationCode` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `delivery_assignments_parcelNo_idx` (`parcelNo`),
  KEY `delivery_assignments_teamMemberId_idx` (`teamMemberId`),
  KEY `delivery_assignments_orderId_idx` (`orderId`),
  KEY `delivery_assignments_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: earnings (from logistic-service)
CREATE TABLE IF NOT EXISTS `earnings` (
  `id` VARCHAR(36) NOT NULL PRIMARY KEY,
  `logisticsId` VARCHAR(255) NOT NULL,
  `orderId` VARCHAR(255) NOT NULL,
  `amount` DECIMAL(10, 2) NOT NULL,
  `transactionId` VARCHAR(255) NULL,
  `paymentMethod` VARCHAR(50) NOT NULL DEFAULT 'Wallet',
  `status` VARCHAR(50) NOT NULL DEFAULT 'PENDING',
  `paidAt` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `earnings_logisticsId_idx` (`logisticsId`),
  KEY `earnings_orderId_idx` (`orderId`),
  KEY `earnings_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- NOTIFICATION SERVICE TABLES
-- =====================================================

-- Table: notifications (from notification-service)
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` VARCHAR(255) NOT NULL PRIMARY KEY,
  `userId` VARCHAR(255) NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  `title` VARCHAR(100) NOT NULL,
  `message` VARCHAR(500) NOT NULL,
  `phoneNumber` VARCHAR(50) NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
  `priority` VARCHAR(50) NOT NULL DEFAULT 'medium',
  `metadata` JSON NOT NULL DEFAULT ('{}'),
  `sentAt` DATETIME NULL,
  `readAt` DATETIME NULL,
  `deliveredAt` DATETIME NULL,
  `errorMessage` VARCHAR(500) NULL,
  `retryCount` INT NOT NULL DEFAULT 0,
  `maxRetries` INT NOT NULL DEFAULT 3,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `notifications_userId_created_at_idx` (`userId`, `created_at` DESC),
  KEY `notifications_status_idx` (`status`),
  KEY `notifications_type_idx` (`type`),
  KEY `notifications_priority_idx` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- END OF SCHEMA
-- =====================================================

