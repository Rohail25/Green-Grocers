# ✅ User-Service Complete Migration Guide

## 🎯 Overview

Successfully converted `user-service` from **MongoDB/Mongoose** to **MySQL/Prisma**.

- ✅ **Only 2 tables** (matching the 2 models in `src/models/`)
- ✅ **All 23 methods** converted to Prisma
- ✅ **All field names** match exactly
- ✅ **JSON storage** for nested arrays (like MongoDB)

---

## 📋 What Was Created

### 1. Prisma Schema (`prisma/schema.prisma`)
- ✅ `User` model - All fields from `user.model.js`
- ✅ `Vehicle` model - All fields from `Vehicle.model.js`
- ✅ JSON columns for nested arrays

### 2. Prisma Client (`src/utils/prisma.js`)
- ✅ Singleton pattern
- ✅ Graceful shutdown

### 3. Database Connection (`config/db.js`)
- ✅ Changed from Mongoose to Prisma

---

## 📋 What Was Updated

### Controllers

#### `src/controllers/user.controller.js` (18 methods)
1. ✅ `registerUser()` - Prisma create + password hashing
2. ✅ `loginUser()` - Prisma findFirst + bcrypt.compare
3. ✅ `createAgent()` - Prisma create + password hashing
4. ✅ `resetPassword()` - Prisma update + password hashing
5. ✅ `getProfile()` - Prisma findUnique
6. ✅ `updateProfile()` - Prisma update + JSON parsing
7. ✅ `confirmEmail()` - Prisma update
8. ✅ `resendConfirmationEmail()` - Prisma findFirst
9. ✅ `addAddress()` - Prisma update + JSON array manipulation
10. ✅ `updateAddress()` - Prisma update + JSON array manipulation
11. ✅ `deleteAddress()` - Prisma update + JSON array manipulation
12. ✅ `addFavoriteProduct()` - Prisma update + JSON array manipulation
13. ✅ `rateLogistics()` - Updated for Prisma
14. ✅ `getLogisticRatings()` - Updated for Prisma
15. ✅ `uploadVerificationDocument()` - Prisma update + JSON array manipulation
16. ✅ `setAvailabilityAndAddress()` - Prisma update
17. ✅ `getAgents()` - Prisma findMany
18. ✅ `updateAgent()` - Prisma update (with validation)
19. ✅ `deleteAgent()` - Prisma delete (with validation)

#### `src/controllers/vehicle.controller.js` (5 methods)
1. ✅ `addVehicle()` - Prisma create
2. ✅ `getVehicles()` - Prisma findMany
3. ✅ `getVehicleById()` - Prisma findUnique
4. ✅ `updateVehicle()` - Prisma update
5. ✅ `deleteVehicle()` - Prisma delete

### Configuration
- ✅ `package.json` - Added Prisma dependencies and scripts

---

## 🔑 Key Conversion Patterns

### Pattern 1: Finding Records
```javascript
// Before (Mongoose)
const user = await User.findOne({ email, platform });
const user = await User.findById(id);

// After (Prisma)
const user = await prisma.user.findFirst({ 
  where: { email: email?.toLowerCase().trim(), platform } 
});
const user = await prisma.user.findUnique({ where: { id } });
```

### Pattern 2: Creating Records
```javascript
// Before (Mongoose)
const user = new User({ email, password, ... });
await user.save(); // Password auto-hashed

// After (Prisma)
const hashedPassword = await bcrypt.hash(password, 10);
const user = await prisma.user.create({
  data: { email, password: hashedPassword, ... }
});
```

### Pattern 3: Updating Records
```javascript
// Before (Mongoose)
user.field = value;
await user.save();

// After (Prisma)
await prisma.user.update({
  where: { id },
  data: { field: value }
});
```

### Pattern 4: JSON Array Operations
```javascript
// Before (Mongoose)
user.addresses.push(address);
await user.save();

// After (Prisma)
const addresses = typeof user.addresses === 'string' 
  ? JSON.parse(user.addresses) 
  : user.addresses || [];
addresses.push(address);
await prisma.user.update({
  where: { id },
  data: { addresses }
});
```

---

## 🚀 Setup Instructions

### Step 1: Install Dependencies
```bash
cd user-service
npm install
```

### Step 2: Create `.env` File
Create `user-service/.env`:

```env
DATABASE_URL="mysql://root:password@localhost:3306/user_service"
PORT=3001
JWT_SECRET=your_jwt_secret_key_minimum_32_characters
CLIENT_SERVICE_URL=http://localhost:3005
NODE_ENV=development
```

**Update DATABASE_URL with your MySQL credentials!**

### Step 3: Create MySQL Database
```bash
mysql -u root -p
CREATE DATABASE user_service;
exit;
```

### Step 4: Generate Prisma Client
```bash
npm run prisma:generate
```

### Step 5: Run Migrations
```bash
npm run prisma:migrate
# Enter migration name: "init"
```

### Step 6: Start Service
```bash
npm run dev
```

**Service runs on port 3001** (unchanged) ✅

---

## 📊 Database Schema

### Table: `users`
- All fields from `user.model.js`
- JSON columns: `verificationDocuments`, `preferredVendors`, `addresses`
- Unique constraint: `[email, platform]` (same email can exist on different platforms)

### Table: `vehicles`
- All fields from `Vehicle.model.js`
- Unique constraint: `plateNumber`

---

## ✅ Verification Checklist

- [x] Prisma schema created (2 models)
- [x] Database connection updated
- [x] All 18 user controller methods converted
- [x] All 5 vehicle controller methods converted
- [x] Password hashing implemented manually
- [x] JSON array operations implemented
- [x] Package.json updated
- [x] Port 3001 unchanged

---

## 📝 Important Notes

1. **Password Hashing**: Done manually with `bcrypt.hash()` (no pre-save hooks)
2. **JSON Arrays**: Parse → Modify → Update pattern
3. **ID References**: `user._id` → `user.id` (UUID string)
4. **Unique Constraints**: `@@unique([email, platform])` allows same email on different platforms
5. **Error Handling**: Prisma throws `P2025` for record not found

---

## 🎉 Result

- ✅ **Only 2 tables** (User + Vehicle)
- ✅ **All methods converted** (23 total)
- ✅ **Same functionality** preserved
- ✅ **Same port** (3001)
- ✅ **All field names match** original models

---

**Migration Complete! Ready to use with MySQL!** 🚀




