<script>
// JavaScript file for property_management
const BASE_URL = '<?php echo  $baseUrl; ?>';
$(document).ready(function() {
    fetchProjects();
    fetchBlock();
});
function AddPropertyManagement(){
    const title = "Add Category";
    $("#dialog_emp").remove();
    $('body').append("<div id='dialog_emp'></div>");
    SYS_dialog3('#dialog_emp', '250', '400', title, function() {
        PropertySave();
    });

    const str = `
       <div>
            <label for="category_name">Category Name:</label>
            <input type="text" id="category_name" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Enter Description"></input>
        </div>
        `;

    $("#dialog_emp").html(str).dialog("open");
}

function PropertySave(){
    const category_name = $("#category_name").val();
    if (!validateRequiredFields(["#category_name"])) {
        return;
    }

    $.ajax({
        url: BASE_URL + 'backend/project_management.php?action=create',
        type: 'POST',
        data: {
            category_name: category_name
        },
        success: function(response) {
            if(response.success === true){
                showLoadingAlert(
                    'Processing...',                         
                    'Please wait while we process your request...',
                    400                                    
                );           
            } else {
                showAlert('error', 'Error', response.message);
            }
        },
        error: function(xhr, status, error) {
            showAlert('error', 'Error', xhr.responseText);
        }
    });
}


function fetchProjects(){
    $.ajax({
        url: BASE_URL + 'backend/project_management.php?action=fetchAll',
        type: 'GET',
        success: function (response) {
            console.log(response.data)
            displayFolders(response.data); // Directly access the parsed response
        },
        error: function (xhr, status, error) {
            showAlert('error', 'Error', xhr.responseText);
        }
    });
}

function displayFolders(data) {
    console.log(data)
    let str = '';
    data.forEach((item, index) => {
        str += `
            <div class="bg-white shadow-md rounded-lg p-4 mb-4">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center">
                    <div class="flex items-center mb-2 sm:mb-0">
                        <i class="fa-solid fa-folder text-blue-500 mr-2"></i>
                        <p class="text-lg font-semibold">${item.projectName}</p>
                    </div>
                    <div class="flex">
                        <a href="javascript:void(0);" class="bg-green-500 text-white px-2 py-1 rounded-md mr-2" onclick="confirmNavigation(${item.pj_id})">View</a>
                        <button class="bg-yellow-500 text-white px-2 py-1 rounded-md mr-2" onclick="editCategory(${item.pj_id})">Edit</button>
                        <button class="bg-red-500 text-white px-2 py-1 rounded-md" onclick="deleteCategory(${item.pj_id})">Delete</button>
                    </div>
                </div>
            </div>
        `;
    });

    $('#project_table').html(str);
}

function confirmNavigation(pj_id) {
    Swal.fire({
        title: "Where do you want to go?",
        text: "Do you want to go to the Assigned Block or Unassigned?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Assigned Block",
        cancelButtonText: "Unassigned",
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Redirect to Assigned Block
            window.location.href = `dashboard.php?page=property_management/project_folder&pj_id=${pj_id}&block=assigned`;
        } else if (result.dismiss === Swal.DismissReason.cancel) {
            // Redirect to Unassigned
            window.location.href = `dashboard.php?page=property_management/project_folder&pj_id=${pj_id}&block=unassigned`;
        }
    });
}

function editCategory(projectid) {
    // Implement edit functionality here
    $.ajax({
        url: BASE_URL + 'backend/project_management.php?action=fetch_single&id=' + projectid,
        type: 'GET',
        success: function(response) {
            if (response.success) {
                displayEditForm(response.data);
                console.log(response.data)
            } else {
                showAlert('error', 'Error', response.message);
            }
        },
        error: function() {
            showAlert('error', 'Error', 'Failed to fetch project.');
        }
    });
    console.log('Edit category with ID:', projectid);
}

function displayEditForm(data){
    const title = "Edit Project";
        $("#dialog_emp").remove();
        $('body').append("<div id='dialog_emp'></div>");
        SYS_dialog3('#dialog_emp', '400', '450', title, function() {
            editprojectSave('update');
        });
        const str = `
                <input type="hidden" id="selected_id">
                <div>
                    <label for="category_name">Category Name:</label>
                    <input type="text" id="category_name" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Enter Description"></input>
                </div>
                
        `;

        $("#dialog_emp").html(str).dialog("open");
        $("#selected_id").val(data.pj_id);
        $("#category_name").val(data.projectName); 
}

