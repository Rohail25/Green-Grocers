const jwt = require('jsonwebtoken');
const { getUserFromToken } = require('../utils/communicateUserService');

module.exports = async (req, res, next) => {
  try {
    const authHeader = req.headers.authorization;
    console.log('authHeader: ', authHeader);
    
    // Log FULL authorization header for debugging
    // console.log('[Auth Middleware] ========================================');
    // console.log('[Auth Middleware] FULL Authorization header:');
    // console.log('[Auth Middleware]', authHeader);
    // console.log('[Auth Middleware] Length:', authHeader ? authHeader.length : 0, 'characters');
    // console.log('[Auth Middleware] ========================================');
    
    if (!authHeader) {
      return res.status(401).json({ message: 'Token missing' });
    }

    // Extract token from "Bearer <token>" format
    const tokenParts = authHeader.split(' ');
    console.log('[Auth Middleware] Token parts count:', tokenParts.length);
    console.log('[Auth Middleware] First part (should be "Bearer"):', tokenParts[0]);
    console.log('[Auth Middleware] Second part length:', tokenParts[1] ? tokenParts[1].length : 0);
    
    if (tokenParts.length !== 2 || tokenParts[0] !== 'Bearer') {
      console.error('[Auth Middleware] Invalid format. Expected: Bearer <token>');
      console.error('[Auth Middleware] Received:', authHeader);
      console.error('[Auth Middleware] Split result:', JSON.stringify(tokenParts));
      return res.status(401).json({ message: 'Invalid authorization format. Use: Bearer <token>' });
    }

    const token = tokenParts[1];
    // console.log('[Auth Middleware] Token extracted - Full length:', token.length);
    // console.log('[Auth Middleware] Token extracted - First 50 chars:', token.substring(0, 50));
    // console.log('[Auth Middleware] Token extracted - Last 50 chars:', token.substring(Math.max(0, token.length - 50)));
    // console.log('[Auth Middleware] USER_SERVICE_URL:', process.env.USER_SERVICE_URL || 'NOT SET');
    // console.log('[Auth Middleware] JWT_SECRET exists:', !!process.env.JWT_SECRET);

    // Option 1: Validate via user-service (Recommended - ensures token is valid in user-service)
    if (process.env.USER_SERVICE_URL) {
      console.log('[Auth Middleware] Attempting user-service validation...');
      try {
        const user = await getUserFromToken(`Bearer ${token}`);
        console.log('[Auth Middleware] User service validation SUCCESS:', user.email || user.id);
        req.user = {
          id: user.id || user._id,
          email: user.email,
          platform: user.platform,
          role: user.role,
          vendorId: user.vendorId,
          clientId: user.clientId,
          ...user
        };
        return next();
      } catch (userServiceError) {
        console.error('[Auth Middleware] User service validation FAILED:', userServiceError.message);
        if (userServiceError.response) {
          console.error('[Auth Middleware] User service HTTP status:', userServiceError.response.status);
          console.error('[Auth Middleware] User service error data:', JSON.stringify(userServiceError.response.data));
        }
        if (userServiceError.code === 'ECONNREFUSED') {
          console.error('[Auth Middleware] Cannot connect to user-service. Is it running?');
        }
        console.log('[Auth Middleware] Falling back to JWT verification...');
        // Fall through to JWT verification as backup
      }
    } else {
      console.log('[Auth Middleware] USER_SERVICE_URL not set, using JWT verification only');
    }

    // Option 2: Verify JWT directly (Backup - requires same JWT_SECRET as user-service)
    if (!process.env.JWT_SECRET) {
      console.error('[Auth Middleware] JWT_SECRET not configured!');
      return res.status(500).json({ 
        message: 'JWT_SECRET not configured. Please set JWT_SECRET or USER_SERVICE_URL in .env' 
      });
    }

    console.log('[Auth Middleware] JWT_SECRET length:', process.env.JWT_SECRET.length, 'characters');
    
    // First decode without verification to see the payload
    const decodedUnverified = jwt.decode(token);
    console.log('[Auth Middleware] Token payload (decoded):', JSON.stringify(decodedUnverified, null, 2));
    
    try {
      const decoded = jwt.verify(token, process.env.JWT_SECRET);
      console.log('[Auth Middleware] JWT verification SUCCESS');
      
      // Ensure we have the required fields
      if (!decoded.id) {
        console.error('[Auth Middleware] Token missing id field:', decoded);
        return res.status(401).json({ 
          message: 'Invalid token payload. Please login again to get a fresh token.' 
        });
      }
      
      req.user = decoded;
      next();
    } catch (jwtError) {
      console.error('[Auth Middleware] Token (first 50 chars):', token.substring(0, 50) + '...');
      console.error('[Auth Middleware] JWT_SECRET (first 10 chars):', process.env.JWT_SECRET.substring(0, 10) + '...');
      console.error('[Auth Middleware] JWT_SECRET length:', process.env.JWT_SECRET.length);
      
      // Decode token to show what we're trying to verify
      const decodedInfo = jwt.decode(token);
      console.error('[Auth Middleware] Token payload:', JSON.stringify(decodedInfo, null, 2));
      
      if (jwtError.name === 'TokenExpiredError') {
        return res.status(401).json({ message: 'Token expired. Please login again.' });
      }
      if (jwtError.name === 'JsonWebTokenError') {
        // Most likely: JWT_SECRET mismatch
        return res.status(401).json({ 
          message: 'Invalid token. JWT_SECRET mismatch between services. Please ensure both user-service and vendor-service have the SAME JWT_SECRET in their .env files.',
          hint: 'Check your .env files: user-service/.env and vendor-service/.env must have identical JWT_SECRET values.'
        });
      }
      
      return res.status(401).json({ 
        message: 'Invalid token', 
        error: jwtError.message 
      });
    }
  } catch (err) {
    console.error('[Auth Middleware] Unexpected error:', err.message);
    res.status(401).json({ message: 'Authentication failed', error: err.message });
  }
};
