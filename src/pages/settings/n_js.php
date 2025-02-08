<script>
// JavaScript file for settings
const BASE_URL = '<?php echo  $baseUrl; ?>';

$(document).ready(function() {
    fetchUserProfile(); // Call the function to fetch profile data when the page is ready
});

// Fetch user profile from the backend
function fetchUserProfile() {
    $.ajax({
        url: BASE_URL + 'backend/profile_details.php?action=getUserData',
        type: 'GET',
        success: function(response) {
        console.log(response); // Log the entire response to check its structure
        populateProfileDetails(response.data);
    },
        error: function (xhr, status, error) {
            showAlert('error', 'Error', xhr.responseText); // Handle error if request fails
        }
    });
}

// Populate profile details in the HTML
function populateProfileDetails(data) {
    
    // Check if the data exists and is valid
    if (data) {

        const fullName = `${data.fname || ""} ${data.mname || ""} ${data.lname || ""}`.trim();
        const address = `${data.address || ""} ${data.city || ""} ${data.state || ""} ${data.postal_code || ""} ${data.country || ""}`.trim();
        const formatDate = (dateStr) => {
            const date = new Date(dateStr);
            return !isNaN(date) ? date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : "N/A";
        };
         // Function to format date and time
         const formatDateTime = (dateStr) => {
        const [datePart, timePart] = dateStr.split(' '); // Split date and time
        const date = new Date(datePart); // Get the date part only
        if (!isNaN(date)) {
            const formattedDate = date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });

            // Standardize time to 12-hour format (AM/PM) without seconds
            if (timePart) {
                const [hours, minutes] = timePart.split(':');
                let hours12 = parseInt(hours, 10);
                const ampm = hours12 >= 12 ? 'PM' : 'AM';
                hours12 = hours12 % 12;
                if (hours12 === 0) {
                    hours12 = 12; // 0 hours is 12 in 12-hour format
                }
                const formattedTime = `${hours12}:${minutes} ${ampm}`;
                return `${formattedDate} ${formattedTime}`; // Combine date and time
            }
            return `${formattedDate} N/A`; // If no time, only return date
        }
        return "N/A"; // Return "N/A" if invalid
    };
    
    $("#userFullName").text(fullName || "N/A");
    $("#userEmail").text(data.email || "N/A");
    $("#dateOfBirth").text(data.date_of_birth || "N/A");
    $("#phoneNumber").text(data.phone_number || "N/A");
    $("#address").text(address || "N/A");
    $("#joinOn").text(formatDateTime(data.created_at) || "N/A");


    } else {
        console.error("No data available to populate profile.");
    }
}

function changePass(uID) {
    // Initiate the request to fetch the user's information if necessary (based on provided `uID`)
    $.ajax({
        url: BASE_URL + 'backend/profile_details.php?action=getUserData&id=' + uID,
        type: 'GET',
        success: function(response) {
            if (response.success) {
                // Display dialog for editing the password
                displayEditPass(response.data);
                console.log(response.data);
            } else {
                showAlert('error', 'Error', response.message);
            }
        },
        error: function() {
            showAlert('error', 'Error', 'Failed to fetch user details.');
        }
    });
}
function displayEditPass(data) {
    const title = 'Change Password';
    $("#dialog_emp").remove();
    $('body').append("<div id='dialog_emp'></div>");
    SYS_dialog3('#dialog_emp', '300', '350', title, function() {
        changePassSave();
    });

    const str = `
        <input type="hidden" id="uID">
        <div class="relative">
            <label for="new_password">New Password:</label>
            <div class="relative">
                <input type="password" id="new_password" 
                       class="w-full border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-500" 
                       placeholder="Enter new password">
                <button type="button" id="toggle_new_password" 
                        class="absolute inset-y-0 right-2 flex items-center text-gray-500 focus:outline-none">
                    <i class="fas fa-eye-slash" id="new_password_icon"></i>
                </button>
            </div>
        </div>
        <div class="relative mt-4">
            <label for="confirm_password">Confirm Password:</label>
            <div class="relative">
                <input type="password" id="confirm_password" 
                       class="w-full border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-500" 
                       placeholder="Confirm new password">
                <button type="button" id="toggle_confirm_password" 
                        class="absolute inset-y-0 right-2 flex items-center text-gray-500 focus:outline-none">
                    <i class="fas fa-eye-slash" id="confirm_password_icon"></i>
                </button>
            </div>
        </div>
    `;

    // Display dialog content
    $("#dialog_emp").html(str).dialog("open");
    $("#uID").val(data.user_id);

    // Attach toggle functionality
    $("#toggle_new_password").on('click', function() {
        togglePasswordVisibility("#new_password", "#new_password_icon");
    });
    $("#toggle_confirm_password").on('click', function() {
        togglePasswordVisibility("#confirm_password", "#confirm_password_icon");
    });
}

// Function to toggle password visibility
function togglePasswordVisibility(inputSelector, iconSelector) {
    const input = document.querySelector(inputSelector);
    const icon = document.querySelector(iconSelector);

    if (input.type === "password") {
        input.type = "text";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
    } else {
        input.type = "password";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
    }
}


