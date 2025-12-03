# Vendor ID Generation Flow - Visual Guide

## 🔄 Complete Flow Diagram

```
┌─────────────────────────────────────────────────────────────┐
│ 1. USER REGISTRATION                                         │
│    User fills form at: auth/register.php                    │
│    - Email: user@example.com                                │
│    - Password: ********                                      │
│    - Platform: trivestore (or trivemart)                    │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 2. FORM SUBMISSION                                          │
│    POST to: auth/register.php                              │
│    Calls: registerUser($userData)                          │
│    Location: includes/auth.php                             │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 3. VALIDATION                                               │
│    ✓ Check if email + platform already exists              │
│    ✓ Validate password match                               │
│    ✓ Check platform is provided                            │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 4. GENERATE VENDOR ID / CLIENT ID                          │
│                                                             │
│    if (platform === 'trivestore') {                        │
│        vendorId = 'VEND-' + random 8 chars                │
│        Example: VEND-A1B2C3D4                             │
│    }                                                        │
│                                                             │
│    if (platform === 'trivemart') {                         │
│        clientId = 'MART-' + random 8 chars                │
│        Example: MART-X9Y8Z7W6                             │
│    }                                                        │
│                                                             │
│    Code Location: includes/auth.php (lines 103-110)       │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 5. STORE IN DATABASE                                        │
│                                                             │
│    INSERT INTO users (                                      │
│        email, platform, vendorId, clientId, ...            │
│    ) VALUES (                                               │
│        'user@example.com',                                 │
│        'trivestore',                                       │
│        'VEND-A1B2C3D4',  ← Stored here                    │
│        NULL,                                               │
│        ...                                                  │
│    )                                                        │
│                                                             │
│    Code Location: includes/auth.php (lines 119-145)        │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 6. DATABASE RESULT                                          │
│                                                             │
│    users table:                                            │
│    ┌─────────────┬──────────────┬──────────────┐         │
│    │ email       │ platform     │ vendorId     │         │
│    ├─────────────┼──────────────┼──────────────┤         │
│    │ user@ex.com │ trivestore   │ VEND-A1B2C3D4 │         │
│    └─────────────┴──────────────┴──────────────┘         │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 7. AVAILABLE AFTER LOGIN                                    │
│                                                             │
│    $_SESSION['user'] = [                                    │
│        'id' => 'uuid-123',                                 │
│        'email' => 'user@example.com',                      │
│        'platform' => 'trivestore',                         │
│        'vendorId' => 'VEND-A1B2C3D4',  ← Available        │
│        'clientId' => null                                  │
│    ]                                                        │
│                                                             │
│    Code Location: includes/auth.php (lines 60-70)          │
└─────────────────────────────────────────────────────────────┘
```

## 📝 Step-by-Step Code Explanation

### Step 1: Registration Form
**File**: `auth/register.php`

```php
<form method="POST">
    <input type="email" name="email" required>
    <input type="password" name="password" required>
    <input type="hidden" name="platform" value="trivestore">  <!-- or trivemart -->
    <button type="submit">Register</button>
</form>
```

### Step 2: Generate ID
**File**: `includes/auth.php` (lines 103-110)

```php
// Generate vendorId/clientId based on platform
$vendorId = null;
$clientId = null;

if ($platform === 'trivestore') {
    // For trivestore: Generate vendorId
    $vendorId = 'VEND-' . strtoupper(substr(uniqid(), -8));
    // Result: VEND-A1B2C3D4 (example)
}

elseif ($platform === 'trivemart') {
    // For trivemart: Generate clientId
    $clientId = 'MART-' . strtoupper(substr(uniqid(), -8));
    // Result: MART-X9Y8Z7W6 (example)
}
```

### Step 3: Store in Database
**File**: `includes/auth.php` (lines 119-145)

```php
$stmt = $conn->prepare("
    INSERT INTO users (
        email, platform, vendorId, clientId, ...
    ) VALUES (
        :email, :platform, :vendorId, :clientId, ...
    )
");

$stmt->execute([
    ':email' => $email,
    ':platform' => $platform,
    ':vendorId' => $vendorId,      // ← VEND-A1B2C3D4 stored here
    ':clientId' => $clientId,       // ← MART-X9Y8Z7W6 stored here (or NULL)
    // ...
]);
```

### Step 4: Retrieve After Login
**File**: `includes/auth.php` (lines 60-70)

```php
// After successful login
$_SESSION['user'] = [
    'id' => $user['id'],
    'email' => $user['email'],
    'platform' => $user['platform'],
    'vendorId' => $user['vendorId'] ?? null,  // ← Available in session
    'clientId' => $user['clientId'] ?? null,  // ← Available in session
];
```

## 🧪 How to Test

### Test 1: Register Trivestore User
1. Go to: `http://localhost/green-grocers/auth/register.php`
2. Fill form with platform `trivestore`
3. Submit registration
4. Check database:
   ```sql
   SELECT email, platform, vendorId, clientId 
   FROM users 
   WHERE email = 'your-email@test.com';
   ```
5. Should see: `vendorId = 'VEND-XXXXXXXX'`, `clientId = NULL`

### Test 2: Register Trivemart User
1. Go to: `http://localhost/green-grocers/auth/register.php`
2. Fill form with platform `trivemart`
3. Submit registration
4. Check database:
   ```sql
   SELECT email, platform, vendorId, clientId 
   FROM users 
   WHERE email = 'your-email@test.com';
   ```
5. Should see: `vendorId = NULL`, `clientId = 'MART-XXXXXXXX'`

### Test 3: Check in Session (After Login)
```php
// In any PHP page after login
$user = getCurrentUser();
echo "Vendor ID: " . ($user['vendorId'] ?? 'Not set');
echo "Client ID: " . ($user['clientId'] ?? 'Not set');
```

## 📊 Database Query Examples

### Get All Users with Vendor IDs
```sql
SELECT id, email, platform, vendorId 
FROM users 
WHERE vendorId IS NOT NULL;
```

### Get All Users with Client IDs
```sql
SELECT id, email, platform, clientId 
FROM users 
WHERE clientId IS NOT NULL;
```

### Get User by Vendor ID
```sql
SELECT * FROM users WHERE vendorId = 'VEND-A1B2C3D4';
```

### Get User by Client ID
```sql
SELECT * FROM users WHERE clientId = 'MART-X9Y8Z7W6';
```

## ✅ Summary

**VendorId/ClientId is automatically:**
1. ✅ Generated during registration (based on platform)
2. ✅ Stored in database (in `users` table)
3. ✅ Available in session (after login)
4. ✅ Can be retrieved anytime from database

**No manual action needed - it happens automatically!**

