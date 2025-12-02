const prisma = require('../utils/prisma');
const { updateCouponInProducts, removeCouponFromProducts } = require('../utils/communicateProductService');
const { createCouponObject, validateCouponStructure } = require('../utils/couponValidator');

// CREATE
const createCoupon = async (req, res) => {
  const { vendorId, coupon } = req.body;

  try {
    const vendor = await prisma.vendor.findUnique({
      where: { vendorId }
    });
    if (!vendor) return res.status(404).json({ message: 'Vendor not found' });

    // Parse coupons JSON
    const coupons = typeof vendor.coupons === 'string' 
      ? JSON.parse(vendor.coupons) 
      : (vendor.coupons || []);

    // Validate coupon structure
    const validation = validateCouponStructure(coupon);
    if (!validation.valid) {
      return res.status(400).json({ 
        message: 'Invalid coupon structure', 
        errors: validation.errors 
      });
    }

    // Create properly structured coupon object (matches Mongoose schema)
    const couponWithId = createCouponObject(coupon);

    // Add new coupon
    coupons.push(couponWithId);

    // Update vendor
    await prisma.vendor.update({
      where: { vendorId },
      data: { coupons }
    });

    res.status(201).json({ message: 'Coupon created', coupon: couponWithId });
  } catch (err) {
    console.error("[Create Coupon Error]", err.message);
    res.status(500).json({ message: 'Failed to create coupon', error: err.message });
  }
};

// GET ALL
const getCoupons = async (req, res) => {
  const { vendorId } = req.params;
  try {
    const vendor = await prisma.vendor.findUnique({
      where: { vendorId }
    });
    if (!vendor) return res.status(404).json({ message: 'Vendor not found' });

    // Parse coupons JSON
    const coupons = typeof vendor.coupons === 'string' 
      ? JSON.parse(vendor.coupons) 
      : (vendor.coupons || []);

    res.json(coupons);
  } catch (err) {
    console.error("[Get Coupons Error]", err.message);
    res.status(500).json({ message: 'Failed to fetch coupons' });
  }
};

// GET ONE
const getCoupon = async (req, res) => {
  const { vendorId, couponId } = req.params;
  try {
    const vendor = await prisma.vendor.findUnique({
      where: { vendorId }
    });
    if (!vendor) return res.status(404).json({ message: 'Vendor not found' });

    // Parse coupons JSON
    const coupons = typeof vendor.coupons === 'string' 
      ? JSON.parse(vendor.coupons) 
      : (vendor.coupons || []);

    // Find coupon by ID (in JSON, we use index or find by a property)
    const coupon = coupons.find(c => c.id === couponId || c._id === couponId);
    if (!coupon) return res.status(404).json({ message: 'Coupon not found' });

    res.json(coupon);
  } catch (err) {
    console.error("[Get Coupon Error]", err.message);
    res.status(500).json({ message: 'Failed to fetch coupon' });
  }
};

// UPDATE
const updateCoupon = async (req, res) => {
  const { vendorId } = req.user;
  const { couponId } = req.params;

  try {
    const vendor = await prisma.vendor.findUnique({
      where: { vendorId }
    });
    if (!vendor) return res.status(404).json({ message: 'Vendor not found' });

    // Parse coupons JSON
    const coupons = typeof vendor.coupons === 'string' 
      ? JSON.parse(vendor.coupons) 
      : (vendor.coupons || []);

    // Find coupon index
    const couponIndex = coupons.findIndex(c => c.id === couponId || c._id === couponId);
    if (couponIndex === -1) return res.status(404).json({ message: 'Coupon not found' });

    // Merge updates with existing coupon
    const updatedCouponData = { ...coupons[couponIndex], ...req.body };
    
    // Validate updated structure
    const validation = validateCouponStructure(updatedCouponData);
    if (!validation.valid) {
      return res.status(400).json({ 
        message: 'Invalid coupon structure', 
        errors: validation.errors 
      });
    }

    // Update coupon with proper structure
    coupons[couponIndex] = createCouponObject(updatedCouponData, couponId);
    coupons[couponIndex].updatedAt = new Date().toISOString();

    // Save updated coupons
    await prisma.vendor.update({
      where: { vendorId },
      data: { coupons }
    });

    await updateCouponInProducts({
      couponId: couponId,
      updates: req.body
    });

    res.json({ message: 'Coupon updated', coupon: coupons[couponIndex] });
  } catch (err) {
    console.error("[Update Coupon Error]", err.message);
    res.status(500).json({ message: 'Failed to update coupon', error: err.message });
  }
};

// DELETE
const deleteCoupon = async (req, res) => {
  const { couponId } = req.params;
  const { vendorId } = req.user;
  try {
    const vendor = await prisma.vendor.findUnique({
      where: { vendorId }
    });
    if (!vendor) return res.status(404).json({ message: 'Vendor not found' });

    // Parse coupons JSON
    const coupons = typeof vendor.coupons === 'string' 
      ? JSON.parse(vendor.coupons) 
      : (vendor.coupons || []);

    // Filter out the coupon to delete
    const updatedCoupons = coupons.filter(c => c.id !== couponId && c._id !== couponId);

    // Check if coupon was found
    if (coupons.length === updatedCoupons.length) {
      return res.status(404).json({ message: 'Coupon not found' });
    }

    // Save updated coupons
    await prisma.vendor.update({
      where: { vendorId },
      data: { coupons: updatedCoupons }
    });

    await removeCouponFromProducts(couponId);
    res.json({ message: 'Coupon deleted' });
  } catch (err) {
    console.error('[Delete Coupon Error]', err);
    res.status(500).json({ message: 'Failed to delete coupon', error: err.message });
  }
};

const validateCoupon = async (req, res) => {
  const { vendorId, couponCode } = req.body;

  try {
    const vendor = await prisma.vendor.findUnique({
      where: { vendorId }
    });
    if (!vendor) return res.status(404).json({ valid: false });

    // Parse coupons JSON
    const coupons = typeof vendor.coupons === 'string' 
      ? JSON.parse(vendor.coupons) 
      : (vendor.coupons || []);

    const now = new Date();
    const coupon = coupons.find(c =>
      c.code === couponCode &&
      new Date(c.startDate) <= now &&
      new Date(c.endDate) >= now
    );

    if (!coupon) {
      return res.status(404).json({ valid: false });
    }

    return res.json({
      valid: true,
      discountValue: coupon.discountValue
    });
  } catch (err) {
    console.error('[Coupon Validation Error]', err.message);
    res.status(500).json({ valid: false, error: err.message });
  }
};

module.exports = {
  createCoupon,
  getCoupons,
  getCoupon,
  updateCoupon,
  deleteCoupon,
  validateCoupon
};
