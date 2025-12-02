-- CreateTable
CREATE TABLE `notifications` (
    `id` VARCHAR(255) NOT NULL,
    `userId` VARCHAR(255) NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `title` VARCHAR(100) NOT NULL,
    `message` VARCHAR(500) NOT NULL,
    `phoneNumber` VARCHAR(50) NULL,
    `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
    `priority` VARCHAR(50) NOT NULL DEFAULT 'medium',
    `metadata` JSON NOT NULL,
    `sentAt` DATETIME(3) NULL,
    `readAt` DATETIME(3) NULL,
    `deliveredAt` DATETIME(3) NULL,
    `errorMessage` VARCHAR(500) NULL,
    `retryCount` INTEGER NOT NULL DEFAULT 0,
    `maxRetries` INTEGER NOT NULL DEFAULT 3,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL,

    INDEX `notifications_userId_created_at_idx`(`userId`, `created_at` DESC),
    INDEX `notifications_status_idx`(`status`),
    INDEX `notifications_type_idx`(`type`),
    INDEX `notifications_priority_idx`(`priority`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
