// routes/deliveryAssignment.routes.js
const express = require("express");
const router = express.Router();
const deliveryController = require("../controllers/deliveryAssignment.controller");

// Manager assigns delivery to team member
router.post("/", deliveryController.assignDelivery);
 
// Existing endpoints
router.get(
  "/my-assignments/:teamMemberId",
  deliveryController.getMyAssignments
);
router.patch(
  "/update-status/:assignmentId",
  deliveryController.updateAssignmentStatus
);
router.patch(
  "/toggle-availability/:teamMemberId",
  deliveryController.toggleAvailability
);
router.get("/analytics", deliveryController.getDeliveryAnalytics);

module.exports = router;
