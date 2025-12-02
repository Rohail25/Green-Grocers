# Product Service - Environment Setup

## MySQL Database Connection

The product-service now uses MySQL with Prisma. Update your `.env` file with the following:

```env
# Database Connection (MySQL)
DATABASE_URL="mysql://root:password@localhost:3306/product_service"

# Server Configuration
PORT=3003

# JWT Configuration (if used)
JWT_SECRET=your_jwt_secret_key

# AWS S3 Configuration (for file uploads)
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_REGION=your_region
AWS_BUCKET_NAME=your_bucket_name

# Other Service URLs (for inter-service communication)
VENDOR_SERVICE_URL=http://localhost:3004
```

## Setup Steps

1. **Create MySQL Database:**
   ```sql
   CREATE DATABASE product_service;
   ```

2. **Install Dependencies:**
   ```bash
   npm install
   ```

3. **Generate Prisma Client:**
   ```bash
   npm run prisma:generate
   ```

4. **Run Database Migrations:**
   ```bash
   npm run prisma:migrate
   ```

5. **Start the Service:**
   ```bash
   npm run dev
   ```

## Database Schema

The product-service uses the following tables (matching the models):
- `products` - Product information with JSON fields for variants, images, tags, coupons
- `brands` - Brand information
- `categories` - Category information
- `packages` - Package deals with JSON fields for items, discount, tags
- `review_ratings` - Product reviews with JSON field for user info

All nested arrays and objects are stored as JSON columns (like MongoDB).

## Notes

- The DATABASE_URL format: `mysql://username:password@host:port/database_name`
- Make sure MySQL is running before starting the service
- JSON fields are automatically parsed/stringified by Prisma