function changePassSave() {
    const uID = $("#uID").val();
    const newPassword = $("#new_password").val();
    const confirmPassword = $("#confirm_password").val();  // New password from dialog


    if (newPassword !== confirmPassword) {
        Swal.fire({
            icon: 'error',
            title: 'Registration Failed',
            text: 'Passwords do not match'
        });
        return;
        }
    $.ajax({
        url: BASE_URL + 'backend/profile_details.php?action=changePassword',
        type: 'POST',
        data: {
            user_id: uID,
            new_password: newPassword,  // Send new password
        },
        success: function(response) {
            if (response.success) {
                // Display success message
                showAlert('success', 'Success', 'Password updated successfully.');
                $("#dialog_emp").dialog("close");  // Close the dialog
            } else {
                showAlert('error', 'Error', response.message);
            }
        },
        error: function(xhr, status, error) {
            showAlert('error', 'Error', 'Failed to change password.');
        }
    });
}


function editProfile(uID){
    $.ajax({
        url: BASE_URL + 'backend/profile_details.php?action=getUserData&id=' + uID,
        type: 'GET',
        success: function(response) {
            if (response.success) {
                // Display dialog for editing the password
                displayEditProfile(response.data);
                console.log(response.data);
            } else {
                showAlert('error', 'Error', response.message);
            }
        },
        error: function() {
            showAlert('error', 'Error', 'Failed to fetch user details.');
        }
    });
}

function displayEditProfile(data){
    const title = 'Edit Profile Details';
    $("#dialog_emp").remove();
    $('body').append("<div id='dialog_emp'></div>");
    SYS_dialog3('#dialog_emp', '500', '700', title, function() {
        EditProfileSave();
    });


    const str = `
        <input type="hidden" id="uID" >
        <div class="grid gap-y-1">
        <div>
            <label for="fname" class="font-semibold mb-1">Firstname:</label>
          <input type="text" id="fname" 
                        class="w-full border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-500" 
                        placeholder="Enter Firstname">
        </div>
         <div class="mt-2">
            <label for="lname" class="font-semibold mb-1">Lastname:</label>
          <input type="text" id="lname" 
                        class="w-full border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-500" 
                        placeholder="Enter Lastname">
        </div>
           <div>
            <label for="mname" class="font-semibold mb-1">Middlename:</label>
          <input type="text" id="mname" 
                        class="w-full border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-500" 
                        placeholder="Enter Middlename">
        </div>
           <div>
            <label for="email" class="font-semibold mb-1">Email:</label>
          <input type="email" id="email" 
                        class="w-full border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-500" 
                        placeholder="Enter Email">
        </div>
        <div>
            <label for="date_of_birth" class="font-semibold mb-1">Date of Birth:</label>
          <input type="date" id="date_of_birth" 
                        class="w-full border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-500" 
                        placeholder="Select Date ">
        </div>
        <div>
            <label for="phone_number" class="font-semibold mb-1">Phone Number:</label>
          <input type="number" id="phone_number" 
                        class="w-full border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-500" 
                        placeholder="Enter Phone Number">
        </div>
        
         <div>
            <label for="address1" class="font-semibold mb-1">Street:</label>
          <input type="text" id="address1" 
                        class="w-full border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-500" 
                        placeholder="Enter Street">
        </div>
         <div>
            <label for="city" class="font-semibold mb-1">City:</label>
          <input type="text" id="city" 
                        class="w-full border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-500" 
                        placeholder="Enter City">
        </div>
         <div>
            <label for="state" class="font-semibold mb-1">State:</label>
          <input type="text" id="state" 
                        class="w-full border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-500" 
                        placeholder="Enter State">
        </div>
         <div>
            <label for="postal_code" class="font-semibold mb-1">Postal Code:</label>
          <input type="number" id="postal_code" 
                        class="w-full border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-500" 
                        placeholder="Enter Postal Code">
        </div>
        <div>
            <label for="country" class="font-semibold mb-1">Country:</label>
          <input type="text" id="country" 
                        class="w-full border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-500" 
                        placeholder="Enter Country">
        </div>
        </div>
        
    `;
    $("#dialog_emp").html(str).dialog("open");
    $("#uID").val(data.user_id);
    $("#fname").val(data.fname);
    $("#lname").val(data.lname);
    $("#mname").val(data.mname);
    $("#email").val(data.email);
    $("#date_of_birth").val(data.date_of_birth);
    $("#phone_number").val(data.phone_number);
    $("#address1").val(data.address);
    $("#city").val(data.city);
    $("#state").val(data.state);
    $("#postal_code").val(data.postal_code);
    $("#country").val(data.country);

}

function EditProfileSave(){
    const user_id = $("#uID").val();
    const fname = $("#fname").val();
    const lname = $("#lname").val();
    const mname = $("#mname").val();
    const email = $("#email").val();
    const date_of_birth = $("#date_of_birth").val();
    const phone_number = $("#phone_number").val();
    const address = $("#address1").val();
    const city = $("#city").val();
    const state = $("#state").val();
    const postal_code = $("#postal_code").val();
    const country = $("#country").val();



    $.ajax({
            url: BASE_URL + 'backend/profile_details.php?action=update',
            type: 'POST',
            data: {
                user_id: user_id,
                fname: fname,
                lname: lname,
                mname: mname,
                email: email,
                date_of_birth: date_of_birth,
                phone_number: phone_number,
                address: address,
                city: city,
                state: state,
                postal_code: postal_code,
                country: country
            },
           
            
            success: function(response) {
                if(response.success === true){
                  
                showLoadingAlert(
                    'Processing...',                         
                    'Please wait while we process your request...',
                    900                                    
                );
                                
                } else {
                    showAlert('error', 'Error', response.message);
                }
            },
            error: function(xhr, status, error) {
                showAlert('error', 'Error', xhr.responseText);
            }
        });

        
}
</script>
