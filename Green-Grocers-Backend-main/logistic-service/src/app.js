const express = require("express");
const cors = require("cors");
const morgan = require("morgan");
const helmet = require("helmet");
const logger = require("./utils/logger");
const earningRoutes = require("./routes/earning.routes");
const deliveryAssignment = require("./routes/deliveryAssignment.routes");
const metricsPerformance = require("./routes/metrics.routes");

const app = express();

// Middleware setup
app.use(express.json({ limit: "50mb" }));
app.use(express.urlencoded({ limit: "50mb", extended: true }));
app.use(cors());
app.use(morgan("dev"));
app.use(helmet());

app.use("/api/earnings", earningRoutes);
app.use("/api/assign", deliveryAssignment);
app.use("/api/performance", metricsPerformance);

app.get("/health", (req, res) => {
  res.status(200).json({ status: "UP", message: "Client Service is running." });
});

app.use((err, req, res, next) => {
  logger.error(err.stack);
  res.status(500).json({ message: "Internal server error" });
});

module.exports = app;
