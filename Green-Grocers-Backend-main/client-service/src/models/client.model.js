const mongoose = require("mongoose");
const crypto = require("crypto");

const clientSchema = new mongoose.Schema(
  {
    userId: {
      type: mongoose.Schema.Types.ObjectId,
      ref: "User",
      required: true,
    },
    clientId: { type: String, required: true, unique: true },
    fullName: String,
    email: String,
    phone: String,
    favoriteProducts: [
      { type: mongoose.Schema.Types.ObjectId, ref: "Product" },
    ],
    ratingHistory: [
      {
        productId: { type: mongoose.Schema.Types.ObjectId, ref: "Product" },
        rating: Number,
        review: String,
      },
    ],
    logisticRatings: [
      {
        orderId: { type: mongoose.Schema.Types.ObjectId, ref: "Order" },
        rating: Number,
        comment: String,
      },
    ],

    // ✅ Referral fields
    referral: {
      code: { type: String, unique: true }, // client’s referral code
      referredBy: { type: String }, // code of the user who referred this client
      totalReferrals: { type: Number, default: 0 },
      totalPoints: { type: Number, default: 0 },
      history: [
        {
          referredUser: { type: mongoose.Schema.Types.ObjectId, ref: "User" },
          hasOrdered: { type: Boolean, default: false },
          pointsEarned: { type: Number, default: 0 },
        },
      ],
    },
  },
  { timestamps: true }
);

// 🔑 Generate referral code before saving
clientSchema.pre("save", function (next) {
  if (!this.referral.code) {
    // Example: generate a short random referral code
    this.referral.code = crypto.randomBytes(4).toString("hex").toUpperCase();
  }
  next();
});

module.exports = mongoose.model("Client", clientSchema);
