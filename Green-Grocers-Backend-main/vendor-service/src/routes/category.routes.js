const express = require('express');
const router = express.Router();
const auth = require('../middlewares/auth.middleware');
const {
  getCategories,
  addCategory,
  deleteCategory,
  getProductsByCategory,
  getCategoryOverview,
  updateCategory,
} = require('../controllers/category.controller');

router.get('/:vendorId', auth, getCategories);
router.post('/:vendorId', auth, addCategory);
router.delete('/:category/:vendorId', auth, deleteCategory);
router.get('/:category/:vendorId/products', auth, getProductsByCategory);
router.get('/:vendorId/categories/overview', auth, getCategoryOverview);
router.put('/:vendorId/:categoryName', auth, updateCategory);

module.exports = router;
