<script>
    const BASE_URL = '<?php echo $baseUrl; ?>';
    console.log(BASE_URL);
    function FetchModules() {
        showConfirmationAlert(
            'Fetch Modules',
            'This will fetch and insert all modules from the log file. Continue?',
            function() {
                $.ajax({
                    url: BASE_URL + 'backend/module_management.php?action=fetch_from_log',
                    type: 'POST',
                    success: function(response) {
                        if(response.success) {
                            showLoadingAlert();                            // Refresh the display
                        } else {
                            showAlert('error', 'Error', response.message || 'Failed to fetch modules.');
                        }
                    },
                    error: function(xhr, status, error) {
                        showAlert('error', 'Error', 'Failed to fetch modules from log file.');
                    }
                });
            }
        );
    }
    
    function AddModule() {
        const title = "Add Module";
        $("#dialog_emp").remove();
        $('body').append("<div id='dialog_emp'></div>");
        SYS_dialog3('#dialog_emp', '400', '450', title, function() {
            addModuleSave();
        });

        const str = `
            <div class="p-4">
                <div class="mb-4">
                    <label for="moduleName" class="block text-gray-700 font-medium mb-2">Module Name:</label>
                    <input type="text" id="moduleName" 
                           class="w-full border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-500" 
                           placeholder="Enter module name">
                </div>
                <div class="mb-4">
                    <label for="moduleAlias" class="block text-gray-700 font-medium mb-2">Module Alias: (page_alias)</label>
                    <input type="text" id="moduleAlias" 
                           class="w-full border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-500" 
                           placeholder="Enter module alias">
                </div>
                <div class="mb-4">
                    <label for="sequenceNumber" class="block text-gray-700 font-medium mb-2">Sequence Number:</label>
                    <input type="number" id="sequenceNumber" 
                           class="w-full border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-500" 
                           placeholder="Enter sequence number">
                </div>
            </div>
        `;

        $("#dialog_emp").html(str).dialog("open");
    }

    function addModuleSave() {
        const moduleName = $("#moduleName").val();
        const moduleAlias = $("#moduleAlias").val();
        const sequenceNumber = $("#sequenceNumber").val();
        
        console.log("Module Name:", moduleName);
        console.log("Module Alias:", moduleAlias);
        console.log("Sequence Number:", sequenceNumber);

        $.ajax({
            url: BASE_URL + 'backend/module_management.php?action=create',
            type: 'POST',
            data: {
                moduleName: moduleName,
                moduleAlias: moduleAlias,
                sequenceNumber: sequenceNumber
            },
            success: function(response) {
                if(response.success === true){
                    showAlert('success', 'Module Added', 'Module was added successfully!');
                    fetchModules();
                } else {
                    showAlert('error', 'Error', 'Failed to add the module.');
                }
            },
            error: function(xhr, status, error) {
                showAlert('error', 'Error', xhr.responseText);
            }
        });
    }

    function fetchModules() {
        $.ajax({
            url: BASE_URL + 'backend/module_management.php?action=fetch',
            type: 'GET',
            success: function(response) {
                if(response.success) {
                    displayModules(response.data);
                } else {
                    // showAlert('info', 'No Modules Found', 'No modules found to display.');
                    $('#modulesList').html('<p>No modules found.</p>');
                }
            },
            error: function(xhr, status, error) {
                console.log(xhr.responseText);
                showAlert('error', 'Error Fetching Modules', 'There was an error fetching the modules.');
                $('#modulesList').html('<p>Error fetching modules.</p>');
            }
        });
    }

    function displayModules(modules) {
        let modulesHtml = `
            <table class="min-w-full bg-white border border-gray-300 rounded-lg shadow-md text-sm">
                <thead>
                    <tr class="bg-[#044389] text-left text-white">
                        <th class="py-1 px-2 border-b">Module Name</th>
                        <th class="py-1 px-2 border-b">Module Alias</th>
                        <th class="py-1 px-2 border-b">Seq #</th>
                        <th class="py-1 px-2 border-b">Actions</th>
                    </tr>
                </thead>
                <tbody>
        `;

        modules.forEach(function(module) {
            modulesHtml += `
                <tr class="hover:bg-gray-50">
                    <td class="py-1 px-2 border-b">${module.module_name}</td>
                    <td class="py-1 px-2 border-b">${module.module_alias}</td>
                    <td class="py-1 px-2 border-b">${module.sequence_number}</td>
                    <td class="py-1 px-2 border-b">
                        <div class="flex gap-1 justify-center">
                            <!-- Edit Button: Green Background -->
                            <button class="edit-btn text-white bg-green-500 hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-50 rounded-lg px-2 py-1 transition-all text-xs"
                                data-id="${module.id}"
                                data-name="${module.module_name}"
                                data-alias="${module.module_alias}"
                                data-sequence="${module.sequence_number}">
                                <i class="fas fa-edit text-xs"></i>
                                <span class="hidden sm:inline"> Edit</span>
                            </button>
                            <!-- Delete Button: Red Background -->
                            <button class="delete-btn text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-opacity-50 rounded-lg px-2 py-1 transition-all text-xs"
                                data-id="${module.id}"
                                data-alias="${module.module_alias}">
                                <i class="fas fa-trash text-xs"></i>
                                <span class="hidden sm:inline"> Delete</span>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });

        modulesHtml += `
                </tbody>
            </table>
        `;

        $('#modulesList').html(modulesHtml);

        $('.edit-btn').on('click', function() {
            const moduleId = $(this).data('id');
            const moduleName = $(this).data('name');
            const moduleAlias = $(this).data('alias');
            const sequenceNumber = $(this).data('sequence');

            showEditDialog(moduleId, moduleName, moduleAlias, sequenceNumber);
        });

        $('.delete-btn').on('click', function() {
            const moduleId = $(this).data('id');
            const moduleAlias = $(this).data('alias');
            deleteModule(moduleId, moduleAlias);
        });
    }



    function updateModule(moduleId, moduleName, sequenceNumber) {
        $.ajax({
            url: BASE_URL + 'backend/module_management.php?action=update',
            type: 'POST',
            data: {
                moduleId: moduleId,
                moduleName: moduleName,
                sequenceNumber: sequenceNumber
            },
            success: function(response) {
                if(response.success) {
                    showAlert('success', 'Module Updated', 'Module updated successfully');
                } else {
                    showAlert('error', 'Error Updating Module', 'Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                showAlert('error', 'Error Updating Module', 'Error: Could not update module.');
            }
        });
    }

// Delete module function using the custom showConfirmationAlert
function deleteModule(moduleId, moduleAlias) {
    showConfirmationAlert(
        'Are you sure?',
        `You are about to delete the module ${moduleAlias}. This action cannot be undone.`,
        function() {  // onConfirm
            $.ajax({
                url: BASE_URL + 'backend/module_management.php?action=delete',
                type: 'POST',
                data: {
                    moduleId: moduleId,
                    moduleAlias: moduleAlias
                },
                success: function(response) {
                    if(response.success) {
                        showAlert('success', 'Module Deleted', 'The module has been deleted.');
                        fetchModules();  // Refresh the list after deletion
                    } else {
                        showAlert('error', 'Error Deleting Module', 'There was an issue deleting the module.');
                    }
                },
                error: function(xhr, status, error) {
                    showAlert('error', 'Error Deleting Module', 'Could not delete the module.');
                }
            });
        },
    );
}

function showEditDialog(moduleId, moduleName, moduleAlias, sequenceNumber) {
    const title = "Edit Module";
    $("#dialog_emp").remove();
    $('body').append("<div id='dialog_emp'></div>");
    SYS_dialog3('#dialog_emp', '400', '450', title, function() {
        saveEditedModule(moduleId);
    });

    const editForm = `
        <div class="p-4">
            <div class="mb-4">
                <label for="editModuleName" class="block text-gray-700 font-medium mb-2">Module Name:</label>
                <input type="text" id="editModuleName" 
                       class="w-full border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-500" 
                       value="${moduleName}">
            </div>
            <div class="mb-4">
                <label for="editModuleAlias" class="block text-gray-700 font-medium mb-2">Module Alias:</label>
                <input type="text" id="editModuleAlias" 
                       class="w-full border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-500" 
                       value="${moduleAlias}" readonly>
            </div>
            <div class="mb-4">
                <label for="editSequenceNumber" class="block text-gray-700 font-medium mb-2">Sequence Number:</label>
                <input type="number" id="editSequenceNumber" 
                       class="w-full border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-500" 
                       value="${sequenceNumber}">
            </div>
        </div>
    `;

    $("#dialog_emp").html(editForm).dialog("open");
}

function saveEditedModule(moduleId) {
    const moduleName = $("#editModuleName").val();
    const sequenceNumber = $("#editSequenceNumber").val();

    $.ajax({
        url: BASE_URL + 'backend/module_management.php?action=update',
        type: 'POST',
        data: {
            moduleId: moduleId,
            moduleName: moduleName,
            sequenceNumber: sequenceNumber
        },
        success: function(response) {
            if (response.success) {
                showAlert('success', 'Module Updated', 'Module updated successfully.');
                fetchModules();
            } else {
                showAlert('error', 'Error Updating Module', 'Error: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            showAlert('error', 'Error Updating Module', 'Error: Could not update module.');
        }
    });
}


    $(document).ready(function() {
        fetchModules();
    });


</script>