const express = require("express");
const router = express.Router();
const auth = require("../middlewares/auth.middleware");
const {
  getWallet,
  withdrawFromWallet,
  creditWallet,
  debitWallet,
} = require("../controllers/wallet.controller");

router.get("/", auth, getWallet);
router.post("/withdraw", auth, withdrawFromWallet);
router.post("/credit", auth, creditWallet);
router.post("/debit", auth, debitWallet); // 🔥 new route

module.exports = router;
