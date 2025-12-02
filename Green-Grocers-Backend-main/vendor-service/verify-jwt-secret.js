// Quick script to verify JWT_SECRET matches between services
require('dotenv').config();
const jwt = require('jsonwebtoken');

// Token from your latest login response
const token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpZCI6IjI0MGQ4ZDljLWI1MzQtNGNiMi05ZTdlLTNlNzdhMDAzNzJiZCIsInBsYXRmb3JtIjoidHJpdmVtYXJ0Iiwicm9sZSI6ImFkbWluIiwiaWF0IjoxNzYyMTA5NTc3LCJleHAiOjE3NjI3MTQzNzd9.WGITPhUfJX_CnU6IK5QNcHxJxsA2IfD6I0_eKCZK1z8';

// Or pass token as command line argument: node verify-jwt-secret.js YOUR_TOKEN_HERE
const tokenFromArg = process.argv[2];
const tokenToTest = tokenFromArg || token;

console.log('='.repeat(60));
console.log('JWT_SECRET Verification Script');
console.log('='.repeat(60));
console.log('\n');

// Check if JWT_SECRET is loaded
if (!process.env.JWT_SECRET) {
  console.error('❌ ERROR: JWT_SECRET not found in .env file!');
  console.error('Make sure vendor-service/.env has: JWT_SECRET=your_secret');
  process.exit(1);
}

console.log('✅ JWT_SECRET loaded from .env');
console.log('   Length:', process.env.JWT_SECRET.length, 'characters');
console.log('   First 10 chars:', process.env.JWT_SECRET.substring(0, 10) + '...');
console.log('   Last 10 chars:', '...' + process.env.JWT_SECRET.substring(process.env.JWT_SECRET.length - 10));
console.log('\n');

// Decode token without verification (to see payload)
console.log('📄 Token Payload (decoded):');
const decoded = jwt.decode(tokenToTest);
console.log(JSON.stringify(decoded, null, 2));
console.log('\n');

// Try to verify the token
console.log('🔐 Attempting to verify token with JWT_SECRET...');
try {
  const verified = jwt.verify(tokenToTest, process.env.JWT_SECRET);
  console.log('✅ SUCCESS! Token is valid!');
  console.log('   Verified payload:', JSON.stringify(verified, null, 2));
  console.log('\n');
  console.log('🎉 This means JWT_SECRET in vendor-service matches user-service!');
  console.log('   If you still get errors, check USER_SERVICE_URL configuration.');
} catch (err) {
  console.error('❌ FAILED! Token verification failed!');
  console.error('   Error type:', err.name);
  console.error('   Error message:', err.message);
  console.error('\n');
  console.error('🔧 SOLUTION:');
  console.error('   1. Check user-service/.env file');
  console.error('   2. Copy the JWT_SECRET value');
  console.error('   3. Paste it into vendor-service/.env');
  console.error('   4. Make sure they are EXACTLY identical (no spaces, same case)');
  console.error('   5. Restart vendor-service');
  console.error('\n');
  console.error('   Current JWT_SECRET in vendor-service/.env:');
  console.error('   "' + process.env.JWT_SECRET + '"');
  console.error('\n');
  process.exit(1);
}

console.log('='.repeat(60));

