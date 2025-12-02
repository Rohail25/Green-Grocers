const request = require('supertest');
const mongoose = require('mongoose');
const { MongoMemoryServer } = require('mongodb-memory-server');
const jwt = require('jsonwebtoken');
const app = require('../src/app');
const Product = require('../src/models/product.model');

// ✅ Mock vendorClient (only update call)
jest.mock('../src/utils/communicateVendorService.js', () => ({
  updateVendorInventoryAndCategories: jest.fn(() => Promise.resolve({ message: 'Vendor updated' }))
}));

const { updateVendorInventoryAndCategories } = require('../src/utils/communicateVendorService.js');

let mongoServer;
let token = '';
const vendorId = 'VEND-123456';

beforeAll(async () => {
  mongoServer = await MongoMemoryServer.create();
  await mongoose.connect(mongoServer.getUri());

  token = jwt.sign({ vendorId, platform: 'trivestore' }, process.env.JWT_SECRET || 'testsecret');
});

afterAll(async () => {
  await mongoose.disconnect();
  await mongoServer.stop();
});

afterEach(async () => {
  await Product.deleteMany({});
  jest.clearAllMocks();
});

describe('Product Service', () => {
  const productData = {
    name: 'Test Product',
    description: 'Testing product',
    itemSize: 'L',
    totalQuantityInStock: 100,
    images: ['img1.jpg'],
    variants: [
      { image: 'v1.jpg', size: 'L', color: 'Blue', quantity: 100, inStock: true }
    ],
    brand: 'BrandX',
    category: 'Shoes',
    gender: 'Unisex',
    collection: 'Winter 2025',
    tags: ['boots'],
    retailPrice: 120,
    wholesalePrice: 90,
    minWholesaleQty: 5,
    preSalePrice: 110,
    preSalePeriod: {
      start: new Date(),
      end: new Date(Date.now() + 5 * 86400000)
    },
    discount: {
      type: 'percentage',
      value: 10
    }
  };

  test('should create a product', async () => {
    const res = await request(app)
      .post('/api/products')
      .set('Authorization', `Bearer ${token}`)
      .send(productData);

    expect(res.statusCode).toBe(201);
    expect(res.body.name).toBe(productData.name);
    expect(updateVendorInventoryAndCategories).toHaveBeenCalled();
  });

  test('should get all products for vendor', async () => {
    await Product.create({ ...productData, vendorId });
    const res = await request(app)
      .get('/api/products')
      .set('Authorization', `Bearer ${token}`);

    expect(res.statusCode).toBe(200);
    expect(res.body.length).toBeGreaterThan(0);
  });

  test('should update a product', async () => {
    const product = await Product.create({ ...productData, vendorId });
    const res = await request(app)
      .put(`/api/products/${product._id}`)
      .set('Authorization', `Bearer ${token}`)
      .send({ name: 'Updated Name' });

    expect(res.statusCode).toBe(200);
    expect(res.body.name).toBe('Updated Name');
    expect(updateVendorInventoryAndCategories).toHaveBeenCalled();
  });

  test('should delete a product', async () => {
    const product = await Product.create({ ...productData, vendorId });
    const res = await request(app)
      .delete(`/api/products/${product._id}`)
      .set('Authorization', `Bearer ${token}`);

    expect(res.statusCode).toBe(200);
    expect(res.body.message).toMatch(/deleted/i);
    expect(updateVendorInventoryAndCategories).toHaveBeenCalled();
  });
});
