<?php

session_start();
if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_NAME'] == '127.0.0.1') {

    $baseUrl = 'http://localhost/Scheduling_WebAPP/';
} else {
    $baseUrl = 'https://wealthinvestproperties.com/';
}

if (isset($_SESSION['user']['id'])) {
    header('Location: ' . $baseUrl . 'src/pages/dashboard.php?page=dashboard');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <?php include_once 'src/header_cdn.php'; ?>
    <script>
    $(document).ready(function() {
        var baseUrl;

        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
            baseUrl = 'http://localhost/Scheduling_WebAPP/';
        } else {
            baseUrl = 'https://wealthinvestproperties.com/';
        }

        $('#loginForm').on('submit', function(event) {
            event.preventDefault();

            var email = $('#email').val();
            var password = $('#password').val();
            
            $.ajax({
                url: baseUrl + 'backend/login.php?action=login',
                type: 'POST',
                data: {
                    email: email,
                    password: password
                },
                success: function(response) {
                    console.log(response);
                    if (response.success === true) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Login Successful',
                            text: response.message
                        });
                        setTimeout(() => {
                            location.href = baseUrl + 'src/pages/dashboard.php?page=dashboard';
                        }, 1000);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Login Failed',
                            text: response.message
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Login Failed',
                        text: 'Error: ' + xhr.responseText
                    });
                }
            });
        });
    });
</script>


</head>


<!-- <body class="bg-black dark:bg-black text-white">
    <div class="flex items-center justify-center min-h-screen ">
        <div class="bg-gray-800 p-6 rounded-lg shadow-lg w-[500px]">
            <h2 class="text-2xl mb-4">Login</h2>
            <form id="loginForm">
                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium">Email</label>
                    <input type="email" id="email" name="email" class="mt-1 block w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium">Password</label>
                    <input type="password" id="password" name="password" class="mt-1 block w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md">Login</button>
                Don't have an account? <a href="src/index_register.php" class="text-blue-400 hover:underline">Register</a>
            </form>
        </div>
    </div>
</body> -->

<body class="bg-[#044389] flex items-center justify-center min-h-screen">
  <div class="bg-white shadow-lg rounded-lg p-8 max-w-md w-full">
   <div class="flex justify-center mb-6">
    <img alt="Company logo placeholder image" class="w-24 h-24" height="100" src="resources/images/cropped-logo_favicon.png" width="100"/>
    <img alt="Company logo placeholder image" class="w-24 h-24" height="100" src="resources/images/1738888990405-removebg-preview-removebg-preview.png" width="100"/>

   </div>
   <h2 class="text-2xl font-bold text-center mb-6">
    Login to Your Account
   </h2>
   <form id="loginForm">
    <div class="mb-4">
     <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
      Email
     </label>
     <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="email" name="email" placeholder="Email" type="email"/>
    </div>
    <div class="mb-6">
     <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
      Password
     </label>
     <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline" id="password" name="password" placeholder="Password" type="password"/>
    </div>
    <div class="flex items-center justify-center">
     <button class="bg-[#044389] hover:bg-[#3cc5dd] text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit">
      Login
     </button>
    </div>
   </form>
   <div class="text-center mt-6">
    <p class="text-gray-700 text-sm">
     Don't have an account?
     <a href="src/index_register.php" class="text-indigo-600 hover:text-indigo-800 font-bold" href="#">
      Register
     </a>
    </p>
   </div>
  </div>
 </body>


</html>