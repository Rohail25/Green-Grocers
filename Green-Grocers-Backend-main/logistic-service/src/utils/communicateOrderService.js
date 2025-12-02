const axios = require("axios");

// create order
exports.getOrderLogisticStat = async (logisticId, authHeader) => {
  try {
    const response = await axios.get(
      `${process.env.ORDER_SERVICE_URL}/orders/logistic/${logisticId}`,
      {
        headers: {
          authorization: authHeader,
        },
      }
    );

    return response.data;
  } catch (err) {
    console.error("[getOrderLogisticStat Error]", err.message);
    throw new Error("Failed to create order");
  }
};
exports.getOrderById = async (orderId, authHeader) => {
  try {
    const response = await axios.get(
      `${process.env.ORDER_SERVICE_URL}/orders/${orderId}`,
      {
        headers: {
          Authorization: authHeader,
        },
      }
    );
    return response.data;
  } catch (err) {
    console.error("[getOrderLogisticStat Error]", err.message);
    throw new Error("Failed to create order");
  }
};
exports.UpdateOrderStatus = async (
  orderId,
  status,
  orderProgress,
  authHeader
) => {
  try {
    const response = await axios.put(
      `${process.env.ORDER_SERVICE_URL}/orders/${orderId}/status`,
      { status, orderProgress },
      {
        headers: {
          Authorization: authHeader,
        },
      }
    );
    return response.data;
  } catch (err) {
    console.error("[getOrderLogisticStat Error]", err.message);
    throw new Error("Failed to create order");
  }
};
