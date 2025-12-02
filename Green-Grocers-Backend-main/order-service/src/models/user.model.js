const mongoose = require("mongoose");

const extraDetailsSchema = new mongoose.Schema(
  {
    label: String,
    value: String,
  },
  { _id: false }
);

const userSchema = new mongoose.Schema(
  {
    email: {
      type: String,
      required: [true, "Email is required"],
      lowercase: true,
      trim: true,
    },
    phone: {
      type: String,
    },
    password: {
      type: String,
    },
    vendorId: {
      type: String,
      unique: function () {
        return this.platform === "trivestore"; // Only enforce uniqueness for trivestore
      },
      required: function () {
        return this.platform === "trivestore"; // Only required for trivestore
      },
    },
    clientId: {
      type: String,
      unique: function () {
        return this.platform === "trivemart"; // Only enforce uniqueness for trivemart
      },
      required: function () {
        return this.platform === "trivemart"; // Only required for trivemart
      },
    },
    platform: {
      type: String,
      enum: ["trivemart", "trivestore", "triveexpress"],
      required: [true, "Platform is required"],
    },
    googleId: String,
    facebookId: String,
    name: String,
    role: { type: String, default: "user", enum: ["user", "admin"] },
    firstName: String,
    lastName: String,

    addresses: [
      {
        country: String,
        state: String,
        city: String,
        localGovernment: String,
        address: String,
        postalCode: String,
        isDefault: {
          type: Boolean,
          default: false,
        },
      },
    ],

    profileImage: String, // Path or URL
    extraDetails: [extraDetailsSchema], // Array of objects
    emailConfirmation: {
      token: String,
      expiresAt: Date,
    },
    isEmailConfirmed: {
      type: Boolean,
      default: false,
    },
  },
  { timestamps: true }
);

module.exports = mongoose.model("User", userSchema);
