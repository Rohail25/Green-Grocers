const axios = require("axios");
const logger = require("../utils/logger");

const registerClient = async (user, token) => {
  try {
    const response = await axios.post(
      `${process.env.CLIENT_SERVICE_URL}/register`,
      {
        clientId: user.vendorId || `MART-${user._id.toString().slice(-6)}`,
        userId: user._id,
        fullName: `${user.firstName || ""} ${user.lastName || ""}`.trim(),
        email: user.email,
        phone: user.phone,
      },
      {
        headers: {
          Authorization: `Bearer ${token}`,
        },
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
  }
};
const creditUserWallet = async (userId, amount, description, token) => {
  try {
    const response = await axios.post(
      `${process.env.CLIENT_SERVICE_URL}/api/wallets/credit`,
      { amount, description },
      {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      }
    );

    return response.data;
  } catch (err) {
    console.error("[Wallet Credit Error]", err.message);
    throw new Error("Failed to credit wallet");
  }
};
const debitWallet = async (userId, amount, description, token) => {
  try {
    const response = await axios.post(
      `${process.env.CLIENT_SERVICE_URL}/api/wallets/debit`,
      { amount, description },
      {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      }
    );

    return response.data;
  } catch (err) {
    console.error("[Wallet Credit Error]", err.message);
    throw new Error("Failed to credit wallet");
  }
};
module.exports = { registerClient, creditUserWallet, debitWallet };
