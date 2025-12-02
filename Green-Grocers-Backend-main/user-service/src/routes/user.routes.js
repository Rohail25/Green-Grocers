const express = require("express");
const {
  registerUser,
  loginUser,
  getProfile,
  updateProfile,
  confirmEmail,
  resendConfirmationEmail,
  addAddress,
  updateAddress,
  deleteAddress,
  addFavoriteProduct,
  rateLogistics,
  getLogisticRatings,
  createAgent, // Added
  resetPassword,
  uploadVerificationDocument,
  setAvailabilityAndAddress,
  getAgents,
  updateAgent,
  deleteAgent, // Added
} = require("../controllers/user.controller");

const router = express.Router();
const auth = require("../middlewares/auth.middleware");
const upload = require("../middlewares/upload.middleware");

// =============================
// Auth
// =============================
router.post("/register", registerUser);
router.post("/login", loginUser);

// =============================
// Profile
// =============================
router.get("/", auth, getProfile);
router.put("/profile", auth, upload.single("profileImage"), updateProfile);
router.post(
  "/upload-verification",
  auth,
  upload.single("document"),
  uploadVerificationDocument
);

// =============================
// Email Confirmation
// =============================
router.post("/confirm", confirmEmail);
router.post("/resend-confirmation", resendConfirmationEmail);

// =============================
// Address Management
// =============================
router.post("/add-address", auth, addAddress);
router.patch("/address/:addressId", auth, updateAddress);
router.delete("/address/:addressId", auth, deleteAddress);

// =============================
// Favorites
// =============================
router.post("/favorites", auth, addFavoriteProduct);

// =============================
// Logistics
// =============================
router.post("/rate-logistics", auth, rateLogistics);
router.get("/rate-logistics/:logisticId", auth, getLogisticRatings);

// =============================
// Logistic Creates Agent
// =============================
router.post("/agents", auth, createAgent);
router.get("/agents", auth, getAgents);
router.put("/agents/:id", auth, updateAgent);
router.delete("/agents/:id", auth, deleteAgent);
// =============================
// Password Reset
// =============================
router.post("/reset-password", resetPassword);
router.put(
  "/availability",
  auth, // ensure the user is logged in
  setAvailabilityAndAddress
);

module.exports = router;
