# Database Tables Summary

This document lists all tables and their fields extracted from the Prisma schema files.

## Total Tables: 17

---

## 1. users (User Service)
**Source:** `user-service/prisma/schema.prisma`

| Field Name | Data Type | Nullable | Default | Notes |
|------------|-----------|----------|---------|-------|
| id | VARCHAR(36) | NO | UUID | Primary Key |
| email | VARCHAR(255) | YES | - | Unique with platform |
| phone | VARCHAR(50) | YES | - | |
| password | VARCHAR(255) | NO | - | Required |
| platform | VARCHAR(50) | NO | - | Required |
| role | VARCHAR(50) | NO | 'customer' | |
| parentLogistic | VARCHAR(255) | YES | - | |
| isEmailConfirmed | BOOLEAN | NO | FALSE | |
| emailVerificationToken | VARCHAR(255) | YES | - | |
| emailVerificationExpires | DATETIME | YES | - | |
| verificationDocuments | JSON | NO | '[]' | |
| isVerified | BOOLEAN | NO | FALSE | |
| documentsUploaded | BOOLEAN | NO | FALSE | |
| isAvailable | BOOLEAN | NO | FALSE | |
| firstName | VARCHAR(255) | YES | - | |
| lastName | VARCHAR(255) | YES | - | |
| profileImage | VARCHAR(500) | YES | - | |
| preferredVendors | JSON | NO | '[]' | |
| addresses | JSON | NO | '[]' | |
| vendorId | VARCHAR(255) | YES | - | |
| clientId | VARCHAR(255) | YES | - | |
| googleId | VARCHAR(255) | YES | - | |
| facebookId | VARCHAR(255) | YES | - | |
| created_at | DATETIME | NO | CURRENT_TIMESTAMP | |
| updated_at | DATETIME | NO | CURRENT_TIMESTAMP | Auto-update |

**Indexes:**
- UNIQUE: (email, platform)
- INDEX: email, platform, role, parentLogistic

---

## 2. vehicles (User Service)
**Source:** `user-service/prisma/schema.prisma`

| Field Name | Data Type | Nullable | Default | Notes |
|------------|-----------|----------|---------|-------|
| id | VARCHAR(36) | NO | UUID | Primary Key |
| userId | VARCHAR(255) | NO | - | Required |
| vehicleType | VARCHAR(255) | NO | - | Required |
| vehicleModel | VARCHAR(255) | NO | - | Required |
| vehicleColor | VARCHAR(255) | NO | - | Required |
| plateNumber | VARCHAR(255) | NO | - | Unique |
| workHours | VARCHAR(255) | NO | - | Required |
| created_at | DATETIME | NO | CURRENT_TIMESTAMP | |
| updated_at | DATETIME | NO | CURRENT_TIMESTAMP | Auto-update |

**Indexes:**
- UNIQUE: plateNumber
- INDEX: userId, plateNumber

---

## 3. brands (Product Service)
**Source:** `product-service/prisma/schema.prisma`

| Field Name | Data Type | Nullable | Default | Notes |
|------------|-----------|----------|---------|-------|
| id | VARCHAR(36) | NO | UUID | Primary Key |
| title | VARCHAR(255) | NO | - | Required |
| image | VARCHAR(500) | NO | - | Required |
| description | TEXT | YES | - | |
| created_at | DATETIME | NO | CURRENT_TIMESTAMP | |
| updated_at | DATETIME | NO | CURRENT_TIMESTAMP | Auto-update |

**Indexes:**
- INDEX: title

---

## 4. categories (Product Service)
**Source:** `product-service/prisma/schema.prisma`

| Field Name | Data Type | Nullable | Default | Notes |
|------------|-----------|----------|---------|-------|
| id | VARCHAR(36) | NO | UUID | Primary Key |
| title | VARCHAR(255) | NO | - | Required |
| image | VARCHAR(500) | NO | - | Required |
| created_at | DATETIME | NO | CURRENT_TIMESTAMP | |
| updated_at | DATETIME | NO | CURRENT_TIMESTAMP | Auto-update |

