const axios = require('axios');
const prisma = require('./prisma');
const logger = require('../utils/logger');


const updateVendorInventoryAndCategories = async (vendorId, token) => {
  try {
    // Get all products for vendor
    const products = await prisma.product.findMany({
      where: { vendorId }
    });

    const totalStock = products.reduce((sum, p) => sum + (p.totalQuantityInStock || 0), 0);

    const response = await axios.put(
      `${process.env.VENDOR_SERVICE_URL}/${vendorId}/inventory`,
      {
        inventoryCount: totalStock,
       
      },
      {
        headers: {
          Authorization: `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      }
    );

    logger.info(`Vendor ${vendorId} inventory updated: ${JSON.stringify(response.data)}`);
    return response.data;
  } catch (error) {
    logger.error(`Failed to update vendor ${vendorId}: ${error.message}`);
    if (error.response) {
      logger.error(`Vendor service error response: ${JSON.stringify(error.response.data)}`);
    }
    throw error;
  }
};

module.exports = { updateVendorInventoryAndCategories };
