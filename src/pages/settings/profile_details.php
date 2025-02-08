<?php
include_once 'settings/n_js.php';
?>




<div class="container mx-auto p-4">
   <div class="bg-gray-50 shadow-lg rounded-lg p-6">
      <div class="flex gap-2">
         <button onclick='changePass()' class="bg-red-500 text-white px-4 py-2 rounded-lg flex items-center hover:bg-red-600">
         <i class="fas fa-key mr-2"></i> Change Password
      </button>
      <button onclick="editProfile()"  class="bg-blue-500  hover:bg-blue-600 text-white px-4 py-1 rounded-lg flex items-center hover:bg-blue-600">
         <i class="fas fa-edit mr-2"></i> Edit Profile
      </button>
   </div>
    <div class="flex items-center space-x-4">
    </div>
    <div class="mt-6">
    <div class="bg-[#044389] text-white p-4 rounded-md mb-4">
         <h3 class="text-lg font-semibold">Account Details</h3>
   </div>
     <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div class="bg-white p-4 rounded-lg shadow">
       <h4 class="text-lg font-medium">
        Email
       </h4>
       <p class="text-gray-700" id="userEmail">

       </p>
      </div>
      <div class="bg-white p-4 rounded-lg shadow">
       <h4 class="text-lg font-medium">
        Member Since
       </h4>
       <p class="text-gray-700" id="joinOn">
        
       </p>
      </div>

     </div>
    </div>
    <div class="mt-6">
      <div class="bg-[#044389] text-white p-4 rounded-md mb-4">
         <h3 class="text-lg font-semibold">Personal Information
   </div>
     <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div class="bg-white p-4 rounded-lg shadow">
       <h4 class="text-lg font-medium">
        Full Name
       </h4>
       <p class="text-gray-700" id="userFullName">
       </p>
      </div>
      <div class="bg-white p-4 rounded-lg shadow">
       <h4 class="text-lg font-medium">
        Date of Birth
       </h4>
       <p class="text-gray-700" id="dateOfBirth">

       </p>
      </div>
      <div class="bg-white p-4 rounded-lg shadow">
       <h4 class="text-lg font-medium">
        Address
       </h4>
       <p class="text-gray-700" id="address">
       
       </p>
      </div>
      <div class="bg-white p-4 rounded-lg shadow">
       <h4 class="text-lg font-medium">
        Phone Number
       </h4>
       <p class="text-gray-700" id="phoneNumber">
       </p>
      </div>
     </div>
    </div>
   </div>
  </div>
 </body>
</html>


