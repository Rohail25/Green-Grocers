# User Service - Method Changes Summary

## 🔄 All Methods Converted from Mongoose to Prisma

This document shows how each method was changed.

---

## User Controller Methods

### 1. registerUser()
**Before:**
```javascript
const user = new User({ email, password, ... });
await user.save(); // Password auto-hashed by pre-save hook
```

**After:**
```javascript
const hashedPassword = await bcrypt.hash(password, 10);
const user = await prisma.user.create({
  data: { email, password: hashedPassword, ... }
});
```

**Changes:**
- ✅ Manual password hashing with `bcrypt.hash()`
- ✅ `user._id` → `user.id`
- ✅ `new User()` → `prisma.user.create()`

---

### 2. loginUser()
**Before:**
```javascript
const user = await User.findOne({ email, platform });
const isMatch = await user.comparePassword(password);
```

**After:**
```javascript
const user = await prisma.user.findFirst({ 
  where: { email: email.toLowerCase().trim(), platform } 
});
const isMatch = await bcrypt.compare(password, user.password);
```

**Changes:**
- ✅ `findOne()` → `findFirst()`
- ✅ `user.comparePassword()` → `bcrypt.compare()`
- ✅ Email lowercased and trimmed

---

### 3. createAgent()
**Before:**
```javascript
const agent = new User({ ... });
await agent.save(); // Password auto-hashed
```

**After:**
```javascript
const hashedPassword = await bcrypt.hash(randomPassword, 10);
const agent = await prisma.user.create({
  data: { ...password: hashedPassword, ... }
});
```

**Changes:**
- ✅ Manual password hashing
- ✅ `new User()` → `prisma.user.create()`

---

### 4. resetPassword()
**Before:**
```javascript
const user = await User.findById(decoded.id);
user.password = newPassword;
await user.save(); // Auto-hashed
```

**After:**
```javascript
const user = await prisma.user.findUnique({ where: { id: decoded.id } });
const hashedPassword = await bcrypt.hash(newPassword, 10);
await prisma.user.update({
  where: { id: decoded.id },
  data: { password: hashedPassword }
});
```

**Changes:**
- ✅ `findById()` → `findUnique()`
- ✅ Manual password hashing before update

---

### 5. getProfile()
**Before:**
```javascript
const user = await User.findById(req.user.id).select("-password");
```

**After:**
```javascript
const user = await prisma.user.findUnique({ where: { id: req.user.id } });
const { password, ...userWithoutPassword } = user;
```

**Changes:**
- ✅ `findById()` → `findUnique()`
- ✅ Manual password removal (no `.select()` in Prisma)

---

### 6. updateProfile()
**Before:**
```javascript
const updatedUser = await User.findByIdAndUpdate(userId, updates, {
  new: true,
  runValidators: true,
}).select('-password');
```

**After:**
```javascript
const updatedUser = await prisma.user.update({
  where: { id: userId },
  data: updates
});
const { password, ...userWithoutPassword } = updatedUser;
```

**Changes:**
- ✅ `findByIdAndUpdate()` → `update()`
- ✅ JSON fields parsed before update
- ✅ Manual password removal

---

### 7. confirmEmail()
**Before:**
```javascript
const user = await User.findById(payload.id);
user.isEmailConfirmed = true;
await user.save();
```

**After:**
```javascript
await prisma.user.update({
  where: { id: payload.id },
  data: { isEmailConfirmed: true }
});
```

**Changes:**
- ✅ Direct update without fetching first

---

### 8. resendConfirmationEmail()
**Before:**
```javascript
const user = await User.findOne({ email, platform });
const token = jwt.sign({ id: user._id, platform }, ...);
```

**After:**
```javascript
const user = await prisma.user.findFirst({ 
  where: { email: email?.toLowerCase().trim(), platform } 
});
const token = jwt.sign({ id: user.id, platform }, ...);
```

**Changes:**
- ✅ `findOne()` → `findFirst()`
- ✅ `user._id` → `user.id`

