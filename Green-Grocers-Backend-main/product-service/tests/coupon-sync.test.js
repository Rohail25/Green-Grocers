const request = require('supertest');
const mongoose = require('mongoose');
const { MongoMemoryServer } = require('mongodb-memory-server');
const app = require('../src/app');
const Product = require('../src/models/product.model');

let mongoServer;
let testProduct;

beforeAll(async () => {
  mongoServer = await MongoMemoryServer.create();
  await mongoose.connect(mongoServer.getUri());

  testProduct = await Product.create({
    name: 'Product 1',
    vendorId: 'VEND-1000',
    appliedCoupons: [{
      couponId: 'coup123',
      code: 'OLD20',
      discountValue: 20
    }]
  });
});

afterAll(async () => {
  await mongoose.disconnect();
  await mongoServer.stop();
});

describe('Product Service Coupon Sync', () => {
  test('should update coupon in all applicable products', async () => {
    const res = await request(app)
      .post('/api/products/update-coupon')
      .send({
        couponId: 'coup123',
        updates: { code: 'UPDATED20', discountValue: 25 }
      });

    expect(res.statusCode).toBe(200);

    const updatedProduct = await Product.findById(testProduct._id);
    expect(updatedProduct.appliedCoupons[0].code).toBe('UPDATED20');
    expect(updatedProduct.appliedCoupons[0].discountValue).toBe(25);
  });

  test('should remove coupon from all applicable products', async () => {
    const res = await request(app)
      .post('/api/products/remove-coupon')
      .send({ couponId: 'coup123' });

    expect(res.statusCode).toBe(200);

    const updatedProduct = await Product.findById(testProduct._id);
    expect(updatedProduct.appliedCoupons).toHaveLength(0);
  });
});