**Indexes:**
- INDEX: title

---

## 5. products (Product Service)
**Source:** `product-service/prisma/schema.prisma`

| Field Name | Data Type | Nullable | Default | Notes |
|------------|-----------|----------|---------|-------|
| id | VARCHAR(36) | NO | UUID | Primary Key |
| vendorId | VARCHAR(255) | YES | - | |
| brandId | VARCHAR(255) | YES | - | |
| categoryId | VARCHAR(255) | YES | - | |
| name | VARCHAR(255) | NO | - | Required |
| description | TEXT | YES | - | |
| itemSize | VARCHAR(255) | YES | - | |
| totalQuantityInStock | INT | NO | 0 | |
| images | JSON | NO | '[]' | |
| variants | JSON | NO | '[]' | |
| brand | VARCHAR(255) | YES | - | |
| category | VARCHAR(255) | YES | - | |
| gender | VARCHAR(50) | YES | - | |
| collection | VARCHAR(255) | YES | - | |
| tags | JSON | NO | '[]' | |
| retailPrice | DECIMAL(10,2) | YES | - | |
| wholesalePrice | DECIMAL(10,2) | YES | - | |
| minWholesaleQty | INT | YES | - | |
| preSalePrice | DECIMAL(10,2) | YES | - | |
| preSalePeriod | JSON | YES | - | |
| discount | JSON | YES | - | |
| appliedCoupons | JSON | NO | '[]' | |
| status | VARCHAR(50) | NO | 'active' | |
| isFeatured | BOOLEAN | NO | FALSE | |
| created_at | DATETIME | NO | CURRENT_TIMESTAMP | |
| updated_at | DATETIME | NO | CURRENT_TIMESTAMP | Auto-update |

**Indexes:**
- INDEX: vendorId, brandId, categoryId, status, isFeatured

---

## 6. packages (Product Service)
**Source:** `product-service/prisma/schema.prisma`

| Field Name | Data Type | Nullable | Default | Notes |
|------------|-----------|----------|---------|-------|
| id | VARCHAR(36) | NO | UUID | Primary Key |
| name | VARCHAR(255) | NO | - | Required |
| description | TEXT | YES | - | |
| image | VARCHAR(500) | YES | - | |
| packageDay | VARCHAR(50) | NO | - | Required |
| items | JSON | NO | '[]' | |
| retailPrice | DECIMAL(10,2) | NO | - | Required |
| discount | JSON | NO | '{"type":"percentage","value":0}' | |
| status | VARCHAR(50) | NO | 'active' | |
| isFeatured | BOOLEAN | NO | FALSE | |
| tags | JSON | NO | '[]' | |
| category | VARCHAR(255) | YES | - | |
| rating | DECIMAL(3,2) | NO | 0 | |
| totalOrders | INT | NO | 0 | |
| created_at | DATETIME | NO | CURRENT_TIMESTAMP | |
| updated_at | DATETIME | NO | CURRENT_TIMESTAMP | Auto-update |

**Indexes:**
- INDEX: packageDay, status, isFeatured

---

## 7. review_ratings (Product Service)
**Source:** `product-service/prisma/schema.prisma`

| Field Name | Data Type | Nullable | Default | Notes |
|------------|-----------|----------|---------|-------|
| id | VARCHAR(36) | NO | UUID | Primary Key |
| productId | VARCHAR(255) | NO | - | Required |
| user | JSON | NO | - | Required |
| rating | INT | NO | - | Required |
| review | TEXT | NO | - | Required |
| date | DATETIME | NO | CURRENT_TIMESTAMP | |
| created_at | DATETIME | NO | CURRENT_TIMESTAMP | |
| updated_at | DATETIME | NO | CURRENT_TIMESTAMP | Auto-update |

**Indexes:**
- INDEX: productId, date

---

## 8. orders (Order Service)
**Source:** `order-service/prisma/schema.prisma`

