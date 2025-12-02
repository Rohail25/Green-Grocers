const axios = require("axios");
const logger = require("../utils/logger");

exports.updateOrderStatus = async (orderId, payload, token) => {
  try {
    const response = await axios.put(
      `${process.env.ORDER_SERVICE_URL}/${orderId}/status`,
      payload,
      {
        headers: {
          authorization: token,
          "Content-Type": "application/json",
        },
      }
    );

    logger.info(`Order ${orderId} status updated: ${payload.status}`);
    return response.data.order;
  } catch (err) {
    logger.error(`[Order Status Update Error]: ${err.message}`);
    if (err.response) {
      logger.error(
        `[Order Service Response]: ${JSON.stringify(err.response.data)}`
      );
    }
    throw new Error("Failed to update order status");
  }
};
