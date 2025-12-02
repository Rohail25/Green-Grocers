const axios = require('axios');



exports.removeCouponFromProducts = async (couponId) => {
  try {
    await axios.post(`${process.env.PRODUCT_SERVICE_URL}/remove-coupon`, { couponId });
  } catch (err) {
    console.error(`[Vendor ➡ Product] Failed to remove coupon: ${err.message}`);
  }
};

exports.updateCouponInProducts = async (couponId, updates) => {
  try {
    await axios.post(`${process.env.PRODUCT_SERVICE_URL}/update-coupon`, {
      couponId,
      updates,
    });
  } catch (err) {
    console.error(`[Vendor ➡ Product] Failed to update coupon in products: ${err.message}`);
  }
};



exports.getProductsByCategoryService = async (vendorId, category, token) => {
  try {
    const res = await axios.get(
      `${process.env.PRODUCT_SERVICE_URL}/vendor/${vendorId}/category/${category}`,
      {
        headers: {
          Authorization: `Bearer ${token}`
        }
      }
    );
    return res.data;
  } catch (err) {
    console.error('[Communicator Error]', err.response?.data || err.message);
    throw err;
  }
};

exports.updateProductCategoryService = async (vendorId, oldCategory, newCategory) => {
  await axios.put(`${process.env.PRODUCT_SERVICE_URL}/vendor/${vendorId}/update-category`, {
    oldCategory,
    newCategory
  });
};