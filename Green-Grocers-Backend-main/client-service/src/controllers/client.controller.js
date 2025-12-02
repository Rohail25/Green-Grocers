const prisma = require("../utils/prisma");
const crypto = require("crypto");
const {
  createOrder,
  fetchSingleOrder,
  fetchUserOrders,
  createCart,
  fetchCartByUserId,
  updateCartItem,
  removeCartItem,
  clearCart,
} = require("../utils/communicateOrderService");
const {
  validateCoupon,
  fetchStoreDetail,
} = require("../utils/communicateVendorService");
const {
  fetchProducts,
  fetchProductById,
  fetchCategories,
  fetchBrands,
  postReviewOnProduct,
  getAllReviews,
  getReviewById,
  getReviewsByProductId,
  getReviewsByUserId,
  updateReviewById,
  deleteReviewById,
} = require("../utils/communicateProductService");
const {
  addAddress,
  updateAddress,
  deleteAddress,
  addFavoriteProduct,
  rateLogistics,
} = require("../utils/communicateUserService");

const addUserFavoriteProduct = async (req, res) => {
  try {
    const response = await addFavoriteProduct(
      req.body,
      req.headers.authorization
    );
    res.status(200).json(response);
  } catch (err) {
    console.log(err);
    res.status(500).json({ error: err.message });
  }
};

