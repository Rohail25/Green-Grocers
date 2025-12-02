const mongoose = require("mongoose");

const walletSchema = new mongoose.Schema(
  {
    userId: {
      type: mongoose.Schema.Types.ObjectId,
      ref: "User",
      required: true,
    },
    balance: { type: Number, default: 0 },
    transactions: [
      {
        transactionId: { type: String, required: true },
        type: { type: String, enum: ["CREDIT", "DEBIT"], required: true },
        amount: { type: Number, required: true },
        description: String,
        bankName: String,
        accountNumber: String,
        status: {
          type: String,
          enum: ["PENDING", "SUCCESS", "FAILED"],
          default: "PENDING",
        },
        createdAt: { type: Date, default: Date.now },
      },
    ],
  },
  { timestamps: true }
);
module.exports = mongoose.model("Wallet", walletSchema);
