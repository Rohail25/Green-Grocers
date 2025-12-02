const request = require('supertest');
const mongoose = require('mongoose');
const { MongoMemoryServer } = require('mongodb-memory-server');
const jwt = require('jsonwebtoken');
const app = require('../src/app');
const User = require('../src/models/user.model');
const { sendEmail } = require('../src/utils/ses');

jest.mock('../src/utils/ses', () => ({
  sendEmail: jest.fn(() => Promise.resolve('mocked-email-sent'))
}));

// ✅ Mock communicator for client registration
jest.mock('../src/utils/communicateClientService', () => ({
  registerClient: jest.fn(() => Promise.resolve({ clientId: 'MART-mocked' }))
}));


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
  await User.deleteMany({});
});

describe('User Service Routes', () => {
  const userData = {
    email: 'test@example.com',
    phone: '12345678900',
    password: 'password123',
    confirmPassword: 'password123',
    platform: 'trivemart'
  };

  test('should register a trivimart user and trigger client registration', async () => {
    const res = await request(app).post('/api/users/register').send(userData);
    expect(res.statusCode).toBe(201);
    expect(res.body.message).toMatch(/check your email/i);

    const { registerClient } = require('../src/utils/communicateClientService');
    expect(registerClient).toHaveBeenCalledTimes(1);
    expect(registerClient).toHaveBeenCalledWith(
      expect.objectContaining({ email: userData.email }),
      expect.any(String)
    );
  });

  test('should not register with duplicate email for same platform', async () => {
    await request(app).post('/api/users/register').send(userData);
    const res = await request(app).post('/api/users/register').send(userData);
    expect(res.statusCode).toBe(409);
    expect(res.body.message).toMatch(/already registered/i);
  });

  test('should fail if password does not match confirmPassword', async () => {
    const res = await request(app).post('/api/users/register').send({
      ...userData,
      confirmPassword: 'wrongpass'
    });
    expect(res.statusCode).toBe(400);
    expect(res.body.message).toMatch(/passwords do not match/i);
  });

  test('should login with valid credentials', async () => {
    await request(app).post('/api/users/register').send(userData);
    // ⛳ Confirm user before login
    await User.updateOne({ email: userData.email }, { isEmailConfirmed: true });

    const res = await request(app).post('/api/users/login').send({
      email: userData.email,
      password: userData.password,
      platform: userData.platform
    });

    expect(res.statusCode).toBe(200);
    expect(res.body.token).toBeDefined();
    expect(res.body.user.email).toBe(userData.email);
  });

  test('should fail login with wrong password', async () => {
    await request(app).post('/api/users/register').send(userData);
    await User.updateOne({ email: userData.email }, { isEmailConfirmed: true });

    const res = await request(app).post('/api/users/login').send({
      email: userData.email,
      password: 'wrongpassword',
      platform: userData.platform
    });

    expect(res.statusCode).toBe(401);
    expect(res.body.message).toMatch(/invalid password/i);
  });

  test('should fail login with unknown user', async () => {
    const res = await request(app).post('/api/users/login').send({
      email: 'nouser@example.com',
      password: 'anything',
      platform: userData.platform
    });
    expect(res.statusCode).toBe(404);
    expect(res.body.message).toMatch(/not found/i);
  });

  test('should generate a vendorId when platform is trivestore', async () => {
    const vendorUser = {
      email: 'vendor@example.com',
      phone: '12345678900',
      password: 'password123',
      confirmPassword: 'password123',
      platform: 'trivestore'
    };

    const res = await request(app).post('/api/users/register').send(vendorUser);
    expect(res.statusCode).toBe(201);
    expect(res.body.message).toMatch(/check your email/i);
    const user = await User.findOne({ email: vendorUser.email });
    expect(user.vendorId).toBeDefined();
    expect(user.vendorId).toMatch(/^VEND-/);
  });
});
