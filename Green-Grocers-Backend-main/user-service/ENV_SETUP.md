# User Service - .env File Setup

## 📋 Create `.env` File

Create a file named `.env` in the `user-service/` directory with this content:

```env
# ============================================
# MySQL Database Configuration
# ============================================
DATABASE_URL="mysql://root:password@localhost:3306/user_service"

# ============================================
# Server Configuration
# ============================================
PORT=3001

# ============================================
# JWT Configuration
# ============================================
JWT_SECRET=your_jwt_secret_key_here_minimum_32_characters

# ============================================
# Service URLs (Microservices Communication)
# ============================================
CLIENT_SERVICE_URL=http://localhost:3005

# ============================================
# Optional: Environment
# ============================================
NODE_ENV=development
```

## 🔧 Update Your Credentials

**IMPORTANT:** Replace these values:

1. **DATABASE_URL** - Update with your MySQL credentials:
   ```env
   DATABASE_URL="mysql://YOUR_USERNAME:YOUR_PASSWORD@localhost:3306/user_service"
   ```

2. **JWT_SECRET** - Generate a strong secret:
   ```bash
   node -e "console.log(require('crypto').randomBytes(32).toString('hex'))"
   ```
   Then update:
   ```env
   JWT_SECRET=generated_key_here
   ```

## ✅ Verification

After creating `.env`, test:

```bash
cd user-service
npm install
npm run prisma:generate
npm run prisma:migrate
npm run dev
```

You should see:
```
MySQL database connected via Prisma
Database connection verified
User service running on port 3001
```

---

**Port 3001 remains unchanged!** ✅




