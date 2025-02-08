<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <?php include_once 'header_cdn.php'; ?>
    <script>
        $(document).ready(function() {
            $('#registerForm').on('submit', function(event) {
                event.preventDefault();
                var firstName = $('#firstName').val();
                var middleName = $('#middleName').val();
                var lastName = $('#lastName').val();
                var email = $('#email').val();
                var password = $('#password').val();
                var confirmPassword = $('#confirmPassword').val();

                if (password !== confirmPassword) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Registration Failed',
                        text: 'Passwords do not match'
                    });
                    return;
                }

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
                        // Handle success response
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
                        // Handle error response
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
<!-- <body class="bg-white dark:bg-black text-white">
    <div class="flex items-center justify-center min-h-screen ">
        <div class="bg-gray-800 p-6 rounded-lg shadow-lg w-[500px]">
            <h2 class="text-2xl mb-4">Register</h2>
            <form id="registerForm">
                <div class="mb-4">
                    <label for="firstName" class="block text-sm font-medium">First Name</label>
                    <input type="text" id="firstName" name="firstName" class="mt-1 block w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="mb-4">
                    <label for="middleName" class="block text-sm font-medium">Middle Name</label>
                    <input type="text" id="middleName" name="middleName" class="mt-1 block w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="mb-4">
                    <label for="lastName" class="block text-sm font-medium">Last Name</label>
                    <input type="text" id="lastName" name="lastName" class="mt-1 block w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium">Email</label>
                    <input type="email" id="email" name="email" class="mt-1 block w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium">Password</label>
                    <input type="password" id="password" name="password" class="mt-1 block w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="mb-4">
                    <label for="confirmPassword" class="block text-sm font-medium">Confirm Password</label>
                    <input type="password" id="confirmPassword" name="confirmPassword" class="mt-1 block w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md">Register</button>
                Already have an account? <a href="../index.php" class="text-blue-400 hover:underline">Login</a>
            </form>
        </div>
    </div>
</body> -->

<body  class="bg-[#044389] flex items-center justify-center min-h-screen">
<div class="bg-white shadow-lg rounded-lg p-8 max-w-md w-full mt-10">
   <h2 class="text-2xl font-bold text-center mb-6">
    Register
   </h2>
   <form id="registerForm">
    <div class="mb-4">
     <label class="block text-gray-700 text-sm font-bold mb-2" for="firstName">
      First Name
     </label>
     <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="firstName" name="firstName" placeholder="First Name" type="text"/>
    </div>
    <div class="mb-4">
     <label class="block text-gray-700 text-sm font-bold mb-2" for="middleName">
      Middle Name
     </label>
     <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="middleName" name="middleName" placeholder="Middle Name" type="text"/>
    </div>
    <div class="mb-4">
     <label class="block text-gray-700 text-sm font-bold mb-2" for="lastName">
      Last Name
     </label>
     <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="lastName" name="lastName" placeholder="Last Name" type="text"/>
    </div>
    <div class="mb-4">
     <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
      Email
     </label>
     <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="email" name="email" placeholder="Email" type="email"/>
    </div>
    <div class="mb-4">
     <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
      Password
     </label>
     <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="password" name="password" placeholder="Password" type="password"/>
    </div>
    <div class="mb-4">
     <label class="block text-gray-700 text-sm font-bold mb-2" for="confirmPassword">
      Confirm Password
     </label>
     <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="confirmPassword" name="confirmPassword" placeholder="Confirm Password" type="password"/>
    </div>
    <div class="flex items-center justify-center">
     <button class="bg-[#044389] hover:bg-[#3cc5dd] text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit">
      Register
     </button>
    </div>
   </form>
   <div class="text-center mt-6">
    <p class="text-gray-700 text-sm">
     Already have an account?
     <a class="text-indigo-600 hover:text-indigo-800 font-bold" href="../index.php">
      Login
     </a>
    </p>
   </div>
  </div>
</body>
</html>