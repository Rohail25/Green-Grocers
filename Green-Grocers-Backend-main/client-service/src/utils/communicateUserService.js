const axios = require("axios");


exports.addAddress = async (address, authHeader) => {
  try {
    const response = await axios.post(
      `${process.env.USER_SERVICE_URL}/users/add-address`,
      address,
      {
        headers: {
          Authorization: authHeader,
        },
      }
    );
    return response.data;
  } catch (err) {
    console.error("[Add Address Error]", err.message);
    throw new Error("Failed to add address");
  }
};

exports.updateAddress = async (addressId, address, authHeader) => {
  try {
    const response = await axios.patch(
      `${process.env.USER_SERVICE_URL}/users/address/${addressId}`,
      address,
      {
        headers: {
          Authorization: authHeader,
        },
      }
    );
    return response.data;
  } catch (err) {
    console.error("[Update Address Error]", err.message);
    throw new Error("Failed to update address");
  }
};

exports.deleteAddress = async (addressId, authHeader) => {
  try {
    const response = await axios.delete(
      `${process.env.USER_SERVICE_URL}/users/address/${addressId}`,
      {
        headers: {
          Authorization: authHeader,
        },
      }
    );
    return response.data;
  } catch (err) {
    console.error("[Delete Address Error]", err.message);
    throw new Error("Failed to delete address");
  }
};

exports.addFavoriteProduct = async (data, authHeader) => {
  try {
    const response = await axios.post(
      `${process.env.USER_SERVICE_URL}/users/favorites`,
      data,
      {
        headers: {
          Authorization: authHeader,
        },
      }
    );
    return response.data;
  } catch (err) {
    console.error("[Add Favorite Product Error]", err.message);
    throw new Error("Failed to add favorite product");
  }
};


exports.rateLogistics = async (ratingData, authHeader) => {
  try {
    const response = await axios.post(
      `${process.env.USER_SERVICE_URL}/users/rate-logistics`,
      ratingData,
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