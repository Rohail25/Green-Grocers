-- CreateTable
CREATE TABLE `delivery_assignments` (
    `id` VARCHAR(191) NOT NULL,
    `parcelNo` VARCHAR(255) NOT NULL,
    `teamMemberId` VARCHAR(255) NOT NULL,
    `orderId` VARCHAR(255) NOT NULL,
    `vendorLocation` JSON NULL,
    `travelDistance` DECIMAL(10, 2) NULL,
    `estimatedTime` INTEGER NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'assigned',
    `assignedAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `startedAt` DATETIME(3) NULL,
    `completedAt` DATETIME(3) NULL,
    `authenticationCode` VARCHAR(255) NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL,

    INDEX `delivery_assignments_parcelNo_idx`(`parcelNo`),
    INDEX `delivery_assignments_teamMemberId_idx`(`teamMemberId`),
    INDEX `delivery_assignments_orderId_idx`(`orderId`),
    INDEX `delivery_assignments_status_idx`(`status`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `earnings` (
    `id` VARCHAR(191) NOT NULL,
    `logisticsId` VARCHAR(255) NOT NULL,
    `orderId` VARCHAR(255) NOT NULL,
    `amount` DECIMAL(10, 2) NOT NULL,
    `transactionId` VARCHAR(255) NULL,
    `paymentMethod` VARCHAR(50) NOT NULL DEFAULT 'Wallet',
    `status` VARCHAR(50) NOT NULL DEFAULT 'PENDING',
    `paidAt` DATETIME(3) NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL,

    INDEX `earnings_logisticsId_idx`(`logisticsId`),
    INDEX `earnings_orderId_idx`(`orderId`),
    INDEX `earnings_status_idx`(`status`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
