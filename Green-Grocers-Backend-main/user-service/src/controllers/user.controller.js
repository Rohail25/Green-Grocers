const prisma = require("../utils/prisma");
const jwt = require("jsonwebtoken");
const bcrypt = require("bcryptjs");
const { v4: uuidv4 } = require("uuid");
const { uploadFileToS3 } = require("../middlewares/s3Uploader.middleware");
const crypto = require("crypto");

const { sendEmail } = require("../utils/ses");
const { getPlatformPath } = require("../utils/helper");
const { registerClient } = require("../utils/communicateClientService");
// const url = "http://51.21.203.90:3000";
const url = "http://localhost:5173";
const registerUser = async (req, res) => {
  try {
    const {
      email,
      phone,
      password,
      confirmPassword,
      platform,
      googleId,
      facebookId,
      referralCode,
      role,
    } = req.body;

    if (!platform && role !== "agent")
      return res.status(400).json({ message: "Platform is required" });

    // Default role
    const userRole = role || "customer";

    // Password check for manual signup
    if (userRole !== "agent" && password && password !== confirmPassword) {
      return res.status(400).json({ message: "Passwords do not match" });
    }

    // Check existing
    const existingUser = await prisma.user.findFirst({
      where: { 
        email: email?.toLowerCase().trim(),
        platform 
      }
    });
    if (existingUser) {
      return res.status(409).json({ message: "Email already registered" });
    }

    const vendorId =
      platform === "trivestore" ? `VEND-${uuidv4().slice(0, 8)}` : "";
    const clientId =
      platform === "trivemart" ? `MART-${uuidv4().slice(0, 8)}` : "";

    // Validate required environment variables
    if (!process.env.JWT_SECRET) {
      console.error("[Register Error] JWT_SECRET is missing");
      return res.status(500).json({ 
        message: "Server configuration error. Please contact support." 
      });
    }

    // Hash password before saving
    const hashedPassword = await bcrypt.hash(password, 10);

    // Save user FIRST to get the id
    const user = await prisma.user.create({
      data: {
        email: email?.toLowerCase().trim(),
        phone,
        password: hashedPassword,
        platform,
        vendorId: vendorId || null,
        clientId: clientId || null,
        googleId: googleId || null,
        facebookId: facebookId || null,
        role: userRole,
        isEmailConfirmed: false,
        isVerified: userRole === "logistic" ? false : true, // Logistic must be admin-approved
        verificationDocuments: [],
        preferredVendors: [],
        addresses: []
      }
    });

    // Generate confirm token AFTER user is saved (so we have user.id)
    const token = jwt.sign(
      { id: user.id, platform: user.platform },
      process.env.JWT_SECRET,
      { expiresIn: "24h" }
    );
    
    const plateFormRole = getPlatformPath(platform);
    const confirmLink = `${url}/auth/verify?key=${token}`;

    // Send email (wrap in try-catch so registration still succeeds if email fails)
    try {
      await sendEmail(
        email,
        "Confirm your account",
        `Click here to confirm your email: ${confirmLink}`
      );
    } catch (emailError) {
      console.error("[Email Error]", emailError.message);
      // Don't fail registration if email fails
      // User can still confirm via resend confirmation endpoint
    }

    // Register client in client-service if platform is trivemart
    if (platform === "trivemart") {
      try {
        await registerClient(user, token, referralCode);
      } catch (clientRegError) {
        console.error("[Client Registration Error]", clientRegError.message);
        // Don't fail user registration if client service call fails
      }
    }

    return res.status(201).json({
      message: "User registered. Please check your email to confirm.",
      requiresConfirmation: true,
    });
  } catch (err) {
    console.error("[Register Error]", err.message);
    console.error("[Register Error Stack]", err.stack);
    
    // Provide more specific error messages
    if (err.name === 'ValidationError') {
      return res.status(400).json({ 
        message: "Validation error", 
        error: err.message 
      });
    }
    
    if (err.code === 11000) {
      return res.status(409).json({ 
        message: "Email already registered" 
      });
    }
    
    return res.status(500).json({ 
      message: "Internal server error",
      error: process.env.NODE_ENV === 'development' ? err.message : undefined
    });
  }
};

