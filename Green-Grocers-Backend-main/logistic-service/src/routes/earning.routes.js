const express = require("express");
const router = express.Router();
const {
  createEarning,
  getEarningsByManager,
  getEarningsAgent,
} = require("../controllers/earning.controller");

// ➤ Create earning
router.post("/create", createEarning);
router.get("/manager/:managerId", getEarningsByManager);
router.get("/agent/:agentId", getEarningsAgent);
module.exports = router;
