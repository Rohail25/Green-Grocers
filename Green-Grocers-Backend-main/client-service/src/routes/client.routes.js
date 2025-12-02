const express = require("express");
const router = express.Router();
const auth = require("../middlewares/auth.middleware");

const {
  registerClient,
  addUserFavoriteProduct,

  // Product
  getAllProducts,
  getProductById,
  getAllCategories,
  getAllBrands,

  // Reviews
  createProductReview,
  reviewsByProductId,
  reviewsByUserId,
  updateReview,
  deleteReview,

  // Orders
  placeOrder,
  getUserOrders,
  getOrderById,
  orderFeedback,

  // Cart
  addToCart,
  getCartByUserId,
  updateUserCartItem,
  removeUserCartItem,
  clearUserCart,

  // Address
  addUserAddress,
  updateUserAddress,
  deleteUserAddress,
  getReferralStats,
} = require("../controllers/client.controller");

// ===================
// Auth / Registration
// ===================
router.post("/register", registerClient);
router.get("/referral/stats", auth, getReferralStats);

// ===================
// Favorites
// ===================
router.post("/add-favorite", auth, addUserFavoriteProduct);

// ===================
// Products
// ===================
router.get("/products", getAllProducts);
router.get("/products/:id", getProductById);
router.get("/categories", getAllCategories);
router.get("/brands", getAllBrands);

// ===================
// Reviews
// ===================
router.post("/rate-product", auth, createProductReview);
router.get("/reviews-by-product/:productId", reviewsByProductId);
router.get("/reviews-by-user", auth, reviewsByUserId);
router.patch("/reviews/:reviewId", auth, updateReview);
router.delete("/reviews/:reviewId", auth, deleteReview);

// ===================
// Orders
// ===================
router.post("/place-order", auth, placeOrder);
router.get("/orders", auth, getUserOrders);
router.get("/orders/:id", auth, getOrderById);
router.post("/rate-logistics", auth, orderFeedback);

// ===================
// Cart
// ===================
router.post("/cart", auth, addToCart);
router.get("/cart", auth, getCartByUserId);
router.patch("/cart/:productId", auth, updateUserCartItem);
router.delete("/cart/:itemId", auth, removeUserCartItem);
router.delete("/cart", auth, clearUserCart);

// ===================
// Address
// ===================
router.post("/address", auth, addUserAddress);
router.patch("/address/:addressId", auth, updateUserAddress);
router.delete("/address/:addressId", auth, deleteUserAddress);

module.exports = router;