| Field Name | Data Type | Nullable | Default | Notes |
|------------|-----------|----------|---------|-------|
| id | VARCHAR(36) | NO | UUID | Primary Key |
| userId | VARCHAR(255) | NO | - | Required |
| vendorId | VARCHAR(255) | NO | - | Required |
| logisticsId | VARCHAR(255) | YES | - | |
| items | JSON | NO | '[]' | |
| authenticationCode | VARCHAR(255) | YES | - | |
| deliveryTimeline | VARCHAR(255) | YES | - | |
| shippingAddress | JSON | YES | - | |
| customerName | VARCHAR(255) | YES | - | |
| totalAmount | DECIMAL(10,2) | NO | - | Required |
| discountAmount | DECIMAL(10,2) | NO | 0 | |
| deliveryCharges | DECIMAL(10,2) | NO | 0 | |
| couponCode | VARCHAR(255) | YES | - | |
| paymentMethod | VARCHAR(50) | NO | 'COD' | |
| paymentStatus | VARCHAR(50) | NO | 'PENDING' | |
| transactionId | VARCHAR(255) | YES | - | |
| purchaseDate | DATETIME | NO | CURRENT_TIMESTAMP | |
| expectedDeliveryDate | DATETIME | YES | - | |
| actualDeliveryDate | DATETIME | YES | - | |
| status | VARCHAR(50) | NO | 'inprogress' | |
| deliveryStatus | VARCHAR(50) | NO | 'Pending' | |
| orderProgress | VARCHAR(255) | NO | 'Awaiting Confirmation' | |
| notes | TEXT | YES | - | |
| vendorNotes | TEXT | YES | - | |
| statusHistory | JSON | NO | '[]' | |
| platform | VARCHAR(50) | NO | 'Web' | |
| isDeleted | BOOLEAN | NO | FALSE | |
| isReturnRequested | BOOLEAN | NO | FALSE | |
| returnRequest | JSON | YES | - | |
| created_at | DATETIME | NO | CURRENT_TIMESTAMP | |
| updated_at | DATETIME | NO | CURRENT_TIMESTAMP | Auto-update |

**Indexes:**
- INDEX: userId, vendorId, logisticsId, status, paymentStatus

---

## 9. carts (Order Service)
**Source:** `order-service/prisma/schema.prisma`

| Field Name | Data Type | Nullable | Default | Notes |
|------------|-----------|----------|---------|-------|
| id | VARCHAR(36) | NO | UUID | Primary Key |
| userId | VARCHAR(255) | NO | - | Unique, Required |
| items | JSON | NO | '[]' | |
| totalPrice | DECIMAL(10,2) | NO | 0 | |
| created_at | DATETIME | NO | CURRENT_TIMESTAMP | |
| updated_at | DATETIME | NO | CURRENT_TIMESTAMP | Auto-update |

**Indexes:**
- UNIQUE: userId
- INDEX: userId

---

## 10. order_users (Order Service)
**Source:** `order-service/prisma/schema.prisma`
**Note:** Renamed from `users` to `order_users` to avoid conflict with user-service users table

| Field Name | Data Type | Nullable | Default | Notes |
|------------|-----------|----------|---------|-------|
| id | VARCHAR(36) | NO | UUID | Primary Key |
| email | VARCHAR(255) | NO | - | Required |
| phone | VARCHAR(50) | YES | - | |
| password | VARCHAR(255) | YES | - | |
| vendorId | VARCHAR(255) | YES | - | Unique |
| clientId | VARCHAR(255) | YES | - | Unique |
| platform | VARCHAR(50) | NO | - | Required |
| googleId | VARCHAR(255) | YES | - | |
| facebookId | VARCHAR(255) | YES | - | |
| name | VARCHAR(255) | YES | - | |
| role | VARCHAR(50) | NO | 'user' | |
| firstName | VARCHAR(255) | YES | - | |
| lastName | VARCHAR(255) | YES | - | |
| addresses | JSON | NO | '[]' | |
| profileImage | VARCHAR(500) | YES | - | |
| extraDetails | JSON | NO | '[]' | |
| emailConfirmation | JSON | YES | - | |
| isEmailConfirmed | BOOLEAN | NO | FALSE | |
| created_at | DATETIME | NO | CURRENT_TIMESTAMP | |
| updated_at | DATETIME | NO | CURRENT_TIMESTAMP | Auto-update |

