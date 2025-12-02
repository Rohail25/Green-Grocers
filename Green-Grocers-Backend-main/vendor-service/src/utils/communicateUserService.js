const axios = require('axios');
const logger = require('./logger');

exports.getUserFromToken = async (token) => {
  if (!token) throw new Error('Token is required for user validation');

  if (!process.env.USER_SERVICE_URL) {
    throw new Error('USER_SERVICE_URL not configured in .env');
  }

  console.log('[getUserFromToken] Calling user-service:', process.env.USER_SERVICE_URL);
  console.log('[getUserFromToken] Token (first 30 chars):', token.substring(0, 30) + '...');
  
  try {
    const response = await axios.get(process.env.USER_SERVICE_URL, {
      headers: { Authorization: token },
      timeout: 5000 // 5 second timeout
    });
    
    console.log('[getUserFromToken] User service response status:', response.status);
    console.log('[getUserFromToken] User service response data:', JSON.stringify(response.data, null, 2));

    if (!response.data || !response.data.user) {
      throw new Error('Invalid response from user-service: missing user data');
    }

    logger.info('Successfully validated user via User Service');
    return response.data.user;
  } catch (error) {
    if (error.code === 'ECONNREFUSED') {
      logger.error('User service is not running or not accessible at:', process.env.USER_SERVICE_URL);
      throw new Error(`Cannot connect to user-service at ${process.env.USER_SERVICE_URL}. Is it running?`);
    }
    if (error.response) {
      logger.error('User service validation failed:', error.response.status, error.response.data);
      throw new Error(`User service returned ${error.response.status}: ${JSON.stringify(error.response.data)}`);
    }
    logger.error('User service validation failed: ' + error.message);
    throw new Error('Unable to validate user from user-service: ' + error.message);
  }
};

exports.checkUserServiceHealth = async () => {
  try {
    const url = process.env.USER_SERVICE_URL.replace(/\/api\/users\/me$/, '/health');
    const response = await axios.get(url);
    logger.info(`User Service Health: ${response.status} ${response.data.status || ''}`);
    return response.data;
  } catch (err) {
    logger.error('User Service is unreachable: ' + err.message);
    return { status: 'DOWN', error: err.message };
  }
};