---

### 9. addAddress()
**Before:**
```javascript
const user = await User.findById(userId);
user.addresses.push(address);
await user.save();
```

**After:**
```javascript
const user = await prisma.user.findUnique({ where: { id: userId } });
const addresses = typeof user.addresses === 'string' 
  ? JSON.parse(user.addresses) 
  : user.addresses || [];
addresses.push(address);
await prisma.user.update({
  where: { id: userId },
  data: { addresses }
});
```

**Changes:**
- ✅ JSON array parsing
- ✅ Manual array manipulation
- ✅ Update with new array

---

### 10. updateAddress()
**Before:**
```javascript
const addressToUpdate = user.addresses.find(
  (addr) => addr._id.toString() === addressId
);
addressToUpdate.set(address);
await user.save();
```

**After:**
```javascript
const addresses = JSON.parse(user.addresses) || [];
const addressIndex = addresses.findIndex(...);
addresses[addressIndex] = { ...addresses[addressIndex], ...address };
await prisma.user.update({
  where: { id: userId },
  data: { addresses }
});
```

**Changes:**
- ✅ JSON parsing
- ✅ Array index-based update
- ✅ Spread operator for merging

---

### 11. deleteAddress()
**Before:**
```javascript
user.addresses = user.addresses.filter(
  (addr) => addr._id.toString() !== addressId
);
await user.save();
```

**After:**
```javascript
const addresses = JSON.parse(user.addresses) || [];
const filteredAddresses = addresses.filter(...);
await prisma.user.update({
  where: { id: userId },
  data: { addresses: filteredAddresses }
});
```

**Changes:**
- ✅ JSON parsing
- ✅ Array filter operation
- ✅ Update with filtered array

---

### 12. addFavoriteProduct()
**Before:**
```javascript
await User.findByIdAndUpdate(
  req.user.id,
  { $addToSet: { favoriteProducts: {...} } },
  { new: true }
);
```

**After:**
```javascript
const favoriteProducts = JSON.parse(user.preferredVendors) || [];
const updatedFavorites = [...favoriteProducts, {...}];
await prisma.user.update({
  where: { id: req.user.id },
  data: { preferredVendors: updatedFavorites }
});
```

**Changes:**
- ✅ JSON parsing
- ✅ Manual array manipulation
- ✅ No `$addToSet` operator (check manually)

---

### 13. uploadVerificationDocument()
**Before:**
```javascript
user.verificationDocuments.push({...});
user.documentsUploaded = true;
await user.save();
```

**After:**
```javascript
const verificationDocuments = JSON.parse(user.verificationDocuments) || [];
verificationDocuments.push({...});
await prisma.user.update({
  where: { id: userId },
  data: { verificationDocuments, documentsUploaded: true }
});
```

**Changes:**
- ✅ JSON array parsing
- ✅ Manual array push
- ✅ Combined update

---

### 14. setAvailabilityAndAddress()
**Before:**
```javascript
user.isAvailable = available;
if (address) user.addresses = [address];
await user.save();
```

**After:**
```javascript
const updateData = { isAvailable: available };
if (address) updateData.addresses = [address];
await prisma.user.update({
  where: { id: userId },
  data: updateData
});
```

**Changes:**
- ✅ Direct update without fetch-first

---

### 15. getAgents()
**Before:**
```javascript
const agents = await User.find({
  parentLogistic: logisticId,
  role: "agent",
}).select("-password");
```

**After:**
```javascript
const agents = await prisma.user.findMany({
  where: {
    parentLogistic: logisticId,
    role: "agent",
  }
});
const agentsWithoutPassword = agents.map(({ password, ...agent }) => agent);
```

**Changes:**
- ✅ `find()` → `findMany()`
- ✅ Manual password removal

---

### 16. updateAgent()
**Before:**
```javascript
const agent = await User.findOneAndUpdate(
  { _id: id, parentLogistic: logisticId, role: "agent" },
  req.body,
  { new: true }
).select("-password");
```