**Indexes:**
- UNIQUE: vendorId, clientId
- INDEX: email, platform, vendorId, clientId

---

## 11. vendors (Vendor Service)
**Source:** `vendor-service/prisma/schema.prisma`

| Field Name | Data Type | Nullable | Default | Notes |
|------------|-----------|----------|---------|-------|
| id | VARCHAR(36) | NO | UUID | Primary Key |
| vendorId | VARCHAR(255) | NO | - | Unique, Required |
| userId | VARCHAR(255) | NO | - | Required |
| storeName | VARCHAR(255) | NO | - | Required |
| phone | VARCHAR(50) | YES | - | |
| email | VARCHAR(255) | YES | - | |
| address | TEXT | YES | - | |
| vendorProfileImage | VARCHAR(500) | YES | - | |
| vendorBannerImage | VARCHAR(500) | YES | - | |
| inventoryCount | INT | NO | 0 | |
| categories | JSON | NO | '[]' | |
| description | TEXT | YES | - | |
| status | VARCHAR(50) | NO | 'pending' | |
| storeEnabled | BOOLEAN | NO | TRUE | |
| coupons | JSON | NO | '[]' | |
| storeCurrency | VARCHAR(10) | YES | - | |
| timezone | VARCHAR(100) | YES | - | |
| workHours | VARCHAR(255) | YES | - | |
| state | VARCHAR(255) | YES | - | |
| city | VARCHAR(255) | YES | - | |
| localGovernment | VARCHAR(255) | YES | - | |
| country | VARCHAR(255) | YES | - | |
| storeIndustries | JSON | NO | '[]' | |
| storeAddress | TEXT | YES | - | |
| created_at | DATETIME | NO | CURRENT_TIMESTAMP | |
| updated_at | DATETIME | NO | CURRENT_TIMESTAMP | Auto-update |

**Indexes:**
- UNIQUE: vendorId
- INDEX: vendorId, userId, status

---

## 12. clients (Client Service)
**Source:** `client-service/prisma/schema.prisma`

| Field Name | Data Type | Nullable | Default | Notes |
|------------|-----------|----------|---------|-------|
| id | VARCHAR(36) | NO | UUID | Primary Key |
| userId | VARCHAR(255) | NO | - | Required |
| clientId | VARCHAR(255) | NO | - | Unique, Required |
| fullName | VARCHAR(255) | YES | - | |
| email | VARCHAR(255) | YES | - | |
| phone | VARCHAR(50) | YES | - | |
| favoriteProducts | JSON | NO | '[]' | |
| ratingHistory | JSON | NO | '[]' | |
| logisticRatings | JSON | NO | '[]' | |
| referral | JSON | NO | '{"code":"","referredBy":null,"totalReferrals":0,"totalPoints":0,"history":[]}' | |
| created_at | DATETIME | NO | CURRENT_TIMESTAMP | |
| updated_at | DATETIME | NO | CURRENT_TIMESTAMP | Auto-update |

**Indexes:**
- UNIQUE: clientId
- INDEX: userId, clientId

---

## 13. wallets (Client Service)
**Source:** `client-service/prisma/schema.prisma`

| Field Name | Data Type | Nullable | Default | Notes |
|------------|-----------|----------|---------|-------|
| id | VARCHAR(36) | NO | UUID | Primary Key |
| userId | VARCHAR(255) | NO | - | Unique, Required |
| balance | DECIMAL(10,2) | NO | 0 | |
| transactions | JSON | NO | '[]' | |
| created_at | DATETIME | NO | CURRENT_TIMESTAMP | |
| updated_at | DATETIME | NO | CURRENT_TIMESTAMP | Auto-update |

**Indexes:**
- UNIQUE: userId
- INDEX: userId

---

## 14. delivery_assignments (Logistic Service)
**Source:** `logistic-service/prisma/schema.prisma`

