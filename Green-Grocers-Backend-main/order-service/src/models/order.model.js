const mongoose = require("mongoose");

const orderSchema = new mongoose.Schema(
  {
    userId: { type: String, required: true }, // customer placing the order
    vendorId: { type: String, required: true },
    logisticsId: { type: String }, // delivery handler if any

    items: [
      {
        productId: String,
        productName: String,
        productImage: String,
        itemId: String,
        category: String,
        price: Number,
        quantity: Number,
      },
    ],

    authenticationCode: { type: String }, // 4-digit OTP after vendor confirms
    deliveryTimeline: String,

    shippingAddress: {
      street: String,
      city: String,
      state: String,
      postalCode: String,
      country: String,
      phone: String,
    },

    customerName: String,
    totalAmount: { type: Number, required: true },
    discountAmount: { type: Number, default: 0 },
    deliveryCharges: { type: Number, default: 0 },

    couponCode: String,

    paymentMethod: {
      type: String,
      enum: [
        "COD", // Cash on Delivery
        "WALLET", // Pay via in-app wallet
        "CARD", // Stripe/Paystack/Flutterwave card
        "BANK", // Bank transfer
        "TRANSFER", // Manual transfer
        "USSD", // USSD flow
        "PAYPAL",
        "STRIPE",
      ],
      default: "COD",
    },

    paymentStatus: {
      type: String,
      enum: ["PENDING", "PAID", "FAILED", "REFUNDED"],
      default: "PENDING",
    },

    transactionId: {
      type: String, // store reference from Stripe/Paystack/Bank
    },

    paymentStatus: {
      type: String,
      enum: ["PAID", "UNPAID"],
      default: "UNPAID",
    },
    transactionId: String,

    purchaseDate: { type: Date, default: Date.now },
    expectedDeliveryDate: Date,
    actualDeliveryDate: Date,

    status: {
      type: String,
      enum: ["inprogress", "assigned", "delivered", "canceled"],
      default: "inprogress",
    },
    deliveryStatus: {
      type: String,
      enum: ["Pending", "Out for Delivery", "Delivered", "Failed"],
      default: "Pending",
    },
    orderProgress: { type: String, default: "Awaiting Confirmation" },

    notes: String, // from customer
    vendorNotes: String, // internal or vendor remarks

    statusHistory: [
      {
        status: String,
        updatedAt: { type: Date, default: Date.now },
        updatedBy: String, // could be user/admin/vendor
      },
    ],

    platform: {
      type: String,
      enum: ["Web", "Mobile", "POS"],
      default: "Web",
    },

    isDeleted: { type: Boolean, default: false },
    isReturnRequested: { type: Boolean, default: false },
    returnRequest: {
      isRequested: { type: Boolean, default: false },
      reason: String,
      requestedAt: Date,
      status: {
        type: String,
        enum: ["Pending", "Approved", "Rejected", "Refunded"],
        default: "Pending",
      },
      refundAmount: Number,
      processedBy: String,
    },
  },
  { timestamps: true }
);

module.exports = mongoose.model("Order", orderSchema);
