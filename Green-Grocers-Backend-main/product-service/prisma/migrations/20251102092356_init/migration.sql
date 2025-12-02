-- CreateTable
CREATE TABLE `brands` (
    `id` VARCHAR(191) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `image` VARCHAR(500) NOT NULL,
    `description` TEXT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL,

    INDEX `brands_title_idx`(`title`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `categories` (
    `id` VARCHAR(191) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `image` VARCHAR(500) NOT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL,

    INDEX `categories_title_idx`(`title`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `products` (
    `id` VARCHAR(191) NOT NULL,
    `vendorId` VARCHAR(255) NULL,
    `brandId` VARCHAR(255) NULL,
    `categoryId` VARCHAR(255) NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `itemSize` VARCHAR(255) NULL,
    `totalQuantityInStock` INTEGER NOT NULL DEFAULT 0,
    `images` JSON NOT NULL,
    `variants` JSON NOT NULL,
    `brand` VARCHAR(255) NULL,
    `category` VARCHAR(255) NULL,
    `gender` VARCHAR(50) NULL,
    `collection` VARCHAR(255) NULL,
    `tags` JSON NOT NULL,
    `retailPrice` DECIMAL(10, 2) NULL,
    `wholesalePrice` DECIMAL(10, 2) NULL,
    `minWholesaleQty` INTEGER NULL,
    `preSalePrice` DECIMAL(10, 2) NULL,
    `preSalePeriod` JSON NULL,
    `discount` JSON NULL,
    `appliedCoupons` JSON NOT NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'active',
    `isFeatured` BOOLEAN NOT NULL DEFAULT false,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL,

    INDEX `products_vendorId_idx`(`vendorId`),
    INDEX `products_brandId_idx`(`brandId`),
    INDEX `products_categoryId_idx`(`categoryId`),
    INDEX `products_status_idx`(`status`),
    INDEX `products_isFeatured_idx`(`isFeatured`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `packages` (
    `id` VARCHAR(191) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `image` VARCHAR(500) NULL,
    `packageDay` VARCHAR(50) NOT NULL,
    `items` JSON NOT NULL,
    `retailPrice` DECIMAL(10, 2) NOT NULL,
    `discount` JSON NOT NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'active',
    `isFeatured` BOOLEAN NOT NULL DEFAULT false,
    `tags` JSON NOT NULL,
    `category` VARCHAR(255) NULL,
    `rating` DECIMAL(3, 2) NOT NULL DEFAULT 0,
    `totalOrders` INTEGER NOT NULL DEFAULT 0,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL,

    INDEX `packages_packageDay_idx`(`packageDay`),
    INDEX `packages_status_idx`(`status`),
    INDEX `packages_isFeatured_idx`(`isFeatured`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `review_ratings` (
    `id` VARCHAR(191) NOT NULL,
    `productId` VARCHAR(255) NOT NULL,
    `user` JSON NOT NULL,
    `rating` INTEGER NOT NULL,
    `review` TEXT NOT NULL,
    `date` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL,

    INDEX `review_ratings_productId_idx`(`productId`),
    INDEX `review_ratings_date_idx`(`date`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
