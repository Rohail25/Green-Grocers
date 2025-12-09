<?php
/**
 * Email Configuration
 * 
 * This file contains your email settings
 */

// Email sender configuration
// IMPORTANT: For Gmail, FROM address should match your Gmail address
define('EMAIL_FROM_ADDRESS', 'malikrohail252@gmail.com'); // Use your Gmail address
define('EMAIL_FROM_NAME', 'Green Grocers');

// SMTP Configuration (if using SMTP)
define('SMTP_ENABLED', true); // Set to true to use SMTP
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'malikrohail252@gmail.com');
define('SMTP_PASSWORD', 'vebxwsjreqifndty'); // Password must be in quotes
define('SMTP_ENCRYPTION', 'tls'); // 'tls' or 'ssl'

// For Gmail:
// 1. Enable 2-Step Verification
// 2. Generate an App Password: https://myaccount.google.com/apppasswords
// 3. Use the app password as SMTP_PASSWORD

// For other email providers, check their SMTP settings

