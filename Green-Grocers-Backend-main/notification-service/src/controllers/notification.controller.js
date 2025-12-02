const { v4: uuidv4 } = require('uuid');
const prisma = require('../utils/prisma');
const { sendSMSNotification } = require('../utils/snsService');
const { sendInAppNotification } = require('../utils/socketManager');
const { validateNotification } = require('../utils/validators');

class NotificationController {
  // Send a new notification
  async sendNotification(req, res) {
    try {
      console.log('=== Notification Request Body ===');
      console.log(JSON.stringify(req.body, null, 2));

      const { error, value } = validateNotification(req.body);
      if (error) {
        console.log('Validation error:', error.details);
        return res.status(400).json({
          success: false,
          message: 'Validation error',
          errors: error.details.map(detail => detail.message)
        });
      }

      console.log('=== Validated Data ===');
      console.log(JSON.stringify(value, null, 2));

      const notificationId = uuidv4();
      const notificationData = {
        id: notificationId,
        userId: value.userId,
        type: value.type,
        title: value.title,
        message: value.message,
        phoneNumber: value.phoneNumber || null,
        status: 'pending',
        priority: value.priority || 'medium',
        metadata: value.metadata || {},
        retryCount: 0,
        maxRetries: value.maxRetries || 3
      };

      // Create notification record
      console.log('Creating notification record...');
      const notification = await prisma.notification.create({
        data: notificationData
      });
      console.log('Notification saved with ID:', notification.id);

      // Send based on type
      const results = [];
      
      if (notification.type === 'in_app' || notification.type === 'both') {
        try {
          console.log('Sending in-app notification...');
          await sendInAppNotification(notification.userId, {
            id: notification.id,
            title: notification.title,
            message: notification.message,
            priority: notification.priority,
            metadata: notification.metadata,
            timestamp: notification.createdAt
          });
          results.push({ type: 'in_app', status: 'sent' });
          console.log('In-app notification sent successfully');
        } catch (error) {
          console.error('In-app notification failed:', error);
          results.push({ type: 'in_app', status: 'failed', error: error.message });
        }
      }

      if (notification.type === 'sms' || notification.type === 'both') {
        try {
          console.log('=== SMS Sending Details ===');
          console.log('Phone Number:', notification.phoneNumber);
          console.log('Message:', `${notification.title}: ${notification.message}`);
          
          // Validate phone number exists
          if (!notification.phoneNumber) {
            throw new Error('Phone number is required for SMS notifications');
          }

          // Check if SMS service is properly imported
          if (typeof sendSMSNotification !== 'function') {
            throw new Error('SMS service not properly configured - sendSMSNotification is not a function');
          }

          console.log('Calling sendSMSNotification...');
          const smsResult = await sendSMSNotification(
            notification.phoneNumber,
            `${notification.title}: ${notification.message}`
          );
          
          console.log('SMS sent successfully:', smsResult);
          results.push({ type: 'sms', status: 'sent', messageId: smsResult.MessageId });
          
          // Mark as sent
          await prisma.notification.update({
            where: { id: notification.id },
            data: {
              status: 'sent',
              sentAt: new Date()
            }
          });
          
        } catch (error) {
          console.error('=== SMS Error Details ===');
          console.error('Error message:', error.message);
          console.error('Error stack:', error.stack);
          console.error('Error code:', error.code);
          
          // Mark as failed - get current retryCount first
          const currentNotification = await prisma.notification.findUnique({
            where: { id: notification.id },
            select: { retryCount: true }
          });
          
          await prisma.notification.update({
            where: { id: notification.id },
            data: {
              status: 'failed',
              errorMessage: error.message,
              retryCount: (currentNotification?.retryCount || 0) + 1
            }
          });
          results.push({ type: 'sms', status: 'failed', error: error.message });
        }
      }

      console.log('=== Final Results ===');
      console.log(JSON.stringify(results, null, 2));

      res.status(201).json({
        success: true,
        message: 'Notification processed',
        data: {
          notificationId: notification.id,
          results
        }
      });

    } catch (error) {
      console.error('=== Controller Error ===');
      console.error('Error message:', error.message);
      console.error('Error stack:', error.stack);
      
      res.status(500).json({
        success: false,
        message: 'Internal server error',
        error: process.env.NODE_ENV === 'development' ? error.message : undefined
      });
    }
  }

