const express = require('express');
const notificationController = require('../controllers/notification.controller');
const { asyncHandler } = require('../middlewares/asyncHandler');

const router = express.Router();

// Send notification
router.post('/send', asyncHandler(notificationController.sendNotification));

// Get user notifications
router.get('/user/:userId', asyncHandler(notificationController.getUserNotifications));

// Get notification by ID
router.get('/:notificationId', asyncHandler(notificationController.getNotificationById));

// Mark notification as read
router.patch('/:notificationId/read', asyncHandler(notificationController.markAsRead));

// Get notification statistics
router.get('/user/:userId/stats', asyncHandler(notificationController.getNotificationStats));

module.exports = router;