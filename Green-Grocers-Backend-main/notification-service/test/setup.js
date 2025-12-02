// Test setup file
require('dotenv').config({ path: '.env.test' });

// Suppress console logs during tests (optional)
if (process.env.NODE_ENV === 'test') {
  console.log = jest.fn();
  console.error = jest.fn();
  console.warn = jest.fn();
}

// Set test environment variables
process.env.NODE_ENV = 'test';
process.env.MONGODB_URI = 'mongodb://localhost:27017/notification_service_test';
process.env.AWS_REGION = 'us-east-1';
process.env.AWS_ACCESS_KEY_ID = 'test-access-key';
process.env.AWS_SECRET_ACCESS_KEY = 'test-secret-key';

// Mock AWS SDK v2 for tests
jest.mock('aws-sdk', () => {
  const mockSNS = {
    publish: jest.fn().mockReturnValue({
      promise: jest.fn().mockResolvedValue({
        MessageId: 'test-message-id-123'
      })
    }),
    getSMSAttributes: jest.fn().mockReturnValue({
      promise: jest.fn().mockResolvedValue({
        attributes: {
          'MonthlySpendLimit': '1.00',
          'DeliveryStatusIAMRole': '',
          'DeliveryStatusSuccessSamplingRate': '0',
          'DefaultSenderID': '',
          'DefaultSMSType': 'Transactional',
          'UsageReportS3Bucket': ''
        }
      })
    })
  };

  return {
    SNS: jest.fn(() => mockSNS),
    config: {
      update: jest.fn()
    }
  };
});

// Mock the socketManager to avoid websocket issues in tests
jest.mock('../src/utils/socketManager.js', () => ({
  sendInAppNotification: jest.fn().mockResolvedValue(true)
}));

// Global test timeout
jest.setTimeout(30000);