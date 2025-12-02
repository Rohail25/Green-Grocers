const axios = require("axios");

exports.fetchProducts = async () => {
  const response = await axios.get(
    `${process.env.PRODUCT_SERVICE_URL}/products`,
    {
      headers: { "Content-Type": "application/json" },
    }
  );

  return response.data;
};

exports.fetchProductById = async (id) => {
  
  const response = await axios.get(
    `${process.env.PRODUCT_SERVICE_URL}/products/one/${id}`,
    {
      headers: { "Content-Type": "application/json" },
    }
  );
  return response.data;
};

exports.fetchCategories = async () => {
  const response = await axios.get(
    `${process.env.PRODUCT_SERVICE_URL}/categories`,
    {
      headers: { "Content-Type": "application/json" },
    }
  );

  return response.data;
};

exports.fetchBrands = async () => {
  const response = await axios.get(
    `${process.env.PRODUCT_SERVICE_URL}/brands`,
    {
      headers: { "Content-Type": "application/json" },
    }
  );

  return response.data;
};

exports.postReviewOnProduct = async (body, authHeader) => {
  const response = await axios.post(
    `${process.env.PRODUCT_SERVICE_URL}/product-reviews`,
    body,
    {
      headers: { "Content-Type": "application/json", Authorization: authHeader },
    }
  );

  return response.data;
};

exports.getAllReviews = async () => {
  const response = await axios.get(
    `${process.env.PRODUCT_SERVICE_URL}/product-reviews`,
    {
      headers: { "Content-Type": "application/json" },
    }
  );
  return response.data;
};

exports.getReviewById = async (reviewId) => {
  const response = await axios.get(
    `${process.env.PRODUCT_SERVICE_URL}/product-reviews/${reviewId}`,
    {
      headers: { "Content-Type": "application/json" },
    }
  );
  return response.data;
};

exports.updateReviewById = async (reviewId, body, authHeader) => {
  const response = await axios.put(
    `${process.env.PRODUCT_SERVICE_URL}/product-reviews/${reviewId}`,
    body,
    {
      headers: { "Content-Type": "application/json", Authorization: authHeader },
    }
  );
  return response.data;
};

exports.deleteReviewById = async (reviewId, authHeader) => {
  const response = await axios.delete(
    `${process.env.PRODUCT_SERVICE_URL}/product-reviews/${reviewId}`,
    {
      headers: { "Content-Type": "application/json", Authorization: authHeader },
    }
  );
  return response.data;
};

exports.getReviewsByProductId = async (productId) => {
  const response = await axios.get(
    `${process.env.PRODUCT_SERVICE_URL}/product-reviews/product/${productId}`,
    {
      headers: { "Content-Type": "application/json" },
    }
  );
  return response.data;
};

exports.getReviewsByUserId = async (userId, authHeader) => {
  const response = await axios.get(
    `${process.env.PRODUCT_SERVICE_URL}/product-reviews/user/${userId}`,
    {
      headers: { "Content-Type": "application/json", Authorization: authHeader },
    }
  );
  return response.data;
};

