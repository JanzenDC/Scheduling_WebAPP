<?php
include_once 'property_management/n_js.php';
?>

<div onclick='AddPropertyManagement()' class='text-white bg-[#044389] p-1 rounded-md cursor-pointer hover:bg-indigo-800 transition duration-200 text-xs flex items-center justify-center w-32 mb-5'>
    <i class="fa-solid fa-plus mr-1 text-sm"></i> 
    <span class="text-sm">Add Project</span>
</div>
<div class='w-full flex items-center justify-center mb-5'>
    <div class='flex border-2 rounded'>
        <input type='text' placeholder='Search.....' class='p-2 outline-none w-[500px]' oninput='searchProjects(this.value)'>
        <button class='p-2 bg-[#044389] text-white rounded-r hover:bg-indigo-800 transition duration-200'>
            <i class="fa-solid fa-magnifying-glass"></i>
        </button>
    </div>
</div>
<div id='error_msg'></div>

<div class='grid grid-cols-4 gap-4' id='project_table'>
    
</div>