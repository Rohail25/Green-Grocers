const express = require('express');
const router = express.Router();
const auth = require('../middlewares/auth.middleware');
const upload = require('../middlewares/upload.middleware');

const {
  createCategory,
  getCategories,
  getCategory,
  updateCategory,
  deleteCategory
} = require('../controllers/category.controller');

// Routes for product categories
router.post('/',  upload.single('image'), createCategory);
router.get('/',  getCategories);
router.get('/:categoryId', getCategory);
router.patch('/:categoryId', upload.single('image'), updateCategory);
router.delete('/:categoryId', deleteCategory);

module.exports = router; 