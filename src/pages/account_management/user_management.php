<?php
include_once 'account_management/n_js.php';
?>

<!-- User Management Description -->
<div class="bg-gray-200 p-4 rounded-md text-sm text-gray-700 mb-6">
    <strong>User Management:</strong> 
    In this section, you can manage user accounts. You can view existing users, add new users, and modify or delete users as needed.
</div>

<h2 class="text-2xl font-semibold mb-4">User Management</h2>

<!-- Add User button will be inserted here by JavaScript -->
<table id="usersTable" class="min-w-full table-auto border-collapse text-sm">
    <thead>
        <tr class="bg-gray-100 border-b">
            <th class="px-2 py-1 text-left">User ID</th>
            <th class="px-2 py-1 text-left">Full Name</th>
            <th class="px-2 py-1 text-left">Email</th>
            <th class="px-2 py-1 text-center">Actions</th>
        </tr>
    </thead>
    <tbody>
        <!-- Data will be dynamically inserted here -->
    </tbody>
</table>
