const request = require('supertest');
const express = require('express');
const mongoose = require('mongoose');
const Client = require('../src/models/client.model');
const clientRoutes = require('../src/routes/client.routes');
const { createOrder, fetchUserOrders, fetchSingleOrder } = require('../src/utils/communicateOrderService');
const { validateCoupon } = require('../src/utils/communicateVendorService');

// Mock the external dependencies
jest.mock('../src/models/client.model.js');
jest.mock('../src/utils/communicateOrderService');
jest.mock('../src/utils/communicateVendorService');
jest.mock('../src/middlewares/auth.middleware', () => (req, res, next) => {
  req.user = { id: 'mockUserId123' };
  next();
});

const app = express();
app.use(express.json());
app.use('/api/client', clientRoutes);

describe('Client Controller Tests', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('POST /api/client/register', () => {
    it('should register a new client successfully', async () => {
      const mockClientData = {
        userId: 'user123',
        clientId: 'client123',
        fullName: 'John Doe',
        email: 'john@example.com',
        phone: '1234567890'
      };

      Client.findOne.mockResolvedValue(null);
      Client.create.mockResolvedValue({
        ...mockClientData,
        _id: 'mongoId123'
      });

      const response = await request(app)
        .post('/api/client/register')
        .send(mockClientData);

      expect(response.status).toBe(201);
      expect(response.body.message).toBe('Client registered successfully');
      expect(response.body.clientId).toBe('client123');
      expect(Client.findOne).toHaveBeenCalledWith({ userId: 'user123' });
      expect(Client.create).toHaveBeenCalledWith(mockClientData);
    });

    it('should return 409 if client already exists', async () => {
      const mockClientData = {
        userId: 'user123',
        clientId: 'client123',
        fullName: 'John Doe',
        email: 'john@example.com',
        phone: '1234567890'
      };

      Client.findOne.mockResolvedValue({ userId: 'user123' });

      const response = await request(app)
        .post('/api/client/register')
        .send(mockClientData);

      expect(response.status).toBe(409);
      expect(response.body.message).toBe('Client already exists');
      expect(Client.create).not.toHaveBeenCalled();
    });

    it('should handle registration errors', async () => {
      const mockClientData = {
        userId: 'user123',
        clientId: 'client123',
        fullName: 'John Doe',
        email: 'john@example.com',
        phone: '1234567890'
      };

      Client.findOne.mockResolvedValue(null);
      Client.create.mockRejectedValue(new Error('Database error'));

      const response = await request(app)
        .post('/api/client/register')
        .send(mockClientData);

      expect(response.status).toBe(500);
      expect(response.body.message).toBe('Failed to register client');
    });
  });

  describe('POST /api/client/favorites', () => {
    it('should add a product to favorites successfully', async () => {
      const mockClient = {
        userId: 'mockUserId123',
        favoriteProducts: ['product123', 'product456']
      };

      Client.findOneAndUpdate.mockResolvedValue(mockClient);

      const response = await request(app)
        .post('/api/client/favorites')
        .send({ productId: 'product123' });

      expect(response.status).toBe(200);
      expect(response.body).toEqual(['product123', 'product456']);
      expect(Client.findOneAndUpdate).toHaveBeenCalledWith(
        { userId: 'mockUserId123' },
        { $addToSet: { favoriteProducts: 'product123' } },
        { new: true }
      );
    });

    it('should handle errors when adding to favorites', async () => {
      Client.findOneAndUpdate.mockRejectedValue(new Error('Database error'));

      const response = await request(app)
        .post('/api/client/favorites')
        .send({ productId: 'product123' });

      expect(response.status).toBe(500);
      expect(response.body.error).toBe('Database error');
    });
  });

  describe('POST /api/client/rate-product', () => {
    it('should rate a product successfully', async () => {
      const mockClient = {
        userId: 'mockUserId123',
        ratingHistory: [
          { productId: 'product123', rating: 5, review: 'Great product!' }
        ]
      };

      Client.findOneAndUpdate.mockResolvedValue(mockClient);

      const response = await request(app)
        .post('/api/client/rate-product')
        .send({
          productId: 'product123',
          rating: 5,
          review: 'Great product!'
        });

      expect(response.status).toBe(200);
      expect(response.body.message).toBe('Rating added');
      expect(response.body.ratingHistory).toEqual(mockClient.ratingHistory);
      expect(Client.findOneAndUpdate).toHaveBeenCalledWith(
        { userId: 'mockUserId123' },
        {
          $push: {
            ratingHistory: { productId: 'product123', rating: 5, review: 'Great product!' }
          }
        },
        { new: true }
      );
    });

    it('should handle errors when rating product', async () => {
      Client.findOneAndUpdate.mockRejectedValue(new Error('Database error'));

      const response = await request(app)
        .post('/api/client/rate-product')
        .send({
          productId: 'product123',
          rating: 5,
          review: 'Great product!'
        });

      expect(response.status).toBe(500);
      expect(response.body.error).toBe('Database error');
    });
  });

  describe('POST /api/client/rate-logistics', () => {
    it('should rate logistics successfully', async () => {
      const mockClient = {
        userId: 'mockUserId123',
        logisticRatings: [
          { orderId: 'order123', rating: 4, comment: 'Good delivery' }
        ]
      };

      Client.findOneAndUpdate.mockResolvedValue(mockClient);

      const response = await request(app)
        .post('/api/client/rate-logistics')
        .send({
          orderId: 'order123',
          rating: 4,
          comment: 'Good delivery'
        });

      expect(response.status).toBe(200);
      expect(response.body.message).toBe('Logistics rated');
      expect(response.body.logisticRatings).toEqual(mockClient.logisticRatings);
      expect(Client.findOneAndUpdate).toHaveBeenCalledWith(
        { userId: 'mockUserId123' },
        {
          $push: {
            logisticRatings: { orderId: 'order123', rating: 4, comment: 'Good delivery' }
          }
        },
        { new: true }
      );
    });

    it('should handle errors when rating logistics', async () => {
      Client.findOneAndUpdate.mockRejectedValue(new Error('Database error'));

      const response = await request(app)
        .post('/api/client/rate-logistics')
        .send({
          orderId: 'order123',
          rating: 4,
          comment: 'Good delivery'
        });

      expect(response.status).toBe(500);
      expect(response.body.error).toBe('Database error');
    });
  });

  describe('POST /api/client/place-order', () => {
    it('should place order successfully without coupon', async () => {
      const mockOrderData = {
        vendorId: 'vendor123',
        items: [{ productId: 'product123', quantity: 2 }],
        totalAmount: 100,
        shippingAddress: '123 Main St',
        customerName: 'John Doe'
      };

      const mockOrder = {
        _id: 'order123',
        ...mockOrderData,
        userId: 'mockUserId123',
        paymentStatus: 'UNPAID'
      };

      createOrder.mockResolvedValue(mockOrder);

      const response = await request(app)
        .post('/api/client/place-order')
        .send(mockOrderData);

      expect(response.status).toBe(201);
      expect(response.body.message).toBe('Order placed successfully');
      expect(response.body.order).toEqual(mockOrder);
      expect(createOrder).toHaveBeenCalledWith(
        {
          userId: 'mockUserId123',
          vendorId: 'vendor123',
          items: [{ productId: 'product123', quantity: 2 }],
          totalAmount: 100,
          discount: 0,
          couponCode: undefined,
          shippingAddress: '123 Main St',
          customerName: 'John Doe',
          paymentStatus: 'UNPAID'
        },
        undefined
      );
    });

    it('should place order successfully with valid coupon', async () => {
      const mockOrderData = {
        vendorId: 'vendor123',
        items: [{ productId: 'product123', quantity: 2 }],
        totalAmount: 100,
        shippingAddress: '123 Main St',
        customerName: 'John Doe',
        couponCode: 'SAVE10'
      };

      const mockCoupon = {
        valid: true,
        discountValue: 10
      };

      const mockOrder = {
        _id: 'order123',
        ...mockOrderData,
        userId: 'mockUserId123',
        totalAmount: 90,
        discount: 10,
        paymentStatus: 'UNPAID'
      };

      validateCoupon.mockResolvedValue(mockCoupon);
      createOrder.mockResolvedValue(mockOrder);

      const response = await request(app)
        .post('/api/client/place-order')
        .send(mockOrderData);

      expect(response.status).toBe(201);
      expect(response.body.message).toBe('Order placed successfully');
      expect(validateCoupon).toHaveBeenCalledWith('vendor123', 'SAVE10', mockOrderData.items);
      expect(createOrder).toHaveBeenCalledWith(
        {
          userId: 'mockUserId123',
          vendorId: 'vendor123',
          items: mockOrderData.items,
          totalAmount: 90,
          discount: 10,
          couponCode: 'SAVE10',
          shippingAddress: '123 Main St',
          customerName: 'John Doe',
          paymentStatus: 'UNPAID'
        },
        undefined
      );
    });

    it('should reject order with invalid coupon', async () => {
      const mockOrderData = {
        vendorId: 'vendor123',
        items: [{ productId: 'product123', quantity: 2 }],
        totalAmount: 100,
        shippingAddress: '123 Main St',
        customerName: 'John Doe',
        couponCode: 'INVALID'
      };

      const mockCoupon = {
        valid: false
      };

      validateCoupon.mockResolvedValue(mockCoupon);

      const response = await request(app)
        .post('/api/client/place-order')
        .send(mockOrderData);

      expect(response.status).toBe(400);
      expect(response.body.message).toBe('Invalid or expired coupon');
      expect(createOrder).not.toHaveBeenCalled();
    });

    it('should handle order creation errors', async () => {
      const mockOrderData = {
        vendorId: 'vendor123',
        items: [{ productId: 'product123', quantity: 2 }],
        totalAmount: 100,
        shippingAddress: '123 Main St',
        customerName: 'John Doe'
      };

      createOrder.mockRejectedValue(new Error('Order service unavailable'));

      const response = await request(app)
        .post('/api/client/place-order')
        .send(mockOrderData);

      expect(response.status).toBe(500);
      expect(response.body.message).toBe('Failed to place order');
      expect(response.body.error).toBe('Order service unavailable');
    });
  });

  describe('GET /api/client/orders', () => {
    it('should fetch user orders successfully', async () => {
      const mockOrders = [
        { _id: 'order1', userId: 'mockUserId123', totalAmount: 100 },
        { _id: 'order2', userId: 'mockUserId123', totalAmount: 200 }
      ];

      fetchUserOrders.mockResolvedValue(mockOrders);

      const response = await request(app)
        .get('/api/client/orders');

      expect(response.status).toBe(200);
      expect(response.body.orders).toEqual(mockOrders);
      expect(fetchUserOrders).toHaveBeenCalledWith('mockUserId123', undefined);
    });

    it('should handle errors when fetching orders', async () => {
      fetchUserOrders.mockRejectedValue(new Error('Service unavailable'));

      const response = await request(app)
        .get('/api/client/orders');

      expect(response.status).toBe(500);
      expect(response.body.message).toBe('Failed to fetch orders');
    });
  });

  describe('GET /api/client/orders/:id', () => {
    it('should fetch single order successfully', async () => {
      const mockOrder = {
        _id: 'order123',
        userId: 'mockUserId123',
        totalAmount: 100
      };

      fetchSingleOrder.mockResolvedValue(mockOrder);

      const response = await request(app)
        .get('/api/client/orders/order123');

      expect(response.status).toBe(200);
      expect(response.body.order).toEqual(mockOrder);
      expect(fetchSingleOrder).toHaveBeenCalledWith('order123', undefined);
    });

    it('should return 403 for unauthorized access to order', async () => {
      const mockOrder = {
        _id: 'order123',
        userId: 'differentUserId',
        totalAmount: 100
      };

      fetchSingleOrder.mockResolvedValue(mockOrder);

      const response = await request(app)
        .get('/api/client/orders/order123');

      expect(response.status).toBe(403);
      expect(response.body.message).toBe('Unauthorized access to order');
    });

    it('should return 403 when order not found', async () => {
      fetchSingleOrder.mockResolvedValue(null);

      const response = await request(app)
        .get('/api/client/orders/order123');

      expect(response.status).toBe(403);
      expect(response.body.message).toBe('Unauthorized access to order');
    });

    it('should handle errors when fetching single order', async () => {
      fetchSingleOrder.mockRejectedValue(new Error('Service unavailable'));

      const response = await request(app)
        .get('/api/client/orders/order123');

      expect(response.status).toBe(500);
      expect(response.body.message).toBe('Failed to fetch order');
    });
  });
});

