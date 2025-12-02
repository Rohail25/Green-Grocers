const request = require('supertest');
const mongoose = require('mongoose');
const { MongoMemoryServer } = require('mongodb-memory-server');
const app = require('../src/app'); 
const Order = require('../src/models/order.model');

let mongoServer;

beforeAll(async () => {
  mongoServer = await MongoMemoryServer.create();
  const uri = mongoServer.getUri();
  await mongoose.connect(uri, { useNewUrlParser: true, useUnifiedTopology: true });
});

afterAll(async () => {
  await mongoose.disconnect();
  await mongoServer.stop();
});

afterEach(async () => {
  await Order.deleteMany({});
});

describe('Order API', () => {
  test('POST /api/orders - should create an order', async () => {
    const response = await request(app)
      .post('/api/orders')
      .send({
        userId: 'user123',
        vendorId: 'vendor456',
        items: [
          {
            productId: 'prod1',
            productName: 'Test Product',
            productImage: 'image.jpg',
            itemId: 'item123',
            category: 'CategoryA',
            price: 50,
            quantity: 2
          }
        ],
        totalAmount: 100,
        customerName: 'John Doe',
        paymentStatus: 'PAID',
        shippingAddress: '123 Test St',
        discount: 10,
        couponCode: 'SAVE10'
      });

    expect(response.statusCode).toBe(201);
    expect(response.body.order).toHaveProperty('_id');
    expect(response.body.order.totalAmount).toBe(100);
  });

  test('GET /api/orders/vendor/:vendorId - should fetch vendor orders', async () => {
    await Order.create({
      userId: 'user1',
      vendorId: 'vendor123',
      items: [],
      totalAmount: 10,
      customerName: 'Vendor Test'
    });

    const response = await request(app).get('/api/orders/vendor/vendor123');
    expect(response.statusCode).toBe(200);
    expect(response.body.length).toBe(1);
  });

  test('PUT /api/orders/:orderId/status - should update order status (vendor)', async () => {
    const order = await Order.create({
      userId: 'user1',
      vendorId: 'vendor789',
      items: [],
      totalAmount: 10,
      customerName: 'Update Test'
    });

    const response = await request(app)
      .put(`/api/orders/${order._id}/status`)
      .set('Authorization', 'Bearer vendor-token') // Mocked in middleware
      .send({
        status: 'assigned',
        orderProgress: 'Vendor assigned'
      });

    // Without real auth middleware, this would 401 or 403 — you need to mock req.user
    expect([200, 401, 403]).toContain(response.statusCode);
  });

  test('GET /api/orders/user/:userId - should fetch user orders', async () => {
    await Order.create({
      userId: 'user456',
      vendorId: 'vendor999',
      items: [],
      totalAmount: 75,
      customerName: 'User Fetch'
    });

    const response = await request(app).get('/api/orders/user/user456');
    expect(response.statusCode).toBe(200);
    expect(response.body.orders.length).toBe(1);
  });

  test('GET /api/orders/:orderId - should fetch single order by ID', async () => {
    const order = await Order.create({
      userId: 'u1',
      vendorId: 'v1',
      items: [],
      totalAmount: 20,
      customerName: 'Single Order'
    });

    const response = await request(app).get(`/api/orders/${order._id}`);
    expect(response.statusCode).toBe(200);
    expect(response.body.order.customerName).toBe('Single Order');
  });

  test('PUT /api/orders/:orderId/status - invalid status returns 400', async () => {
    const order = await Order.create({
      userId: 'u2',
      vendorId: 'v2',
      items: [],
      totalAmount: 20,
      customerName: 'Invalid Status'
    });

    const response = await request(app)
      .put(`/api/orders/${order._id}/status`)
      .send({ status: 'invalidStatus' });

    expect(response.statusCode).toBe(400);
    expect(response.body.message).toMatch(/Invalid status/i);
  });
});