| Field Name | Data Type | Nullable | Default | Notes |
|------------|-----------|----------|---------|-------|
| id | VARCHAR(36) | NO | UUID | Primary Key |
| parcelNo | VARCHAR(255) | NO | - | Required |
| teamMemberId | VARCHAR(255) | NO | - | Required |
| orderId | VARCHAR(255) | NO | - | Required |
| vendorLocation | JSON | YES | - | |
| travelDistance | DECIMAL(10,2) | YES | - | |
| estimatedTime | INT | YES | - | |
| status | VARCHAR(50) | NO | 'assigned' | |
| assignedAt | DATETIME | NO | CURRENT_TIMESTAMP | |
| startedAt | DATETIME | YES | - | |
| completedAt | DATETIME | YES | - | |
| authenticationCode | VARCHAR(255) | YES | - | |
| created_at | DATETIME | NO | CURRENT_TIMESTAMP | |
| updated_at | DATETIME | NO | CURRENT_TIMESTAMP | Auto-update |

**Indexes:**
- INDEX: parcelNo, teamMemberId, orderId, status

---

## 15. earnings (Logistic Service)
**Source:** `logistic-service/prisma/schema.prisma`

| Field Name | Data Type | Nullable | Default | Notes |
|------------|-----------|----------|---------|-------|
| id | VARCHAR(36) | NO | UUID | Primary Key |
| logisticsId | VARCHAR(255) | NO | - | Required |
| orderId | VARCHAR(255) | NO | - | Required |
| amount | DECIMAL(10,2) | NO | - | Required |
| transactionId | VARCHAR(255) | YES | - | |
| paymentMethod | VARCHAR(50) | NO | 'Wallet' | |
| status | VARCHAR(50) | NO | 'PENDING' | |
| paidAt | DATETIME | YES | - | |
| created_at | DATETIME | NO | CURRENT_TIMESTAMP | |
| updated_at | DATETIME | NO | CURRENT_TIMESTAMP | Auto-update |

**Indexes:**
- INDEX: logisticsId, orderId, status

---

## 16. notifications (Notification Service)
**Source:** `notification-service/prisma/schema.prisma`

| Field Name | Data Type | Nullable | Default | Notes |
|------------|-----------|----------|---------|-------|
| id | VARCHAR(255) | NO | UUID | Primary Key |
| userId | VARCHAR(255) | NO | - | Required |
| type | VARCHAR(50) | NO | - | Required |
| title | VARCHAR(100) | NO | - | Required |
| message | VARCHAR(500) | NO | - | Required |
| phoneNumber | VARCHAR(50) | YES | - | |
| status | VARCHAR(50) | NO | 'pending' | |
| priority | VARCHAR(50) | NO | 'medium' | |
| metadata | JSON | NO | '{}' | |
| sentAt | DATETIME | YES | - | |
| readAt | DATETIME | YES | - | |
| deliveredAt | DATETIME | YES | - | |
| errorMessage | VARCHAR(500) | YES | - | |
| retryCount | INT | NO | 0 | |
| maxRetries | INT | NO | 3 | |
| created_at | DATETIME | NO | CURRENT_TIMESTAMP | |
| updated_at | DATETIME | NO | CURRENT_TIMESTAMP | Auto-update |

**Indexes:**
- INDEX: (userId, created_at DESC), status, type, priority

---

## Important Notes

1. **Table Name Conflict:** Both `user-service` and `order-service` have a `User` model that maps to `users` table. To avoid conflict in a single database, the order-service User table has been renamed to `order_users`. All field names and data types remain unchanged.

2. **JSON Fields:** Many fields use JSON data type to store nested arrays and objects, matching the original MongoDB structure.

3. **UUID Generation:** All `id` fields use VARCHAR(36) to store UUIDs. MySQL doesn't have native UUID type, so UUIDs are generated by the application.

4. **Timestamps:** All tables have `created_at` and `updated_at` fields with automatic timestamp management.

5. **Indexes:** All indexes from the Prisma schemas have been preserved.

