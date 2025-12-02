const axios = require("axios");

// create order
exports.createOrder = async (orderData, authHeader) => {
  try {
    const response = await axios.post(
      `${process.env.ORDER_SERVICE_URL}/orders`,
      orderData,
      {
        headers: {
          Authorization: authHeader,
        },
      }
    );
    return response.data;
  } catch (err) {
    console.error("[Order Creation Error]", err.message);
    throw new Error("Failed to create order");
  }
};

// Fetch all orders for a user
exports.fetchUserOrders = async (userId, authHeader) => {
  const response = await axios.get(
    `${process.env.ORDER_SERVICE_URL}/orders/user/${userId}`,
    {
      headers: { Authorization: authHeader },
    }
  );
  return response.data.orders;
};

// Fetch a single order by ID
exports.fetchSingleOrder = async (orderId, authHeader) => {
  const response = await axios.get(
    `${process.env.ORDER_SERVICE_URL}/orders/${orderId}`,
    { headers: { Authorization: authHeader } }
  );
  return response.data.order;
};

// Fetch a single order by ID
exports.createCart = async (cartData, authHeader) => {
  try {
    const response = await axios.post(
      `${process.env.ORDER_SERVICE_URL}/cart`,
      cartData,
      {
        headers: {
          Authorization: authHeader,
        },
      }
    );
    return response.data;
  } catch (err) {
    console.error("[cart Creation Error]", err.message);
    throw new Error("Failed to create cart");
  }
};

exports.fetchCartByUserId = async (authHeader) => {
  try {
    const response = await axios.get(
      `${process.env.ORDER_SERVICE_URL}/cart/cart-by-user`,
      {
        headers: {
          Authorization: authHeader,
        },
      }
    );

    return response.data;
  } catch (err) {
    console.error("[cart Creation Error]", err.message);
    throw new Error("Failed to create cart");
  }
};

exports.updateCartItem = async (cartItem, authHeader, productId) => {
  try {
    const response = await axios.patch(
      `${process.env.ORDER_SERVICE_URL}/cart/update-item/${productId}`,
      cartItem,
      {
        headers: {
          Authorization: authHeader,
        },
      }
    );
    return response.data;
  } catch (err) {
    console.error("[cart Creation Error]", err.message);
    throw new Error("Failed to create cart");
  }
};

exports.removeCartItem = async (itemId, authHeader) => {
  try {
    const response = await axios.delete(
      `${process.env.ORDER_SERVICE_URL}/cart/remove-item/${itemId}`,
      {
        headers: {
          Authorization: authHeader,
        },
      }
    );
    return response.data;
  } catch (err) {
    console.error("[cart Creation Error]", err.message);
    throw new Error("Failed to create cart");
  }
};

exports.clearCart = async (authHeader) => {
  try {
    const response = await axios.delete(
      `${process.env.ORDER_SERVICE_URL}/cart/clear-cart`,
      orderData,
      {
        headers: {
          Authorization: authHeader,
        },
      }
    );
    return response.data;
  } catch (err) {
    console.error("[cart Creation Error]", err.message);
    throw new Error("Failed to create cart");
  }
};
exports.requestReturn = async (orderId, reason, authHeader) => {
  try {
    const response = await axios.put(
      `${process.env.ORDER_SERVICE_URL}/orders/${orderId}/return-request`,
      { reason },
      {
        headers: { Authorization: authHeader },
      }
    );
    return response.data;
  } catch (err) {
    console.error("[Return Request Error]", err.message);
    throw new Error("Failed to request return");
  }
};
