const { uploadFileToS3 } = require("../middlewares/s3Uploader.middleware");
const prisma = require("../utils/prisma");
const { getUserFromToken } = require("../utils/communicateUserService");
const { updateOrderStatus } = require("../utils/communicateOrderService");

const registerVendor = async (req, res) => {
  const {
    vendorId,
    storeName,
    phone,
    email,
    address,
    categories,
    description,
  } = req.body;

  try {
    const authHeader = req.headers.authorization;
    
    if (!authHeader) {
      return res.status(401).json({ message: 'Authorization header missing' });
    }
    
    const user = await getUserFromToken(authHeader);
    console.log('[registerVendor] User validated:', user.email || user.id);

    // Check platform - must be trivestore to register as vendor
    if (!user || user.platform !== "trivestore") {
      return res
        .status(403)
        .json({ 
          message: "Only trivestore platform users can register as vendors",
          userPlatform: user?.platform,
          requiredPlatform: "trivestore"
        });
    }

    // Check if user has vendorId
    if (!user.vendorId) {
      return res.status(400).json({ 
        message: "User does not have a vendorId. Please register with trivestore platform first.",
        hint: "When you register with platform 'trivestore', a vendorId is automatically generated and stored in your user profile."
      });
    }

    // Check if vendorId matches
    if (user.vendorId !== vendorId) {
      return res
        .status(400)
        .json({ 
          message: "Vendor ID mismatch with user profile",
          userVendorId: user.vendorId,
          providedVendorId: vendorId,
          hint: "Use the vendorId from your user profile. You can get it by logging in - it's returned in the login response."
        });
    }

    // Parse categories if it's a string
    const categoriesData = typeof categories === 'string' 
      ? JSON.parse(categories) 
      : (categories || []);

    const vendor = await prisma.vendor.create({
      data: {
        vendorId,
        userId: user.id || user._id, // Support both formats
        storeName,
        phone: phone || null,
        email: email || null,
        address: address || null,
        categories: categoriesData,
        description: description || null,
        coupons: [],
      }
    });

    res.status(201).json(vendor);
  } catch (err) {
    console.error("Error registering vendor:", err.message);
    if (err.code === 'P2002') { // Prisma unique constraint violation
      return res.status(409).json({ message: "Vendor already exists" });
    }
    res.status(500).json({ message: "Vendor registration failed", error: err.message });
  }
};

const getVendor = async (req, res) => {
  try {
    const user = await getUserFromToken(req.headers.authorization);
    const vendor = await prisma.vendor.findFirst({
      where: { userId: user.id || user._id }
    });
    if (!vendor) return res.status(404).json({ message: "Vendor not found" });
    res.json(vendor);
  } catch (err) {
    console.error("[Get Vendor Error]", err.message);
    res.status(500).json({ message: "Failed to fetch vendor" });
  }
};

