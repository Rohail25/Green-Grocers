const express = require('express');
const router = express.Router();
const auth = require('../middlewares/auth.middleware');
const upload = require("../middlewares/upload.middleware");

const {
  createBrand,
  getBrands,
  getBrand,
  updateBrand,
  deleteBrand
} = require('../controllers/brand.controller');
 
// Routes for vendor brands
router.post('/',  upload.single('image'), createBrand);
router.get('/',  getBrands);
router.get('/:brandId',  getBrand);
router.patch('/:brandId',  upload.single('image'), updateBrand);
router.delete('/:brandId',  deleteBrand);

module.exports = router; 