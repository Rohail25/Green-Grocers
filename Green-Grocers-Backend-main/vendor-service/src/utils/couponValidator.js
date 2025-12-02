/**
 * Coupon Structure Validator
 * Ensures coupons match the original Mongoose schema structure
 */

/**
 * Validate coupon structure matches the original schema
 * @param {Object} coupon - Coupon object to validate
 * @returns {{valid: boolean, errors: string[]}}
 */
const validateCouponStructure = (coupon) => {
  const errors = [];

  // Required fields
  if (!coupon.couponType) {
    errors.push('couponType is required');
  } else if (!['Discount', 'FreeShipping', 'BuyXGetY'].includes(coupon.couponType)) {
    errors.push('couponType must be one of: Discount, FreeShipping, BuyXGetY');
  }

  if (!coupon.name) {
    errors.push('name is required');
  }

  if (!coupon.code) {
    errors.push('code is required');
  }

  if (!coupon.appliesTo) {
    errors.push('appliesTo is required');
  } else if (!['AllProducts', 'SelectedProducts'].includes(coupon.appliesTo)) {
    errors.push('appliesTo must be one of: AllProducts, SelectedProducts');
  }

  // Optional but should be validated if present
  if (coupon.productIds && !Array.isArray(coupon.productIds)) {
    errors.push('productIds must be an array');
  }

  if (coupon.buyXProductIds && !Array.isArray(coupon.buyXProductIds)) {
    errors.push('buyXProductIds must be an array');
  }

  if (coupon.getYProductIds && !Array.isArray(coupon.getYProductIds)) {
    errors.push('getYProductIds must be an array');
  }

  if (coupon.discountValue !== undefined && typeof coupon.discountValue !== 'number') {
    errors.push('discountValue must be a number');
  }

  if (coupon.maxRedemptions !== undefined && typeof coupon.maxRedemptions !== 'number') {
    errors.push('maxRedemptions must be a number');
  }

  if (coupon.perCustomerLimit !== undefined && typeof coupon.perCustomerLimit !== 'number') {
    errors.push('perCustomerLimit must be a number');
  }

  // Date validation
  if (coupon.startDate) {
    const startDate = new Date(coupon.startDate);
    if (isNaN(startDate.getTime())) {
      errors.push('startDate must be a valid date');
    }
  }

  if (coupon.endDate) {
    const endDate = new Date(coupon.endDate);
    if (isNaN(endDate.getTime())) {
      errors.push('endDate must be a valid date');
    }
  }

  // Date range validation
  if (coupon.startDate && coupon.endDate) {
    const startDate = new Date(coupon.startDate);
    const endDate = new Date(coupon.endDate);
    if (endDate < startDate) {
      errors.push('endDate must be after startDate');
    }
  }

  return {
    valid: errors.length === 0,
    errors
  };
};

/**
 * Create a properly structured coupon object from input
 * Matches the original Mongoose couponSchema structure
 * @param {Object} couponData - Raw coupon data
 * @param {string} id - Optional coupon ID (will generate if not provided)
 * @returns {Object} - Structured coupon object
 */
const createCouponObject = (couponData, id = null) => {
  const { v4: uuidv4 } = require('uuid');
  
  const coupon = {
    // ID fields
    id: id || couponData.id || couponData._id || uuidv4(),
    _id: id || couponData._id || couponData.id || uuidv4(),
    
    // Required fields (from couponSchema)
    couponType: couponData.couponType, // enum: 'Discount', 'FreeShipping', 'BuyXGetY'
    name: couponData.name,
    code: couponData.code,
    appliesTo: couponData.appliesTo, // enum: 'AllProducts', 'SelectedProducts'
    
    // Optional array fields
    productIds: Array.isArray(couponData.productIds) ? couponData.productIds : [],
    buyXProductIds: Array.isArray(couponData.buyXProductIds) ? couponData.buyXProductIds : [],
    getYProductIds: Array.isArray(couponData.getYProductIds) ? couponData.getYProductIds : [],
    
    // Optional number fields
    discountValue: couponData.discountValue !== undefined ? couponData.discountValue : null,
    maxRedemptions: couponData.maxRedemptions !== undefined ? couponData.maxRedemptions : null,
    perCustomerLimit: couponData.perCustomerLimit !== undefined ? couponData.perCustomerLimit : null,
    
    // Date fields (convert to ISO strings for JSON storage)
    startDate: couponData.startDate ? new Date(couponData.startDate).toISOString() : null,
    endDate: couponData.endDate ? new Date(couponData.endDate).toISOString() : null,
    
    // Timestamps (like Mongoose timestamps: true)
    createdAt: couponData.createdAt || new Date().toISOString(),
    updatedAt: couponData.updatedAt || new Date().toISOString()
  };

  // Validate the structure
  const validation = validateCouponStructure(coupon);
  if (!validation.valid) {
    throw new Error(`Invalid coupon structure: ${validation.errors.join(', ')}`);
  }

  return coupon;
};

/**
 * Get the expected coupon structure (for documentation/reference)
 * @returns {Object} - Example coupon structure
 */
const getCouponSchemaStructure = () => {
  return {
    id: "uuid-string",
    _id: "uuid-string",
    couponType: "Discount | FreeShipping | BuyXGetY",
    name: "string",
    code: "string (unique)",
    appliesTo: "AllProducts | SelectedProducts",
    productIds: ["product-id-1", "product-id-2"], // Array of strings
    buyXProductIds: ["product-id-1"], // For BuyXGetY type
    getYProductIds: ["product-id-2"], // For BuyXGetY type
    discountValue: 10, // Number
    startDate: "2024-01-01T00:00:00.000Z", // ISO date string
    endDate: "2024-12-31T23:59:59.999Z", // ISO date string
    maxRedemptions: 100, // Number
    perCustomerLimit: 1, // Number
    createdAt: "2024-01-01T00:00:00.000Z", // ISO date string
    updatedAt: "2024-01-01T00:00:00.000Z" // ISO date string
  };
};

module.exports = {
  validateCouponStructure,
  createCouponObject,
  getCouponSchemaStructure
};

