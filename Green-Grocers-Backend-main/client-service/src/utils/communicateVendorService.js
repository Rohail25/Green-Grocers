const axios = require('axios');

exports.validateCoupon = async (vendorId, couponCode, items) => {
  try {
    const response = await axios.post(`${process.env.VENDOR_SERVICE_URL}/validate-coupon`, {
      vendorId,
      couponCode,
      items
    });
    return response.data;
  } catch (err) {
    console.error('[Coupon Validation Error]', err.message);
    return { valid: false };
  }
};

exports.fetchStoreDetail = async (vendorId, authHeader) => {

  const response = await axios.get(`${process.env.VENDOR_SERVICE_URL}/vendor-by-id/${vendorId}`, {
    headers: {
      Authorization: authHeader
    }
  });
  return response.data;
}