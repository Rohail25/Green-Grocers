const request = require('supertest');
const mongoose = require('mongoose');
const jwt = require('jsonwebtoken');
const { MongoMemoryServer } = require('mongodb-memory-server');
const app = require('../src/app');
const Vendor = require('../src/models/vendor.model');

// Mock communicator
jest.mock('../src/utils/communicateProductService', () => ({
  getProductsByCategoryService: jest.fn(() => Promise.resolve([{ name: 'Product A' }]))
}));
const { getProductsByCategoryService } = require('../src/utils/communicateProductService');

let mongoServer;
let token = '';
const vendorId = 'VEND-TEST';

beforeAll(async () => {
  mongoServer = await MongoMemoryServer.create();
  await mongoose.connect(mongoServer.getUri());
  await Vendor.create({ vendorId, userId: 'USER-TEST', storeName: 'Store X' });
  process.env.JWT_SECRET = 'testsecret';
  token = jwt.sign({ vendorId }, process.env.JWT_SECRET);
});

afterAll(async () => {
  await mongoose.disconnect();
  await mongoServer.stop();
});

describe('Vendor Category Routes', () => {
  const base = '/api/vendors/categories';

  test('should add a category', async () => {
    const res = await request(app)
      .post(base)
      .set('Authorization', `Bearer ${token}`)
      .send({ name: 'Grocery' });

    expect(res.statusCode).toBe(201);
    expect(res.body.categories).toContain('Grocery');
  });

  test('should get all categories', async () => {
    const res = await request(app)
      .get(base)
      .set('Authorization', `Bearer ${token}`);

    expect(res.statusCode).toBe(200);
    expect(res.body.categories).toContain('Grocery');
  });

  test('should fetch products by category via communicator', async () => {
    const res = await request(app)
      .get(`${base}/Grocery/products`)
      .set('Authorization', `Bearer ${token}`);

    expect(res.statusCode).toBe(200);
    expect(getProductsByCategoryService).toHaveBeenCalledWith(vendorId, 'Grocery');
    expect(res.body[0].name).toBe('Product A');
  });

  test('should delete a category', async () => {
    const res = await request(app)
      .delete(`${base}/Grocery`)
      .set('Authorization', `Bearer ${token}`);

    expect(res.statusCode).toBe(200);
    expect(res.body.categories).not.toContain('Grocery');
  });
});
