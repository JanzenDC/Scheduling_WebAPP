<?php
include_once 'authorization_management/n_js.php';
?>

<!-- Permission Management Description -->
<div class="bg-gray-200 p-4 rounded-md text-sm text-gray-700 mb-6">
    <strong>Permission Management:</strong> 
    This section allows you to manage user roles and assign specific permissions to each role. Select a role from the dropdown and then configure the permissions accordingly. Once finished, click "Save" to apply the changes.
</div>

<!-- Permission Management Controls -->
<div class='flex justify-between'>
    <div class="flex items-center border border-gray-300 rounded-lg w-[200px]">
        <select id="rolesDropdown" class="px-4 py-2 w-full focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-r-lg bg-gray-100 text-gray-700 border-r border-gray-300">
            <option value="" disabled selected>-- SELECT --</option>
        </select>
        <span class="px-4 py-2 bg-gray-100 text-gray-700 border-r border-gray-300">
            <i class="fa-regular fa-user"></i>
        </span>
    </div>
    <button class='save-permissions gap-3 bg-green-600 hover:bg-[#044389] text-white rounded-lg w-[100px] flex p-2 items-center justify-center cursor-pointer'>
        <i class="fa-solid fa-floppy-disk"></i> Save
    </button>
</div>

<!-- Display Table for Permissions -->
<div id="permissionsTable" class="mt-6">
    <!-- Table will be dynamically inserted here -->
</div>
