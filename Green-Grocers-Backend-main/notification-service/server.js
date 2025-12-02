require('dotenv').config();
const http = require('http');
const app = require('./src/app');
const { connectDatabase } = require('./src/config/db');
const { initializeWebSocket } = require('./src/utils/socketManager');

const PORT = process.env.PORT || 3000;

async function startServer() {
  try {
    // Connect to database
    await connectDatabase();
    console.log('✓ Database connected successfully');

    // Create HTTP server
    const server = http.createServer(app);

    // Initialize WebSocket
    const io = initializeWebSocket(server);
    console.log('✓ WebSocket initialized');

    // Start server
    server.listen(PORT, () => {
      console.log(`🚀 Notification service running on port ${PORT}`);
      console.log(`Environment: ${process.env.NODE_ENV}`);
    });

    // Graceful shutdown
    process.on('SIGTERM', () => {
      console.log('SIGTERM received, shutting down gracefully');
      server.close(() => {
        process.exit(0);
      });
    });

  } catch (error) {
    console.error('❌ Failed to start server:', error);
    process.exit(1);
  }
}

startServer();