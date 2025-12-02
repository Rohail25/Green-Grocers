const mongoose = require("mongoose");
const bcrypt = require("bcryptjs");

const verificationSchema = new mongoose.Schema({
  documentType: { type: String, required: true }, // e.g., "CNIC", "License"
  documentUrl: { type: String, required: true }, // uploaded file link
  status: {
    type: String,
    enum: ["pending", "approved", "rejected"],
    default: "pending",
  },
  uploadedAt: { type: Date, default: Date.now },
});

const userSchema = new mongoose.Schema(
  {
    email: { type: String, lowercase: true, trim: true, unique: true },
    phone: String,
    password: { type: String, required: true },

    platform: {
      type: String,
      enum: ["trivemart", "trivestore", "triveexpress"],
      required: true,
    },

    role: {
      type: String,
      enum: ["customer", "logistic", "vendor", "admin", "support", "agent"],
      default: "customer",
    },

    // For logistic agents
    parentLogistic: {
      type: mongoose.Schema.Types.ObjectId,
      ref: "User",
      required: function () {
        return this.role === "agent";
      },
    },

    // Email verification
    isEmailConfirmed: { type: Boolean, default: false },
    emailVerificationToken: String,
    emailVerificationExpires: Date,

    // Logistic verification
    verificationDocuments: [verificationSchema],
    isVerified: { type: Boolean, default: false },
    documentsUploaded: { type: Boolean, default: false },
    isAvailable: {
      type: Boolean,
      default: false,
    },
    firstName: String,
    lastName: String,
    profileImage: String,
    preferredVendors: [
      {
        type: mongoose.Schema.Types.ObjectId,
        ref: "User", // assuming vendors are also stored in User collection
      },
    ],

    addresses: [
      {
        street: String,
        city: String,
        state: String,
        zipCode: String,
        country: String,
      },
    ],
  },
  { timestamps: true }
); 

// Hash password before save
userSchema.pre("save", async function (next) {
  if (!this.isModified("password")) return next();
  this.password = await bcrypt.hash(this.password, 10);
  next();
});

// Compare password
userSchema.methods.comparePassword = async function (candidatePassword) {
  return bcrypt.compare(candidatePassword, this.password);
};

module.exports = mongoose.model("User", userSchema);