const orderFeedback = async (req, res) => {
  try {
    const response = await rateLogistics(req.body, req.headers.authorization);
    res.status(200).json(response);
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
};

const placeOrder = async (req, res) => {
  try {
    const {
      userId,
      vendorId,
      items,
      totalAmount,
      shippingAddress,
      customerName,
      couponCode,
    } = req.body;

    let discount = 0;

    if (couponCode) {
      const coupon = await validateCoupon(vendorId, couponCode, items);
      if (!coupon.valid) {
        return res.status(400).json({ message: "Invalid or expired coupon" });
      }
      discount = coupon.discountValue || 0;
    }

    const finalAmount = totalAmount - discount;

    const order = await createOrder(
      {
        userId: userId || req.user.id,
        vendorId,
        items,
        totalAmount: finalAmount,
        discount,
        couponCode,
        shippingAddress,
        customerName,
        paymentStatus: "UNPAID",
      },
      req.headers.authorization
    );

    res.status(201).json({ message: "Order placed successfully", order });
  } catch (err) {
    console.error("[Place Order Error]", err.message);
    res
      .status(500)
      .json({ message: "Failed to place order", error: err.message });
  }
};

const registerClient = async (req, res) => {
  try {
    const { userId, clientId, fullName, email, phone, referralCode } = req.body;

    // Check if already registered
    const existing = await prisma.client.findFirst({
      where: { userId: String(userId) }
    });
    
    if (existing) {
      return res.status(409).json({ message: "Client already exists" });
    }

    let referredBy = null;
    let referralCodeToUse = crypto.randomBytes(4).toString("hex").toUpperCase();

    if (referralCode) {
      // Find referrer by referral code (search in JSON field)
      const allClients = await prisma.client.findMany();
      const referrerClient = allClients.find(c => {
        const referral = typeof c.referral === 'string' ? JSON.parse(c.referral) : c.referral;
        return referral && referral.code === referralCode;
      });
      
      if (!referrerClient) {
        return res.status(400).json({ message: "Invalid referral code" });
      }

      referredBy = referralCode;
      
      // Update referrer's referral stats in JSON
      const referrerReferral = typeof referrerClient.referral === 'string' 
        ? JSON.parse(referrerClient.referral) 
        : referrerClient.referral || { code: '', referredBy: null, totalReferrals: 0, totalPoints: 0, history: [] };
      
      referrerReferral.totalReferrals = (referrerReferral.totalReferrals || 0) + 1;
      referrerReferral.history = referrerReferral.history || [];
      referrerReferral.history.push({
        referredUser: String(userId),
        hasOrdered: false,
        pointsEarned: 0
      });

      await prisma.client.update({
        where: { id: referrerClient.id },
        data: {
          referral: referrerReferral
        }
      });
    }

    // Create new client with referral JSON
    const newClient = await prisma.client.create({
      data: {
        userId: String(userId),
        clientId,
        fullName,
        email,
        phone,
        favoriteProducts: [],
        ratingHistory: [],
        logisticRatings: [],
        referral: {
          code: referralCodeToUse,
          referredBy: referredBy || null,
          totalReferrals: 0,
          totalPoints: 0,
          history: []
        }
      }
    });

    res.status(201).json({
      message: "Client registered successfully",
      clientId: newClient.clientId,
      referredBy,
    });
  } catch (err) {
    console.error("[Register With Referral Error]", err.message);
    console.error("[Register Error Stack]", err.stack);
    res.status(500).json({ message: "Failed to register client", error: err.message });
  }
};

const getUserOrders = async (req, res) => {
  try {
    const orders = await fetchUserOrders(
      req.user.id,
      req.headers.authorization
    );
    res.json(orders || []);
  } catch (err) {
    console.error("[Fetch Orders Error]", err.message);
    res.status(500).json({ message: "Failed to fetch orders" });
  }
};

const getOrderById = async (req, res) => {
  try {
    const order = await fetchSingleOrder(
      req.params.id,
      req.headers.authorization
    );

    if (!order || order.userId !== req.user.id) {
      return res.status(403).json({ message: "Unauthorized access to order" });
    }
    res.json(order);
  } catch (err) {
    console.error("[Fetch Order Error]", err.message);
    res.status(500).json({ message: "Failed to fetch order" });
  }
};

const getAllProducts = async (req, res) => {
  try {
    const products = await fetchProducts();
    res.json(products);
  } catch (err) {
    console.error("[Get All Products Error]", err.message);
    res.status(500).json({ message: "Failed to fetch products" });
  }
};

const getProductById = async (req, res) => {
  try {
    const product = await fetchProductById(req.params.id);
    if (product?.vendorId) {
      const storeDetail = await fetchStoreDetail(
        product.vendorId,
        req.headers.authorization
      );
      product.storeDetail = storeDetail;
    }

    res.json(product);
  } catch (err) {
    console.error("[Get Product By Id Error]", err.message);
    res.status(500).json({ message: "Failed to fetch product" });
  }
};

const getAllCategories = async (req, res) => {
  try {
    const categories = await fetchCategories();
    res.json(categories);
  } catch (err) {
    console.error("[Get All categories Error]", err.message);
    res.status(500).json({ message: "Failed to fetch categories" });
  }
};

const getAllBrands = async (req, res) => {
  try {
    const brands = await fetchBrands();
    res.json(brands);
  } catch (err) {
    console.error("[Get All brands Error]", err.message);
    res.status(500).json({ message: "Failed to fetch brands" });
  }
};

const addToCart = async (req, res) => {
  try {
    const cart = await createCart(req.body, req.headers.authorization);
    res.json(cart);
  } catch (err) {
    console.error("[Add To Cart Error]", err.message);
    res.status(500).json({ message: "Failed to add to cart" });
  }
};

const getCartByUserId = async (req, res) => {
  try {
    const cart = await fetchCartByUserId(req.headers.authorization);
    res.json(cart);
  } catch (err) {
    console.error("[Get Cart By User Id Error]", err.message);
    res.status(500).json({ message: "Failed to get cart by user id" });
  }
};

const updateUserCartItem = async (req, res) => {
  try {
    const cart = await updateCartItem(
      req.body,
      req.headers.authorization,
      req.params.productId
    );
    res.json(cart);
  } catch (err) {
    console.error("[Update Cart Item Error]", err.message);
    res.status(500).json({ message: "Failed to update cart item" });
  }
};

const removeUserCartItem = async (req, res) => {
  try {
    const cart = await removeCartItem(
      req.params.itemId,
      req.headers.authorization
    );
    res.json(cart);
  } catch (err) {
    console.error("[Remove Cart Item Error]", err.message);
    res.status(500).json({ message: "Failed to remove cart item" });
  }
};

const clearUserCart = async (req, res) => {
  try {
    const cart = await clearCart(req.headers.authorization);
    res.json(cart);
  } catch (err) {
    console.error("[Clear Cart Error]", err.message);
    res.status(500).json({ message: "Failed to clear cart" });
  }
};

const addUserAddress = async (req, res) => {
  try {
    const address = await addAddress(req.body, req.headers.authorization);
    res.json(address);
  } catch (err) {
    console.error("[Add User Address Error]", err.message);
    res.status(500).json({ message: "Failed to add user address" });
  }
};

const updateUserAddress = async (req, res) => {
  try {
    const address = await updateAddress(
      req.params.addressId,
      req.body,
      req.headers.authorization
    );
    res.json(address);
  } catch (err) {
    console.error("[Update User Address Error]", err.message);
    res.status(500).json({ message: "Failed to update user address" });
  }
};

const deleteUserAddress = async (req, res) => {
  try {
    const address = await deleteAddress(
      req.params.addressId,
      req.headers.authorization
    );
    res.json(address);
  } catch (err) {
    console.error("[Delete User Address Error]", err.message);
    res.status(500).json({ message: "Failed to delete user address" });
  }
};

const createProductReview = async (req, res) => {
  try {
    const response = await postReviewOnProduct(
      req.body,
      req.headers.authorization
    );
    res.status(200).json(response);
  } catch (err) {
    console.error("[Create Product Review Error]", err.message);
    res.status(500).json({ message: "Failed to create product review" });
  }
};

const allReviews = async (req, res) => {
  try {
    const response = await getAllReviews(req.headers.authorization);
    res.status(200).json(response);
  } catch (err) {
    console.error("[Get All Product Reviews Error]", err.message);
    res.status(500).json({ message: "Failed to fetch product reviews" });
  }
};

const reviewById = async (req, res) => {
  try {
    const response = await getReviewById(
      req.params.reviewId,
      req.headers.authorization
    );
    res.status(200).json(response);
  } catch (err) {
    console.error("[Get Product Review By ID Error]", err.message);
    res.status(500).json({ message: "Failed to fetch product review" });
  }
};

const reviewsByProductId = async (req, res) => {
  try {
    const response = await getReviewsByProductId(
      req.params.productId,
      req.headers.authorization
    );
    res.status(200).json(response);
  } catch (err) {
    console.error("[Get Reviews By Product ID Error]", err.message);
    res
      .status(500)
      .json({ message: "Failed to fetch product reviews by productId" });
  }
};

const reviewsByUserId = async (req, res) => {
  try {
    const response = await getReviewsByUserId(
      req.user.id,
      req.headers.authorization
    );
    res.status(200).json(response);
  } catch (err) {
    console.error("[Get Reviews By User ID Error]", err.message);
    res
      .status(500)
      .json({ message: "Failed to fetch product reviews by userId" });
  }
};

const updateReview = async (req, res) => {
  try {
    const response = await updateReviewById(
      req.params.reviewId,
      req.body,
      req.headers.authorization
    );
    res.status(200).json(response);
  } catch (err) {
    console.error("[Update Product Review Error]", err.message);
    res.status(500).json({ message: "Failed to update product review" });
  }
};

const deleteReview = async (req, res) => {
  try {
    const response = await deleteReviewById(
      req.params.reviewId,
      req.headers.authorization
    );
    res.status(200).json(response);
  } catch (err) {
    console.error("[Delete Product Review Error]", err.message);
    res.status(500).json({ message: "Failed to delete product review" });
  }
};
const getReferralStats = async (req, res) => {
  try {
    const client = await prisma.client.findFirst({
      where: { userId: String(req.user.id) }
    });
    
    if (!client) {
      return res.status(404).json({ message: "Client not found" });
    }

    // Parse JSON referral field
    const referral = typeof client.referral === 'string' 
      ? JSON.parse(client.referral) 
      : client.referral || { code: '', referredBy: null, totalReferrals: 0, totalPoints: 0, history: [] };

    res.json({
      referralCode: referral.code || '',
      totalReferrals: referral.totalReferrals || 0,
      totalPoints: referral.totalPoints || 0,
      history: (referral.history || []).map(h => ({
        referredUser: h.referredUser,
        hasOrdered: h.hasOrdered || false,
        pointsEarned: h.pointsEarned || 0
      })),
    });
  } catch (err) {
    console.error("[Get Referral Stats Error]", err.message);
    res.status(500).json({ message: "Failed to fetch referral stats" });
  }
};
module.exports = {
  registerClient,
  addUserFavoriteProduct,
  orderFeedback,
  placeOrder,
  getUserOrders,
  getOrderById,
  addToCart,
  getCartByUserId,
  updateUserCartItem,
  removeUserCartItem,
  clearUserCart,
  addUserAddress,
  updateUserAddress,
  deleteUserAddress,
  getAllProducts,
  getProductById,
  getAllCategories,
  getAllBrands,
  createProductReview,
  allReviews,
  reviewById,
  reviewsByProductId,
  reviewsByUserId,
  updateReview,
  deleteReview,
  getReferralStats,
};
