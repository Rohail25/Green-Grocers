const AWS = require('aws-sdk');

const ses = new AWS.SES({ region: 'eu-north-1' });

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

module.exports = { sendEmail };
