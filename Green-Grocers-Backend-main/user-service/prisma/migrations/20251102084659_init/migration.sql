-- CreateTable
CREATE TABLE `users` (
    `id` VARCHAR(191) NOT NULL,
    `email` VARCHAR(255) NULL,
    `phone` VARCHAR(50) NULL,
    `password` VARCHAR(255) NOT NULL,
    `platform` VARCHAR(50) NOT NULL,
    `role` VARCHAR(50) NOT NULL DEFAULT 'customer',
    `parentLogistic` VARCHAR(255) NULL,
    `isEmailConfirmed` BOOLEAN NOT NULL DEFAULT false,
    `emailVerificationToken` VARCHAR(255) NULL,
    `emailVerificationExpires` DATETIME(3) NULL,
    `verificationDocuments` JSON NOT NULL,
    `isVerified` BOOLEAN NOT NULL DEFAULT false,
    `documentsUploaded` BOOLEAN NOT NULL DEFAULT false,
    `isAvailable` BOOLEAN NOT NULL DEFAULT false,
    `firstName` VARCHAR(255) NULL,
    `lastName` VARCHAR(255) NULL,
    `profileImage` VARCHAR(500) NULL,
    `preferredVendors` JSON NOT NULL,
    `addresses` JSON NOT NULL,
    `vendorId` VARCHAR(255) NULL,
    `clientId` VARCHAR(255) NULL,
    `googleId` VARCHAR(255) NULL,
    `facebookId` VARCHAR(255) NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL,

    INDEX `users_email_idx`(`email`),
    INDEX `users_platform_idx`(`platform`),
    INDEX `users_role_idx`(`role`),
    INDEX `users_parentLogistic_idx`(`parentLogistic`),
    UNIQUE INDEX `users_email_platform_key`(`email`, `platform`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `vehicles` (
    `id` VARCHAR(191) NOT NULL,
    `userId` VARCHAR(255) NOT NULL,
    `vehicleType` VARCHAR(255) NOT NULL,
    `vehicleModel` VARCHAR(255) NOT NULL,
    `vehicleColor` VARCHAR(255) NOT NULL,
    `plateNumber` VARCHAR(255) NOT NULL,
    `workHours` VARCHAR(255) NOT NULL,
    `created_at` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updated_at` DATETIME(3) NOT NULL,

    UNIQUE INDEX `vehicles_plateNumber_key`(`plateNumber`),
    INDEX `vehicles_userId_idx`(`userId`),
    INDEX `vehicles_plateNumber_idx`(`plateNumber`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
