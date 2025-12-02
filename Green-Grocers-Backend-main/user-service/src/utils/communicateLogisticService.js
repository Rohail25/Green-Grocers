const axios = require("axios");
const logger = require("./logger");

const LoginLogistic = async (email, password) => {
  try {
    const response = await axios.post(
      `${process.env.LOGISTIC_SERVICE_URL}/login`,
      {
        email,
        password,
      }
    );
    logger.info(`Logistic Login success: ${response}`);
    return response.data;
  } catch (err) {
    logger.error(`Logistic registration failed: ${err.message}`);
    if (err.response) {
      logger.error(
        `Logistic service response: ${JSON.stringify(err.response.data)}`
      );
    }
  }
};

module.exports = { LoginLogistic };
