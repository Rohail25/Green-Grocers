// tests/coupons.test.js
const request = require('supertest');
const mongoose = require('mongoose');
const jwt = require('jsonwebtoken');
const { MongoMemoryServer } = require('mongodb-memory-server');
const app = require('../src/app');
const Vendor = require('../src/models/vendor.model');

// Mock communicator
jest.mock('../src/utils/communicateProductService.js', () => ({
  updateCouponInProducts: jest.fn(() => Promise.resolve({ status: 200 })),
  removeCouponFromProducts: jest.fn(() => Promise.resolve({ status: 200 }))
}));

const {
  updateCouponInProducts,
  removeCouponFromProducts
} = require('../src/utils/communicateProductService.js');

let mongoServer;
let token = '';
let couponId = '';

beforeAll(async () => {
  mongoServer = await MongoMemoryServer.create();
  await mongoose.connect(mongoServer.getUri());

  // Create test vendor
  await Vendor.create({
    vendorId: 'VEND-1000',
    userId: 'USER-1000',
    storeName: 'Test Store'
  });

  process.env.JWT_SECRET = 'testsecret';
  token = jwt.sign({ vendorId: 'VEND-1000' }, process.env.JWT_SECRET);
});

afterAll(async () => {
  await mongoose.disconnect();
  await mongoServer.stop();
});

beforeEach(async () => {
  // Clear coupons and insert fresh one
  await Vendor.updateOne(
    { vendorId: 'VEND-1000' },
    { $set: { coupons: [] } }
  );

  const vendor = await Vendor.findOne({ vendorId: 'VEND-1000' });
  const newCoupon = {
    _id: new mongoose.Types.ObjectId(),
    couponType: 'Discount',
    name: 'Test Coupon',
    code: 'TESTCODE',
    discountValue: 20,
    appliesTo: 'AllProducts'
  };
  vendor.coupons.push(newCoupon);
  await vendor.save();

  couponId = newCoupon._id.toString();
});

describe('Vendor Coupon Flow', () => {
  const base = '/api/vendors/coupons';

  it('should create a coupon', async () => {
    const res = await request(app)
      .post(base)
      .set('Authorization', `Bearer ${token}`)
      .send({
        couponType: 'Discount',
        name: 'Summer Sale',
        code: 'SUMMER20',
        discountValue: 20,
        appliesTo: 'AllProducts'
      });

    expect(res.statusCode).toBe(201);
    expect(res.body.coupon).toBeDefined();
    expect(res.body.coupon.code).toBe('SUMMER20');
  });

  it('should get all coupons for a vendor', async () => {
    // Get all coupons
    const res = await request(app)
      .get(base)
      .set('Authorization', `Bearer ${token}`);
    
    expect(res.statusCode).toBe(200);
    expect(Array.isArray(res.body)).toBeTruthy();
    expect(res.body.length).toBeGreaterThan(0); // Just verify we get some coupons
    
  });
  
  it('should get a single coupon by id', async () => {
    const res = await request(app)
      .get(`${base}/${couponId}`)
      .set('Authorization', `Bearer ${token}`);

    expect(res.statusCode).toBe(200);
    expect(res.body).toBeDefined();
    expect(res.body._id).toBe(couponId);
    expect(res.body.name).toBe('Test Coupon');
    expect(res.body.code).toBe('TESTCODE');
    expect(res.body.discountValue).toBe(20);
    expect(res.body.appliesTo).toBe('AllProducts');
  });

  it('should return 404 when getting a non-existent coupon', async () => {
    const nonExistentId = new mongoose.Types.ObjectId().toString();

    const res = await request(app)
      .get(`${base}/${nonExistentId}`)
      .set('Authorization', `Bearer ${token}`);

    expect(res.statusCode).toBe(404);
    expect(res.body.message).toBe('Coupon not found');
  });

  it('should require authentication for getting coupons', async () => {
    const resAll = await request(app).get(base);
    expect(resAll.statusCode).toBe(401);

    const resSingle = await request(app).get(`${base}/${couponId}`);
    expect(resSingle.statusCode).toBe(401);
  });


  it('should update coupon and sync with product service', async () => {
    const res = await request(app)
      .put(`${base}/${couponId}`)
      .set('Authorization', `Bearer ${token}`)
      .send({ discountValue: 30 });

    expect(res.statusCode).toBe(200);
    expect(updateCouponInProducts).toHaveBeenCalledWith({
      couponId: couponId,
      updates: { discountValue: 30 }
    });
  });

  it('should delete coupon and notify product service', async () => {
    const res = await request(app)
      .delete(`${base}/${couponId}`)
      .set('Authorization', `Bearer ${token}`);

    expect(res.statusCode).toBe(200);
    expect(removeCouponFromProducts).toHaveBeenCalledWith(couponId);
  });
});
