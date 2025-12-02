const express = require("express");
const cors = require("cors");
const morgan = require("morgan");
const helmet = require("helmet");
const logger = require("./utils/logger");
const vendorRoutes = require("./routes/vendor.routes");
const couponRoutes = require("./routes/coupons.routes");
const categoryRoutes = require("./routes/category.routes");
const { checkUserServiceHealth } = require("./utils/communicateUserService");
const path = require("path");

const app = express();

// Middleware setup - LOG BEFORE ANY OTHER MIDDLEWARE
app.use((req, res, next) => {
  // Log IMMEDIATELY when request arrives - before any processing
  console.log('[App] ========================================');
  console.log('[App] REQUEST ARRIVED - Logging headers FIRST');
  console.log('[App] Raw authorization header:', req.headers.authorization);
  console.log('[App] Authorization length:', req.headers.authorization ? req.headers.authorization.length : 0);
  console.log('[App] All headers:', JSON.stringify(req.headers, null, 2));
  console.log('[App] ========================================');
  next();
});

app.use("/uploads", express.static(path.join(__dirname, "uploads")));
app.use(express.json());
const corsOptions = {
  origin: "*", // or '*' if you want to allow all
  methods: ["GET", "POST", "PUT", "DELETE", "OPTIONS", "PATCH"],
  allowedHeaders: ["Content-Type", "Authorization"],
  credentials: true, // if you're using cookies or auth headers
};

app.use(cors(corsOptions));

// Log AFTER CORS to see if it modifies headers
app.use((req, res, next) => {
  console.log('[App] After CORS - Authorization:', req.headers.authorization);
  next();
});

app.use(morgan("dev"));

// Log AFTER MORGAN
app.use((req, res, next) => {
  console.log('[App] After MORGAN - Authorization:', req.headers.authorization);
  next();
});

// Check if helmet modifies headers
const helmetInstance = helmet();
app.use((req, res, next) => {
  console.log('[App] Before HELMET - Authorization:', req.headers.authorization);
  next();
});
app.use(helmetInstance);
app.use((req, res, next) => {
  console.log('[App] After HELMET - Authorization:', req.headers.authorization);
  next();
});

app.use("/api/vendors", vendorRoutes);
app.use("/api/vendors/coupons", couponRoutes);
app.use("/api/vendors/categories", categoryRoutes);

app.get("/health", (req, res) => {
  res.status(200).json({ status: "UP", message: "Vendor Service is running." });
});

app.get("/health/userservice", async (req, res) => {
  const status = await checkUserServiceHealth();
  res.json(status);
});

app.use((err, req, res, next) => {
  logger.error(err.stack);
  res.status(500).json({ message: "Internal server error" });
});

module.exports = app;
