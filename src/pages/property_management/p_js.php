<script>
// JavaScript file for property_management
const BASE_URL = '<?php echo $baseUrl; ?>';
const PJ_ID = '<?php echo isset($_GET['pj_id']) ? $_GET['pj_id'] : ''; ?>'; // Get pj_id from the URL
const BLOCK = '<?php echo isset($_GET['block']) ? $_GET['block'] : ''; ?>'; // Get pj_id from the URL

$(document).ready(function() {
    if(BLOCK === 'unassigned') {
        fetchBlockUnassigned();
    } else {
        fetchBlockAssigned();
    }
});

function fetchBlockUnassigned() {
    $.ajax({
        url: BASE_URL + 'backend/project_folder.php?action=fetchBlockUnassiged',
        type: 'GET',
        success: function (response) {
            console.log(response.data)
            displayBlock(response.data); // Directly access the parsed response
        },


        error: function (xhr, status, error) {
            showAlert('error', 'Error', xhr.responseText);
        }
    });
}

function fetchBlockAssigned(){
    $.ajax({
        url: BASE_URL + 'backend/project_folder.php?action=fetchBlockAssigned',
        type: 'GET',
        success: function (response) {
            console.log(response.data)
            displayBlock(response.data); // Directly access the parsed response
        },


        error: function (xhr, status, error) {
            showAlert('error', 'Error', xhr.responseText);
        }
    });
}

function displayBlock(data) {
    if ($.fn.DataTable.isDataTable('#blockTable')) {
        $('#blockTable').DataTable().destroy();
    }

    const tableBody = $('#blockTable tbody');
    tableBody.empty(); // Clear any previous table rows

    // Iterate over each block data and create rows for the table
    data.forEach(block => {
        const blockRow = `
            <tr class="border-b">
                <td class="px-2 py-1 text-sm">${getSafeValue(block.bl_ID)}</td>
                <td class="px-2 py-1 text-sm">${getSafeValue(block.projectName)}</td>
                <td class="px-2 py-1 text-sm">${getSafeValue(block.block_number)}</td>
                <td class="px-2 py-1 text-sm">${getSafeValue(block.lot_number)}</td>
                <td class="px-2 py-1 text-sm">${getSafeValue(block.type)}</td>
                <td class="px-2 py-1 text-sm">${getSafeValue(block.lot_size)}</td>
                <td class="px-2 py-1 text-sm">${getSafeValue(block.name)}</td>
                <td class="px-2 py-1 text-center flex justify-center">
                      <button class="action-btn bg-yellow-500 text-white px-3 py-1 text-xs rounded-md hover:bg-yellow-700 focus:outline-none mr-2" onclick="editBlock(${block.bl_ID})" data-toggle="tooltip" data-placement="top" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                </td>
            </tr>
        `;
        tableBody.append(blockRow);
    });
    

    initializePopperTooltips();
    initializeDataTable('#blockTable');
}
</script>
