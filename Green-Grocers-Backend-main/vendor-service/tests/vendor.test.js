const request = require('supertest');
const mongoose = require('mongoose');
const { MongoMemoryServer } = require('mongodb-memory-server');
const jwt = require('jsonwebtoken');
const app = require('../src/app');
const Vendor = require('../src/models/vendor.model');


jest.mock('../src/utils/communicateUserService', () => ({
  getUserFromToken: jest.fn(() => Promise.resolve({
    id: 'fake-user-id',
    platform: 'trivestore',
    vendorId: 'VEND-123456',
    storeName: 'My Store',
  }))
}));

// Fake user + token
const user = {
  userId: 'fake-user-id',
  platform: 'trivestore',
  vendorId: 'VEND-123456',
  storeName: 'My Store',
};
const token = jwt.sign(user, process.env.JWT_SECRET || 'supersecret');

let mongoServer;
beforeAll(async () => {
  mongoServer = await MongoMemoryServer.create();
  const uri = mongoServer.getUri();
  await mongoose.connect(uri);
});
afterAll(async () => {
  await mongoose.disconnect();
  await mongoServer.stop();
});
afterEach(async () => {
  await Vendor.deleteMany({});
});

describe('Vendor Routes', () => {
  const base = '/api/vendors';

  test('should create a vendor completely', async () => {
    const res = await request(app).post(`${base}/register-vendor`)
      .set('Authorization', `Bearer ${token}`)
      .send({
        vendorId: user.vendorId,
        storeName: 'My Store',
        phone: '1234567890',
        email: 'vendor@example.com',
        address: 'City',
        categories: ['Grocery'],
        description: 'A nice store'
      });
    expect(res.statusCode).toBe(201);
    expect(res.body.storeName).toBe('My Store');
  });

  test('should get vendor details', async () => {
    await Vendor.create({ ...user, storeName: 'Test Store' });
    const res = await request(app).get(`${base}/get-vendor`).set('Authorization', `Bearer ${token}`);
    expect(res.statusCode).toBe(200);
    expect(res.body.storeName).toBe('Test Store');
  });

  test('should update vendor store name', async () => {
    await Vendor.create({ ...user, storeName: 'Old Name' });
    const res = await request(app).put(`${base}/update-vendor`)
      .set('Authorization', `Bearer ${token}`)
      .send({ storeName: 'New Name' });
    expect(res.statusCode).toBe(200);
    expect(res.body.storeName).toBe('New Name');
  });

  test('should delete vendor', async () => {
    await Vendor.create({ ...user, storeName: 'DeleteMe' });
    const res = await request(app).delete(`${base}/delete-vendor`).set('Authorization', `Bearer ${token}`);
    expect(res.statusCode).toBe(200);
    expect(res.body.message).toMatch(/deleted/i);
  });
});
