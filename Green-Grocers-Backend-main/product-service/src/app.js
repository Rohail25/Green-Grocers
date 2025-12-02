const express = require('express');
const cors = require('cors');
const morgan = require('morgan');
const helmet = require('helmet');
const logger = require('./utils/logger');
const productRoutes = require('./routes/product.routes');
const brandRoutes = require('./routes/brand.routes');
const categoryRoutes = require('./routes/category.routes');
const packageRoutes = require('./routes/package.routes');
const productReviewRoutes = require('./routes/review.rating.routes')
const path = require('path');

const app = express();

// Middleware setup
app.use("/uploads", express.static(path.join(__dirname, "uploads")));
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

app.use("/api/products", productRoutes);
app.use("/api/brands", brandRoutes); 
app.use("/api/categories", categoryRoutes);
app.use("/api/packages", packageRoutes);
app.use('/api/product-reviews', productReviewRoutes)

app.get('/health', (req, res) => {
  res.status(200).json({ status: 'UP', message: 'Product Service is running.' });
});

app.use((err, req, res, next) => {
  logger.error(err.stack);
  res.status(500).json({ message: "Internal server error" });
});

module.exports = app;
