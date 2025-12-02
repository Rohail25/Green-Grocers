const { Server } = require('socket.io');

let io = null;
const connectedUsers = new Map(); // Map to store userId -> socket mapping

/**
 * Initialize Socket.IO server
 * @param {object} server - HTTP server instance
 * @returns {object} Socket.IO server instance
 */
const initializeWebSocket = (server) => {
  io = new Server(server, {
    cors: {
      origin: process.env.ALLOWED_ORIGINS ? process.env.ALLOWED_ORIGINS.split(',') : ['http://localhost:3000'],
      methods: ['GET', 'POST'],
      credentials: true
    },
    transports: ['websocket', 'polling']
  });

  io.on('connection', (socket) => {
    console.log(`🔌 Client connected: ${socket.id}`);

    // Handle user authentication/identification
    socket.on('authenticate', (data) => {
      try {
        const { userId, token } = data;
        
        // In a real app, you'd validate the token here
        // For this example, we'll just store the userId
        if (userId) {
          socket.userId = userId;
          connectedUsers.set(userId, socket);
          
          // Join user-specific room
          socket.join(`user_${userId}`);
          
          console.log(`✓ User authenticated: ${userId} (${socket.id})`);
          
          socket.emit('authenticated', {
            success: true,
            message: 'Successfully authenticated',
            userId
          });

          // Send any pending notifications count
          sendPendingNotificationsCount(userId);
        } else {
          socket.emit('auth_error', {
            success: false,
            message: 'User ID is required'
          });
        }
      } catch (error) {
        console.error('Authentication error:', error);
        socket.emit('auth_error', {
          success: false,
          message: 'Authentication failed'
        });
      }
    });

    // Handle notification acknowledgment
    socket.on('notification_received', (data) => {
      try {
        const { notificationId } = data;
        console.log(`📨 Notification ${notificationId} received by user ${socket.userId}`);
        
        // Here you could update the notification status in database
        // For example: mark as delivered
        
        socket.emit('notification_ack', {
          notificationId,
          status: 'acknowledged'
        });
      } catch (error) {
        console.error('Notification acknowledgment error:', error);
      }
    });

    // Handle mark as read
    socket.on('mark_as_read', (data) => {
      try {
        const { notificationId } = data;
        console.log(`👁 Notification ${notificationId} marked as read by user ${socket.userId}`);
        
        // Update notification status in database
        // This would typically be handled by the notification controller
        
        socket.emit('mark_read_ack', {
          notificationId,
          status: 'read'
        });
      } catch (error) {
        console.error('Mark as read error:', error);
      }
    });

    // Handle disconnect
    socket.on('disconnect', (reason) => {
      console.log(`🔌 Client disconnected: ${socket.id} (${reason})`);
      
      if (socket.userId) {
        connectedUsers.delete(socket.userId);
        console.log(`👋 User ${socket.userId} disconnected`);
      }
    });

    // Handle connection errors
    socket.on('error', (error) => {
      console.error(`🚨 Socket error for ${socket.id}:`, error);
    });

    // Send initial connection confirmation
    socket.emit('connected', {
      message: 'Connected to notification service',
      socketId: socket.id,
      timestamp: new Date().toISOString()
    });
  });

  // Handle server errors
  io.engine.on('connection_error', (err) => {
    console.error('Socket.IO connection error:', err);
  });

  console.log('🚀 WebSocket server initialized');
  return io;
};

/**
 * Send in-app notification to a specific user
 * @param {string} userId - User ID
 * @param {object} notification - Notification data
 * @returns {Promise<boolean>} Success status
 */
const sendInAppNotification = async (userId, notification) => {
  try {
    if (!io) {
      throw new Error('WebSocket server not initialized');
    }

    // Send to user-specific room
    const roomName = `user_${userId}`;
    const clientsInRoom = await io.in(roomName).fetchSockets();
    
    if (clientsInRoom.length === 0) {
      console.log(`📪 No active connections for user ${userId}. Notification will be stored for later delivery.`);
      return false; // User not connected
    }

    // Send notification to all user's connected devices
    io.to(roomName).emit('new_notification', {
      ...notification,
      timestamp: new Date().toISOString()
    });

    console.log(`📨 In-app notification sent to user ${userId} (${clientsInRoom.length} device(s))`);
    return true;

  } catch (error) {
    console.error('Send in-app notification error:', error);
    throw error;
  }
};

/**
 * Send notification to multiple users
 * @param {Array<string>} userIds - Array of user IDs
 * @param {object} notification - Notification data
 * @returns {Promise<object>} Results summary
 */
const sendBulkInAppNotification = async (userIds, notification) => {
  try {
    if (!io) {
      throw new Error('WebSocket server not initialized');
    }

    const results = {
      sent: 0,
      failed: 0,
      offline: 0
    };

    for (const userId of userIds) {
      try {
        const success = await sendInAppNotification(userId, notification);
        if (success) {
          results.sent++;
        } else {
          results.offline++;
        }
      } catch (error) {
        console.error(`Failed to send notification to user ${userId}:`, error);
        results.failed++;
      }
    }

    return results;

  } catch (error) {
    console.error('Bulk in-app notification error:', error);
    throw error;
  }
};

/**
 * Send pending notifications count to user
 * @param {string} userId - User ID
 */
const sendPendingNotificationsCount = async (userId) => {
  try {
    // Query the database for unread notifications
    const prisma = require('../utils/prisma');
    
    const unreadCount = await prisma.notification.count({
      where: {
        userId,
        status: { not: 'read' }
      }
    });

    const roomName = `user_${userId}`;
    io.to(roomName).emit('unread_count', {
      count: unreadCount,
      timestamp: new Date().toISOString()
    });

    console.log(`📊 Sent unread count (${unreadCount}) to user ${userId}`);

  } catch (error) {
    console.error('Send pending notifications count error:', error);
  }
};

/**
 * Get connected users count
 * @returns {number} Number of connected users
 */
const getConnectedUsersCount = () => {
  return connectedUsers.size;
};

/**
 * Get all connected user IDs
 * @returns {Array<string>} Array of connected user IDs
 */
const getConnectedUserIds = () => {
  return Array.from(connectedUsers.keys());
};

/**
 * Check if user is online
 * @param {string} userId - User ID
 * @returns {boolean} Online status
 */
const isUserOnline = (userId) => {
  return connectedUsers.has(userId);
};

/**
 * Broadcast system message to all connected users
 * @param {object} message - Message data
 */
const broadcastSystemMessage = (message) => {
  if (!io) {
    console.error('WebSocket server not initialized');
    return;
  }

  io.emit('system_message', {
    ...message,
    timestamp: new Date().toISOString()
  });

  console.log('📢 System message broadcasted to all users');
};

module.exports = {
  initializeWebSocket,
  sendInAppNotification,
  sendBulkInAppNotification,
  sendPendingNotificationsCount,
  getConnectedUsersCount,
  getConnectedUserIds,
  isUserOnline,
  broadcastSystemMessage
};