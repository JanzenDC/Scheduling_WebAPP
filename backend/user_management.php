<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

require '../config.php';
require_once('../vendor/autoload.php');

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
require_once('../vendor/autoload.php');
switch ($action) {
    case 'fetch_all':
        $query = "
            SELECT 
                u.user_id, 
                u.fname, 
                u.mname, 
                u.lname, 
                u.email, 
                r.role_name 
            FROM 
                users u
            LEFT JOIN 
                user_roles ur ON u.user_id = ur.user_id
            LEFT JOIN 
                roles r ON ur.role_id = r.role_id
        ";
        $result = mysqli_query($conn, $query);
    
        if ($result) {
            $users = mysqli_fetch_all($result, MYSQLI_ASSOC);
            $response['success'] = true;
            $response['data'] = $users;
            $response['message'] = 'Users fetched successfully.';
        } else {
            $response['message'] = 'Error fetching users: ' . mysqli_error($conn);
        }
        break;

    case 'fetch_single': 
        $userId = mysqli_real_escape_string($conn, $_GET['user_id'] ?? '');
        
        if (!$userId) {
            $response['message'] = 'User ID is required.';
            break;
        }
    
        // Query to fetch user and their roles
        $query = "
            SELECT u.*, ur.role_id
            FROM users u
            LEFT JOIN user_roles ur ON u.user_id = ur.user_id
            WHERE u.user_id = '$userId'
        ";
        $result = mysqli_query($conn, $query);
    
        if ($result && mysqli_num_rows($result) > 0) {
            $userData = mysqli_fetch_assoc($result);
            
            // Prepare the roles array if there are multiple roles
            $roles = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $roles[] = $row['role_id'];
            }
            
            $response['success'] = true;
            $response['data'] = $userData;
            $response['data']['roles'] = $roles;  // Add roles to the response data
            $response['message'] = 'User fetched successfully.';
        } else {
            $response['message'] = 'User not found.';
        }
        break;
        

    case 'create':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            $response['message'] = 'Invalid data provided.';
            break;
        }
    
        $fname = mysqli_real_escape_string($conn, $data['fname'] ?? '');
        $mname = mysqli_real_escape_string($conn, $data['mname'] ?? '');
        $lname = mysqli_real_escape_string($conn, $data['lname'] ?? '');
        $email = mysqli_real_escape_string($conn, $data['email'] ?? '');
        $phone = mysqli_real_escape_string($conn, $data['phone_number'] ?? '');
        $address = mysqli_real_escape_string($conn, $data['address'] ?? '');
        $city = mysqli_real_escape_string($conn, $data['city'] ?? '');
        $state = mysqli_real_escape_string($conn, $data['state'] ?? '');
        $country = mysqli_real_escape_string($conn, $data['country'] ?? '');
    
        $password = bin2hex(random_bytes(8));
    
        mysqli_begin_transaction($conn);
    
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $query = "INSERT INTO users (fname, mname, lname, email, phone_number, address, city, state, country, password) 
                        VALUES ('$fname', '$mname', '$lname', '$email', '$phone', '$address', '$city', '$state', '$country', '$hashedPassword')";
            
            if (mysqli_query($conn, $query)) {
                $userId = mysqli_insert_id($conn);
    
                $emailContent = "
                    <html>
                        <head>
                            <style>
                                body { font-family: Arial, sans-serif; }
                                .container { width: 100%; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f4f4f4; border-radius: 8px; }
                                h2 { color: #333; }
                                p { font-size: 16px; color: #555; }
                                .button { display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; }
                                .footer { text-align: center; font-size: 12px; color: #999; }
                            </style>
                        </head>
                        <body>
                            <div class='container'>
                                <h2>Welcome, $fname $lname!</h2>
                                <p>We are excited to have you as part of our community. Below are your account details:</p>
                                <ul>
                                    <li><strong>Email:</strong> $email</li>
                                    <li><strong>Password:</strong> $password</li>
                                </ul>
                                <p>Use the above credentials to login to your account and get started.</p>
                                <p>If you have any questions, feel free to reach out to our support team.</p>
                                <p>Best regards,<br>Your Company Name</p>
                                <div class='footer'>
                                    <p>&copy; 2025 Your Company Name. All rights reserved.</p>
                                </div>
                            </div>
                        </body>
                    </html>";
    
                $sendSmtpEmail = new SendSmtpEmail([
                    'sender' => [
                        'email' => 'janzendelacruz28@gmail.com',  // Add the sender email here
                        'name' => 'NexusPH'
                    ],
                    'to' => [[
                        'email' => $email,
                        'name' => "$fname $lname"
                    ]],
                    'subject' => 'Welcome to NexusPH!',
                    'htmlContent' => $emailContent,
                    'headers' => [
                        'Some-Custom-Header' => 'unique-id-1234'
                    ]
                ]);
    
                try {
                    $result = $apiInstance->sendTransacEmail($sendSmtpEmail);
                    
                    mysqli_commit($conn);
                    
                    $response['success'] = true;
                    $response['message'] = 'User created successfully and welcome email sent.';
                    $response['data'] = [
                        'user_id' => $userId,
                        'email_result' => $result
                    ];
                } catch (Exception $e) {
                    error_log("Email sending failed: " . $e->getMessage());
                    mysqli_commit($conn);
                    
                    $response['success'] = true;
                    $response['message'] = $e->getMessage();
                    $response['data'] = ['user_id' => $userId];
                }
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $response['message'] = 'Error creating user: ' . $e->getMessage();
        }
        break;
    case 'regeneratePassword':
        $data = json_decode(file_get_contents('php://input'), true);
    
        if (!isset($data['user_id'])) {
            $response['message'] = 'User ID is missing.';
            break;
        }
    
        $userId = mysqli_real_escape_string($conn, $data['user_id']);
        
        // Generate a new password
        $newPassword = bin2hex(random_bytes(8));  // Random 8-character password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
        // Update password in the database
        $query = "UPDATE users SET password = '$hashedPassword' WHERE user_id = '$userId'";
        
        if (mysqli_query($conn, $query)) {
            // Fetch user details (email) for sending the new password
            $userQuery = "SELECT email, fname, lname FROM users WHERE user_id = '$userId'";
            $result = mysqli_query($conn, $userQuery);
            $user = mysqli_fetch_assoc($result);
    
            $email = $user['email'];
            $fname = $user['fname'];
            $lname = $user['lname'];
    
            // Construct email content
            $emailContent = "
                <html>
                    <head>
                        <style>
                            body { font-family: Arial, sans-serif; }
                            .container { width: 100%; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f4f4f4; border-radius: 8px; }
                            h2 { color: #333; }
                            p { font-size: 16px; color: #555; }
                            .footer { text-align: center; font-size: 12px; color: #999; }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <h2>Hi, $fname $lname!</h2>
                            <p>Your password has been reset. Below are your new account details:</p>
                            <ul>
                                <li><strong>Email:</strong> $email</li>
                                <li><strong>New Password:</strong> $newPassword</li>
                            </ul>
                            <p>Use the new password to log in to your account.</p>
                            <p>If you have any issues, feel free to contact our support team.</p>
                            <p>Best regards,<br>Your Company Name</p>
                            <div class='footer'>
                                <p>&copy; 2025 Your Company Name. All rights reserved.</p>
                            </div>
                        </div>
                    </body>
                </html>";
    
            // Send the email
            $sendSmtpEmail = new SendSmtpEmail([
                'sender' => [
                    'email' => 'no-reply@yourdomain.com',  // Replace with your sender email
                    'name' => 'Your Company Name'
                ],
                'to' => [[
                    'email' => $email,
                    'name' => "$fname $lname"
                ]],
                'subject' => 'Your Password has been Reset',
                'htmlContent' => $emailContent,
                'headers' => [
                    'Some-Custom-Header' => 'unique-id-1234'
                ]
            ]);
    
            try {
                $result = $apiInstance->sendTransacEmail($sendSmtpEmail);
                $response['success'] = true;
                $response['message'] = 'Password has been reset and email sent to the user.';
                $response['data'] = [
                    'user_id' => $userId,
                    'email_result' => $result
                ];
            } catch (Exception $e) {
                error_log("Email sending failed: " . $e->getMessage());
                $response['success'] = false;
                $response['message'] = 'Error sending the email.';
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'Error updating password.';
        }
        break;
               

    case 'update':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['user_id'])) {
            $response['message'] = 'Invalid data provided.';
            break;
        }

        $userId = mysqli_real_escape_string($conn, $data['user_id']);
        $fname = mysqli_real_escape_string($conn, $data['fname'] ?? '');
        $mname = mysqli_real_escape_string($conn, $data['mname'] ?? '');
        $lname = mysqli_real_escape_string($conn, $data['lname'] ?? '');
        $email = mysqli_real_escape_string($conn, $data['email'] ?? '');
        $phone = mysqli_real_escape_string($conn, $data['phone_number'] ?? '');
        $address = mysqli_real_escape_string($conn, $data['address'] ?? '');
        $city = mysqli_real_escape_string($conn, $data['city'] ?? '');
        $state = mysqli_real_escape_string($conn, $data['state'] ?? '');
        $country = mysqli_real_escape_string($conn, $data['country'] ?? '');

        $query = "UPDATE users SET 
                  fname = '$fname',
                  mname = '$mname',
                  lname = '$lname',
                  email = '$email',
                  phone_number = '$phone',
                  address = '$address',
                  city = '$city',
                  state = '$state',
                  country = '$country'
                  WHERE user_id = '$userId'";

        if (mysqli_query($conn, $query)) {
            $response['success'] = true;
            $response['message'] = 'User updated successfully.';
        } else {
            $response['message'] = 'Error updating user: ' . mysqli_error($conn);
        }
        break;

    case 'delete':
        $userId = mysqli_real_escape_string($conn, $_POST['user_id'] ?? '');
        
        if (!$userId) {
            $response['message'] = 'User ID is required.';
            break;
        }

        $query = "DELETE FROM users WHERE user_id = '$userId'";

        if (mysqli_query($conn, $query)) {
            $response['success'] = true;
            $response['message'] = 'User deleted successfully.';
        } else {
            $response['message'] = 'Error deleting user: ' . mysqli_error($conn);
        }
        break;

    default:
        $response['message'] = 'Invalid action.';
        break;
}

echo json_encode($response);
mysqli_close($conn);
?>