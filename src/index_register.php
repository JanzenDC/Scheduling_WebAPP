<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <?php include_once 'header_cdn.php'; ?>
    <script>
        $(document).ready(function() {
            // Show/Hide Password Toggling
            // 1) For the main password field
            $('#togglePassword').on('click', function() {
                let passwordField = $('#password');
                let icon = $('#toggleIcon');
                if (passwordField.attr('type') === 'password') {
                    passwordField.attr('type', 'text');
                    icon.removeClass('fa-eye').addClass('fa-eye-slash');
                } else {
                    passwordField.attr('type', 'password');
                    icon.removeClass('fa-eye-slash').addClass('fa-eye');
                }
            });

            // 2) For the confirm password field
            $('#toggleConfirmPassword').on('click', function() {
                let passwordField = $('#confirmPassword');
                let icon = $('#toggleConfirmIcon');
                if (passwordField.attr('type') === 'password') {
                    passwordField.attr('type', 'text');
                    icon.removeClass('fa-eye').addClass('fa-eye-slash');
                } else {
                    passwordField.attr('type', 'password');
                    icon.removeClass('fa-eye-slash').addClass('fa-eye');
                }
            });

            // Registration form submission
            $('#registerForm').on('submit', function(event) {
                event.preventDefault();
                var firstName = $('#firstName').val();
                var middleName = $('#middleName').val();
                var lastName = $('#lastName').val();
                var email = $('#email').val();
                var password = $('#password').val();
                var confirmPassword = $('#confirmPassword').val();

                // Check if passwords match
                if (password !== confirmPassword) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Registration Failed',
                        text: 'Passwords do not match'
                    });
                    return;
                }

                // Make AJAX call to register
                $.ajax({
                    url: '../backend/login.php?action=register',
                    type: 'POST',
                    data: {
                        firstName: firstName,
                        middleName: middleName,
                        lastName: lastName,
                        email: email,
                        password: password,
                        confirmPassword: confirmPassword
                    },
                    success: function(response) {
                        console.log(response);
                        if(response.success === true){
                            Swal.fire({
                                icon: 'success',
                                title: 'Registration Successful',
                                text: response.message
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    // Show loading then refresh
                                    Swal.fire({
                                        title: 'Loading...',
                                        allowOutsideClick: false,
                                        didOpen: () => {
                                            Swal.showLoading();
                                        }
                                    });
                                    setTimeout(() => {
                                        location.reload();
                                    }, 2000);
                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Registration Failed',
                                text: response.message
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Registration Failed',
                            text: 'Error: ' + xhr.responseText
                        });
                    }
                });
            });
        });
    </script>
</head>

<body class="flex items-center justify-center min-h-screen" 
      style="background-image: url('../resources/images/background.jpg'), linear-gradient(to right, rgba(0, 68, 137, 0.7), rgba(0, 204, 221, 0.7));
             background-size: cover;
             background-position: center center;
             background-attachment: fixed;">
  <div class="bg-white shadow-lg rounded-lg p-8 max-w-md w-full mt-10">
    <h2 class="text-2xl font-bold text-center mb-6">Register</h2>
    <form id="registerForm">
      <!-- First Name -->
      <div class="mb-4">
        <label class="block text-gray-700 text-sm font-bold mb-2" for="firstName">
          First Name
        </label>
        <input
          class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
          id="firstName"
          name="firstName"
          type="text"
          placeholder="First Name"
        />
      </div>

      <!-- Middle Name -->
      <div class="mb-4">
        <label class="block text-gray-700 text-sm font-bold mb-2" for="middleName">
          Middle Name
        </label>
        <input
          class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
          id="middleName"
          name="middleName"
          type="text"
          placeholder="Middle Name"
        />
      </div>

      <!-- Last Name -->
      <div class="mb-4">
        <label class="block text-gray-700 text-sm font-bold mb-2" for="lastName">
          Last Name
        </label>
        <input
          class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
          id="lastName"
          name="lastName"
          type="text"
          placeholder="Last Name"
        />
      </div>

      <!-- Email -->
      <div class="mb-4">
        <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
          Email
        </label>
        <input
          class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
          id="email"
          name="email"
          type="email"
          placeholder="Email"
        />
      </div>

      <!-- Password Field with Eye Icon -->
      <div class="mb-4">
        <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
          Password
        </label>
        <div class="relative">
          <input
            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
            id="password"
            name="password"
            type="password"
            placeholder="Password"
          />
          <button
            id="togglePassword"
            type="button"
            class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-700 hover:text-gray-900"
          >
            <i id="toggleIcon" class="fa fa-eye"></i>
          </button>
        </div>
      </div>

      <!-- Confirm Password Field with Eye Icon -->
      <div class="mb-4">
        <label class="block text-gray-700 text-sm font-bold mb-2" for="confirmPassword">
          Confirm Password
        </label>
        <div class="relative">
          <input
            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
            id="confirmPassword"
            name="confirmPassword"
            type="password"
            placeholder="Confirm Password"
          />
          <button
            id="toggleConfirmPassword"
            type="button"
            class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-700 hover:text-gray-900"
          >
            <i id="toggleConfirmIcon" class="fa fa-eye"></i>
          </button>
        </div>
      </div>

      <!-- Submit Button -->
      <div class="flex items-center justify-center">
        <button
          class="bg-[#044389] hover:bg-[#3cc5dd] text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
          type="submit"
        >
          Register
        </button>
      </div>
    </form>
    
    <!-- Login Link -->
    <div class="text-center mt-6">
      <p class="text-gray-700 text-sm">
        Already have an account?
        <a
          class="text-[#044389] hover:text-indigo-800 font-bold"
          href="../index.php"
        >
          Login
        </a>
      </p>
    </div>
  </div>
</body>
</html>
