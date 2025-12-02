const axios = require('axios');
const logger = require('../utils/logger');


const updateProductQuantityService  = async (productId, quantity, variantIndex) => {
    try {
        const response = await axios.patch(`${process.env.PRODUCT_SERVICE_URL}/products/update-quantity/${productId}`, { quantity, variantIndex });
        return response.data;
    } catch (error) {
        logger.error(`Error updating product quantity: ${error.message}`);
        throw error;
    }
}

module.exports = { updateProductQuantityService };