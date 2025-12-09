# Email Setup Guide for Green Grocers

## Problem
When users register, they are not receiving verification emails.

## Solution Implemented
I've created an email sending system that will send verification emails to users when they register.

## Current Implementation
The system uses PHP's built-in `mail()` function by default. This works on most servers but may have limitations.

## Setup Instructions

### Option 1: Basic Setup (Using PHP mail() function)
This is the simplest option and works if your server has mail configured.

1. **Configure the sender email** in `includes/email.php`:
   - Change `$fromEmail` to your domain email (e.g., `noreply@yourdomain.com`)
   - Change `$fromName` to your business name

2. **Test the email**:
   - Register a new user
   - Check if the email is received
   - Check spam folder if not in inbox

**Note:** The basic `mail()` function may not work on:
- Local development (XAMPP/WAMP)
- Some shared hosting providers
- Servers without mail server configuration

### Option 2: SMTP Setup (Recommended for Production)
For reliable email delivery, use SMTP with PHPMailer.

#### Step 1: Install PHPMailer
```bash
composer require phpmailer/phpmailer
```

#### Step 2: Configure Email Settings
1. Copy the example config file:
   ```bash
   cp config/email-config.php.example config/email-config.php
   ```

2. Edit `config/email-config.php` with your SMTP settings:
   ```php
   define('SMTP_ENABLED', true);
   define('SMTP_HOST', 'smtp.gmail.com');
   define('SMTP_PORT', 587);
   define('SMTP_USERNAME', 'your-email@gmail.com');
   define('SMTP_PASSWORD', 'your-app-password');
   define('SMTP_ENCRYPTION', 'tls');
   ```

#### Step 3: For Gmail Users
1. Enable 2-Step Verification on your Google account
2. Generate an App Password:
   - Go to: https://myaccount.google.com/apppasswords
   - Create a new app password for "Mail"
   - Use this password as `SMTP_PASSWORD`

#### Step 4: Update email.php
Uncomment the `sendEmailSMTP()` function in `includes/email.php` and modify `sendEmail()` to use it when SMTP is enabled.

### Option 3: Use Email Service (Best for Production)
For production, consider using email services like:

1. **SendGrid** (Free tier: 100 emails/day)
2. **Mailgun** (Free tier: 5,000 emails/month)
3. **Amazon SES** (Pay as you go)
4. **Postmark** (Free tier: 100 emails/month)

These services provide:
- Better deliverability
- Email tracking
- Analytics
- SPF/DKIM configuration

## Testing Email Functionality

### Test Registration Flow:
1. Go to registration page
2. Fill in the form
3. Submit registration
4. Check your email inbox (and spam folder)
5. Click the verification link
6. You should be redirected to login page

### Debug Email Issues:
1. Check PHP error logs: `error_log` entries will show email sending status
2. Check server mail logs
3. Verify SMTP credentials are correct
4. Test with a real email address (not localhost)

## Troubleshooting

### Emails not sending:
- **Check server mail configuration**: Some servers need mail server setup
- **Check spam folder**: Emails might be marked as spam
- **Verify email address**: Make sure the recipient email is valid
- **Check error logs**: Look for email-related errors in PHP error log

### Emails going to spam:
- **Configure SPF record**: Add your server IP to SPF records
- **Configure DKIM**: Set up DKIM signing for your domain
- **Use SMTP**: SMTP from a reputable provider reduces spam issues
- **Use email service**: Services like SendGrid handle deliverability

### For Local Development (XAMPP/WAMP):
The `mail()` function won't work locally. Options:
1. Use SMTP with Gmail or another provider
2. Use a mail testing tool like MailHog or Mailtrap
3. Use an email service with a free tier

## Files Modified/Created:
1. `includes/email.php` - New email sending functions
2. `includes/auth.php` - Updated to send verification emails
3. `config/email-config.php.example` - Email configuration template

## Next Steps:
1. Choose your email setup method (Option 1, 2, or 3)
2. Configure the email settings
3. Test registration with a real email address
4. Monitor email delivery and adjust as needed

## Important Notes:
- The email system is now functional but needs proper configuration
- For production, use SMTP or an email service
- Always test with real email addresses
- Monitor email delivery rates and spam complaints

