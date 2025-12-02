const mongoose = require("mongoose");

const earningSchema = new mongoose.Schema(
  {
    logisticsId: {
      type: mongoose.Schema.Types.ObjectId,
      ref: "TeamMember",
      required: true,
    },
    orderId: {
      type: mongoose.Schema.Types.ObjectId,
      ref: "Order",
      required: true,
    },
    amount: { type: Number, required: true }, // how much the agent earned
    transactionId: String, // optional if external
    paymentMethod: {
      type: String,
      enum: ["Wallet", "Bank", "Cash"],
      default: "Wallet",
    },
    status: {
      type: String,
      enum: ["PAID", "PENDING", "FAILED"],
      default: "PENDING",
    },
    paidAt: Date,
  },
  { timestamps: true }
);

module.exports = mongoose.model("Earning", earningSchema);
