const Joi = require('joi');

// Notification validation schema
const notificationSchema = Joi.object({
  userId: Joi.string()
    .required()
    .min(1)
    .max(100)
    .messages({
      'string.empty': 'User ID is required',
      'string.min': 'User ID must be at least 1 character',
      'string.max': 'User ID must not exceed 100 characters'
    }),

  type: Joi.string()
    .valid('in_app', 'sms', 'both')
    .required()
    .messages({
      'any.only': 'Type must be one of: in_app, sms, both',
      'any.required': 'Notification type is required'
    }),

  title: Joi.string()
    .required()
    .min(1)
    .max(100)
    .trim()
    .messages({
      'string.empty': 'Title is required',
      'string.min': 'Title must be at least 1 character',
      'string.max': 'Title must not exceed 100 characters'
    }),

  message: Joi.string()
    .required()
    .min(1)
    .max(500)
    .trim()
    .messages({
      'string.empty': 'Message is required',
      'string.min': 'Message must be at least 1 character',
      'string.max': 'Message must not exceed 500 characters'
    }),

  phoneNumber: Joi.string()
    .when('type', {
      is: Joi.valid('sms', 'both'),
      then: Joi.required(),
      otherwise: Joi.optional()
    })
    .messages({
      'any.required': 'Phone number is required for SMS notifications'
    }),

  priority: Joi.string()
    .valid('low', 'medium', 'high', 'urgent')
    .default('medium')
    .messages({
      'any.only': 'Priority must be one of: low, medium, high, urgent'
    })
});

const validateNotification = (data) => notificationSchema.validate(data, { abortEarly: false });

module.exports = { validateNotification };
