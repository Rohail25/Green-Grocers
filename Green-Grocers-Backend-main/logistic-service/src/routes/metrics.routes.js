const express = require("express");
const router = express.Router();
const metricsController = require("../controllers/metrics.controller.js");

router.get("/:logisticId", metricsController.getLogisticsPerformanceMetrics);

module.exports = router;
