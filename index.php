<?php
session_start();
if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_NAME'] == '127.0.0.1') {
    $baseUrl = 'http://localhost/Scheduling_WebAPP/';
} else {
    $baseUrl = 'https://yourhosting.com/';
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
            baseUrl = 'https://yourhosting.com/';
        }

        // Toggle Show/Hide Password with Eye Icon
        $('#togglePassword').on('click', function() {
            var passwordField = $('#password');
            var passwordFieldType = passwordField.attr('type');
            var icon = $('#toggleIcon');
            if (passwordFieldType === 'password') {
                passwordField.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                passwordField.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });

        // Handle login form submission
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
                        setTimeout(function() {
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
<body class="flex items-center justify-center min-h-screen"
      style="background-image: url('resources/images/background.jpg'), linear-gradient(to right, rgba(0, 68, 137, 0.7), rgba(0, 204, 221, 0.7));
             background-size: cover;
             background-position: center center;
             background-attachment: fixed;">
  <div class="bg-white shadow-lg rounded-lg p-8 max-w-md w-full">
    <div class="flex justify-center mb-6">
      <img
        src="resources/images/cropped-logo_favicon.png"
        alt="Company logo placeholder image"
        class="w-24 h-24"
        width="100"
        height="100"
      />
      <img
        src="resources/images/1738888990405-removebg-preview-removebg-preview.png"
        alt="Company logo placeholder image"
        class="w-24 h-24"
        width="100"
        height="100"
      />
    </div>
    <h2 class="text-2xl font-bold text-center mb-6">Login to Your Account</h2>
    
    <form id="loginForm">
      <!-- Email Field -->
      <div class="mb-4">
        <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
          Email
        </label>
        <input
          id="email"
          name="email"
          type="email"
          placeholder="Email"
          class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
        />
      </div>
      
      <!-- Password Field with Eye Icon -->
      <div class="mb-6">
        <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
          Password
        </label>
        <div class="relative">
          <input
            id="password"
            name="password"
            type="password"
            placeholder="Password"
            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline"
          />
          <button
            id="togglePassword"
            type="button"
            class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-700 hover:text-gray-900"
          >
            <i id="toggleIcon" class="fa fa-eye -mt-4"></i>
          </button>
        </div>
        <div class="text-end">
          <a href="src/forgot_password.php" class="text-[#044389] hover:text-indigo-800 font-bold">
            Forgot Password?
          </a>
        </div>
      </div>
      
      <!-- Submit Button -->
      <div class="flex items-center justify-center">
        <button
          type="submit"
          class="bg-[#044389] hover:bg-[#3cc5dd] text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
        >
          Login
        </button>
      </div>
    </form>
    <!-- Forgot Password Link -->


    <!-- Registration Link -->
    <div class="text-center mt-6">
      <p class="text-gray-700 text-sm">
        Don't have an account?
        <a
          href="src/index_register.php"
          class="text-[#044389] hover:text-indigo-800 font-bold"
        >
          Register
        </a>
      </p>
    </div>
  </div>
</body>
</html>