const loginUser = async (req, res) => {
  try {
    const { email, phone, password, platform, googleId, facebookId } = req.body;

    const user = email 
      ? await prisma.user.findFirst({ 
          where: { email: email.toLowerCase().trim(), platform } 
        })
      : await prisma.user.findFirst({ 
          where: { phone, platform } 
        });
    if (!user)
      return res
        .status(404)
        .json({ message: "User not found for this platform" });

    if (!user.isEmailConfirmed) {
      return res
        .status(403)
        .json({ message: "Please confirm your email first" });
    }

    const token = jwt.sign(
      { id: user.id, platform: user.platform, role: user.role },
      process.env.JWT_SECRET,
      { expiresIn: "7d" }
    );

    // 🚨 Logistic + Agent must be verified & upload docs
    if ((user.role === "logistic" || user.role === "agent") && !user.isVerified) {
      if (!user.documentsUploaded) {
        // Case 1: documents not uploaded yet
        return res.status(403).json({
          message: "Please upload documents for verification",
          requiresVerification: true,
          documentsUploaded: false,
          token,
        });
      } else {
        // Case 2: documents uploaded but waiting for admin
        return res.status(403).json({
          message: "Admin has not verified your account yet",
          requiresVerification: true,
          documentsUploaded: true,
        });
      }
    }

    // 🚨 Agent also requires parent logistic to be verified
    if (user.role === "agent" && user.parentLogistic) {
      const parent = await prisma.user.findUnique({
        where: { id: user.parentLogistic }
      });
      if (!parent || !parent.isVerified) {
        return res
          .status(403)
          .json({ message: "Parent logistic not verified yet" });
      }
    }

    if (password) {
      if (!user.password) {
        return res
          .status(400)
          .json({ message: "Password not set. Use social login." });
      }
      const isMatch = await bcrypt.compare(password, user.password);
      if (!isMatch)
        return res.status(401).json({ message: "Invalid password" });
    }

    if (googleId || facebookId) {
      if (
        (googleId && user.googleId !== googleId) ||
        (facebookId && user.facebookId !== facebookId)
      ) {
        return res.status(401).json({ message: "Social login failed" });
      }
    }

    // Remove password from response
    const { password: _, ...userWithoutPassword } = user;
    
    res.json({
      token,
      user: {
        id: user.id,
        email: user.email,
        role: user.role,
        platform: user.platform,
        vendorId: user.vendorId,
        clientId: user.clientId,
      },
    });
  } catch (err) {
    console.error("[Login Error]", err);
    res.status(500).json({ message: "Internal server error" });
  }
};


const createAgent = async (req, res) => {
  try {
    const logisticId = req.user.id; // from auth middleware
    const logistic = await prisma.user.findUnique({
      where: { id: logisticId }
    });
    const randomPassword = crypto.randomBytes(6).toString("hex"); // 12-char password

    if (!logistic || logistic.role !== "logistic") {
      return res
        .status(403)
        .json({ message: "Only logistic can create agents" });
    }
    if (!logistic.isVerified) {
      return res
        .status(403)
        .json({ message: "Logistic must be verified by admin first" });
    }

    const { email, firstName, lastName, phone } = req.body;
    const existing = await prisma.user.findFirst({ 
      where: { email: email?.toLowerCase().trim(), platform: logistic.platform } 
    });
    if (existing)
      return res.status(400).json({ message: "Agent already exists" });

    // Hash password
    const hashedPassword = await bcrypt.hash(randomPassword, 10);

    const agent = await prisma.user.create({
      data: {
        email: email?.toLowerCase().trim(),
        role: "agent",
        platform: logistic.platform,
        parentLogistic: logistic.id,
        isEmailConfirmed: true,
        isVerified: false,
        firstName,
        lastName,
        password: hashedPassword,
        phone: phone,
        verificationDocuments: [],
        preferredVendors: [],
        addresses: []
      }
    });

    const token = jwt.sign(
      { id: agent.id, type: "resetPassword" },
      process.env.JWT_SECRET,
      { expiresIn: "1d" }
    );

    const resetLink = `${url}/auth/reset-password?key=${token}`;

    await sendEmail(
      agent.email,
      "Set up your password",
      `Hello ${firstName || "Agent"},\n\nYour account has been created by ${
        logistic.email
      }.\nClick the link below to set your password and activate your account:\n\n${resetLink}\n\nThis link is valid for 24 hours.`
    );

    res
      .status(201)
      .json({ message: "Agent created. Reset password email sent." });
  } catch (err) {
    console.error("[Create Agent Error]", err.message);
    res.status(500).json({ message: "Failed to create agent" });
  }
};

