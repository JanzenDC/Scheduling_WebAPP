<?php
include_once 'page_management/n_js.php';
?>
    <div onclick='fetchPages()' class='text-white bg-blue-600 p-1 rounded-md cursor-pointer hover:bg-blue-700 transition duration-200 text-xs flex items-center justify-center w-32'>
        <i class="fa-solid fa-download mr-1 text-sm"></i> 
        <span class="text-sm">Fetch Pages</span>
    </div>
<!-- Note Section: Informing about Page Management -->
<div class="bg-gray-200 p-3 rounded-md text-sm text-gray-700 mb-4">
    <strong>Page Management Note:</strong> 
    Only developers can manage pages in this section. Please contact your administrator if you need access to this feature.
</div>

<!-- Table for Page Management -->
<table class="w-full border-collapse bg-white shadow-sm rounded-lg overflow-hidden">
    <thead>
        <tr class="bg-[#044389] text-white border-b text-sm">
            <th class="p-2 text-left">ID</th>
            <th class="p-2 text-left">Icons</th>
            <th class="p-2 text-left">Page Name</th>
            <th class="p-2 text-left">Page Folder</th>
            <th class="p-2 text-left">Module</th>
            <th class="p-2 text-left">Seq #</th>
            <th class="p-2 text-center">Switch</th>
            <th class="p-2 text-left">Actions</th>
        </tr>
    </thead>
    <tbody>
        <!-- Dynamically populated by JavaScript -->
    </tbody>
</table>