describe('Utility Functions Tests', () => {
  describe('communicateOrderService', () => {
    const axios = require('axios');
    jest.mock('axios');

    beforeEach(() => {
      jest.clearAllMocks();
      process.env.ORDER_SERVICE_URL = 'http://localhost:3001/orders';
    });

    it('should create order successfully', async () => {
      const mockOrderData = {
        userId: 'user123',
        vendorId: 'vendor123',
        items: [{ productId: 'product123', quantity: 2 }],
        totalAmount: 100
      };

      const mockResponse = {
        data: {
          order: { _id: 'order123', ...mockOrderData }
        }
      };

      axios.post.mockResolvedValue(mockResponse);

      const result = await createOrder(mockOrderData, 'Bearer token123');

      expect(result).toEqual(mockResponse.data.order);
      expect(axios.post).toHaveBeenCalledWith(
        'http://localhost:3001/orders',
        mockOrderData,
        {
          headers: {
            Authorization: 'Bearer token123'
          }
        }
      );
    });

    it('should handle create order errors', async () => {
      const mockOrderData = {
        userId: 'user123',
        vendorId: 'vendor123',
        items: [{ productId: 'product123', quantity: 2 }],
        totalAmount: 100
      };

      axios.post.mockRejectedValue(new Error('Network error'));

      await expect(createOrder(mockOrderData, 'Bearer token123'))
        .rejects.toThrow('Failed to create order');
    });

    it('should fetch user orders successfully', async () => {
      const mockOrders = [
        { _id: 'order1', userId: 'user123' },
        { _id: 'order2', userId: 'user123' }
      ];

      const mockResponse = {
        data: { orders: mockOrders }
      };

      axios.get.mockResolvedValue(mockResponse);

      const result = await fetchUserOrders('user123', 'Bearer token123');

      expect(result).toEqual(mockOrders);
      expect(axios.get).toHaveBeenCalledWith(
        'http://localhost:3001/orders/user/user123',
        {
          headers: { Authorization: 'Bearer token123' }
        }
      );
    });

    it('should fetch single order successfully', async () => {
      const mockOrder = { _id: 'order123', userId: 'user123' };
      const mockResponse = {
        data: { order: mockOrder }
      };

      axios.get.mockResolvedValue(mockResponse);

      const result = await fetchSingleOrder('order123', 'Bearer token123');

      expect(result).toEqual(mockOrder);
      expect(axios.get).toHaveBeenCalledWith(
        'http://localhost:3001/orders/order123',
        {
          headers: { Authorization: 'Bearer token123' }
        }
      );
    });
  });

  describe('communicateVendorService', () => {
    const axios = require('axios');

    beforeEach(() => {
      jest.clearAllMocks();
      process.env.VENDOR_SERVICE_URL = 'http://localhost:3002/vendors';
    });

    it('should validate coupon successfully', async () => {
      const mockCoupon = {
        valid: true,
        discountValue: 10
      };

      const mockResponse = {
        data: mockCoupon
      };

      axios.post.mockResolvedValue(mockResponse);

      const result = await validateCoupon('vendor123', 'SAVE10', []);

      expect(result).toEqual(mockCoupon);
      expect(axios.post).toHaveBeenCalledWith(
        'http://localhost:3002/vendors/validate-coupon',
        {
          vendorId: 'vendor123',
          couponCode: 'SAVE10',
          items: []
        }
      );
    });

    it('should handle coupon validation errors', async () => {
      axios.post.mockRejectedValue(new Error('Network error'));

      const result = await validateCoupon('vendor123', 'SAVE10', []);

      expect(result).toEqual({ valid: false });
    });
  });
});

describe('Client Model Tests', () => {
  it('should have correct schema structure', () => {
    const Client = require('../models/client.model');
    const schema = Client.schema;

    expect(schema.paths.userId).toBeDefined();
    expect(schema.paths.clientId).toBeDefined();
    expect(schema.paths.fullName).toBeDefined();
    expect(schema.paths.email).toBeDefined();
    expect(schema.paths.phone).toBeDefined();
    expect(schema.paths.favoriteProducts).toBeDefined();
    expect(schema.paths.ratingHistory).toBeDefined();
    expect(schema.paths.logisticRatings).toBeDefined();
  });
});