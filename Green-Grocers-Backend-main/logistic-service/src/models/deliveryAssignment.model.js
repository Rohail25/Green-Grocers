// deliveryAssignment.model.js
const mongoose = require("mongoose");

const deliveryAssignmentSchema = new mongoose.Schema(
  {
    parcelNo: { type: String, required: true }, // e.g., #P124
    teamMemberId: {
      type: mongoose.Schema.Types.ObjectId,
      ref: "TeamMember",
      required: true,
    },
    orderId: {
      type: mongoose.Schema.Types.ObjectId,
      ref: "Order",
      required: true,
    },
    vendorLocation: {
      address: String,
      coordinates: {
        lat: Number,
        lng: Number,
      },
    },
    travelDistance: Number, // in km
    estimatedTime: Number, // in minutes
    status: {
      type: String,
      enum: ["assigned", "in-progress", "completed", "cancelled"],
      default: "assigned",
    },
    assignedAt: { type: Date, default: Date.now },
    startedAt: Date,
    completedAt: Date,
    authenticationCode: String, // For delivery verification
  },
  { timestamps: true }
);

module.exports = mongoose.model("DeliveryAssignment", deliveryAssignmentSchema);
