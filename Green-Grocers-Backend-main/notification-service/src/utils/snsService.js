const AWS = require('aws-sdk');

// Set AWS region
AWS.config.update({ 
  region: process.env.AWS_REGION || 'us-east-1',
  accessKeyId: process.env.AWS_ACCESS_KEY_ID,
  secretAccessKey: process.env.AWS_SECRET_ACCESS_KEY
});

// Create SNS service object
const sns = new AWS.SNS({ apiVersion: '2010-03-31' });

/**
 * Send SMS notification using AWS SNS
 * @param {string} phoneNumber - Phone number in E.164 format
 * @param {string} message - Message content
 * @param {object} options - Additional options
 * @returns {Promise<object>} SNS response
 */
const sendSMSNotification = async (phoneNumber, message, options = {}) => {
  try {
    // Validate inputs
    if (!phoneNumber || !message) {
      throw new Error('Phone number and message are required');
    }

    // Message length validation
    if (message.length > 1600) {
      throw new Error('Message too long. SMS messages are limited to 1600 characters');
    }

    const params = {
      Message: message,
      PhoneNumber: phoneNumber,
      MessageAttributes: {
        'AWS.SNS.SMS.SenderID': {
          DataType: 'String',
          StringValue: options.senderId || 'NotifyApp'
        },
        'AWS.SNS.SMS.SMSType': {
          DataType: 'String',
          StringValue: options.smsType || 'Transactional' // or 'Promotional'
        }
      }
    };

    // Add max price if specified
    if (options.maxPrice) {
      params.MessageAttributes['AWS.SNS.SMS.MaxPrice'] = {
        DataType: 'String',
        StringValue: options.maxPrice
      };
    }

    // Add custom message attributes if provided
    if (options.attributes) {
      Object.keys(options.attributes).forEach(key => {
        params.MessageAttributes[key] = {
          DataType: 'String',
          StringValue: String(options.attributes[key])
        };
      });
    }

    console.log(`Sending SMS to ${phoneNumber}...`);
    
    // Correct AWS SDK v2 syntax
    const result = await sns.publish(params).promise();
    
    console.log(`SMS sent successfully. MessageId: ${result.MessageId}`);
    return result;

  } catch (error) {
    console.error('SMS sending failed:', error);
    
    // Handle specific AWS errors
    if (error.code === 'InvalidParameter') {
      throw new Error('Invalid phone number or message format');
    } else if (error.code === 'Throttling') {
      throw new Error('SMS sending rate limit exceeded. Please try again later');
    } else if (error.code === 'InvalidClientTokenId') {
      throw new Error('Invalid AWS credentials');
    } else if (error.code === 'OptedOut') {
      throw new Error('Phone number has opted out from receiving SMS');
    } else if (error.code === 'EndpointDisabled') {
      throw new Error('SMS endpoint is disabled for this phone number');
    } else if (error.code === 'InternalError') {
      throw new Error('AWS SNS internal error. Please try again later');
    }
    
    throw error;
  }
};

/**
 * Send SMS to multiple recipients
 * @param {Array<string>} phoneNumbers - Array of phone numbers
 * @param {string} message - Message content
 * @param {object} options - Additional options
 * @returns {Promise<Array>} Array of results
 */
const sendBulkSMS = async (phoneNumbers, message, options = {}) => {
  try {
    if (!Array.isArray(phoneNumbers) || phoneNumbers.length === 0) {
      throw new Error('Phone numbers must be a non-empty array');
    }

    const results = [];
    const batchSize = options.batchSize || 10; // Process in batches to avoid overwhelming SNS
    
    for (let i = 0; i < phoneNumbers.length; i += batchSize) {
      const batch = phoneNumbers.slice(i, i + batchSize);
      
      const batchPromises = batch.map(async (phoneNumber) => {
        try {
          const result = await sendSMSNotification(phoneNumber, message, options);
          return {
            phoneNumber,
            success: true,
            messageId: result.MessageId
          };
        } catch (error) {
          return {
            phoneNumber,
            success: false,
            error: error.message
          };
        }
      });

      const batchResults = await Promise.all(batchPromises);
      results.push(...batchResults);

      // Small delay between batches to respect rate limits
      if (i + batchSize < phoneNumbers.length) {
        await new Promise(resolve => setTimeout(resolve, 1000));
      }
    }

    return results;

  } catch (error) {
    console.error('Bulk SMS sending failed:', error);
    throw error;
  }
};

/**
 * Check if SMS service is configured
 * @returns {boolean} Configuration status
 */
const isSMSConfigured = () => {
  return !!(
    process.env.AWS_ACCESS_KEY_ID &&
    process.env.AWS_SECRET_ACCESS_KEY &&
    process.env.AWS_REGION
  );
};

/**
 * Get SMS service status
 * @returns {Promise<object>} Service status
 */
const getSMSServiceStatus = async () => {
  try {
    if (!isSMSConfigured()) {
      return {
        configured: false,
        message: 'AWS credentials not configured'
      };
    }

    // Create SMS Attribute parameters to check
    const params = {
      attributes: [
        'DefaultSMSType',
        'MonthlySpendLimit',
        'DeliveryStatusIAMRole',
        'DefaultSenderID'
      ]
    };

    // Create promise and get SMS attributes
    const getSMSAttributesPromise = sns.getSMSAttributes(params).promise();
    
    // Handle promise
    const data = await getSMSAttributesPromise;
    
    return {
      configured: true,
      region: process.env.AWS_REGION,
      attributes: data.attributes,
      message: 'SMS service is configured and accessible'
    };

  } catch (error) {
    console.error('SMS service status error:', error, error.stack);
    return {
      configured: false,
      error: error.message,
      message: 'SMS service configuration error'
    };
  }
};

module.exports = {
  sendSMSNotification,
  sendBulkSMS,
  isSMSConfigured,
  getSMSServiceStatus
};