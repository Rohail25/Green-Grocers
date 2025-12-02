const express = require("express");
const router = express.Router();
const {
  createOrder,
  getVendorOrders,
  updateOrderStatus,
  getOrdersByUser,
  getOrderById,
  requestReturn,
  processReturn,
  getOrdersByLogistic,
  initiatePayment,
  handleWebhook,
} = require("../controllers/order.controller");
const auth = require("../middlewares/auth.middleware");
 
router.post("/", auth, createOrder);
router.get("/vendor/:vendorId", auth, getVendorOrders);
router.put("/:orderId/status", auth, updateOrderStatus);
router.get("/user/:userId", auth, getOrdersByUser);
router.get("/:orderId", auth, getOrderById);
router.put("/:orderId/return-request", auth, requestReturn); // Customer requests return
router.put("/:orderId/return-process", auth, processReturn); // Admin/vendor processes return/refund
router.get("/logistic/:logisticId", auth, getOrdersByLogistic);
router.post("/pay/:orderId", initiatePayment);
router.post(
  "/webhook",
  express.raw({ type: "application/json" }),
  handleWebhook
);

module.exports = router;
