<?php
/**
 * Email Sending Functions
 * 
 * This file handles sending emails for user registration, verification, etc.
 * 
 * IMPORTANT: For production use, configure SMTP settings below or use a service like:
 * - SendGrid
 * - Mailgun
 * - Amazon SES
 * - PHPMailer with SMTP
 */

/**
 * Send verification email to user
 */
function sendVerificationEmail($email, $firstName, $verificationToken) {
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
    $basePath = defined('BASE_PATH') ? BASE_PATH : '';
    $verificationLink = $baseUrl . $basePath . '/auth/verify.php?key=' . urlencode($verificationToken);
    
    $subject = "Verify Your Email - Green Grocers";
    
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #16a34a; color: white; padding: 20px; text-align: center; }
            .content { background-color: #f9fafb; padding: 30px; }
            .button { display: inline-block; background-color: #16a34a; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Welcome to Green Grocers!</h1>
            </div>
            <div class='content'>
                <h2>Hello " . htmlspecialchars($firstName) . ",</h2>
                <p>Thank you for registering with Green Grocers. Please verify your email address by clicking the button below:</p>
                <div style='text-align: center;'>
                    <a href='" . htmlspecialchars($verificationLink) . "' class='button'>Verify Email Address</a>
                </div>
                <p>Or copy and paste this link into your browser:</p>
                <p style='word-break: break-all; color: #16a34a;'>" . htmlspecialchars($verificationLink) . "</p>
                <p>This link will expire in 24 hours.</p>
                <p>If you didn't create an account with Green Grocers, please ignore this email.</p>
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " Green Grocers. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($email, $subject, $message, $firstName);
}

/**
 * Send email using PHP mail() function
 * 
 * NOTE: For production, you should use SMTP or an email service.
 * The basic mail() function may not work on all servers and emails may go to spam.
 * 
 * To use SMTP, install PHPMailer:
 * composer require phpmailer/phpmailer
 * 
 * Then modify this function to use PHPMailer with SMTP settings.
 */
function sendEmail($to, $subject, $htmlMessage, $toName = '') {
    // Load email configuration if available
    $configFile = __DIR__ . '/../config/email-config.php';
    if (file_exists($configFile)) {
        require_once $configFile;
    }
    
    // Email configuration (can be overridden by config file)
    $fromEmail = (defined('EMAIL_FROM_ADDRESS') && constant('EMAIL_FROM_ADDRESS')) ? constant('EMAIL_FROM_ADDRESS') : 'noreply@green-grocers.com';
    $fromName = (defined('EMAIL_FROM_NAME') && constant('EMAIL_FROM_NAME')) ? constant('EMAIL_FROM_NAME') : 'Green Grocers';
    
    // Check if SMTP is enabled
    $smtpEnabled = (defined('SMTP_ENABLED') && constant('SMTP_ENABLED') === true);
    
    if ($smtpEnabled) {
        // Use SMTP to send email
        return sendEmailSMTP($to, $subject, $htmlMessage, $toName, $fromEmail, $fromName);
    } else {
        // Use PHP mail() function
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: " . $fromName . " <" . $fromEmail . ">" . "\r\n";
        $headers .= "Reply-To: " . $fromEmail . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        
        // Try to send email
        $sent = @mail($to, $subject, $htmlMessage, $headers);
        
        if ($sent) {
            error_log("Email sent successfully to: " . $to);
            return true;
        } else {
            error_log("Failed to send email to: " . $to);
            return false;
        }
    }
}

/**
 * Send email using SMTP (built-in implementation, no PHPMailer required)
 * Improved version with better error handling and Gmail compatibility
 */
function sendEmailSMTP($to, $subject, $htmlMessage, $toName, $fromEmail, $fromName) {
    // Get SMTP configuration
    $smtpHost = (defined('SMTP_HOST') && constant('SMTP_HOST')) ? constant('SMTP_HOST') : 'smtp.gmail.com';
    $smtpPort = (defined('SMTP_PORT') && constant('SMTP_PORT')) ? (int)constant('SMTP_PORT') : 587;
    $smtpUsername = (defined('SMTP_USERNAME') && constant('SMTP_USERNAME')) ? constant('SMTP_USERNAME') : '';
    $smtpPassword = (defined('SMTP_PASSWORD') && constant('SMTP_PASSWORD')) ? constant('SMTP_PASSWORD') : '';
    $smtpEncryption = (defined('SMTP_ENCRYPTION') && constant('SMTP_ENCRYPTION')) ? constant('SMTP_ENCRYPTION') : 'tls';
    
    if (empty($smtpUsername) || empty($smtpPassword)) {
        error_log("SMTP Error: Username or password not configured");
        return false;
    }
    
    // Helper function to read SMTP response (optimized for speed)
    function readSMTPResponse($socket) {
        $response = '';
        $maxLines = 10; // Limit lines to prevent infinite loops
        $lineCount = 0;
        while ($lineCount < $maxLines && ($line = @fgets($socket, 515))) {
            $response .= $line;
            $lineCount++;
            // Check if this is the last line of the response
            if (strlen($line) > 3 && substr($line, 3, 1) == ' ') {
                break;
            }
        }
        return $response;
    }
    
    // Helper function to send SMTP command and check response
    function sendSMTPCommand($socket, $command, $expectedCode, $errorMsg) {
        fputs($socket, $command . "\r\n");
        $response = readSMTPResponse($socket);
        $code = substr($response, 0, 3);
        if ($code != $expectedCode) {
            error_log("SMTP Error: {$errorMsg} - " . trim($response));
            return false;
        }
        return true;
    }
    
    try {
        // Create socket connection with proper context for TLS
        // Reduced timeout for faster connection
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
                'crypto_method' => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT
            ]
        ]);
        
        // Reduced connection timeout from 30 to 10 seconds
        $socket = @stream_socket_client(
            "tcp://{$smtpHost}:{$smtpPort}",
            $errno,
            $errstr,
            10, // Reduced timeout for faster failure
            STREAM_CLIENT_CONNECT,
            $context
        );
        
        if (!$socket) {
            error_log("SMTP Error: Could not connect to {$smtpHost}:{$smtpPort} - {$errstr} ({$errno})");
            return false;
        }
        
        // Set shorter timeouts for faster operation
        stream_set_timeout($socket, 5, 0); // 5 second timeout
        
        // Read server greeting (with timeout check)
        $response = readSMTPResponse($socket);
        if (substr($response, 0, 3) != '220') {
            error_log("SMTP Error: Server greeting failed - " . trim($response));
            fclose($socket);
            return false;
        }
        
        // Send EHLO (skip reading all response lines for speed)
        fputs($socket, "EHLO " . $smtpHost . "\r\n");
        $response = readSMTPResponse($socket);
        if (substr($response, 0, 3) != '250') {
            error_log("SMTP Error: EHLO failed - " . trim($response));
            fclose($socket);
            return false;
        }
        
        // Start TLS if needed
        if ($smtpEncryption === 'tls') {
            fputs($socket, "STARTTLS\r\n");
            $response = readSMTPResponse($socket);
            if (substr($response, 0, 3) != '220') {
                error_log("SMTP Error: STARTTLS failed - " . trim($response));
                fclose($socket);
                return false;
            }
            
            // Enable crypto with specific TLS version for speed
            if (!@stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT)) {
                error_log("SMTP Error: Failed to enable TLS encryption");
                fclose($socket);
                return false;
            }
            
            // Send EHLO again after TLS (skip reading all lines)
            fputs($socket, "EHLO " . $smtpHost . "\r\n");
            $response = readSMTPResponse($socket);
            if (substr($response, 0, 3) != '250') {
                error_log("SMTP Error: EHLO after TLS failed - " . trim($response));
                fclose($socket);
                return false;
            }
        }
        
        // Authenticate
        if (!sendSMTPCommand($socket, "AUTH LOGIN", '334', 'AUTH LOGIN failed')) {
            fclose($socket);
            return false;
        }
        
        // Send username
        fputs($socket, base64_encode($smtpUsername) . "\r\n");
        $response = readSMTPResponse($socket);
        if (substr($response, 0, 3) != '334') {
            error_log("SMTP Error: Username authentication failed - " . trim($response));
            fclose($socket);
            return false;
        }
        
        // Send password
        fputs($socket, base64_encode($smtpPassword) . "\r\n");
        $response = readSMTPResponse($socket);
        if (substr($response, 0, 3) != '235') {
            error_log("SMTP Error: Password authentication failed - " . trim($response));
            error_log("SMTP Debug: Check if app password is correct and 2-Step Verification is enabled");
            fclose($socket);
            return false;
        }
        
        // Set sender (use SMTP username as FROM for Gmail)
        $mailFrom = $smtpUsername; // Gmail requires FROM to match authenticated user
        if (!sendSMTPCommand($socket, "MAIL FROM: <" . $mailFrom . ">", '250', 'MAIL FROM failed')) {
            fclose($socket);
            return false;
        }
        
        // Set recipient
        if (!sendSMTPCommand($socket, "RCPT TO: <" . $to . ">", '250', 'RCPT TO failed')) {
            fclose($socket);
            return false;
        }
        
        // Send data
        if (!sendSMTPCommand($socket, "DATA", '354', 'DATA command failed')) {
            fclose($socket);
            return false;
        }
        
        // Email headers and body
        $emailData = "From: " . $fromName . " <" . $mailFrom . ">\r\n";
        $emailData .= "To: " . ($toName ? $toName . " <" . $to . ">" : $to) . "\r\n";
        $emailData .= "Subject: " . $subject . "\r\n";
        $emailData .= "MIME-Version: 1.0\r\n";
        $emailData .= "Content-Type: text/html; charset=UTF-8\r\n";
        $emailData .= "Content-Transfer-Encoding: 8bit\r\n";
        $emailData .= "X-Mailer: PHP/" . phpversion() . "\r\n";
        $emailData .= "\r\n";
        $emailData .= $htmlMessage . "\r\n";
        $emailData .= ".\r\n";
        
        fputs($socket, $emailData);
        $response = readSMTPResponse($socket);
        if (substr($response, 0, 3) != '250') {
            error_log("SMTP Error: Email sending failed - " . trim($response));
            fclose($socket);
            return false;
        }
        
        // Quit (don't wait for response to speed up)
        @fputs($socket, "QUIT\r\n");
        @fclose($socket);
        
        error_log("SMTP Email sent successfully to: " . $to);
        return true;
        
    } catch (Exception $e) {
        error_log("SMTP Exception: " . $e->getMessage());
        if (isset($socket) && is_resource($socket)) {
            fclose($socket);
        }
        return false;
    }
}

/**
 * Alternative: Send email using SMTP (requires PHPMailer)
 * Uncomment and configure this if you have PHPMailer installed
 */
/*
function sendEmailSMTP($to, $subject, $htmlMessage, $toName = '') {
    require_once __DIR__ . '/../vendor/autoload.php';
    
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    
    $mail = new PHPMailer(true);
    
    try {
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Your SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@gmail.com'; // Your email
        $mail->Password = 'your-app-password'; // Your app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Sender
        $mail->setFrom('noreply@green-grocers.com', 'Green Grocers');
        $mail->addAddress($to, $toName);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlMessage;
        $mail->AltBody = strip_tags($htmlMessage);
        
        $mail->send();
        error_log("Email sent successfully to: " . $to);
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return false;
    }
}
*/

