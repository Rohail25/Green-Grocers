const prisma = require("../utils/prisma");

// CREATE a new review
const createReview = async (req, res) => {
  try {
    const { id, email, name } = req.user;

    const review = await prisma.reviewRating.create({
      data: {
        productId: req.body.productId,
        user: {
          userId: id,
          email: email,
          name: name,
          profileImage: req.user.profileImage || null
        },
        rating: parseInt(req.body.rating),
        review: req.body.review,
      }
    });
    res.status(201).json(review);
  } catch (err) {
    console.error("[Create Review Error]", err);
    res.status(500).json({ message: "Failed to create review" });
  }
};

// GET a single review by ID
const getReview = async (req, res) => {
  const { reviewId } = req.params;
  try {
    const review = await prisma.reviewRating.findUnique({
      where: { id: reviewId }
    });
    if (!review) return res.status(404).json({ message: "Review not found" });
    res.json(review);
  } catch (err) {
    console.error("[Get Review Error]", err);
    res.status(500).json({ message: "Failed to fetch review" });
  }
};

// GET all reviews (optionally filter by productId)
const getAllReviews = async (req, res) => {
  try {
    const { productId } = req.query;
    const where = productId ? { productId } : {};
    const reviews = await prisma.reviewRating.findMany({
      where,
      orderBy: { createdAt: 'desc' }
    });
    res.json(reviews);
  } catch (err) {
    console.error("[Get All Reviews Error]", err);
    res.status(500).json({ message: "Failed to fetch reviews" });
  }
};

// UPDATE a review
const updateReview = async (req, res) => {
  const { reviewId } = req.params;
  try {
    // Check if review exists first
    const existing = await prisma.reviewRating.findUnique({
      where: { id: reviewId }
    });
    if (!existing) return res.status(404).json({ message: "Review not found" });

    const updates = {};
    if (req.body.rating !== undefined) updates.rating = parseInt(req.body.rating);
    if (req.body.review !== undefined) updates.review = req.body.review;
    // User field can be updated if provided
    if (req.body.user !== undefined) {
      updates.user = typeof req.body.user === 'string' 
        ? JSON.parse(req.body.user) 
        : req.body.user;
    }

    const review = await prisma.reviewRating.update({
      where: { id: reviewId },
      data: updates
    });
    if (!review) return res.status(404).json({ message: "Review not found" });
    res.json(review);
  } catch (err) {
    console.error("[Update Review Error]", err);
    res.status(500).json({ message: "Failed to update review" });
  }
};

// DELETE a review
const deleteReview = async (req, res) => {
  const { reviewId } = req.params;
  try {
    // Check if review exists first
    const existing = await prisma.reviewRating.findUnique({
      where: { id: reviewId }
    });
    if (!existing) return res.status(404).json({ message: "Review not found" });

    await prisma.reviewRating.delete({
      where: { id: reviewId }
    });
    
    const review = existing;
    if (!review) return res.status(404).json({ message: "Review not found" });
    res.json({ message: "Review deleted successfully" });
  } catch (err) {
    console.error("[Delete Review Error]", err);
    res.status(500).json({ message: "Failed to delete review" });
  }
};

// GET reviews by productId
const getReviewsByProductId = async (req, res) => {
  const { productId } = req.params;
  try {
    const reviews = await prisma.reviewRating.findMany({
      where: { productId },
      orderBy: { createdAt: 'desc' }
    });
    if (!reviews.length)
      return res
        .status(404)
        .json({ message: "No reviews found for this product" });
    res.json(reviews);
  } catch (err) {
    console.error("[Get Reviews by ProductId Error]", err);
    res.status(500).json({ message: "Failed to fetch reviews by productId" });
  }
};

// GET reviews by userId (inside nested user object)
const getReviewsByUserId = async (req, res) => {
  const { userId } = req.params;
  try {
    // Since user is stored as JSON, we need to fetch all and filter
    // This is a limitation of JSON queries in Prisma/MySQL
    const allReviews = await prisma.reviewRating.findMany({
      orderBy: { createdAt: 'desc' }
    });
    
    const reviews = allReviews.filter(review => {
      const user = typeof review.user === 'string' 
        ? JSON.parse(review.user) 
        : review.user;
      return user && user.userId === userId;
    });
    if (!reviews.length)
      return res
        .status(404)
        .json({ message: "No reviews found for this user" });
    res.json(reviews);
  } catch (err) {
    console.error("[Get Reviews by UserId Error]", err);
    res.status(500).json({ message: "Failed to fetch reviews by userId" });
  }
};

module.exports = {
  createReview,
  getReview,
  getAllReviews,
  getReviewsByProductId,
  getReviewsByUserId,
  updateReview,
  deleteReview,
};