const updateVendor = async (req, res) => {
  try {
    const user = await getUserFromToken(req.headers.authorization);

    // Upload images to S3 if present
    const profileImageFile = req.files?.vendorProfile?.[0];
    const bannerImageFile = req.files?.vendorBanner?.[0];

    const updates = {};
    
    // Handle regular fields
    if (req.body.storeName !== undefined) updates.storeName = req.body.storeName;
    if (req.body.phone !== undefined) updates.phone = req.body.phone || null;
    if (req.body.email !== undefined) updates.email = req.body.email || null;
    if (req.body.address !== undefined) updates.address = req.body.address || null;
    if (req.body.description !== undefined) updates.description = req.body.description || null;
    if (req.body.status !== undefined) updates.status = req.body.status;
    if (req.body.storeEnabled !== undefined) updates.storeEnabled = req.body.storeEnabled === 'true' || req.body.storeEnabled === true;
    if (req.body.storeCurrency !== undefined) updates.storeCurrency = req.body.storeCurrency || null;
    if (req.body.timezone !== undefined) updates.timezone = req.body.timezone || null;
    if (req.body.workHours !== undefined) updates.workHours = req.body.workHours || null;
    if (req.body.state !== undefined) updates.state = req.body.state || null;
    if (req.body.city !== undefined) updates.city = req.body.city || null;
    if (req.body.localGovernment !== undefined) updates.localGovernment = req.body.localGovernment || null;
    if (req.body.country !== undefined) updates.country = req.body.country || null;
    if (req.body.storeAddress !== undefined) updates.storeAddress = req.body.storeAddress || null;

    // Handle JSON fields
    if (req.body.categories !== undefined) {
      updates.categories = typeof req.body.categories === 'string' 
        ? JSON.parse(req.body.categories) 
        : req.body.categories;
    }
    if (req.body.storeIndustries !== undefined) {
      updates.storeIndustries = typeof req.body.storeIndustries === 'string' 
        ? JSON.parse(req.body.storeIndustries) 
        : req.body.storeIndustries;
    }

    if (profileImageFile) {
      const profileUrl = await uploadFileToS3(profileImageFile);
      updates.vendorProfileImage = profileUrl;
    }

    if (bannerImageFile) {
      const bannerUrl = await uploadFileToS3(bannerImageFile);
      updates.vendorBannerImage = bannerUrl;
    }

    // Check if vendor exists first
    const existing = await prisma.vendor.findFirst({
      where: { userId: user.id || user._id }
    });
    if (!existing) {
      return res.status(404).json({ message: "Vendor not found" });
    }

    const updatedVendor = await prisma.vendor.update({
      where: { id: existing.id },
      data: updates
    });

    res.json(updatedVendor);
  } catch (err) {
    console.error("Vendor update failed:", err);
    if (err.code === 'P2025') { // Prisma record not found
      return res.status(404).json({ message: "Vendor not found" });
    }
    res.status(500).json({ message: "Update failed", error: err.message });
  }
};

const deleteVendor = async (req, res) => {
  try {
    const user = await getUserFromToken(req.headers.authorization);
    
    // Check if vendor exists first
    const existing = await prisma.vendor.findFirst({
      where: { userId: user.id || user._id }
    });
    if (!existing) {
      return res.status(404).json({ message: "Vendor not found" });
    }

    await prisma.vendor.delete({
      where: { id: existing.id }
    });
    
    res.json({ message: "Vendor deleted successfully" });
  } catch (err) {
    console.error("[Delete Vendor Error]", err.message);
    if (err.code === 'P2025') {
      return res.status(404).json({ message: "Vendor not found" });
    }
    res.status(500).json({ message: "Deletion failed" });
  }
};

const updateVendorInventory = async (req, res) => {
  const { vendorId } = req.params;
  const { inventoryCount } = req.body;

  try {
    // Check if vendor exists first
    const existing = await prisma.vendor.findUnique({
      where: { vendorId }
    });
    if (!existing) {
      return res.status(404).json({ message: "Vendor not found" });
    }

    const vendor = await prisma.vendor.update({
      where: { vendorId },
      data: {
        inventoryCount: parseInt(inventoryCount) || 0,
      }
    });

    res.status(200).json({
      message: "Vendor inventory updated",
      vendor,
    });
  } catch (err) {
    console.error("[Vendor Inventory Update Error]", err);
    if (err.code === 'P2025') {
      return res.status(404).json({ message: "Vendor not found" });
    }
    res.status(500).json({ message: "Failed to update vendor inventory" });
  }
};

const confirmOrder = async (req, res) => {
  const orderId = req.params.orderId;
  const { status, orderProgress } = req.body;

  try {
    const order = await updateOrderStatus(
      orderId,
      {
        status: status,
        orderProgress,
      },
      req.headers.authorization
    );

    res.status(200).json({ message: "Order confirmed", order });
  } catch (err) {
    console.log(err);

    res
      .status(500)
      .json({ message: "Failed to confirm order", error: err.message });
  }
};

const getVendorById = async (req, res) => {
  const { vendorId } = req.params;
  try {
    const vendor = await prisma.vendor.findUnique({
      where: { vendorId }
    });
    if (!vendor) return res.status(404).json({ message: "Vendor not found" });
    res.json(vendor);
  } catch (error) {
    console.error("[Get Vendor By ID Error]", error.message);
    res.status(500).json({ message: "Failed to fetch vendor" });
  }
};

module.exports = {
  registerVendor,
  getVendor,
  getVendorById,
  updateVendor,
  deleteVendor,
  updateVendorInventory,
  confirmOrder,
};

