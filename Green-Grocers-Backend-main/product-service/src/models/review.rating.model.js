const mongoose = require("mongoose");

const reviewRatingSchema = new mongoose.Schema(
  {
    productId: { type: mongoose.Schema.Types.ObjectId, ref: "Product" },
    user: {
      userId: { type: mongoose.Schema.Types.ObjectId, ref: "User" },
      name: { type: String },
      email: { type: String },
      profileImage: { type: String },
    },
    rating: { type: Number, required: true },
    review: { type: String, required: true },
    date: { type: Date, default: Date.now },
  },
  { timestamps: true }
);

reviewRatingSchema.pre(/^find/, function (next) {
  this.populate("productId");
  next();
});

module.exports = mongoose.model("productReviewRating", reviewRatingSchema);