function editprojectSave(){
    const pj_id = $("#selected_id").val();
    const project_name = $("#category_name").val();
    
    $.ajax({
        url: BASE_URL + 'backend/project_management.php?action=update',
        type: 'POST',
        data: {
            id:pj_id,
            project_name: project_name
        },
        success: function (response) {
            if (response.success === true) {
                showLoadingAlert();
            } else {
                showAlert('error', 'Error', 'Failed to edit project.');
            }
        },
        error: function (xhr, status, error) {
            showAlert('error', 'Error', xhr.responseText);
        }
    });
}


function deleteCategory(projectid) {
    
    showConfirmationAlert(
        'Are you sure?',
        'You are about to delete this user. This action cannot be undone.',
        function() {  // onConfirm
            $.ajax({
                url: BASE_URL + 'backend/project_management.php?action=delete',
                type: 'POST',
                data: { id:projectid },
                success: function(response) {
                    if(response.success) {
                        showAlert('success', 'Success', response.message);
                        location.reload();
                    } else {
                        showAlert('error', 'Error', response.message);
                    }
                },
                error: function() {
                    showAlert('error', 'Error', 'Failed to delete project.');
                }
            });
        }
    );
    console.log('Delete category with ID:', id);
}
function searchProjects(query) {
    if(query === ''){
        $('#error_msg').html('');
        fetchProjects();
        return;
    }
    $.ajax({
        url: BASE_URL + 'backend/project_management.php?action=search',
        type: 'GET',
        data: {
            term: query
        },
        success: function(response) {
            console.log(response.data)
            if(response.message === 'No projects found.'){
                let str = `
                    <div class='w-full bg-red-300 p-4 border-red-600 text-red-600 flex justify-between border rounded-md'>
                        <p>No project found</p>
                        <i class="fa-solid fa-x"></i>
                    </div>
                `;
                $('#error_msg').html(str);
                return;
            } else {
                $('#error_msg').html('');
                displayFolders(response.data);
            }
        },
        error: function(xhr, status, error) {
            showAlert('error', 'Error', xhr.responseText);
        }
    });
}

//Block Management

