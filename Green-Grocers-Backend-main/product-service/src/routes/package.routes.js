const express = require('express');
const router = express.Router();
const auth = require('../middlewares/auth.middleware');
const upload = require('../middlewares/upload.middleware');

const {
  createPackage,
  getAllPackages,
  getFeaturedPackages,
  getPackagesByDay,
  getSinglePackage,
  updatePackage,
  deletePackage,
  updatePackageRating
} = require('../controllers/package.controller');

// --- Create Package ---
router.post(
  '/',
  auth,
  upload.single('image'),
  createPackage
);

// --- Read Packages ---
router.get('/', getAllPackages); // All packages (no auth)
router.get('/featured', getFeaturedPackages); // Featured packages (no auth)
router.get('/day/:day', getPackagesByDay); // Packages by day (no auth)
router.get('/:packageId', getSinglePackage); // Single package (no auth)

// --- Update Package ---
router.put(
  '/:packageId',
  auth,
  upload.single('image'),
  updatePackage
);

// --- Package Rating (No Auth) ---
router.patch('/:packageId/rating', updatePackageRating);

// --- Delete Package ---
router.delete('/:packageId', auth, deletePackage);

module.exports = router;
