const axios = require("axios");
const logger = require("../utils/logger");

const CreateAgentEarning = async (orderId, token) => {
  try {
    const response = await axios.post(
      `${process.env.LOGISTIC_SERVICE_URL}/earnings/create`,
      {
        orderId,
      },
      {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      }
    );
    logger.info(`Logistic earning success: ${response.data.clientId}`);
    return response.data;
  } catch (err) {
    logger.error(`Logistic earning failed: ${err.message}`);
    if (err.response) {
      logger.error(
        `Logistic service response: ${JSON.stringify(err.response.data)}`
      );
    }
  }
};

module.exports = { CreateAgentEarning };
