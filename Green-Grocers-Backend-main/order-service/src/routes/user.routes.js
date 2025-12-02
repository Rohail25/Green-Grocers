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
} = require("../controllers/user.controller");
const router = express.Router();
const auth = require("../middlewares/auth.middleware");
const upload = require("../middlewares/upload.middleware");
router.post("/register", registerUser);
router.post("/login", loginUser);

router.get("/", auth, getProfile);
router.put("/profile", auth, upload.single("profileImage"), updateProfile);
router.post("/confirm", confirmEmail);
router.post("/resend-confirmation", resendConfirmationEmail);
router.post("/add-address", auth, addAddress);
router.patch("/address/:addressId", auth, updateAddress);
router.delete("/address/:addressId", auth, deleteAddress);

module.exports = router;
