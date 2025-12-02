const express = require("express");
const auth = require("../middlewares/auth.middleware");
const router = express.Router();
const {
  createReview,
  getReview,
  getAllReviews,
  updateReview,
  deleteReview,
  getReviewsByProductId,
  getReviewsByUserId,
} = require("../controllers/review.rating.controller");

router.post("/",auth, createReview);
router.get("/", getAllReviews);
router.get("/:reviewId", getReview);
router.put("/:reviewId",auth, updateReview);
router.delete("/:reviewId",auth, deleteReview);
router.get("/product/:productId", getReviewsByProductId);
router.get("/user/:userId", getReviewsByUserId);

module.exports = router;