const resetPassword = async (req, res) => {
  try {
    const { token, newPassword } = req.body;

    const decoded = jwt.verify(token, process.env.JWT_SECRET);
    console.log(decoded);
    
    if (decoded.type !== "resetPassword") {
      return res.status(400).json({ message: "Invalid token type" });
    }

    const user = await prisma.user.findUnique({
      where: { id: decoded.id }
    });
    if (!user) return res.status(404).json({ message: "User not found" });

    // Hash new password
    const hashedPassword = await bcrypt.hash(newPassword, 10);
    
    await prisma.user.update({
      where: { id: decoded.id },
      data: { password: hashedPassword }
    });

    res.json({ message: "Password reset successfully. You can now login." });
  } catch (err) {
    console.error("[Reset Password Error]", err.message);
    res.status(400).json({ message: "Invalid or expired token" });
  }
};

const getProfile = async (req, res) => {
  try {
    const user = await prisma.user.findUnique({
      where: { id: req.user.id }
    });
    if (!user) return res.status(404).json({ message: "User not found" });

    // Remove password from response
    const { password, ...userWithoutPassword } = user;
    
    res.json({ user: userWithoutPassword });
  } catch (err) {
    console.error("[User Profile Error]", err);
    res.status(500).json({ message: "Failed to fetch user profile" });
  }
};

const updateProfile = async (req, res) => {
  try {
    console.log(req.user);
    
    const userId = req.user.id;
    const updates = { ...req.body };

    // ✅ Ensure JSON strings are parsed back into objects/arrays
    const jsonFields = ['verificationDocuments', 'addresses', 'preferredVendors'];

    for (const field of jsonFields) {
      if (typeof updates[field] === 'string') {
        try {
          updates[field] = JSON.parse(updates[field]);
        } catch (err) {
          console.warn(`⚠️ Failed to parse ${field}:`, updates[field]);
          updates[field] = [];
        }
      }
    }

    // ⚠️ Convert boolean strings to actual booleans
    const boolFields = [
      'isEmailConfirmed',
      'isVerified',
      'documentsUploaded',
      'isAvailable',
    ];
    for (const field of boolFields) {
      if (typeof updates[field] === 'string') {
        updates[field] = updates[field] === 'true';
      }
    }
updates.addresses = [
  {
    street: updates.address || "",
    city: updates.city,
    state: updates.state,
    zipCode: updates.postalCode,
    country: updates.country,
  },
];
delete updates.address;
delete updates.city;
delete updates.state;
delete updates.postalCode;
delete updates.country;

    // 🔒 Never allow password/role/platform edits via profile update
    delete updates.password;
    delete updates.role;
    delete updates.platform;
    delete updates._id;
console.log(updates);
console.log("User ID:", userId);

    // ✅ Perform update
    const updatedUser = await prisma.user.update({
      where: { id: userId },
      data: updates
    });

    // Remove password from response
    const { password, ...userWithoutPassword } = updatedUser;

    return res.status(200).json(userWithoutPassword);
  } catch (error) {
    console.error('[Update Profile Error]', error);
    return res.status(500).json({ message: error.message });
  }
};

const confirmEmail = async (req, res) => {
  try {
    const { token } = req.body;

    if (!token) {
      return res
        .status(400)
        .json({ message: "Confirmation token is required" });
    }

    // Decode the token
    let payload;
    try {
      payload = jwt.verify(token, process.env.JWT_SECRET);
    } catch (err) {
      return res
        .status(400)
        .json({ message: "Invalid or expired confirmation token" });
    }

    const user = await prisma.user.findUnique({
      where: { id: payload.id }
    });
    if (!user) return res.status(404).json({ message: "User not found" });

    if (user.isEmailConfirmed) {
      return res.status(400).json({ message: "Email is already confirmed" });
    }

    await prisma.user.update({
      where: { id: payload.id },
      data: { isEmailConfirmed: true }
    });

    res.status(200).json({ message: "Email confirmed successfully" });
  } catch (err) {
    console.error("[Confirm Email Error]", err);
    res.status(500).json({ message: "Failed to confirm email" });
  }
};

