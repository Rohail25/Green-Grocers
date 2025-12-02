jest.mock('../src/utils/snsService.js', () => ({
  sendSMSNotification: jest.fn().mockResolvedValue({ MessageId: 'mocked-message-id' }),
}));
const request = require('supertest');
const mongoose = require('mongoose');
const app = require('../src/app');
const Notification = require('../src/models/notification.model');
// Test database URI
const MONGODB_URI = process.env.MONGODB_TEST_URI || 'mongodb+srv://triverait2025:KXEFrCtMAOOm3znf@trivera-userservice.coejhop.mongodb.net';

describe('Notification Service', () => {
  beforeAll(async () => {
    try {
      if (mongoose.connection.readyState !== 0) {
        await mongoose.connection.close();
      }

      await mongoose.connect(MONGODB_URI, {
        useNewUrlParser: true,
        useUnifiedTopology: true,
        serverSelectionTimeoutMS: 10000,
        socketTimeoutMS: 0,
        keepAlive: true,
        keepAliveInitialDelay: 300000,
      });

      console.log('Test database connected');
    } catch (error) {
      console.error('Test database connection failed:', error);
      throw error;
    }
  }, 15000);

  afterAll(async () => {
    try {
      if (mongoose.connection.db) {
        await mongoose.connection.db.dropDatabase();
      }
      await mongoose.connection.close();
    } catch (error) {
      console.error('Test cleanup error:', error);
    }
  }, 10000);

  beforeEach(async () => {
    await Notification.deleteMany({});
  });

  describe('POST /api/notifications/send', () => {
    const testPhoneNumber = '03150030977';

    it('should send an in-app notification successfully', async () => {
      const notificationData = {
        userId: 'test-user-123',
        type: 'in_app',
        title: 'Test Notification',
        message: 'This is a test notification',
        priority: 'medium'
      };

      const response = await request(app)
        .post('/api/notifications/send')
        .send(notificationData)
        .expect(201);

      expect(response.body.success).toBe(true);
      expect(response.body.data.notificationId).toBeDefined();
      expect(response.body.data.results).toHaveLength(1);
      expect(response.body.data.results[0].type).toBe('in_app');
    });

    it('should validate required fields', async () => {
      const invalidData = {
        userId: 'test-user-123',
        type: 'in_app',
        // missing title and message
      };

      const response = await request(app)
        .post('/api/notifications/send')
        .send(invalidData)
        .expect(400);

      expect(response.body.success).toBe(false);
      expect(response.body.message).toBe('Validation error');
      expect(response.body.errors).toBeDefined();
    });

    it('should require phone number for SMS notifications', async () => {
      const smsData = {
        userId: 'test-user-123',
        type: 'sms',
        title: 'SMS Test',
        message: 'This is an SMS test'
      };

      const response = await request(app)
        .post('/api/notifications/send')
        .send(smsData)
        .expect(400);

      expect(response.body.success).toBe(false);
      expect(response.body.errors.some(error => 
        error.includes('Phone number is required')
      )).toBe(true);
    });

    it('should send an SMS notification with Pakistani number', async () => {
      const smsData = {
        userId: 'test-user-123',
        type: 'sms',
        title: 'SMS Test',
        message: 'This is an SMS test',
        phoneNumber: testPhoneNumber
      };

      const response = await request(app)
        .post('/api/notifications/send')
        .send(smsData)
        .expect(201);

      expect(response.body.success).toBe(true);
      expect(response.body.data.notificationId).toBeDefined();
      expect(response.body.data.results).toEqual(
        expect.arrayContaining([
          expect.objectContaining({
            type: 'sms'
          })
        ])
      );
    });
  });

  describe('GET /api/notifications/user/:userId', () => {
    beforeEach(async () => {
      await Notification.insertMany([
        {
          id: 'notif-1',
          userId: 'test-user-123',
          type: 'in_app',
          title: 'Notification 1',
          message: 'First notification',
          status: 'sent',
          priority: 'high'
        },
        {
          id: 'notif-2',
          userId: 'test-user-123',
          type: 'in_app',
          title: 'Notification 2',
          message: 'Second notification',
          status: 'read',
          priority: 'medium'
        },
        {
          id: 'notif-3',
          userId: 'other-user',
          type: 'in_app',
          title: 'Other Notification',
          message: 'Notification for other user',
          status: 'sent',
          priority: 'low'
        }
      ]);
    });

    it('should get notifications for a specific user', async () => {
      const response = await request(app)
        .get('/api/notifications/user/test-user-123')
        .expect(200);

      expect(response.body.success).toBe(true);
      expect(response.body.data.notifications).toHaveLength(2);
    });

    it('should filter notifications by status', async () => {
      const response = await request(app)
        .get('/api/notifications/user/test-user-123?status=read')
        .expect(200);

      expect(response.body.success).toBe(true);
      expect(response.body.data.notifications).toHaveLength(1);
      expect(response.body.data.notifications[0].status).toBe('read');
    });

    it('should support pagination', async () => {
      const response = await request(app)
        .get('/api/notifications/user/test-user-123?page=1&limit=1')
        .expect(200);

      expect(response.body.success).toBe(true);
      expect(response.body.data.notifications).toHaveLength(1);
      expect(response.body.data.pagination.pages).toBe(2);
    });
  });

  describe('GET /api/notifications/:notificationId', () => {
    let testNotification;

    beforeEach(async () => {
      testNotification = await Notification.create({
        id: 'test-notification-id',
        userId: 'test-user-123',
        type: 'in_app',
        title: 'Test Notification',
        message: 'This is a test notification',
        status: 'sent',
        priority: 'medium'
      });
    });

    it('should get notification by ID', async () => {
      const response = await request(app)
        .get('/api/notifications/test-notification-id')
        .expect(200);

      expect(response.body.success).toBe(true);
      expect(response.body.data.id).toBe('test-notification-id');
    });

    it('should return 404 for non-existent notification', async () => {
      const response = await request(app)
        .get('/api/notifications/non-existent-id')
        .expect(404);

      expect(response.body.success).toBe(false);
      expect(response.body.message).toBe('Notification not found');
    });
  });

  describe('PATCH /api/notifications/:notificationId/read', () => {
    let testNotification;

    beforeEach(async () => {
      testNotification = await Notification.create({
        id: 'test-notification-read',
        userId: 'test-user-123',
        type: 'in_app',
        title: 'Test Notification',
        message: 'This is a test notification',
        status: 'sent',
        priority: 'medium'
      });
    });

    it('should mark notification as read', async () => {
      const response = await request(app)
        .patch('/api/notifications/test-notification-read/read')
        .send({ userId: 'test-user-123' })
        .expect(200);

      expect(response.body.success).toBe(true);

      const updated = await Notification.findOne({ id: 'test-notification-read' });
      expect(updated.status).toBe('read');
    });

    it('should return 404 for non-existent notification', async () => {
      const response = await request(app)
        .patch('/api/notifications/non-existent/read')
        .send({ userId: 'test-user-123' })
        .expect(404);

      expect(response.body.success).toBe(false);
      expect(response.body.message).toBe('Notification not found');
    });
  });
});

describe('Health Check', () => {
  it('should return health status', async () => {
    const response = await request(app)
      .get('/health')
      .expect(200);

    expect(response.body.success).toBe(true);
    expect(response.body.message).toBe('Notification service is healthy');
    expect(response.body.uptime).toBeDefined();
  });

  it('should return detailed health status', async () => {
    const response = await request(app)
      .get('/health/detailed');

    expect([200, 503]).toContain(response.status);
    expect(response.body.services).toBeDefined();
    expect(response.body.memory).toBeDefined();
  });
});
