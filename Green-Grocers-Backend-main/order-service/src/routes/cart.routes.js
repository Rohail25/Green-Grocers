const express = require('express');
const router = express.Router();
const {
  addToCart,
  getCartByUser,
  updateCartItem,
  removeCartItem,
  clearCart
} = require('../controllers/cart.controller');
const auth = require('../middlewares/auth.middleware');

// Add item to cart
router.post('/', auth, addToCart);

// Get user's cart
router.get('/cart-by-user', auth, getCartByUser);

// Update quantity of a cart item
router.patch('/update-item/:productId', auth, updateCartItem);

// Remove an item from cart
router.delete('/remove-item/:itemId', auth, removeCartItem);

// Clear entire cart
router.delete('/clear-cart', auth, clearCart);

module.exports = router; 