const resendConfirmationEmail = async (req, res) => {
  try {
    const { email, platform } = req.body;
    if (!email || !platform) {
      return res
        .status(400)
        .json({ message: "Email and platform are required" });
    }

    const user = await prisma.user.findFirst({
      where: { email: email?.toLowerCase().trim(), platform }
    });
    if (!user) {
      return res.status(404).json({ message: "User not found" });
    }

    if (user.isEmailConfirmed) {
      return res.status(400).json({ message: "Email already confirmed" });
    }

    const confirmationToken = jwt.sign(
      { id: user.id, platform },
      process.env.JWT_SECRET,
      { expiresIn: "1h" }
    );

    const platformPath = getPlatformPath(platform);

    const confirmLink = `${url}/auth/verify?key=${confirmationToken}`;

    await sendEmail(
      email,
      "Confirm your account",
      `Click here to confirm your email: ${confirmLink}`
    );

    res.status(200).json({ message: "Confirmation email resent successfully" });
  } catch (err) {
    console.error("[Resend Confirmation Error]", err.message);
    res.status(500).json({ message: "Failed to resend confirmation email" });
  }
};

const addAddress = async (req, res) => {
  try {
    const userId = req.user.id;
    const address = req.body;
    const user = await prisma.user.findUnique({
      where: { id: userId }
    });

    if (!user) return res.status(404).json({ message: "User not found" });
    
    // Parse addresses JSON
    const addresses = typeof user.addresses === 'string' 
      ? JSON.parse(user.addresses) 
      : user.addresses || [];
    
    addresses.push(address);
    
    const updated = await prisma.user.update({
      where: { id: userId },
      data: { addresses }
    });
    
    const updatedAddresses = typeof updated.addresses === 'string' 
      ? JSON.parse(updated.addresses) 
      : updated.addresses;
    
    res.status(200).json({
      message: "Address added successfully",
      address: updatedAddresses[updatedAddresses.length - 1],
    });
  } catch (err) {
    console.error("[Add Address Error]", err.message);
    res.status(500).json({ message: "Failed to add address" });
  }
};

const updateAddress = async (req, res) => {
  try {
    const userId = req.user.id;
    const { addressId } = req.params;
    const address = req.body;
    const user = await prisma.user.findUnique({
      where: { id: userId }
    });
    if (!user) return res.status(404).json({ message: "User not found" });
    
    // Parse addresses JSON
    const addresses = typeof user.addresses === 'string' 
      ? JSON.parse(user.addresses) 
      : user.addresses || [];
    
    // Find address index (using addressId as index or id field)
    const addressIndex = typeof addressId === 'string' && !isNaN(addressId) 
      ? parseInt(addressId) 
      : addresses.findIndex((addr, idx) => idx === parseInt(addressId) || addr.id === addressId);
    
    if (addressIndex === -1) {
      return res.status(404).json({ message: "Address not found" });
    }
    
    // Update address
    addresses[addressIndex] = { ...addresses[addressIndex], ...address };
    
    await prisma.user.update({
      where: { id: userId },
      data: { addresses }
    });
    
    res.status(200).json({ message: "Address updated successfully", address: addresses[addressIndex] });
  } catch (err) {
    console.error("[Update Address Error]", err.message);
    res.status(500).json({ message: "Failed to update address" });
  }
};

const deleteAddress = async (req, res) => {
  try {
    const userId = req.user.id;
    const { addressId } = req.params;
    const user = await prisma.user.findUnique({
      where: { id: userId }
    });
    if (!user) return res.status(404).json({ message: "User not found" });
    
    // Parse addresses JSON
    const addresses = typeof user.addresses === 'string' 
      ? JSON.parse(user.addresses) 
      : user.addresses || [];
    
    // Filter out the address (using index or id)
    const filteredAddresses = addresses.filter((addr, idx) => {
      if (!isNaN(addressId)) {
        return idx !== parseInt(addressId);
      }
      return addr.id !== addressId && idx !== parseInt(addressId);
    });
    
    await prisma.user.update({
      where: { id: userId },
      data: { addresses: filteredAddresses }
    });
    
    res.status(200).json({ message: "Address deleted successfully" });
  } catch (err) {
    console.error("[Delete Address Error]", err.message);
    res.status(500).json({ message: "Failed to delete address" });
  }
};

