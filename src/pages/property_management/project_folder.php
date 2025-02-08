<?php
include_once 'property_management/p_js.php';

$pj_id = isset($_GET['pj_id']) ? $_GET['pj_id'] : null;
$block = isset($_GET['block']) ? $_GET['block'] : null;
?>
<!-- //ANCHOR (bors)
- Add edit functionalities 
    - Can assign client 

-->
<table id="blockTable" class="min-w-full table-auto border-collapse text-sm">
    <thead>
        <tr class="bg-[#044389] border-b text-white">
            <th class="px-2 py-1 text-left">File Number</th>
            <th class="px-2 py-1 text-left">Project Name</th>
            <th class="px-2 py-1 text-left">Block #</th>
            <th class="px-2 py-1 text-left">Lot #</th>
            <th class="px-2 py-1 text-left">Type</th>
            <th class="px-2 py-1 text-left">Lot Size</th>
            <th class="px-2 py-1 text-left">Client Name</th>
            <th class="px-2 py-1  flex justify-center">Actions</th>
        </tr>
    </thead>
    <tbody>
        
    </tbody>
</table>





