const express = require('express');
const cors = require('cors');
const morgan = require('morgan');
const helmet = require('helmet');
const logger = require('./utils/logger');
const orderRoutes = require('./routes/order.routes');
const cartRoutes = require('./routes/cart.routes');

const app = express();

// Middleware setup
app.use(express.json());
const corsOptions = {
  origin: "*", // or '*' if you want to allow all
  methods: ["GET", "POST", "PUT", "DELETE", "OPTIONS", "PATCH"],
  allowedHeaders: ["Content-Type", "Authorization"],
  credentials: true, // if you're using cookies or auth headers
};

app.use(cors(corsOptions));
app.use(morgan("dev"));
app.use(helmet());

app.use('/api/orders', orderRoutes);
app.use('/api/cart', cartRoutes);

app.get('/health', (req, res) => {
  res.status(200).json({ status: 'UP', message: 'Order Service is running.' });
});

app.use((err, req, res, next) => {
  if (process.env.NODE_ENV === "test") {
    req.user = { vendorId: "vendor789", logisticsId: "logi123" }; // Mock user
  }

  logger.error(err.stack);
  res.status(500).json({ message: "Internal server error" });
});

module.exports = app;