function fetchProjectName() {
    $.ajax({
        url: BASE_URL + 'backend/block_management.php?action=getProjectName', // Adjust the URL for fetching projects
        type: 'GET',
        success: function(response) {
            console.log(response);

            if (response.success === true) {
                const projects = response.data; // Use the correct key from the API response
                const selectElem = $('#project_name');
                selectElem.empty(); // Clear previous entries

                // Add a default "Select" option
                selectElem.append('<option value="" disabled selected>Select a Project</option>');

                // Populate the select dropdown with projects
                projects.forEach(function(project) {
                    selectElem.append('<option value="' + project.pj_id + '">' + project.projectName + '</option>');
                });
            } else {
                showAlert('error', 'Error', response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error:', xhr.responseText);
            showAlert('error', 'Error', xhr.responseText || error);
        }
    });
}


function AddBlock(){
    const title = "Add Block";
    $("#dialog_emp").remove();
    $('body').append("<div id='dialog_emp'></div>");
    SYS_dialog3('#dialog_emp', '500', '400', title, function() {
        BlockSave();
    });

    const str = `

        <div>
            <label for="project_name">Project Name:</label>
            <select id="project_name" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></select>
        </div>
        <div>
            <label for="block_number">Block No.:</label>
            <input type="text" id="block_number" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Enter Block no"></input>
        </div>
         <div>
            <label for="lot_number">Lot no.:</label>
            <input type="text" id="lot_number" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Enter Lot no"></input>
        </div>
        <div>
            <label for="type">Type:</label>
            <input type="text" id="type" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Enter type"></input>
        </div>
        <div>
            <label for="type">Lot size:</label>
            <input type="text" id="lot_size" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Enter Lot size"></input>
        </div>
      
        `;

    $("#dialog_emp").html(str).dialog("open");
    fetchProjectName();
}

function BlockSave(){
    const project_name = $("#project_name").val();
    const block_number = $("#block_number").val();
    const lot_number = $("#lot_number").val();
    const type = $("#type").val();
    const lot_size = $("#lot_size").val();
    if (!validateRequiredFields(["#project_name", "#block_number","#lot_number","#type","#lot_size"])) {
        return;
    }
    $.ajax({
        url: BASE_URL + 'backend/block_management.php?action=addBlock',
        type: 'POST',
        data: {
            project_name: project_name,
            block_number: block_number,
            lot_number: lot_number,
            type: type,
            lot_size: lot_size
        },
        success: function(response) {
            if(response.success === true){
              
            showLoadingAlert(
                'Processing...',                         
                'Please wait while we process your request...',
                2000                                    
            );
                            
            } else {
                showAlert('error', 'Error', response.message);
            }
        },
        error: function(xhr, status, error) {
            showAlert('error', 'Error', xhr.responseText);
        }
    });
    
}
function fetchBlock(){
    $.ajax({
        url: BASE_URL + 'backend/block_management.php?action=fetchBlock',
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

                <td class="px-2 py-1 text-center flex justify-center">
                      <button class="action-btn bg-yellow-500 text-white px-3 py-1 text-xs rounded-md hover:bg-yellow-700 focus:outline-none mr-2" onclick="editBlock(${block.bl_ID})" data-toggle="tooltip" data-placement="top" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="action-btn bg-red-500 text-white px-3 py-1 text-xs rounded-md hover:bg-red-700 focus:outline-none mr-2" onclick="deleteBlock(${block.bl_ID})"data-toggle="tooltip" data-placement="top" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        tableBody.append(blockRow);
    });
    

    initializePopperTooltips();
    initializeDataTable('#blockTable');
}

function editBlock(bID) {
    $.ajax({
        url: BASE_URL + 'backend/block_management.php?action=fetch_single_block&id=' + bID,
        type: 'GET',
        success: function(response) {
            if (response.success) {
                displayEditFormBlock(response.data);
            } else {
                showAlert('error', 'Error', response.message);
            }
        },
        error: function() {
            showAlert('error', 'Error', 'Failed to fetch vendor details.');
        }
    });
}


// ANCHOR adjust the dialog size

function displayEditFormBlock(data) {
    console.log("Data: ", data);
    const title = 'Edit Block';
    $("#dialog_emp").remove();
    $('body').append("<div id='dialog_emp'></div>");
    SYS_dialog3('#dialog_emp', '230', '500', title, function() {
        EditBlockSave();
    });

    const str = `
          <input type='hidden'  id="bID" >
        <div>
            <label for="project_name">Project Name:</label>
            <select id="project_name" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></select>
        </div>
        <div>
            <label for="block_number">Block No.:</label>
            <input type="text" id="block_number" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Enter Block no"></input>
        </div>
         <div>
            <label for="lot_number">Lot no.:</label>
            <input type="text" id="lot_number" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Enter Lot no"></input>
        </div>
        <div>
            <label for="type">Type:</label>
            <input type="text" id="type" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Enter type"></input>
        </div>
        <div>
            <label for="type">Lot size:</label>
            <input type="text" id="lot_size" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Enter Lot size"></input>
        </div>
      
    `;
    
    $("#dialog_emp").html(str).dialog("open");
    fetchProjectName();
    $("#bID").val(data.bl_ID);
    setTimeout(() => {
        $("#project_name").val(data.pj_id);
    }, 500);
    $("#block_number").val(data.block_number);
    $("#lot_number").val(data.lot_number);
    $("#type").val(data.type);
    $("#lot_size").val(data.lot_size);
}


function EditBlockSave() {
    const bID = $("#bID").val();
    const pj_id = $("#project_name").val();
    const block_number = $("#block_number").val();
    const lot_number = $("#lot_number").val();
    const type = $("#type").val();
    const lot_size = $("#lot_size").val();

    if (!validateRequiredFields(["#project_name", "#block_number", "#lot_number", "#type", "#lot_size"])) {
        return;
    }

    $.ajax({
        url: BASE_URL + 'backend/block_management.php?action=update',
        type: 'POST',
        data: {
            bID: bID,
            pj_id: pj_id,
            block_number: block_number,
            lot_number: lot_number,
            type: type,
            lot_size: lot_size,
        },
        success: function(response) {
            if (response.success === true) {
                showLoadingAlert(
                    'Processing...',                         
                    'Please wait while we process your request...',
                    900                                     
                );
            } else {
                showAlert('error', 'Error', response.message);
            }
        },
        error: function(xhr, status, error) {
            showAlert('error', 'Error', xhr.responseText);
        }
    });
}


function deleteBlock(blockID) {
    Swal.fire({
        title: 'Are you sure?',
        text: 'You won’t be able to revert this!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'No, cancel!',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Send a request to delete the block
            $.ajax({
                url: BASE_URL + 'backend/block_management.php?action=delete',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ bl_ID: blockID }),
                success: function(response) {
                    if (response.success) {
                        Swal.fire(
                            'Deleted!',
                            'The block has been successfully deleted.',
                            'success'
                        );
                        setTimeout(function() {
                            location.reload();
                        }, 200);
                    } else {
                        Swal.fire(
                            'Error!',
                            response.message,
                            'error'
                        );
                    }
                },
                error: function(xhr) {
                    Swal.fire(
                        'Error!',
                        'There was an error deleting the block.',
                        'error'
                    );
                }
            });
        } else if (result.dismiss === Swal.DismissReason.cancel) {
            Swal.fire(
                'Cancelled',
                'The block was not deleted.',
                'info'
            );
        }
    });
}




</script>