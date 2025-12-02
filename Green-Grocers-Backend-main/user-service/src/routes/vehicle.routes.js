const express = require("express");
const {
  addVehicle,
  getVehicles,
  getVehicleById,
  updateVehicle,
  deleteVehicle,
} = require("../controllers/vehicle.controller.js");
const auth = require("../middlewares/auth.middleware.js");

const router = express.Router();

// =============================
// Vehicle CRUD
// =============================
router.post("/add", auth, addVehicle);
router.get("/", auth, getVehicles);
router.get("/:id", auth, getVehicleById);
router.put("/:id", auth, updateVehicle);
router.delete("/:id", auth, deleteVehicle);

module.exports = router; // ✅ not "export default"
