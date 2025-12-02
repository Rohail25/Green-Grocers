const prisma = require('../utils/prisma');

// Add item to cart
const addToCart = async (req, res) => {
  try {
    const userId = req.user.id;
    const { productId, productName, productImage, price, quantity, vendorId, variantIndex } = req.body;
    
    let cart = await prisma.cart.findUnique({
      where: { userId }
    });
    
    let items = [];
    if (cart) {
      items = typeof cart.items === 'string' ? JSON.parse(cart.items) : (cart.items || []);
    }

    const existingItemIndex = items.findIndex(item => item.productId === productId);
    if (existingItemIndex !== -1) {
      items[existingItemIndex].quantity += quantity;
    } else {
      items.push({ productId, productName, productImage, price: parseFloat(price), quantity, vendorId, variantIndex: variantIndex || 0 });
    }

    const totalPrice = items.reduce((sum, item) => sum + (parseFloat(item.price) * item.quantity), 0);
    
    if (cart) {
      cart = await prisma.cart.update({
        where: { userId },
        data: {
          items: items,
          totalPrice: totalPrice
        }
      });
    } else {
      cart = await prisma.cart.create({
        data: {
          userId,
          items: items,
          totalPrice: totalPrice
        }
      });
    }

    res.status(200).json({ message: 'Item added to cart', cart });
  } catch (err) {
    res.status(500).json({ message: 'Failed to add to cart', error: err.message });
  }
};

// Get cart by user
const getCartByUser = async (req, res) => {
  try {
    const userId = req.user.id;
    const cart = await prisma.cart.findUnique({
      where: { userId }
    });
    if (!cart) {
      return res.status(204).json({ message: 'Cart is empty' });
    }
    res.status(200).json(cart);
  } catch (err) {
    res.status(500).json({ message: 'Failed to fetch cart', error: err.message });
  }
};

// Update cart item quantity
const updateCartItem = async (req, res) => {
  try {
    const userId = req.user.id;
    const { productId } = req.params;
    const { quantity } = req.body;

    const cart = await prisma.cart.findUnique({
      where: { userId }
    });
        
    if (!cart) {
      return res.status(404).json({ message: 'Cart not found' });
    }

    let items = typeof cart.items === 'string' ? JSON.parse(cart.items) : (cart.items || []);
    const itemIndex = items.findIndex(i => i.productId === productId);
        
    if (itemIndex === -1) {
      return res.status(404).json({ message: 'Item not found in cart' });
    }

    items[itemIndex].quantity = quantity;
    const totalPrice = items.reduce((sum, item) => sum + (parseFloat(item.price) * item.quantity), 0);
    
    const updatedCart = await prisma.cart.update({
      where: { userId },
      data: {
        items: items,
        totalPrice: totalPrice
      }
    });

    res.status(200).json({ message: 'Cart item updated', cart: updatedCart });
  } catch (err) {
    res.status(500).json({ message: 'Failed to update cart item', error: err.message });
  }
};

// Remove item from cart
const removeCartItem = async (req, res) => {
  try {
    const userId = req.user.id;
    const { productId } = req.params; // Changed from itemId to productId to match route
    const cart = await prisma.cart.findUnique({
      where: { userId }
    });
    if (!cart) {
      return res.status(404).json({ message: 'Cart not found' });
    }

    let items = typeof cart.items === 'string' ? JSON.parse(cart.items) : (cart.items || []);
    items = items.filter(i => i.productId !== productId);
    const totalPrice = items.reduce((sum, item) => sum + (parseFloat(item.price) * item.quantity), 0);
    
    const updatedCart = await prisma.cart.update({
      where: { userId },
      data: {
        items: items,
        totalPrice: totalPrice
      }
    });

    res.status(200).json({ message: 'Item removed from cart', cart: updatedCart });
  } catch (err) {
    res.status(500).json({ message: 'Failed to remove cart item', error: err.message });
  }
};

// Clear cart
const clearCart = async (req, res) => {
  try {
    const userId = req.user.id;
    const cart = await prisma.cart.findUnique({
      where: { userId }
    });
    if (!cart) {
      return res.status(404).json({ message: 'Cart not found' });
    }

    const updatedCart = await prisma.cart.update({
      where: { userId },
      data: {
        items: [],
        totalPrice: 0
      }
    });

    res.status(200).json({ message: 'Cart cleared', cart: updatedCart });
  } catch (err) {
    res.status(500).json({ message: 'Failed to clear cart', error: err.message });
  }
};

module.exports = {
  addToCart,
  getCartByUser,
  updateCartItem,
  removeCartItem,
  clearCart
}; 