# Authentication Guide - Product Service

## Problem
Getting "Token missing" error when creating products.

## Solution
You need to login first to get a JWT token, then include it in the Authorization header.

---

## Step-by-Step: Getting Token and Using It

### Step 1: Login to Get JWT Token

**Request:**
```
POST http://localhost:3001/api/users/login
Content-Type: application/json
```

**Body (JSON):**
```json
{
  "email": "vendor@example.com",
  "password": "yourpassword",
  "platform": "trivestore"
}
```

**Response:**
```json
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": "user-id",
    "email": "vendor@example.com",
    "role": "vendor",
    "platform": "trivestore",
    "vendorId": "VEND-12345678"
  }
}
```

**📋 Copy the `token` value from the response!**

---

### Step 2: Create Product with Token

**Request:**
```
POST http://localhost:3003/api/products
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
Content-Type: multipart/form-data
```

**Form Data:**
- `name`: "Product Name"
- `description`: "Product Description"
- `vendorId`: "VEND-12345678"
- `retailPrice`: "99.99"
- `images`: [Select file(s)]
- `variants`: `[{"size":"M","color":"Red","quantity":10}]`
- etc.

---

## Postman Setup

### Option 1: Manual Header (Recommended)

1. **Open Postman**
2. **Create New Request**: `POST http://localhost:3003/api/products`
3. **Go to Headers tab**
4. **Add Header**:
   - Key: `Authorization`
   - Value: `Bearer <your-token-here>`
   - Replace `<your-token-here>` with the actual token from login

**Example:**
```
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpZCI6IjEyMzQ1NiIsInBsYXRmb3JtIjoi...
```

### Option 2: Using Postman Environment Variables

1. **Create Environment**:
   - Click "Environments" → "Create"
   - Name: "Development"
   - Add Variable:
     - Variable: `token`
     - Initial Value: (leave empty)

2. **Set Token After Login**:
   - After login response, copy token
   - Click Environment quick look (eye icon)
   - Paste token into `token` variable

3. **Use in Request**:
   - In Headers, set:
     - Key: `Authorization`
     - Value: `Bearer {{token}}`

4. **Select Environment**: Select "Development" from dropdown

### Option 3: Pre-request Script (Auto Login)

Add this to **Pre-request Script** tab:

```javascript
// Auto login and set token
if (!pm.environment.get("token")) {
    pm.sendRequest({
        url: 'http://localhost:3001/api/users/login',
        method: 'POST',
        header: {
            'Content-Type': 'application/json'
        },
        body: {
            mode: 'raw',
            raw: JSON.stringify({
                email: 'vendor@example.com',
                password: 'yourpassword',
                platform: 'trivestore'
            })
        }
    }, function (err, res) {
        if (res.json().token) {
            pm.environment.set("token", res.json().token);
        }
    });
}
```

Then use `Bearer {{token}}` in Authorization header.

---

## Quick Test Commands

### Using cURL

**1. Login:**
```bash
curl -X POST http://localhost:3001/api/users/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "vendor@example.com",
    "password": "yourpassword",
    "platform": "trivestore"
  }'
```

**Copy the token from response!**

**2. Create Product:**
```bash
curl -X POST http://localhost:3003/api/products \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: multipart/form-data" \
  -F "name=Test Product" \
  -F "vendorId=VEND-12345678" \
  -F "retailPrice=99.99" \
  -F "images=@/path/to/image.jpg"
```

---

## Token Format

The auth middleware expects:
```
Authorization: Bearer <token>
```

**Important:**
- ✅ Must include "Bearer " prefix
- ✅ One space between "Bearer" and token
- ✅ No quotes around token
- ❌ Wrong: `Authorization: token`
- ❌ Wrong: `Authorization: "Bearer token"`
- ✅ Correct: `Authorization: Bearer eyJhbGc...`

---

## Common Errors

### 1. "Token missing"
**Cause**: Authorization header not included or empty  
**Fix**: Add `Authorization: Bearer <token>` header

### 2. "Invalid token"
**Cause**: Token expired, wrong secret, or malformed  
**Fix**: Login again to get new token

### 3. "Token expired"
**Cause**: Token past expiration time  
**Fix**: Login again to get fresh token (tokens expire after 7 days)

---

## Testing Without Auth (Development Only)

If you want to test without authentication (NOT recommended for production):

**Modify `src/routes/product.routes.js`:**

```javascript
// Remove auth from create route
router.post(
  "/",
  // auth,  // ← Comment this out
  upload.fields([...]),
  createProduct
);
```

**⚠️ Warning**: Only do this for development/testing. Always use authentication in production!

---

## User Service Port

The user-service typically runs on:
- **Port**: `3001`
- **Login Endpoint**: `http://localhost:3001/api/users/login`

Check your `.env` file or user-service configuration for the correct port.

---

## Platform Options

When logging in, use one of these platforms:
- `"trivemart"` - For customers/clients
- `"trivestore"` - For vendors
- `"triveexpress"` - For logistics

Make sure you use the correct platform for your user role!

---

## Summary

1. ✅ **Login first** → Get JWT token from user-service
2. ✅ **Copy token** → From login response
3. ✅ **Add header** → `Authorization: Bearer <token>`
4. ✅ **Create product** → Request should work!

That's it! 🎉