  // Get notifications for a user
  async getUserNotifications(req, res) {
    try {
      const { userId } = req.params;
      const { 
        page = 1, 
        limit = 20, 
        status, 
        type, 
        priority,
        unreadOnly = false 
      } = req.query;

      const whereClause = { userId };

      // Add filters
      if (status) whereClause.status = status;
      if (type) whereClause.type = type;
      if (priority) whereClause.priority = priority;
      if (unreadOnly === 'true') {
        whereClause.status = { not: 'read' };
      }

      const skip = (parseInt(page) - 1) * parseInt(limit);

      const [notifications, total] = await Promise.all([
        prisma.notification.findMany({
          where: whereClause,
          orderBy: { createdAt: 'desc' },
          skip: skip,
          take: parseInt(limit)
        }),
        prisma.notification.count({
          where: whereClause
        })
      ]);

      res.json({
        success: true,
        data: {
          notifications,
          pagination: {
            page: parseInt(page),
            limit: parseInt(limit),
            total,
            pages: Math.ceil(total / parseInt(limit))
          }
        }
      });

    } catch (error) {
      console.error('Get user notifications error:', error);
      res.status(500).json({
        success: false,
        message: 'Internal server error'
      });
    }
  }

  // Mark notification as read
  async markAsRead(req, res) {
    try {
      const { notificationId } = req.params;
      const { userId } = req.body;

      const notification = await prisma.notification.findFirst({
        where: { 
          id: notificationId, 
          userId 
        }
      });

      if (!notification) {
        return res.status(404).json({
          success: false,
          message: 'Notification not found'
        });
      }

      await prisma.notification.update({
        where: { id: notificationId },
        data: {
          status: 'read',
          readAt: new Date()
        }
      });

      res.json({
        success: true,
        message: 'Notification marked as read'
      });

    } catch (error) {
      console.error('Mark as read error:', error);
      res.status(500).json({
        success: false,
        message: 'Internal server error'
      });
    }
  }

  // Get notification by ID
  async getNotificationById(req, res) {
    try {
      const { notificationId } = req.params;

      const notification = await prisma.notification.findUnique({
        where: { 
          id: notificationId 
        }
      });

      if (!notification) {
        return res.status(404).json({
          success: false,
          message: 'Notification not found'
        });
      }

      res.json({
        success: true,
        data: notification
      });

    } catch (error) {
      console.error('Get notification error:', error);
      res.status(500).json({
        success: false,
        message: 'Internal server error'
      });
    }
  }

  // Get notification statistics
  async getNotificationStats(req, res) {
    try {
      const { userId } = req.params;
      const { days = 7 } = req.query;

      const startDate = new Date();
      startDate.setDate(startDate.getDate() - parseInt(days));

      // Get all notifications for the user in the date range
      const notifications = await prisma.notification.findMany({
        where: {
          userId,
          createdAt: { gte: startDate }
        }
      });

      // Calculate stats
      const result = {
        total: notifications.length,
        pending: notifications.filter(n => n.status === 'pending').length,
        sent: notifications.filter(n => n.status === 'sent').length,
        delivered: notifications.filter(n => n.status === 'delivered').length,
        read: notifications.filter(n => n.status === 'read').length,
        failed: notifications.filter(n => n.status === 'failed').length,
        inApp: notifications.filter(n => n.type === 'in_app' || n.type === 'both').length,
        sms: notifications.filter(n => n.type === 'sms' || n.type === 'both').length
      };

      res.json({
        success: true,
        data: {
          period: `Last ${days} days`,
          stats: result
        }
      });

    } catch (error) {
      console.error('Get notification stats error:', error);
      res.status(500).json({
        success: false,
        message: 'Internal server error'
      });
    }
  }
}

module.exports = new NotificationController();