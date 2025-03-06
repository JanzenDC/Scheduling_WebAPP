<?php
session_start();
if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_NAME'] == '127.0.0.1') {
    $baseUrl = 'http://localhost/Scheduling_WebAPP/';
} else {
    $baseUrl = 'https://yourhosting.com/';
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <?php include_once 'header_cdn.php'; ?>
    <style>
        .otp-input {
            width: 3rem;
            height: 3rem;
            text-align: center;
            font-size: 1.5rem;
            margin: 0 0.25rem;
        }
        #timer {
            font-weight: bold;
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen"
      style="background-image: url('../resources/images/background.jpg'); background-size: cover;">
    <div class="bg-white shadow-lg rounded-lg p-8 max-w-md w-full">
        <!-- Step 1: Email Form -->
        <div id="emailStep">
            <h2 class="text-2xl font-bold text-center mb-6">Reset Your Password</h2>
            <form id="forgotPasswordForm">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                        Enter your email
                    </label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        required
                        placeholder="Email"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                    />
                </div>
                
                <div class="flex items-center justify-center">
                    <button
                        type="submit"
                        class="bg-[#044389] hover:bg-[#3cc5dd] text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                    >
                        Send OTP Code
                    </button>
                </div>
            </form>
        </div>

        <!-- Step 2: OTP Verification -->
        <div id="otpStep" class="hidden">
            <h2 class="text-2xl font-bold text-center mb-6">Enter OTP Code</h2>
            <p class="text-center mb-4">We've sent a 6-digit code to your email.</p>
            
            <form id="otpVerificationForm">
                <div class="mb-6 text-center">
                    <input type="hidden" id="otpEmail" name="email">
                    <div class="flex justify-center mb-4">
                        <input type="text" maxlength="1" class="otp-input" data-index="1" />
                        <input type="text" maxlength="1" class="otp-input" data-index="2" />
                        <input type="text" maxlength="1" class="otp-input" data-index="3" />
                        <input type="text" maxlength="1" class="otp-input" data-index="4" />
                        <input type="text" maxlength="1" class="otp-input" data-index="5" />
                        <input type="text" maxlength="1" class="otp-input" data-index="6" />
                    </div>
                    <input type="hidden" id="fullOtp" name="otp">
                    
                    <div class="text-center mb-4">
                        <p>Code expires in <span id="timer">5:00</span></p>
                    </div>
                    
                    <div class="text-center mb-4">
                        <a href="#" id="resendOtp" class="text-[#044389] hover:text-indigo-800 disabled:text-gray-400" disabled>
                            Resend OTP <span id="cooldown" class="hidden">(30s)</span>
                        </a>
                    </div>
                </div>
                
                <div class="flex items-center justify-center">
                    <button
                        type="submit"
                        class="bg-[#044389] hover:bg-[#3cc5dd] text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                    >
                        Verify OTP
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Step 3: Reset Password -->
        <div id="resetPasswordStep" class="hidden">
            <h2 class="text-2xl font-bold text-center mb-6">Set New Password</h2>
            
            <form id="resetPasswordForm">
                <input type="hidden" id="resetEmail" name="email">
                <input type="hidden" id="resetToken" name="token">
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                        New Password
                    </label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        required
                        placeholder="New Password"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                    />
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="confirmPassword">
                        Confirm New Password
                    </label>
                    <input
                        id="confirmPassword"
                        name="confirmPassword"
                        type="password"
                        required
                        placeholder="Confirm New Password"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                    />
                </div>
                
                <div class="flex items-center justify-center">
                    <button
                        type="submit"
                        class="bg-[#044389] hover:bg-[#3cc5dd] text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                    >
                        Reset Password
                    </button>
                </div>
            </form>
        </div>

        <div class="text-center mt-6">
            <p class="text-gray-700 text-sm">
                Remember your password?
                <a href="../index.php" class="text-[#044389] hover:text-indigo-800 font-bold">
                    Login
                </a>
            </p>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        let countdownInterval;
        let cooldownInterval;
        let expiryTime;
        
        // Handle OTP input fields behavior
        $('.otp-input').on('input', function() {
            const val = $(this).val();
            const index = parseInt($(this).data('index'));
            
            if (val && index < 6) {
                // Move to next input
                $(`.otp-input[data-index="${index + 1}"]`).focus();
            }
            
            // Collect full OTP
            collectOtp();
        });
        
        $('.otp-input').on('keydown', function(e) {
            const index = parseInt($(this).data('index'));
            
            // Handle backspace
            if (e.keyCode === 8 && !$(this).val() && index > 1) {
                e.preventDefault();
                $(`.otp-input[data-index="${index - 1}"]`).focus().val('');
            }
        });
        
        function collectOtp() {
            let otp = '';
            $('.otp-input').each(function() {
                otp += $(this).val() || '';
            });
            $('#fullOtp').val(otp);
        }
        
        // Submit email for OTP
        $('#forgotPasswordForm').on('submit', function(event) {
            event.preventDefault();
            const email = $('#email').val();
            
            $.ajax({
                url: '<?php echo $baseUrl; ?>backend/forgot_password.php?action=sendCode',
                type: 'POST',
                data: { email: email },
                success: function(response) {
                    if (response.success) {
                        // Move to OTP step
                        $('#emailStep').addClass('hidden');
                        $('#otpStep').removeClass('hidden');
                        $('#otpEmail').val(email);
                        
                        // Focus on first OTP input
                        $('.otp-input[data-index="1"]').focus();
                        
                        // Start countdown timer
                        startExpiryTimer();
                        
                        // Disable resend button and start cooldown
                        startCooldown();
                    }
                    
                    Swal.fire({
                        icon: response.success ? 'success' : 'error',
                        title: response.success ? 'Success!' : 'Error!',
                        text: response.message
                    });
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Something went wrong, please try again.'
                    });
                }
            });
        });
        
        // Verify OTP
        $('#otpVerificationForm').on('submit', function(event) {
            event.preventDefault();
            const email = $('#otpEmail').val();
            const otp = $('#fullOtp').val();
            
            if (otp.length !== 6) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid OTP',
                    text: 'Please enter all 6 digits of the OTP.'
                });
                return;
            }
            
            $.ajax({
                url: '<?php echo $baseUrl; ?>backend/forgot_password.php?action=verifyOTP',
                type: 'POST',
                data: { email: email, otp: otp },
                success: function(response) {
                    if (response.success) {
                        // Stop countdown timer
                        clearInterval(countdownInterval);
                        
                        // Move to reset password step
                        $('#otpStep').addClass('hidden');
                        $('#resetPasswordStep').removeClass('hidden');
                        
                        // Set email and token for reset password form
                        $('#resetEmail').val(email);
                        $('#resetToken').val(response.data.token);
                    }
                    
                    Swal.fire({
                        icon: response.success ? 'success' : 'error',
                        title: response.success ? 'Success!' : 'Error!',
                        text: response.message
                    });
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Something went wrong, please try again.'
                    });
                }
            });
        });
        
        // Reset Password
        $('#resetPasswordForm').on('submit', function(event) {
            event.preventDefault();
            const email = $('#resetEmail').val();
            const token = $('#resetToken').val();
            const password = $('#password').val();
            const confirmPassword = $('#confirmPassword').val();
            
            if (password !== confirmPassword) {
                Swal.fire({
                    icon: 'error',
                    title: 'Passwords Do Not Match',
                    text: 'Please make sure both passwords match.'
                });
                return;
            }
            
            $.ajax({
                url: '<?php echo $baseUrl; ?>backend/forgot_password.php?action=resetPassword',
                type: 'POST',
                data: { email: email, token: token, password: password },
                success: function(response) {
                    Swal.fire({
                        icon: response.success ? 'success' : 'error',
                        title: response.success ? 'Success!' : 'Error!',
                        text: response.message
                    }).then((result) => {
                        if (response.success) {
                            // Redirect to login page
                            window.location.href = '../index.php';
                        }
                    });
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: xhr.responseText
                    });
                }
            });
        });
        
        // Resend OTP
        $('#resendOtp').on('click', function(e) {
            e.preventDefault();
            
            if ($(this).attr('disabled')) {
                return;
            }
            
            const email = $('#otpEmail').val();
            
            $.ajax({
                url: '<?php echo $baseUrl; ?>backend/forgot_password.php?action=resendOTP',
                type: 'POST',
                data: { email: email },
                success: function(response) {
                    if (response.success) {
                        // Reset expiry timer
                        startExpiryTimer();
                        
                        // Reset and start cooldown
                        startCooldown();
                        
                        // Clear OTP inputs
                        $('.otp-input').val('');
                        $('#fullOtp').val('');
                        $('.otp-input[data-index="1"]').focus();
                    }
                    
                    Swal.fire({
                        icon: response.success ? 'success' : 'error',
                        title: response.success ? 'Success!' : 'Error!',
                        text: response.message
                    });
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: xhr.responseText
                    });
                }
            });
        });
        
        // Timer functions
        function startExpiryTimer() {
            // Clear any existing interval
            if (countdownInterval) {
                clearInterval(countdownInterval);
            }
            
            // Set expiry time (5 minutes from now)
            expiryTime = new Date(new Date().getTime() + 5 * 60 * 1000);
            
            // Update timer immediately
            updateExpiryTimer();
            
            // Set interval to update timer every second
            countdownInterval = setInterval(updateExpiryTimer, 1000);
        }
        
        function updateExpiryTimer() {
            const now = new Date();
            const timeLeft = expiryTime - now;
            
            if (timeLeft <= 0) {
                // Timer expired
                clearInterval(countdownInterval);
                $('#timer').text('Expired');
                
                Swal.fire({
                    icon: 'warning',
                    title: 'OTP Expired',
                    text: 'Your OTP has expired. Please request a new one.'
                });
                
                return;
            }
            
            // Calculate minutes and seconds
            const minutes = Math.floor(timeLeft / (60 * 1000));
            const seconds = Math.floor((timeLeft % (60 * 1000)) / 1000);
            
            // Display time in MM:SS format
            $('#timer').text(`${minutes}:${seconds.toString().padStart(2, '0')}`);
        }
        
        function startCooldown() {
            // Disable resend button
            $('#resendOtp').attr('disabled', true).addClass('text-gray-400');
            $('#cooldown').removeClass('hidden');
            
            // Set cooldown time (30 seconds)
            let cooldownTime = 30;
            
            // Clear any existing interval
            if (cooldownInterval) {
                clearInterval(cooldownInterval);
            }
            
            // Update cooldown text immediately
            $('#cooldown').text(`(${cooldownTime}s)`);
            
            // Set interval to update cooldown every second
            cooldownInterval = setInterval(function() {
                cooldownTime--;
                $('#cooldown').text(`(${cooldownTime}s)`);
                
                if (cooldownTime <= 0) {
                    // Cooldown finished
                    clearInterval(cooldownInterval);
                    $('#resendOtp').removeAttr('disabled').removeClass('text-gray-400');
                    $('#cooldown').addClass('hidden');
                }
            }, 1000);
        }
    });
    </script>
</body>
</html>