**After:**
```javascript
// Verify first
const agent = await prisma.user.findFirst({
  where: { id, parentLogistic: logisticId, role: "agent" }
});
// Then update
const updated = await prisma.user.update({
  where: { id },
  data: updates
});
```

**Changes:**
- ✅ Two-step process (verify then update)
- ✅ Manual password removal

---

### 17. deleteAgent()
**Before:**
```javascript
const agent = await User.findOneAndDelete({
  _id: id,
  parentLogistic: logisticId,
  role: "agent",
});
```

**After:**
```javascript
// Verify first
const agent = await prisma.user.findFirst({
  where: { id, parentLogistic: logisticId, role: "agent" }
});
// Then delete
await prisma.user.delete({ where: { id } });
```

**Changes:**
- ✅ Two-step process (verify then delete)

---

## Vehicle Controller Methods

### 1. addVehicle()
**Before:**
```javascript
const vehicle = new Vehicle({...});
await vehicle.save();
```

**After:**
```javascript
const vehicle = await prisma.vehicle.create({
  data: { userId: String(userId), ... }
});
```

**Changes:**
- ✅ `new Vehicle()` → `prisma.vehicle.create()`
- ✅ userId converted to String

---

### 2. getVehicles()
**Before:**
```javascript
const vehicles = await Vehicle.find(filter).sort({ createdAt: -1 });
```

**After:**
```javascript
const vehicles = await prisma.vehicle.findMany({
  where: filter,
  orderBy: { createdAt: 'desc' }
});
```

**Changes:**
- ✅ `find()` → `findMany()`
- ✅ `.sort()` → `orderBy`

---

### 3. getVehicleById()
**Before:**
```javascript
const vehicle = await Vehicle.findById(req.params.id);
```

**After:**
```javascript
const vehicle = await prisma.vehicle.findUnique({
  where: { id: req.params.id }
});
```

**Changes:**
- ✅ `findById()` → `findUnique()`

---

### 4. updateVehicle()
**Before:**
```javascript
const vehicle = await Vehicle.findByIdAndUpdate(id, updates, {
  new: true,
  runValidators: true,
});
```

**After:**
```javascript
const vehicle = await prisma.vehicle.update({
  where: { id },
  data: updates
});
```

**Changes:**
- ✅ `findByIdAndUpdate()` → `update()`

---

### 5. deleteVehicle()
**Before:**
```javascript
const vehicle = await Vehicle.findByIdAndDelete(id);
```

**After:**
```javascript
// Check first
const vehicle = await prisma.vehicle.findUnique({ where: { id } });
if (!vehicle) return res.status(404).json({ message: "Vehicle not found" });
// Then delete
await prisma.vehicle.delete({ where: { id } });
```

**Changes:**
- ✅ Two-step process (check then delete)

---

## 📊 Summary of Changes

### Query Methods:
- `User.findOne()` → `prisma.user.findFirst()`
- `User.findById()` → `prisma.user.findUnique()`
- `User.find()` → `prisma.user.findMany()`

### Update Methods:
- `User.findByIdAndUpdate()` → `prisma.user.update()`
- `User.findOneAndUpdate()` → `prisma.user.update()` (with findFirst first)

### Delete Methods:
- `User.findByIdAndDelete()` → `prisma.user.delete()` (with findUnique first)
- `User.findOneAndDelete()` → `prisma.user.delete()` (with findFirst first)

### Create Methods:
- `new User()` → `prisma.user.create()`
- `await user.save()` → Included in `create()`

### JSON Array Operations:
- Parse JSON → Modify array → Update with Prisma
- No more `.push()` directly on model

### Password Handling:
- Manual `bcrypt.hash()` before create/update
- Manual `bcrypt.compare()` for login
- No pre-save hooks

### ID References:
- `user._id` → `user.id`
- `user.id` is now UUID string (not ObjectId)

---

**All methods successfully converted!** ✅




