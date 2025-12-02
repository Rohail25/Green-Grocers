const express = require("express");
const router = express.Router();
const auth = require("../middlewares/auth.middleware");
const upload = require("../middlewares/upload.middleware");

const {
  createProduct,
  getAllProductsByVendor,
  getSingleProduct,
  updateProduct,
  deleteProduct,
  applyCouponToProducts,
  removeCouponFromProducts,
  getProductsByCategory,
  disableProductsByCategory,
  updateProductsCategory,
  getAllProducts,
  updateProductQuantity,
  getProductsByCategoryName,
  getFeaturedProducts,
  getAllProductsForFrontend,
  toggleFeaturedStatus
} = require("../controllers/product.controller");

// --- Create Product ---
router.post(
  "/",
  auth,
  upload.fields([
    { name: "images", maxCount: 5 },
    { name: "variantImages[0]", maxCount: 1 },
    { name: "variantImages[1]", maxCount: 1 },
    { name: "variantImages[2]", maxCount: 1 },
    { name: "variantImages[3]", maxCount: 1 },
    { name: "variantImages[4]", maxCount: 1 },
    { name: "variantImages[5]", maxCount: 1 },
  ]),
  createProduct
);

// --- Read Products ---
router.get("/", getAllProducts); // All products (no auth)
router.get("/one/:productId", getSingleProduct); // Single product by ID
router.get("/:vendorId", auth, getAllProductsByVendor); // Vendor's products

// --- Frontend APIs (No Auth Required) ---
router.get("/frontend/all", getAllProductsForFrontend); // All products for frontend
router.get("/frontend/featured", getFeaturedProducts); // Featured products
router.get("/frontend/category/:categoryName", getProductsByCategoryName); // Products by category name

// --- Admin Routes ---
router.patch("/:productId/toggle-featured", auth, toggleFeaturedStatus); // Toggle featured status

// --- Update Product ---
router.put(
  "/:vendorId/:productId",
  auth,
  upload.fields([
    { name: "images", maxCount: 5 },
    { name: "variantImages[0]", maxCount: 1 },
    { name: "variantImages[1]", maxCount: 1 },
    { name: "variantImages[2]", maxCount: 1 },
    { name: "variantImages[3]", maxCount: 1 },
    { name: "variantImages[4]", maxCount: 1 },
    { name: "variantImages[5]", maxCount: 1 },
  ]),
  updateProduct
);
router.put("/vendor/:vendorId/update-category", updateProductsCategory); // Change product category
router.patch("/update-quantity/:productId", updateProductQuantity); // Change product quantity

// --- Delete Product ---
router.delete("/:vendorId/:productId", auth, deleteProduct);

// --- Coupon Operations ---
router.post("/update-coupon", auth, applyCouponToProducts);
router.post("/remove-coupon", auth, removeCouponFromProducts);

// --- Category-based Operations ---
router.get("/vendor/:vendorId/category/:categoryId", auth, getProductsByCategory);
router.put(
  "/vendor/:vendorId/category/:category/disable",
  auth,
  disableProductsByCategory
);

module.exports = router;
