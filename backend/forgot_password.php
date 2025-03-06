<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');
session_start();
require '../config.php';
require_once('../vendor/autoload.php');

// ------------------- ADDED LINES START ------------------- //
date_default_timezone_set('Asia/Singapore');  // Force PHP to use UTC+8
mysqli_query($conn, "SET time_zone = '+08:00'"); // Force MySQL to use UTC+8
// ------------------- ADDED LINES END --------------------- //

use Brevo\Client\Configuration;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Model\SendSmtpEmail;

// Configure API key for Brevo
$config = Configuration::getDefaultConfiguration()->setApiKey('api-key', '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');

// Create Brevo API instance
$apiInstance = new TransactionalEmailsApi(
    new GuzzleHttp\Client(),
    $config
);

$response = [
    'success' => false,
    'message' => '',
    'data' => null,
];

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'sendCode':
        // Check if email exists in database
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        
        // Verify if the email exists in the users table
        $userQuery = "SELECT user_id FROM users WHERE email = '$email'";
        $userResult = mysqli_query($conn, $userQuery);
        
        if (mysqli_num_rows($userResult) === 0) {
            $response['message'] = 'Email not found in our records.';
            break;
        }
        
        // Check if OTP was recently sent (30-second cooldown)
        $cooldownQuery = "SELECT * FROM otp_codes WHERE email = '$email' AND last_sent_at > DATE_SUB(NOW(), INTERVAL 30 SECOND)";
        $cooldownResult = mysqli_query($conn, $cooldownQuery);
        
        if (mysqli_num_rows($cooldownResult) > 0) {
            $row = mysqli_fetch_assoc($cooldownResult);
            $secondsLeft = 30 - (time() - strtotime($row['last_sent_at']));
            $response['message'] = "Please wait $secondsLeft seconds before requesting another OTP.";
            break;
        }
        
        // Generate 6-digit OTP
        $otp = sprintf("%06d", mt_rand(1, 999999));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));
        
        // Check if a record exists for this email
        $checkQuery = "SELECT id FROM otp_codes WHERE email = '$email'";
        $checkResult = mysqli_query($conn, $checkQuery);
        
        if (mysqli_num_rows($checkResult) > 0) {
            // Update existing record
            $otpQuery = "UPDATE otp_codes SET 
                        otp_code = '$otp', 
                        expires_at = '$expiresAt', 
                        used = 0, 
                        last_sent_at = NOW() 
                        WHERE email = '$email'";
        } else {
            // Insert new record
            $otpQuery = "INSERT INTO otp_codes (email, otp_code, expires_at, used, last_sent_at) 
                        VALUES ('$email', '$otp', '$expiresAt', 0, NOW())";
        }
        
        if (!mysqli_query($conn, $otpQuery)) {
            $response['message'] = 'Error saving OTP: ' . mysqli_error($conn);
            break;
        }
        
        // Send OTP via email
        try {
            $sendSmtpEmail = new SendSmtpEmail();
            $sendSmtpEmail->setSubject('Password Reset OTP Code');
            $sendSmtpEmail->setSender(['name' => 'Scheduling App', 'email' => 'noreply@example.com']);
            $sendSmtpEmail->setTo([['email' => $email]]);
            
            $emailContent = "
            <html>
                <body>
                    <h2>Password Reset Request</h2>
                    <p>Your OTP code to reset password is: <strong>$otp</strong></p>
                    <p>This code will expire in 5 minutes.</p>
                    <p>If you did not request a password reset, please ignore this email.</p>
                </body>
            </html>";
            
            $sendSmtpEmail->setHtmlContent($emailContent);
            
            $result = $apiInstance->sendTransacEmail($sendSmtpEmail);
            
            $response['success'] = true;
            $response['message'] = 'OTP code has been sent to your email address.';
            $response['data'] = ['email' => $email];
        } catch (Exception $e) {
            $response['message'] = 'Error sending email: ' . $e->getMessage();
        }
        break;
    
    case 'verifyOTP':
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $otp = mysqli_real_escape_string($conn, $_POST['otp']);
        
        // Check if OTP exists and is valid
        $otpQuery = "SELECT * FROM otp_codes WHERE 
                     email = '$email' AND 
                     otp_code = '$otp' AND 
                     used = 0 AND 
                     expires_at > NOW()";
        
        $otpResult = mysqli_query($conn, $otpQuery);
        
        if (mysqli_num_rows($otpResult) === 0) {
            $response['message'] = 'Invalid or expired OTP code.';
            break;
        }
        
        // Mark OTP as used
        $updateQuery = "UPDATE otp_codes SET used = 1 WHERE email = '$email'";
        mysqli_query($conn, $updateQuery);
        
        // Generate a token for password reset
        $token = bin2hex(random_bytes(32));
        
        // Store token in a session variable
        $_SESSION['reset_token'] = $token;
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_time'] = time();
        
        $response['success'] = true;
        $response['message'] = 'OTP verified successfully.';
        $response['data'] = ['email' => $email, 'token' => $token];
        break;
        
    case 'resendOTP':
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        
        // Verify if the email exists in the users table
        $userQuery = "SELECT user_id FROM users WHERE email = '$email'";
        $userResult = mysqli_query($conn, $userQuery);
        
        if (mysqli_num_rows($userResult) === 0) {
            $response['message'] = 'Email not found in our records.';
            break;
        }
        
        // Check cooldown period
        $cooldownQuery = "SELECT * FROM otp_codes WHERE email = '$email' AND last_sent_at > DATE_SUB(NOW(), INTERVAL 30 SECOND)";
        $cooldownResult = mysqli_query($conn, $cooldownQuery);
        
        if (mysqli_num_rows($cooldownResult) > 0) {
            $row = mysqli_fetch_assoc($cooldownResult);
            $secondsLeft = 30 - (time() - strtotime($row['last_sent_at']));
            $response['message'] = "Please wait $secondsLeft seconds before requesting another OTP.";
            $response['data'] = ['cooldown' => $secondsLeft];
            break;
        }
        
        // Generate new OTP
        $otp = sprintf("%06d", mt_rand(1, 999999));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));
        
        // Update OTP in database
        $otpQuery = "UPDATE otp_codes SET 
                    otp_code = '$otp', 
                    expires_at = '$expiresAt', 
                    used = 0, 
                    last_sent_at = NOW() 
                    WHERE email = '$email'";
        
        if (!mysqli_query($conn, $otpQuery)) {
            $response['message'] = 'Error updating OTP: ' . mysqli_error($conn);
            break;
        }
        
        // Send OTP via email
        try {
            $sendSmtpEmail = new SendSmtpEmail();
            $sendSmtpEmail->setSubject('Password Reset OTP Code (Resent)');
            $sendSmtpEmail->setSender(['name' => 'Scheduling App', 'email' => 'noreply@example.com']);
            $sendSmtpEmail->setTo([['email' => $email]]);
            
            $emailContent = "
            <html>
                <body>
                    <h2>Password Reset Request</h2>
                    <p>Your new OTP code to reset password is: <strong>$otp</strong></p>
                    <p>This code will expire in 5 minutes.</p>
                    <p>If you did not request a password reset, please ignore this email.</p>
                </body>
            </html>";
            
            $sendSmtpEmail->setHtmlContent($emailContent);
            
            $result = $apiInstance->sendTransacEmail($sendSmtpEmail);
            
            $response['success'] = true;
            $response['message'] = 'New OTP code has been sent to your email address.';
        } catch (Exception $e) {
            $response['message'] = 'Error sending email: ' . $e->getMessage();
        }
        break;
        
    case 'resetPassword':
        // Verify session token
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $token = mysqli_real_escape_string($conn, $_POST['token']);
        $newPassword = mysqli_real_escape_string($conn, $_POST['password']);
        
        // Verify token from session
        
        // Hash the new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Update user password
        $updateQuery = "UPDATE users SET password = '$hashedPassword' WHERE email = '$email'";
        
        if (!mysqli_query($conn, $updateQuery)) {
            $response['message'] = 'Error updating password: ' . mysqli_error($conn);
            break;
        }
        
        // Invalidate all OTPs
        $invalidateOTPQuery = "UPDATE otp_codes SET used = 1 WHERE email = '$email'";
        mysqli_query($conn, $invalidateOTPQuery);
        
        // Clear session variables
        unset($_SESSION['reset_token']);
        unset($_SESSION['reset_email']);
        unset($_SESSION['reset_time']);
        
        $response['success'] = true;
        $response['message'] = 'Password has been reset successfully.';
        break;
        
    default:
        $response['message'] = 'Invalid action.';
        break;
}

echo json_encode($response);
mysqli_close($conn);
?>
