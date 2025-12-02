const express = require('express');
const prisma = require('../utils/prisma');

const router = express.Router();

// Basic health check
router.get('/', (req, res) => {
  res.json({
    success: true,
    message: 'Notification service is healthy',
    timestamp: new Date().toISOString(),
    uptime: process.uptime(),
    environment: process.env.NODE_ENV
  });
});

// Detailed health check
router.get('/detailed', async (req, res) => {
  const health = {
    success: true,
    timestamp: new Date().toISOString(),
    uptime: process.uptime(),
    environment: process.env.NODE_ENV,
    services: {}
  };

  // Check database connection
  try {
    await prisma.$queryRaw`SELECT 1`;
    health.services.database = {
      status: 'healthy',
      connection: 'connected'
    };
  } catch (error) {
    health.services.database = {
      status: 'unhealthy',
      connection: 'disconnected',
      error: error.message
    };
    health.success = false;
  }

  // Check AWS SNS (basic check)
  try {
    if (process.env.AWS_ACCESS_KEY_ID && process.env.AWS_SECRET_ACCESS_KEY) {
      health.services.sms = {
        status: 'configured',
        region: process.env.AWS_REGION || 'us-east-1'
      };
    } else {
      health.services.sms = {
        status: 'not_configured',
        message: 'AWS credentials not found'
      };
    }
  } catch (error) {
    health.services.sms = {
      status: 'error',
      error: error.message
    };
  }

  // Check memory usage
  const memUsage = process.memoryUsage();
  health.memory = {
    rss: `${Math.round(memUsage.rss / 1024 / 1024)} MB`,
    heapTotal: `${Math.round(memUsage.heapTotal / 1024 / 1024)} MB`,
    heapUsed: `${Math.round(memUsage.heapUsed / 1024 / 1024)} MB`
  };

  const statusCode = health.success ? 200 : 503;
  res.status(statusCode).json(health);
});

module.exports = router;