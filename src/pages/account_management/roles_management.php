<?php
include_once 'account_management/n_js.php';
?>

<!-- Account Management Description -->
<div class="bg-gray-200 p-4 rounded-md text-sm text-gray-700 mb-6">
    <strong>Account Management:</strong> 
    In this section, you can manage user roles, including creating, editing, and activating or deactivating roles. Select a role to modify its settings or delete it if needed.
</div>

<!-- Roles Table for Account Management -->
<table id="rolesTable" class="min-w-full table-auto border-collapse text-sm">
    <thead>
        <tr class="bg-gray-100 border-b">
            <th class="px-2 py-1 text-left">Role ID</th>
            <th class="px-2 py-1 text-left">Role Name</th>
            <th class="px-2 py-1 text-left">Description</th>
            <th class="px-2 py-1 text-left">Active</th>
            <th class="px-2 py-1 text-center">Actions</th>
        </tr>
    </thead>
    <tbody>
        <!-- Data will be dynamically inserted here -->
    </tbody>
</table>
