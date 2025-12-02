const axios = require("axios");
const logger = require("../utils/logger");

const registerClient = async (user, token, referralCode) => {
  try {
    // Check if CLIENT_SERVICE_URL is configured
    if (!process.env.CLIENT_SERVICE_URL) {
      logger.warn('CLIENT_SERVICE_URL not configured, skipping client registration');
      return null;
    }

    const response = await axios.post(
      `${process.env.CLIENT_SERVICE_URL}/api/clients/register`,
      {
        clientId: user.clientId || `MART-${user._id.toString().slice(-6)}`,
        userId: user._id,
        fullName: `${user.firstName || ""} ${user.lastName || ""}`.trim() || user.email.split('@')[0],
        email: user.email,
        phone: user.phone,
        referralCode: referralCode,
      },
      {
        headers: {
          Authorization: `Bearer ${token}`,
        },
        timeout: 10000, // 10 second timeout
      }
    );
    logger.info(`Client registration success: ${response.data.clientId}`);
    return response.data;
  } catch (err) {
    logger.error(`Client registration failed: ${err.message}`);
    if (err.response) {
      logger.error(
        `Client service response: ${JSON.stringify(err.response.data)}`
      );
    }
    // Don't throw - allow user registration to continue even if client registration fails
    return null;
  }
};

module.exports = { registerClient };
