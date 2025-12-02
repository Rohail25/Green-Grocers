-- CreateTable
CREATE TABLE `orders` (
    `id` VARCHAR(191) NOT NULL,
    `userId` VARCHAR(255) NOT NULL,
    `vendorId` VARCHAR(255) NOT NULL,
    `logisticsId` VARCHAR(255) NULL,
    `items` JSON NOT NULL,
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
    `purchaseDate` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `expectedDeliveryDate` DATETIME(3) NULL,
    `actualDeliveryDate` DATETIME(3) NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'inprogress',
    `deliveryStatus` VARCHAR(50) NOT NULL DEFAULT 'Pending',
    `orderProgress` VARCHAR(255) NOT NULL DEFAULT 'Awaiting Confirmation',
    `notes` TEXT NULL,
    `vendorNotes` TEXT NULL,
    `statusHistory` JSON NOT NULL,
    `platform` VARCHAR(50) NOT NULL DEFAULT 'Web',
    `isDeleted` BOOLEAN NOT NULL DEFAULT false,
    `isReturnRequested` BOOLEAN NOT NULL DEFAULT false,
    `returnRequest` JSON NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL,

    INDEX `orders_userId_idx`(`userId`),
    INDEX `orders_vendorId_idx`(`vendorId`),
    INDEX `orders_logisticsId_idx`(`logisticsId`),
    INDEX `orders_status_idx`(`status`),
    INDEX `orders_paymentStatus_idx`(`paymentStatus`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `carts` (
    `id` VARCHAR(191) NOT NULL,
    `userId` VARCHAR(255) NOT NULL,
    `items` JSON NOT NULL,
    `totalPrice` DECIMAL(10, 2) NOT NULL DEFAULT 0,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL,

    UNIQUE INDEX `carts_userId_key`(`userId`),
    INDEX `carts_userId_idx`(`userId`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `users` (
    `id` VARCHAR(191) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(50) NULL,
    `password` VARCHAR(255) NULL,
    `vendorId` VARCHAR(255) NULL,
    `clientId` VARCHAR(255) NULL,
    `platform` VARCHAR(50) NOT NULL,
    `googleId` VARCHAR(255) NULL,
    `facebookId` VARCHAR(255) NULL,
    `name` VARCHAR(255) NULL,
    `role` VARCHAR(50) NOT NULL DEFAULT 'user',
    `firstName` VARCHAR(255) NULL,
    `lastName` VARCHAR(255) NULL,
    `addresses` JSON NOT NULL,
    `profileImage` VARCHAR(500) NULL,
    `extraDetails` JSON NOT NULL,
    `emailConfirmation` JSON NULL,
    `isEmailConfirmed` BOOLEAN NOT NULL DEFAULT false,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL,

    UNIQUE INDEX `users_vendorId_key`(`vendorId`),
    UNIQUE INDEX `users_clientId_key`(`clientId`),
    INDEX `users_email_idx`(`email`),
    INDEX `users_platform_idx`(`platform`),
    INDEX `users_vendorId_idx`(`vendorId`),
    INDEX `users_clientId_idx`(`clientId`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
