const express = require('express');
const router = express.Router();
const auth = require('../middlewares/auth.middleware');
const {
  createCoupon,
  getCoupons,
  getCoupon,
  updateCoupon,
  deleteCoupon,
  validateCoupon
} = require('../controllers/coupons.controller');

// CRUD for vendor coupons
router.post('/', auth, createCoupon);
router.get('/:vendorId', auth, getCoupons);
router.get('/:vendorId/:couponId', auth, getCoupon);
router.put('/:couponId', auth, updateCoupon);
router.delete('/:couponId', auth, deleteCoupon);
router.post('/validate-coupon', validateCoupon);

module.exports = router;
