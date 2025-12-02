// server.js
const dotenv = require('dotenv');
dotenv.config();

const app = require('./src/app');
const connectDB = require('./config/db');
const logger = require('./src/utils/logger');

// Connect to DB and start server
const PORT = process.env.PORT || 3005;

connectDB().then(() => {
    app.listen(PORT, "0.0.0.0", () => {
    logger.info(`Client service running on port ${PORT}`);
  });
}).catch((err) => {
  logger.error('Failed to connect to database:', err);
  process.exit(1);
});