const addFavoriteProduct = async (req, res) => {
  try {
    const { name, images, productId, retailPrice } = req.body;

    const user = await prisma.user.findUnique({
      where: { id: req.user.id }
    });
    if (!user) {
      return res.status(404).json({ error: "User not found" });
    }

    // Parse preferredVendors JSON (used for favorite products)
    const favoriteProducts = typeof user.preferredVendors === 'string' 
      ? JSON.parse(user.preferredVendors) 
      : user.preferredVendors || [];

    const isFavorite = favoriteProducts.some(
      (product) => product.productId === productId || product === productId
    );

    let updatedFavoriteProducts;
    if (isFavorite) {
      // Remove from favorites
      updatedFavoriteProducts = favoriteProducts.filter(
        (product) => (typeof product === 'object' ? product.productId : product) !== productId
      );
    } else {
      // Add to favorites
      updatedFavoriteProducts = [...favoriteProducts, { name, images, productId, retailPrice }];
    }

    const updatedUser = await prisma.user.update({
      where: { id: req.user.id },
      data: { preferredVendors: updatedFavoriteProducts }
    });

    const updatedFavorites = typeof updatedUser.preferredVendors === 'string' 
      ? JSON.parse(updatedUser.preferredVendors) 
      : updatedUser.preferredVendors;

    res.status(200).json({
      message: isFavorite ? "Product removed from favorites" : "Product added to favorites",
      favoriteProducts: updatedFavorites,
    });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
};

const rateLogistics = async (req, res) => {
  try {
    const { orderId, rating, comment, logisticId } = req.body;
    const user = await prisma.user.findUnique({
      where: { id: req.user.id }
    });
    
    if (!user) {
      return res.status(404).json({ error: "User not found" });
    }
    
    // Note: logisticRatings is stored in client-service, not user-service
    // This method may need to call client-service API instead
    // For now, we'll just return success as this is handled by client-service
    res.status(200).json({
      message: "Logistics rated successfully",
    });
  } catch (err) {
    console.error("[Rate Logistics Error]", err.message);
    res.status(500).json({ error: err.message });
  }
};
const getLogisticRatings = async (req, res) => {
  try {
    const { logisticId } = req.params;
    console.log(logisticId);

    // Note: logisticRatings are stored in client-service
    // This should call client-service API to get ratings
    // For now, return empty results as this is handled by client-service
    res.json({ 
      totalReviews: 0, 
      avgRating: 0, 
      ratingDistribution: [0, 0, 0, 0, 0] 
    });
  } catch (err) {
    console.error("[Ratings Fetch Error]", err.message);
    res.status(500).json({ message: "Failed to fetch ratings" });
  }
};
const uploadVerificationDocument = async (req, res) => {
  try {
    console.log(req.user);
    
    const userId = req.user.id; // From auth middleware
    const { documentType } = req.body;

    if (!req.file) {
      return res.status(400).json({ message: "Document file is required" });
    }

    // Upload to S3
    const documentUrl = await uploadFileToS3(req.file);

    const user = await prisma.user.findUnique({
      where: { id: userId }
    });
    if (!user) return res.status(404).json({ message: "User not found" });

    // Parse verificationDocuments JSON
    const verificationDocuments = typeof user.verificationDocuments === 'string' 
      ? JSON.parse(user.verificationDocuments) 
      : user.verificationDocuments || [];

    // Push new document entry
    verificationDocuments.push({
      documentType,
      documentUrl,
      status: "pending",
      uploadedAt: new Date().toISOString()
    });

    const updated = await prisma.user.update({
      where: { id: userId },
      data: {
        verificationDocuments,
        documentsUploaded: true
      }
    });

    const updatedDocuments = typeof updated.verificationDocuments === 'string' 
      ? JSON.parse(updated.verificationDocuments) 
      : updated.verificationDocuments;

    res.status(201).json({
      message: "Document uploaded successfully. Waiting for admin approval.",
      documents: updatedDocuments,
    });
  } catch (err) {
    console.error("[Upload Document Error]", err);
    res.status(500).json({ message: "Failed to upload document" });
  }
};
const setAvailabilityAndAddress = async (req, res) => {
  try {
    const userId = req.user.id; // From auth middleware
    const { available, address } = req.body; // available: true/false, address: { street, city, state, zipCode, country }

    const user = await prisma.user.findUnique({
      where: { id: userId }
    });

    if (!user) {
      return res
        .status(404)
        .json({ success: false, message: "User not found" });
    }

    // Allow both 'agent' and 'logistic' roles
    if (user.role !== "agent" && user.role !== "logistic") {
      return res.status(403).json({
        success: false,
        message: "Only logistic or agent users can set availability",
      });
    }

    // Prepare update data
    const updateData = {
      isAvailable: available
    };

    // Update address if provided
    if (address) {
      updateData.addresses = [address]; // replace previous addresses
    }

    const updated = await prisma.user.update({
      where: { id: userId },
      data: updateData
    });

    // Remove password from response
    const { password, ...userWithoutPassword } = updated;

    return res.status(200).json({
      success: true,
      message: `Availability updated to ${available}`,
      data: {
        user: userWithoutPassword
      },
    });
  } catch (err) {
    console.error(err);
    return res.status(500).json({ success: false, message: "Server error" });
  }
};
const getAgents = async (req, res) => {
  try {
    const logisticId = req.user.id;
    const logistic = await prisma.user.findUnique({
      where: { id: logisticId }
    });

    if (!logistic || logistic.role !== "logistic") {
      return res.status(403).json({ message: "Only logistic can view agents" });
    }

    const agents = await prisma.user.findMany({
      where: {
        parentLogistic: logisticId,
        role: "agent",
      }
    });

    // Remove passwords from response
    const agentsWithoutPassword = agents.map(({ password, ...agent }) => agent);

    res.json({ data: agentsWithoutPassword });
  } catch (err) {
    console.error("[Get Agents Error]", err.message);
    res.status(500).json({ message: "Failed to fetch agents" });
  }
};

const updateAgent = async (req, res) => {
  try {
    const logisticId = req.user.id;
    const { id } = req.params;

    // First verify the agent belongs to this logistic
    const agent = await prisma.user.findFirst({
      where: {
        id: id,
        parentLogistic: logisticId,
        role: "agent"
      }
    });

    if (!agent) return res.status(404).json({ message: "Agent not found" });

    // Remove password from updates if present
    const updates = { ...req.body };
    delete updates.password;
    delete updates.role;
    delete updates.platform;
    delete updates.parentLogistic;

    const updated = await prisma.user.update({
      where: { id: id },
      data: updates
    });

    // Remove password from response
    const { password, ...agentWithoutPassword } = updated;

    res.json(agentWithoutPassword);
  } catch (err) {
    console.error("[Update Agent Error]", err.message);
    res.status(500).json({ message: "Failed to update agent" });
  }
};

const deleteAgent = async (req, res) => {
  try {
    const logisticId = req.user.id;
    const { id } = req.params;

    // First verify the agent belongs to this logistic
    const agent = await prisma.user.findFirst({
      where: {
        id: id,
        parentLogistic: logisticId,
        role: "agent"
      }
    });

    if (!agent) return res.status(404).json({ message: "Agent not found" });

    await prisma.user.delete({
      where: { id: id }
    });

    res.json({ message: "Agent deleted successfully" });
  } catch (err) {
    console.error("[Delete Agent Error]", err.message);
    res.status(500).json({ message: "Failed to delete agent" });
  }
};

module.exports = {
  registerUser,
  loginUser,
  getProfile,
  updateProfile,
  confirmEmail,
  resendConfirmationEmail,
  addAddress,
  updateAddress,
  deleteAddress,
  addFavoriteProduct,
  rateLogistics,
  getLogisticRatings,
  createAgent,
  resetPassword,
  uploadVerificationDocument,
  setAvailabilityAndAddress,
  getAgents,
  updateAgent,
  deleteAgent,
};
