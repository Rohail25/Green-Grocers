// AWS SES Email Service - Commented out for local development
// const AWS = require('aws-sdk');
// const ses = new AWS.SES({ region: 'eu-north-1' });

// Simple email service for local development
const nodemailer = require('nodemailer');

// Email configuration
const EMAIL_CONFIG = {
  user: process.env.GMAIL_USER || 'kiran.ahmad.usman@gmail.com',
  pass: process.env.GMAIL_PASS || 'ldic apiq qoau qdgp',
  devMode: false // Set to false to actually send emails
};

// Create transporter using Gmail SMTP
const transporter = nodemailer.createTransport({
  service: 'gmail',
  auth: {
    user: EMAIL_CONFIG.user,
    pass: EMAIL_CONFIG.pass
  }
});

const sendEmail = async (to, subject, body) => {
  try {
    if (EMAIL_CONFIG.devMode) {
      // For development, just log the email instead of sending
      console.log('📧 Email would be sent:');
      console.log('To:', to);
      console.log('Subject:', subject);
      console.log('Body:', body);
      console.log('---');
      
      // Return success for development
      return { messageId: 'dev-' + Date.now() };
    } else {
      // Actually send emails via Gmail
      const mailOptions = {
        from: EMAIL_CONFIG.user,
        to: to,
        subject: subject,
        text: body
      };
      
      const result = await transporter.sendMail(mailOptions);
      console.log('Email sent successfully:', result.messageId);
      return result;
    }
    
  } catch (error) {
    console.error('Email sending error:', error);
    throw error;
  }
};

// Original AWS SES implementation (commented out)
/*
const sendEmail = async (to, subject, body) => {
  const params = {
    Destination: { ToAddresses: [to] },
    Message: {
      Body: {
        Text: { Data: body }
      },
      Subject: { Data: subject }
    },
    Source: 'kiran.ahmad.usman@gmail.com'
  };

  return ses.sendEmail(params).promise();
};
*/

module.exports = { sendEmail };
