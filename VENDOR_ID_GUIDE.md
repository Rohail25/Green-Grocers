# Vendor ID & Client ID Generation Guide

## 📋 Overview

When a user registers, the system automatically generates and stores either a **vendorId** or **clientId** based on their platform:

- **trivestore** platform → Generates `vendorId` (e.g., `VEND-ABC12345`)
- **trivemart** platform → Generates `clientId` (e.g., `MART-XYZ67890`)

## 🔄 Current Flow (How It Works)

### Step 1: User Registration
When a user fills the registration form and submits:

```php
// In auth/register.php
$userData = [
    'email' => $_POST['email'],
    'password' => $_POST['password'],
    'platform' => 'trivemart',  // or 'trivestore'
    'role' => 'customer',
    // ... other fields
];

$result = registerUser($userData);
```

### Step 2: Generate VendorId/ClientId
Inside `includes/auth.php` → `registerUser()` function:

```php
// Lines 103-110 in includes/auth.php
$vendorId = null;
$clientId = null;

if ($platform === 'trivestore') {
    // Generate vendorId for trivestore users
    $vendorId = 'VEND-' . strtoupper(substr(uniqid(), -8));
    // Example: VEND-A1B2C3D4
} elseif ($platform === 'trivemart') {
    // Generate clientId for trivemart users
    $clientId = 'MART-' . strtoupper(substr(uniqid(), -8));
    // Example: MART-X9Y8Z7W6
}
```

### Step 3: Store in Database
The vendorId/clientId is stored in the `users` table during registration:

```php
// Lines 119-131 in includes/auth.php
$stmt = $conn->prepare("
    INSERT INTO users (
        id, email, phone, password, firstName, lastName, role, platform,
        vendorId, clientId, isEmailConfirmed, isVerified,
        verificationDocuments, preferredVendors, addresses,
        created_at, updated_at
    ) VALUES (
        UUID(), :email, :phone, :password, :firstName, :lastName, :role, :platform,
        :vendorId, :clientId, FALSE, :isVerified,
        '[]', '[]', '[]',
        NOW(), NOW()
    )
");

$stmt->execute([
    ':email' => $email,
    ':password' => $hashedPassword,
    ':platform' => $platform,
    ':vendorId' => $vendorId,      // ← Stored here
    ':clientId' => $clientId,      // ← Stored here
    // ... other fields
]);
```

### Step 4: Available in Session
After login, vendorId/clientId is available in the session:

```php
// In includes/auth.php → loginUser()
$_SESSION['user'] = [
    'id' => $user['id'],
    'email' => $user['email'],
    'vendorId' => $user['vendorId'] ?? null,  // ← Available here
    'clientId' => $user['clientId'] ?? null,  // ← Available here
    // ...
];
```

## 📊 Database Structure

### Users Table
```sql
CREATE TABLE users (
    id VARCHAR(36) PRIMARY KEY,
    email VARCHAR(255),
    platform VARCHAR(50),        -- 'trivemart' or 'trivestore'
    vendorId VARCHAR(255),       -- NULL for trivemart, 'VEND-XXXX' for trivestore
    clientId VARCHAR(255),       -- NULL for trivestore, 'MART-XXXX' for trivemart
    -- ... other fields
);
```

### Example Data
```
| id       | email           | platform   | vendorId      | clientId      |
|----------|-----------------|------------|----------------|---------------|
| uuid-1   | user1@test.com  | trivestore | VEND-A1B2C3D4  | NULL          |
| uuid-2   | user2@test.com  | trivemart  | NULL           | MART-X9Y8Z7W6 |
```

## 🎯 How to Use VendorId/ClientId

### 1. Get from Session (After Login)
```php
$user = getCurrentUser();
$vendorId = $user['vendorId'] ?? null;
$clientId = $user['clientId'] ?? null;

if ($vendorId) {
    echo "Your Vendor ID: " . $vendorId;
}
if ($clientId) {
    echo "Your Client ID: " . $clientId;
}
```

### 2. Get from Database
```php
$conn = getDBConnection();
$stmt = $conn->prepare("SELECT vendorId, clientId FROM users WHERE id = :id");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$vendorId = $user['vendorId'];
$clientId = $user['clientId'];
```

### 3. Check Platform and Get Appropriate ID
```php
$user = getCurrentUser();
$platform = $user['platform'];

if ($platform === 'trivestore') {
    $id = $user['vendorId'];  // Use vendorId
} elseif ($platform === 'trivemart') {
    $id = $user['clientId'];  // Use clientId
}
```

## 🔧 Current Implementation Location

**File**: `includes/auth.php`

**Function**: `registerUser($userData)`

**Lines**: 103-110 (Generation), 142-143 (Storage)

## ✅ What Happens Automatically

1. ✅ User registers with platform
2. ✅ System generates vendorId/clientId automatically
3. ✅ Stored in database during registration
4. ✅ Available in session after login
5. ✅ Can be retrieved anytime from database

## 📝 Notes

- **vendorId** is only generated for `trivestore` platform users
- **clientId** is only generated for `trivemart` platform users
- IDs are unique (generated using `uniqid()`)
- Format: `VEND-XXXXXXXX` or `MART-XXXXXXXX` (8 characters)
- Stored as VARCHAR(255) in database
- Can be NULL if not applicable to the platform

## 🚀 Testing

To test vendorId generation:

1. Register a new user with platform `trivestore`
2. Check database: `SELECT vendorId FROM users WHERE email = 'user@test.com'`
3. Should see: `VEND-XXXXXXXX`

To test clientId generation:

1. Register a new user with platform `trivemart`
2. Check database: `SELECT clientId FROM users WHERE email = 'user@test.com'`
3. Should see: `MART-XXXXXXXX`

---

**The vendorId/clientId is automatically created and stored during registration - no manual action needed!**

