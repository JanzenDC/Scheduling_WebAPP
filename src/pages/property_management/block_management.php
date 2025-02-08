<?php
include_once 'property_management/n_js.php';
?>

<div onclick='AddBlock()' class='text-white bg-[#044389] p-1 rounded-md cursor-pointer hover:bg-indigo-800 transition duration-200 text-xs flex items-center justify-center w-32 mb-5'>
    <i class="fa-solid fa-plus mr-1 text-sm"></i> 
    <span class="text-sm">Add Block</span>
</div>



<table id="blockTable" class="min-w-full table-auto border-collapse text-sm">
    <thead>
        <tr class="bg-[#044389] border-b text-white">
            <th class="px-2 py-1 text-left">File Number</th>
            <th class="px-2 py-1 text-left">Project Name</th>
            <th class="px-2 py-1 text-left">Block #</th>
            <th class="px-2 py-1 text-left">Lot #</th>
            <th class="px-2 py-1 text-left">Type</th>
            <th class="px-2 py-1 text-left">Lot Size</th>
            <th class="px-2 py-1  flex justify-center">Actions</th>
        </tr>
    </thead>
    <tbody>
        
    </tbody>
</table>