# Database Seeders

This directory contains seeder files to populate your database with test data.

## 📁 Files

- `user_seeder.php` - Creates test users with Vendor IDs and Client IDs
- `run_seeder.php` - Runs all seeders (web interface)

## 🚀 How to Use

### Method 1: Web Browser (Easiest)
1. Visit: `http://localhost/green-grocers/database/seeders/run_seeder.php`
2. The seeder will run and show results

### Method 2: Command Line
```bash
cd C:\xampp\htdocs\green-grocers
php database/seeders/user_seeder.php
```

## 📋 What Gets Created

### User Seeder Creates:
- **Trivestore Users** (with vendorId):
  - vendor1@trivestore.com (vendor role)
  - vendor2@trivestore.com (vendor role)
  - customer1@trivestore.com (customer role)

- **Trivemart Users** (with clientId):
  - client1@trivemart.com (customer role)
  - client2@trivemart.com (customer role)

- **Admin User**:
  - admin@green-grocers.com (admin role)

- **Logistic User**:
  - logistic1@green-grocers.com (logistic role)

## 🔑 Default Passwords

All test users (except admin) use password: `password123`
Admin user uses password: `admin123`

## 🔄 Vendor ID Format

- **Trivestore users**: Get `VEND-XXXXXXXX` (8 random characters)
- **Trivemart users**: Get `MART-XXXXXXXX` (8 random characters)

## ⚠️ Important Notes

1. **Duplicate Prevention**: The seeder checks if users already exist and skips them
2. **Safe to Run Multiple Times**: Won't create duplicates
3. **Test Data Only**: Use for development/testing only
4. **Email Confirmation**: All users are pre-confirmed (isEmailConfirmed = true)

## 🧹 Reset Database

If you want to start fresh:

1. Delete existing test users:
   ```sql
   DELETE FROM users WHERE email LIKE '%@trivestore.com' 
      OR email LIKE '%@trivemart.com' 
      OR email LIKE '%@green-grocers.com';
   ```

2. Run seeder again

## 📊 Verify Results

After running the seeder, check your database:

```sql
SELECT email, platform, role, vendorId, clientId 
FROM users 
WHERE email LIKE '%@trivestore.com' 
   OR email LIKE '%@trivemart.com' 
   OR email LIKE '%@green-grocers.com';
```

## 🎯 Example Output

```
🌱 Starting User Seeder...

✅ Created: vendor1@trivestore.com (trivestore) | Vendor ID: VEND-A1B2C3D4
✅ Created: vendor2@trivestore.com (trivestore) | Vendor ID: VEND-E5F6G7H8
✅ Created: client1@trivemart.com (trivemart) | Client ID: MART-X9Y8Z7W6
✅ Created: admin@green-grocers.com (trivemart) | Client ID: MART-A1B2C3D4

📊 Seeder Summary:
✅ Inserted: 7 users
⏭️  Skipped: 0 users
❌ Errors: 0
```

## 🔧 Customization

To add more test users, edit `user_seeder.php` and add entries to the `$users` array.

