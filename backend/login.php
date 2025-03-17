<?php

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

require "../config.php";

$response = [
    'success' => false,
    'message' => '',
    'data' => null,
];

$action = $_GET['action'] ?? '';

session_start();

switch ($action) {
    case 'login':
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $response['message'] = 'Email and password are required.';
        } else {
            $email = mysqli_real_escape_string($conn, $email);

            $query = "SELECT * FROM users WHERE email = '$email'";
            $result = mysqli_query($conn, $query);

            if (mysqli_num_rows($result) > 0) {
                $user = mysqli_fetch_assoc($result);
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user'] = [
                        'id' => $user['user_id'],
                        'first_name' => $user['fname'],
                        'last_name' => $user['lname'],
                        'email' => $user['email']
                    ];
                    $response['success'] = true;
                    $response['message'] = 'Login successful.';
                    $response['data'] = $user;
                } else {
                    $response['message'] = 'Invalid email or password.';
                }
            } else {
                $response['message'] = 'Invalid email or password.';
            }
        }
        break;

        case 'register':
            // Gather all form data
            $firstName       = $_POST['firstName']       ?? '';
            $middleName      = $_POST['middleName']      ?? '';
            $lastName        = $_POST['lastName']        ?? '';
            $email           = $_POST['email']           ?? '';
            $password        = $_POST['password']        ?? '';
            $confirmPassword = $_POST['confirmPassword'] ?? '';
        
            // Additional fields
            $hasDesignation  = $_POST['hasDesignation']  ?? ''; // "yes" or "no"
            $designation     = $_POST['designation']     ?? '';
            $numberOfDeals   = $_POST['numberOfDeals']   ?? '';
        
            // Basic validation
            if (empty($firstName) || empty($lastName) || empty($email) || empty($password) || empty($confirmPassword)) {
                $response['message'] = 'All required fields must be filled.';
            } elseif ($password !== $confirmPassword) {
                $response['message'] = 'Passwords do not match.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $response['message'] = 'Invalid email format.';
            } else {
                // Escape all inputs to prevent SQL injection
                $firstName       = mysqli_real_escape_string($conn, $firstName);
                $middleName      = mysqli_real_escape_string($conn, $middleName);
                $lastName        = mysqli_real_escape_string($conn, $lastName);
                $email           = mysqli_real_escape_string($conn, $email);
                $password        = mysqli_real_escape_string($conn, $password);
                $hasDesignation  = mysqli_real_escape_string($conn, $hasDesignation);
                $designation     = mysqli_real_escape_string($conn, $designation);
                $numberOfDeals   = mysqli_real_escape_string($conn, $numberOfDeals);
        
                // Check if the email already exists
                $checkQuery  = "SELECT * FROM users WHERE email = '$email'";
                $checkResult = mysqli_query($conn, $checkQuery);
        
                if (mysqli_num_rows($checkResult) > 0) {
                    $response['message'] = 'Email is already registered.';
                } else {
                    // Hash the password
                    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        
                    // Insert into the database (make sure your users table has these columns!)
                    $insertQuery = "
                        INSERT INTO users 
                            (fname, mname, lname, email, password, has_designation, designation, number_of_deals)
                        VALUES
                            ('$firstName', '$middleName', '$lastName', '$email', '$hashedPassword', '$hasDesignation', '$designation', '$numberOfDeals')
                    ";
        
                    if (mysqli_query($conn, $insertQuery)) {
                        $response['success'] = true;
                        $response['message'] = 'Registration successful.';
                        $response['data'] = [
                            'firstName'      => $firstName,
                            'middleName'     => $middleName,
                            'lastName'       => $lastName,
                            'email'          => $email,
                            'hasDesignation' => $hasDesignation,
                            'designation'    => $designation,
                            'numberOfDeals'  => $numberOfDeals
                        ];
                    } else {
                        $response['message'] = 'Error registering user. Please try again later.';
                    }
                }
            }
            break;
        

    default:
        $response['message'] = 'Invalid action.';
        break;
}

echo json_encode($response);

mysqli_close($conn);
?>
