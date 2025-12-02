const axios = require("axios");

exports.getUserRateLogistics = async (logisticId, authHeader) => {
  try {
    const response = await axios.get(
      `${process.env.USER_SERVICE_URL}/users/rate-logistics/${logisticId}`,
      {
        headers: {
          Authorization: authHeader,
        },
      }
    );

    return response.data;
  } catch (err) {
    console.error("[Rate Logistics Error]", err.message);
    throw new Error("Failed to rate logistics");
  }
};
exports.getAgents = async (authHeader) => {
  try {
    const response = await axios.get(
      `${process.env.USER_SERVICE_URL}/users/agents`,
      {
        headers: {
          Authorization: authHeader,
        },
      }
    );

    return response.data;
  } catch (err) {
    console.error("[Rate Logistics Error]", err.message);
    throw new Error("Failed to rate logistics");
  }
};
