# 📦 Complete Order Placement Flow - Order Service

## 🎯 Overview

This document explains the complete flow of placing an order in the order-service, from user registration to order completion.

---

## 📋 Table of Contents

1. [Prerequisites](#prerequisites)
2. [Step 1: User Registration & Login](#step-1-user-registration--login)
3. [Step 2: Shopping Cart Management](#step-2-shopping-cart-management)
4. [Step 3: Create Order](#step-3-create-order)
5. [Step 4: Payment Processing](#step-4-payment-processing)
6. [Step 5: Order Status Flow](#step-5-order-status-flow)
7. [Step 6: Returns & Refunds](#step-6-returns--refunds)
8. [Complete Flow Diagram](#complete-flow-diagram)
9. [API Endpoints Summary](#api-endpoints-summary)

---

## 🔐 Prerequisites

Before placing an order, the user must:

1. **Register** with a platform (`trivemart`, `trivestore`, or `triveexpress`)
2. **Confirm email** (sent after registration)
3. **Login** to get JWT token
4. **Add shipping address** (optional but recommended)

---

## 👤 Step 1: User Registration & Login

### 1.1 Register User

**Endpoint:** `POST /api/users/register`

**Request Body:**
```json
{
  "email": "customer@example.com",
  "phone": "+1234567890",
  "password": "SecurePassword123!",
  "confirmPassword": "SecurePassword123!",
  "platform": "trivemart"
}
```

**Response:**
```json
{
  "message": "User registered. Please check your email to confirm.",
  "requiresConfirmation": true
}
```

**What Happens:**
- User is created in the `users` table
- `vendorId` is generated if platform is `trivestore`
- `clientId` is generated if platform is `trivemart`
- Confirmation email is sent
- User profile created in client-service (if trivemart)

---

### 1.2 Confirm Email

**Endpoint:** `POST /api/users/confirm`

**Request Body:**
```json
{
  "token": "<confirmation-token-from-email>"
}
```

**Response:**
```json
{
  "message": "Email confirmed successfully"
}
```

---

### 1.3 Login

**Endpoint:** `POST /api/users/login`

**Request Body:**
```json
{
  "email": "customer@example.com",
  "password": "SecurePassword123!",
  "platform": "trivemart"
}
```

**Response:**
```json
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": "user-uuid",
    "email": "customer@example.com",
    "phone": "+1234567890",
    "platform": "trivemart",
    "vendorId": null,
    "clientId": "MART-abc12345"
  }
}
```

**⚠️ Important:** Save the `token` - you'll need it for all authenticated requests!

---

### 1.4 Add Shipping Address (Optional)

**Endpoint:** `POST /api/users/add-address`

**Headers:**
```
Authorization: Bearer <token>
```

**Request Body:**
```json
{
  "country": "USA",
  "state": "California",
  "city": "Los Angeles",
  "localGovernment": "LA County",
  "address": "123 Main Street, Apt 4B",
  "postalCode": "90001",
  "isDefault": true
}
```

**Response:**
```json
{
  "message": "Address added successfully",
  "address": {
    "country": "USA",
    "state": "California",
    "city": "Los Angeles",
    "address": "123 Main Street, Apt 4B",
    "postalCode": "90001",
    "isDefault": true
  }
}
```

---

## 🛒 Step 2: Shopping Cart Management

### 2.1 Add Item to Cart

**Endpoint:** `POST /api/carts/`

**Headers:**
```
Authorization: Bearer <token>
```

**Request Body:**
```json
{
  "productId": "product-uuid",
  "productName": "Organic Apples",
  "productImage": "/uploads/apples.jpg",
  "price": 5.99,
  "quantity": 2,
  "vendorId": "vendor-uuid",
  "variantIndex": 0
}
```

**Response:**
```json
{
  "message": "Item added to cart",
  "cart": {
    "id": "cart-uuid",
    "userId": "user-uuid",
    "items": [
      {
        "productId": "product-uuid",
        "productName": "Organic Apples",
        "productImage": "/uploads/apples.jpg",
        "price": 5.99,
        "quantity": 2,
        "vendorId": "vendor-uuid",
        "variantIndex": 0
      }
    ],
    "totalPrice": 11.98,
    "createdAt": "2024-01-15T10:30:00Z",
    "updatedAt": "2024-01-15T10:30:00Z"
  }
}
```

**What Happens:**
1. System checks if user has an existing cart
2. If item already exists (same `productId`), quantity is added
3. If item is new, it's added to the cart
4. `totalPrice` is recalculated automatically
5. Cart is saved/updated in database

---

### 2.2 Get User's Cart

**Endpoint:** `GET /api/carts/cart-by-user`

**Headers:**
```
Authorization: Bearer <token>
```

**Response:**
```json
{
  "id": "cart-uuid",
  "userId": "user-uuid",
  "items": [
    {
      "productId": "product-1",
      "productName": "Organic Apples",
      "price": 5.99,
      "quantity": 2,
      "vendorId": "vendor-uuid"
    },
    {
      "productId": "product-2",
      "productName": "Fresh Bananas",
      "price": 3.50,
      "quantity": 1,
      "vendorId": "vendor-uuid"
    }
  ],
  "totalPrice": 15.48,
  "createdAt": "2024-01-15T10:30:00Z",
  "updatedAt": "2024-01-15T10:30:00Z"
}
```

**If cart is empty:**
```json
{
  "message": "Cart is empty"
}
```
(Status: 204)

---

### 2.3 Update Cart Item Quantity

**Endpoint:** `PATCH /api/carts/update-item/:productId`

**Headers:**
```
Authorization: Bearer <token>
```

**Request Body:**
```json
{
  "quantity": 5
}
```

**Response:**
```json
{
  "message": "Cart item updated",
  "cart": {
    "id": "cart-uuid",
    "items": [...],
    "totalPrice": 29.95
  }
}
```

---

### 2.4 Remove Item from Cart

**Endpoint:** `DELETE /api/carts/remove-item/:productId`

**Headers:**
```
Authorization: Bearer <token>
```

**Response:**
```json
{
  "message": "Item removed from cart",
  "cart": {
    "id": "cart-uuid",
    "items": [...],
    "totalPrice": 3.50
  }
}
```

---

### 2.5 Clear Entire Cart

**Endpoint:** `DELETE /api/carts/clear-cart`

**Headers:**
```
Authorization: Bearer <token>
```

**Response:**
```json
{
  "message": "Cart cleared",
  "cart": {
    "id": "cart-uuid",
    "items": [],
    "totalPrice": 0
  }
}
```

---

## 📦 Step 3: Create Order

### 3.1 Create Order

**Endpoint:** `POST /api/orders/`

**Headers:**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "userId": "user-uuid",
  "items": [
    {
      "productId": "product-1",
      "productName": "Organic Apples",
      "productImage": "/uploads/apples.jpg",
      "itemId": "item-1",
      "category": "Fruits",
      "price": 5.99,
      "quantity": 2,
      "variantIndex": 0
    },
    {
      "productId": "product-2",
      "productName": "Fresh Bananas",
      "productImage": "/uploads/bananas.jpg",
      "itemId": "item-2",
      "category": "Fruits",
      "price": 3.50,
      "quantity": 1,
      "variantIndex": 0
    }
  ],
  "vendorId": "vendor-uuid",
  "totalAmount": 15.48,
  "customerName": "John Doe",
  "paymentStatus": "PENDING",
  "shippingAddress": {
    "street": "123 Main Street, Apt 4B",
    "city": "Los Angeles",
    "state": "California",
    "postalCode": "90001",
    "country": "USA",
    "phone": "+1234567890"
  },
  "discount": 1.50,
  "couponCode": "SAVE10"
}
```

**Response:**
```json
{
  "message": "Order created",
  "order": {
    "id": "order-uuid",
    "userId": "user-uuid",
    "vendorId": "vendor-uuid",
    "items": [...],
    "totalAmount": 15.48,
    "discountAmount": 1.50,
    "couponCode": "SAVE10",
    "paymentStatus": "PENDING",
    "status": "inprogress",
    "orderProgress": "Awaiting Confirmation",
    "deliveryStatus": "Pending",
    "shippingAddress": {...},
    "purchaseDate": "2024-01-15T10:45:00Z",
    "createdAt": "2024-01-15T10:45:00Z",
    "updatedAt": "2024-01-15T10:45:00Z"
  }
}
```

**What Happens Behind the Scenes:**

1. ✅ **Order Created:**
   - Order record created in `orders` table
   - Status: `"inprogress"`
   - Order Progress: `"Awaiting Confirmation"`
   - Payment Status: `"PENDING"`

2. ✅ **Product Inventory Updated:**
   - For each item in order:
     - Calls `product-service` to update product quantity
     - Reduces stock by `quantity` ordered
     - Updates variant quantity if `variantIndex` provided

3. ✅ **Cart Cleared:**
   - User's cart is automatically deleted
   - All items removed from cart

4. ✅ **Order Ready for Processing:**
   - Vendor will see order in "pending" status
   - Order can now be assigned to logistics

---

## 💳 Step 4: Payment Processing

After creating an order, the user needs to pay. The order-service supports multiple payment methods.

### 4.1 Initiate Payment

**Endpoint:** `POST /api/orders/pay/:orderId`

**Headers:**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "method": "WALLET"
}
```

**Supported Payment Methods:**
- `"WALLET"` - Pay from user's wallet balance
- `"CARD"` - Credit/Debit card via Stripe
- `"BANK"` - Bank transfer
- `"TRANSFER"` - Manual transfer
- `"USSD"` - USSD payment
- `"COD"` - Cash on Delivery
- `"PAYPAL"` - PayPal payment
- `"STRIPE"` - Stripe payment

---

### 4.2 Payment Flow by Method

#### A. WALLET Payment

**Request:**
```json
{
  "method": "WALLET"
}
```

**What Happens:**
1. Checks user's wallet balance (via `client-service`)
2. If insufficient balance → Returns 400 error
3. If sufficient balance:
   - Debits wallet via `client-service`
   - Updates order: `paymentStatus = "PAID"`
   - Updates order: `paymentMethod = "WALLET"`
   - Generates `transactionId`

**Response:**
```json
{
  "message": "Paid via Wallet",
  "order": {
    "id": "order-uuid",
    "paymentStatus": "PAID",
    "paymentMethod": "WALLET",
    "transactionId": "WALLET-1705312345678"
  }
}
```

---

#### B. CARD Payment (Stripe)

**Request:**
```json
{
  "method": "CARD"
}
```

**What Happens:**
1. Creates Stripe checkout session
2. Updates order: `paymentMethod = "CARD"`, `paymentStatus = "PENDING"`
3. Returns Stripe checkout URL

**Response:**
```json
{
  "url": "https://checkout.stripe.com/pay/cs_test_..."
}
```

**Next Steps:**
- User redirects to Stripe checkout URL
- Completes payment on Stripe
- Stripe sends webhook to order-service
- Order-service updates order to `"PAID"` status

---

#### C. COD (Cash on Delivery)

**Request:**
```json
{
  "method": "COD"
}
```

**What Happens:**
1. Updates order: `paymentMethod = "COD"`
2. Updates order: `paymentStatus = "PENDING"`
3. Payment marked as PAID only after delivery confirmation

**Response:**
```json
{
  "message": "Cash on Delivery selected",
  "order": {
    "id": "order-uuid",
    "paymentMethod": "COD",
    "paymentStatus": "PENDING"
  }
}
```

---

#### D. BANK / TRANSFER / USSD

**Request:**
```json
{
  "method": "BANK"
}
```

**What Happens:**
1. Updates order: `paymentMethod = "BANK"`, `paymentStatus = "PENDING"`
2. Generates transaction ID
3. Vendor/Admin manually confirms payment later

**Response:**
```json
{
  "message": "BANK selected. Awaiting confirmation.",
  "order": {
    "id": "order-uuid",
    "paymentMethod": "BANK",
    "paymentStatus": "PENDING",
    "transactionId": "MANUAL-1705312345678"
  }
}
```

---

### 4.3 Stripe Webhook (For Card Payments)

**Endpoint:** `POST /api/orders/webhook`

**Headers:**
```
Stripe-Signature: <stripe-signature>
Content-Type: application/json
```

**What Happens:**
- Stripe sends webhook when payment completes
- Order-service verifies signature
- If valid, updates order: `paymentStatus = "PAID"`
- Stores `payment_intent` as `transactionId`

---

## 📊 Step 5: Order Status Flow

After order creation, the order goes through different statuses.

### 5.1 Order Statuses

| Status | Description | Who Can Update | Next Status |
|--------|-------------|----------------|-------------|
| `inprogress` | Order created, awaiting vendor confirmation | System | `assigned` |
| `assigned` | Assigned to logistics agent | Vendor | `delivered`, `canceled` |
| `delivered` | Order delivered to customer | Logistics Agent | - |
| `canceled` | Order canceled | Vendor/Logistics | - |

---

### 5.2 Update Order Status

**Endpoint:** `PUT /api/orders/:orderId/status`

**Headers:**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "status": "assigned",
  "orderProgress": "Order assigned to delivery agent"
}
```

**Authorization Rules:**
- `"assigned"` / `"dispatched"`: Only vendor (platform = `"trivestore"`)
- `"delivered"` / `"canceled"`: Only logistics agent assigned to order

**Response:**
```json
{
  "message": "Order status updated",
  "order": {
    "id": "order-uuid",
    "status": "assigned",
    "deliveryStatus": "Out for Delivery",
    "orderProgress": "Order assigned to delivery agent",
    "authenticationCode": "1234",
    "deliveryTimeline": "Tuesday 24/04/24",
    "statusHistory": [
      {
        "status": "inprogress",
        "updatedAt": "2024-01-15T10:45:00Z",
        "updatedBy": "user-uuid"
      },
      {
        "status": "assigned",
        "updatedAt": "2024-01-15T11:00:00Z",
        "updatedBy": "vendor-uuid"
      }
    ]
  }
}
```

**What Happens When Status is `"assigned"`:**
- 4-digit authentication code generated
- Delivery timeline set
- Order status history updated
- Order can be tracked

**What Happens When Status is `"delivered"`:**
- Order marked as delivered
- Delivery date set
- Agent earning created (via `logistic-service`)
- Order completion recorded

---

### 5.3 Get Order Details

**Endpoint:** `GET /api/orders/:orderId`

**Headers:**
```
Authorization: Bearer <token>
```

**Response:**
```json
{
  "order": {
    "id": "order-uuid",
    "userId": "user-uuid",
    "vendorId": "vendor-uuid",
    "items": [...],
    "totalAmount": 15.48,
    "paymentStatus": "PAID",
    "paymentMethod": "WALLET",
    "status": "assigned",
    "deliveryStatus": "Out for Delivery",
    "orderProgress": "Order assigned to delivery agent",
    "shippingAddress": {...},
    "purchaseDate": "2024-01-15T10:45:00Z",
    "expectedDeliveryDate": "2024-01-16T14:00:00Z",
    "statusHistory": [...]
  }
}
```

---

### 5.4 Get User's Orders

**Endpoint:** `GET /api/orders/user/:userId`

**Headers:**
```
Authorization: Bearer <token>
```

**Response:**
```json
{
  "orders": [
    {
      "id": "order-1",
      "status": "delivered",
      "totalAmount": 15.48,
      ...
    },
    {
      "id": "order-2",
      "status": "inprogress",
      "totalAmount": 29.99,
      ...
    }
  ]
}
```

---

### 5.5 Get Vendor's Orders

**Endpoint:** `GET /api/orders/vendor/:vendorId`

**Headers:**
```
Authorization: Bearer <token>
```

**Response:**
```json
[
  {
    "id": "order-1",
    "userId": "user-1",
    "status": "inprogress",
    ...
  },
  {
    "id": "order-2",
    "userId": "user-2",
    "status": "assigned",
    ...
  }
]
```

---

## 🔄 Step 6: Returns & Refunds

### 6.1 Request Return

**Endpoint:** `PUT /api/orders/:orderId/return-request`

**Headers:**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "reason": "Product damaged during delivery"
}
```

**Response:**
```json
{
  "message": "Return requested successfully",
  "order": {
    "id": "order-uuid",
    "isReturnRequested": true,
    "returnRequest": {
      "isRequested": true,
      "reason": "Product damaged during delivery",
      "requestedAt": "2024-01-16T10:00:00Z",
      "status": "Pending"
    }
  }
}
```

**Authorization:** Only the user who placed the order can request return.

---

### 6.2 Process Return (Admin/Vendor)

**Endpoint:** `PUT /api/orders/:orderId/return-process`

**Headers:**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "status": "Approved",
  "refundAmount": 15.48,
  "processedBy": "admin-uuid"
}
```

**Status Options:**
- `"Approved"` - Return approved
- `"Rejected"` - Return rejected
- `"Refunded"` - Refund processed

**What Happens When Status is `"Refunded"`:**
1. Order `paymentStatus` set to `"UNPAID"`
2. Order `status` set to `"canceled"`
3. Refund amount credited to user's wallet (via `client-service`)

**Response:**
```json
{
  "message": "Return refunded successfully",
  "order": {
    "id": "order-uuid",
    "returnRequest": {
      "status": "Refunded",
      "refundAmount": 15.48,
      "processedBy": "admin-uuid"
    },
    "paymentStatus": "UNPAID",
    "status": "canceled"
  }
}
```

---

## 🔄 Complete Flow Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                    ORDER PLACEMENT FLOW                      │
└─────────────────────────────────────────────────────────────┘

1. USER REGISTRATION
   ├─ POST /api/users/register
   ├─ Email confirmation required
   └─ User profile created

2. USER LOGIN
   ├─ POST /api/users/login
   └─ JWT token received

3. ADD ADDRESS (Optional)
   └─ POST /api/users/add-address

4. SHOPPING CART MANAGEMENT
   ├─ Add Items: POST /api/carts/
   ├─ View Cart: GET /api/carts/cart-by-user
   ├─ Update Quantity: PATCH /api/carts/update-item/:productId
   ├─ Remove Item: DELETE /api/carts/remove-item/:productId
   └─ Clear Cart: DELETE /api/carts/clear-cart

5. CREATE ORDER
   ├─ POST /api/orders/
   ├─ Order created in database
   ├─ Product inventory updated (via product-service)
   └─ Cart cleared automatically

6. PAYMENT PROCESSING
   ├─ POST /api/orders/pay/:orderId
   ├─ Payment method selected:
   │   ├─ WALLET → Debit wallet (via client-service)
   │   ├─ CARD → Stripe checkout → Webhook confirmation
   │   ├─ COD → Marked as PENDING
   │   └─ BANK/TRANSFER/USSD → Manual confirmation
   └─ Order paymentStatus updated

7. ORDER STATUS UPDATES
   ├─ Vendor assigns: PUT /api/orders/:orderId/status → "assigned"
   │   └─ Authentication code generated
   ├─ Logistics delivers: PUT /api/orders/:orderId/status → "delivered"
   │   └─ Agent earning created (via logistic-service)
   └─ Status history tracked

8. RETURNS & REFUNDS (Optional)
   ├─ Customer requests: PUT /api/orders/:orderId/return-request
   └─ Admin processes: PUT /api/orders/:orderId/return-process
       └─ Refund to wallet (via client-service)
```

---

## 📚 API Endpoints Summary

### User Management
| Method | Endpoint | Auth Required | Description |
|--------|----------|---------------|-------------|
| POST | `/api/users/register` | No | Register new user |
| POST | `/api/users/login` | No | User login |
| POST | `/api/users/confirm` | No | Confirm email |
| GET | `/api/users/` | Yes | Get user profile |
| PUT | `/api/users/profile` | Yes | Update profile |
| POST | `/api/users/add-address` | Yes | Add shipping address |
| PATCH | `/api/users/address/:addressId` | Yes | Update address |
| DELETE | `/api/users/address/:addressId` | Yes | Delete address |

### Cart Management
| Method | Endpoint | Auth Required | Description |
|--------|----------|---------------|-------------|
| POST | `/api/carts/` | Yes | Add item to cart |
| GET | `/api/carts/cart-by-user` | Yes | Get user's cart |
| PATCH | `/api/carts/update-item/:productId` | Yes | Update item quantity |
| DELETE | `/api/carts/remove-item/:productId` | Yes | Remove item |
| DELETE | `/api/carts/clear-cart` | Yes | Clear entire cart |

### Order Management
| Method | Endpoint | Auth Required | Description |
|--------|----------|---------------|-------------|
| POST | `/api/orders/` | Yes | Create new order |
| GET | `/api/orders/:orderId` | Yes | Get order details |
| GET | `/api/orders/user/:userId` | Yes | Get user's orders |
| GET | `/api/orders/vendor/:vendorId` | Yes | Get vendor's orders |
| GET | `/api/orders/logistic/:logisticId` | Yes | Get logistic's orders |
| PUT | `/api/orders/:orderId/status` | Yes | Update order status |
| PUT | `/api/orders/:orderId/return-request` | Yes | Request return |
| PUT | `/api/orders/:orderId/return-process` | Yes | Process return |
| POST | `/api/orders/pay/:orderId` | No* | Initiate payment |
| POST | `/api/orders/webhook` | No | Stripe webhook |

*Payment endpoint doesn't require auth, but order must exist.

---

## 🔗 Inter-Service Communication

The order-service communicates with other microservices:

1. **Product Service:**
   - Updates product inventory when order is created
   - Endpoint: `PATCH /products/update-quantity/:productId`

2. **Client Service:**
   - Debits wallet for WALLET payments
   - Credits wallet for refunds
   - Endpoints: `/api/wallets/debit`, `/api/wallets/credit`

3. **Logistic Service:**
   - Creates agent earning when order is delivered
   - Endpoint: `POST /earnings/create`

---

## ⚠️ Important Notes

1. **Authentication:** Most endpoints require JWT token in `Authorization: Bearer <token>` header
2. **Cart Deletion:** Cart is automatically deleted when order is created
3. **Payment Status:** COD orders remain `"PENDING"` until delivery
4. **Order Status:** Only authorized users can update order status
5. **Returns:** Only the order owner can request returns
6. **Refunds:** Only admin/vendor can process returns

---

## 🧪 Example: Complete Order Flow

### Step-by-Step Example:

1. **Register:**
```bash
POST /api/users/register
{
  "email": "john@example.com",
  "password": "Password123!",
  "confirmPassword": "Password123!",
  "platform": "trivemart"
}
```

2. **Login:**
```bash
POST /api/users/login
{
  "email": "john@example.com",
  "password": "Password123!",
  "platform": "trivemart"
}
# Save token: "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
```

3. **Add to Cart:**
```bash
POST /api/carts/
Authorization: Bearer <token>
{
  "productId": "prod-1",
  "productName": "Organic Apples",
  "price": 5.99,
  "quantity": 2,
  "vendorId": "vendor-1"
}
```

4. **Create Order:**
```bash
POST /api/orders/
Authorization: Bearer <token>
{
  "userId": "user-uuid",
  "items": [...],
  "vendorId": "vendor-1",
  "totalAmount": 11.98,
  "shippingAddress": {...}
}
```

5. **Pay (Wallet):**
```bash
POST /api/orders/pay/order-uuid
{
  "method": "WALLET"
}
```

6. **Vendor Assigns Order:**
```bash
PUT /api/orders/order-uuid/status
Authorization: Bearer <vendor-token>
{
  "status": "assigned"
}
```

7. **Logistics Delivers:**
```bash
PUT /api/orders/order-uuid/status
Authorization: Bearer <logistics-token>
{
  "status": "delivered"
}
```

---

## 📝 Database Schema Reference

### Cart Table
- `id` (UUID)
- `userId` (String, unique)
- `items` (JSON array)
- `totalPrice` (Decimal)
- `createdAt`, `updatedAt` (DateTime)

### Order Table
- `id` (UUID)
- `userId`, `vendorId`, `logisticsId` (String)
- `items` (JSON array)
- `totalAmount`, `discountAmount`, `deliveryCharges` (Decimal)
- `shippingAddress` (JSON object)
- `statusHistory` (JSON array)
- `returnRequest` (JSON object)
- `paymentStatus`, `paymentMethod`, `status`, `deliveryStatus` (String)
- `createdAt`, `updatedAt` (DateTime)

---

## 🎉 Summary

The order placement flow is:
1. **User Registration** → 2. **Add to Cart** → 3. **Create Order** → 4. **Make Payment** → 5. **Order Processing** → 6. **Delivery** → 7. **Complete**

All operations are tracked, and the system handles inventory updates, cart management, payment processing, and order status updates automatically!



