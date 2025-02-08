<?php
include_once 'account_management/n_js.php';
?>

<!-- Assign Roles Description -->
<div class="bg-gray-200 p-4 rounded-md text-sm text-gray-700 mb-6">
    <strong>Assign Roles:</strong> 
    In this section, you can assign specific roles to users. Select a user to assign a role and manage their permissions accordingly. You can also update or reset user roles as needed.
</div>

<h2 class="text-2xl font-semibold mb-4">Assign Roles</h2>

<!-- Add User button will be inserted here by JavaScript -->
<table id="assignRoles" class="min-w-full table-auto border-collapse text-sm">
    <thead>
        <tr class="bg-gray-100 border-b">
            <th class="px-2 py-1 text-left">User ID</th>
            <th class="px-2 py-1 text-left">Full Name</th>
            <th class="px-2 py-1 text-left">Email</th>
            <th class="px-2 py-1 text-left">Roles</th>
            <th class="px-2 py-1 text-center">Actions</th>
        </tr>
    </thead>
    <tbody>
        <!-- Data will be dynamically inserted here -->
    </tbody>
</table>
