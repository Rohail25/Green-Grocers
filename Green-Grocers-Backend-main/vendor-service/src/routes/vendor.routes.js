const express = require("express");
const {
  registerVendor,
  getVendor,
  getVendorById,
  updateVendor,
  deleteVendor,
  updateVendorInventory,
  confirmOrder,
} = require("../controllers/vendor.controller");
const auth = require("../middlewares/auth.middleware");
const { uploadVendorImages } = require("../middlewares/multer.middleware");

const router = express.Router();

router.post("/register-vendor", auth, registerVendor);
router.get("/get-vendor", auth, getVendor);
router.get("/vendor-by-id/:vendorId", auth, getVendorById);
router.put("/update-vendor", auth, uploadVendorImages, updateVendor);
router.delete("/delete-vendor", auth, deleteVendor);
router.put("/:vendorId/inventory", auth, updateVendorInventory);
router.put("/orders/:orderId/confirm", auth, confirmOrder);

module.exports = router;
