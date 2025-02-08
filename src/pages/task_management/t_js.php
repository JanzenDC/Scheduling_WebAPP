<script>
const BASE_URL = '<?php echo $baseUrl; ?>';

$(document).ready(function() {
    fetchTasks();

});
function fetchTasks() {
    $.ajax({
        url: BASE_URL + 'backend/edittask_management.php?action=fetch_all',
        type: 'GET',
        success: function(response) {
            if (response.success) {
                displayTasks(response.data);
            }
        },
        error: function(xhr, status, error) {
            showAlert('error', 'Error Fetching Data', 'There was an error fetching the task data.');
        }
    });
}

function displayTasks(data) {
    if ($.fn.DataTable.isDataTable('#taskManagement')) {
        $('#taskManagement').DataTable().destroy();
    }
    
    const tableBody = $('#taskManagement tbody');
    tableBody.empty();

    data.forEach(task => {
        // Join the assigned users (assuming assigned_users is an array of users)
        const assignedUsers = task.assigned_users.join(', '); // Combine the users into a string
        
        // Check user permissions
        const canEdit = userPermissions.can_edit;
        const canDelete = userPermissions.can_delete;

        const taskRow = `
            <tr class="border-b">
                <td class="px-2 py-1 text-sm">${getSafeValue(task.task_id)}</td>
                <td class="px-2 py-1 text-sm">${getSafeValue(task.task_name)}</td>
                <td class="px-2 py-1 text-sm">${getSafeValue(task.task_date)}</td>
                <td class="px-2 py-1 text-sm">${getSafeValue(task.start_time)}</td>
                <td class="px-2 py-1 text-sm">${getSafeValue(task.end_time)}</td>
                <td class="px-2 py-1 text-sm">${assignedUsers}</td>
                <td class="text-center">
                    <button class="action-btn bg-blue-500 text-white px-3 py-1 text-xs rounded-md hover:bg-blue-700 focus:outline-none mr-2" onclick="viewTask(${task.task_id})" data-toggle="tooltip" data-placement="top" title="View">
                        <i class="fas fa-eye"></i>
                    </button>
                    
                    ${canEdit ? `
                    <button class="action-btn bg-yellow-500 text-white px-3 py-1 text-xs rounded-md hover:bg-yellow-700 focus:outline-none mr-2" onclick="editTask(${task.task_id})" data-toggle="tooltip" data-placement="top" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    ` : ''}

                    ${canDelete ? `
                    <button class="action-btn bg-red-500 text-white px-3 py-1 text-xs rounded-md hover:bg-red-700 focus:outline-none mr-2" onclick="deleteTask(${task.task_id})" data-toggle="tooltip" data-placement="top" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                    ` : ''}
                </td>
            </tr>
        `;
        tableBody.append(taskRow);
    });

    initializeDataTable('#taskManagement');
    initializePopperTooltips(); 
}
function formatUser(user) {
    if (!user.id) return user.text;
    
    const parts = user.text.split(' - ');
    const name = parts[0];
    const position = parts[1] || 'No Position Assigned';
    
    return $(`
        <div class="select2-result-user">
            <div class="user-name font-medium">${name}</div>
            <div class="user-position text-sm text-gray-600">${position}</div>
        </div>
    `);
}

// Custom formatting for selected items
function formatUserSelection(user) {
    if (!user.id) return user.text;
    return user.text;
}

function editTask(targetID) {
    const title = "Edit Tasks";
    $("#dialog_emp").remove();
    $('body').append("<div id='dialog_emp'></div>");
    SYS_dialog3('#dialog_emp', '400', '450', title, function() {
        saveTaskEdit();
    });

    // Fetch task details
    $.ajax({
        url: BASE_URL + 'backend/edittask_management.php?action=fetch_task_details',
        type: 'GET',
        data: { task_id: targetID },
        success: function(response) {
            if (response.success) {
                const task = response.data.task;
                const selectedUsers = response.data.selected_users;

                const str = `
                    <form id="editTaskForm" class="p-4">
                        <input type="hidden" id="task_id" value="${getSafeValue(task.task_id)}">
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1" for="task_name">Task Name</label>
                            <input type="text" id="task_name" class="w-full px-3 py-2 border rounded-md" 
                                value="${getSafeValue(task.task_name)}" required>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1" for="task_date">Task Date</label>
                            <input type="date" id="task_date" class="w-full px-3 py-2 border rounded-md" 
                                value="${task.task_date}" required>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium mb-1" for="start_time">Start Time</label>
                                <input type="time" id="start_time" class="w-full px-3 py-2 border rounded-md" 
                                    value="${task.start_time}" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1" for="end_time">End Time</label>
                                <input type="time" id="end_time" class="w-full px-3 py-2 border rounded-md" 
                                    value="${task.end_time}" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1">Assign Users</label>
                            <select id="assigned_users" class="w-full" multiple="multiple"></select>
                        </div>
                    </form>
                `;

                $("#dialog_emp").html(str).dialog("open");

                // Initialize Select2
                $('#assigned_users').select2({
                    width: '100%',
                    placeholder: 'Search and select users',
                    ajax: {
                        url: BASE_URL + 'backend/edittask_management.php?action=search_users',
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return {
                                search: params.term,
                                page: params.page || 1
                            };
                        },
                        processResults: function (data, params) {
                            params.page = params.page || 1;
                            return {
                                results: data.data,
                                pagination: {
                                    more: data.pagination.more
                                }
                            };
                        },
                        cache: true
                    },
                    minimumInputLength: 1,
                    templateResult: formatUser,
                    templateSelection: formatUserSelection
                });

                // Set pre-selected users
                if (selectedUsers.length > 0) {
                    $.ajax({
                        url: BASE_URL + 'backend/edittask_management.php?action=get_selected_users',
                        type: 'GET',
                        data: { user_ids: selectedUsers },
                        success: function(response) {
                            if (response.success) {
                                response.data.forEach(user => {
                                    const option = new Option(
                                        user.text, 
                                        user.id, 
                                        true, 
                                        true
                                    );
                                    $('#assigned_users').append(option);
                                });
                                $('#assigned_users').trigger('change');
                            }
                        }
                    });
                }
            } else {
                showAlert('error', 'Error', response.message);
            }
        },
        error: function() {
            showAlert('error', 'Error', 'Failed to fetch task details.');
        }
    });
}

function saveTaskEdit() {
    const taskData = {
        task_id: $('#task_id').val(),
        task_name: $('#task_name').val(),
        task_date: $('#task_date').val(),
        start_time: $('#start_time').val(),
        end_time: $('#end_time').val(),
        assigned_users: $('#assigned_users').val()
    };

    $.ajax({
        url: BASE_URL + 'backend/edittask_management.php?action=update',
        type: 'POST',
        data: taskData,
        success: function(response) {
            if (response.success) {
                showAlert('success', 'Success', response.message);
                $("#dialog_emp").dialog("close");
                fetchTasks();
            } else {
                showAlert('error', 'Error', response.message);
            }
        },
        error: function() {
            showAlert('error', 'Error', 'Failed to update task.');
        }
    });
}
function viewTask(targetID) {
    const title = "View Task Details";
    $("#dialog_emp").remove();
    $('body').append("<div id='dialog_emp'></div>");
    SYS_dialog2('#dialog_emp', '400', '800', title, function() {});

    const str = `
        <div class="p-4">
            <iframe 
                id="taskPdfFrame"
                src="${BASE_URL}backend/edittask_management.php?action=view_pdf&task_id=${targetID}"
                width="100%"
                height="500px"
                frameborder="0"
                style="border: 1px solid #ddd; border-radius: 4px;"
            ></iframe>
        </div>
        <div class="text-center mt-4">
            <button 
                onclick="downloadTaskPDF(${targetID})" 
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
            >
                Download PDF
            </button>
        </div>
    `;

    $("#dialog_emp").html(str).dialog("open");
}

function downloadTaskPDF(taskID) {
    window.open(`${BASE_URL}backend/edittask_management.php?action=download_pdf&task_id=${taskID}`, '_blank');
}

function deleteTask(targetID) {
    showConfirmationAlert(
        'Are you sure?',
        'You are about to delete this tasks?. This action cannot be undone.',
        function() {  
            $.ajax({
                url: BASE_URL + 'backend/edittask_management.php?action=delete',
                type: 'POST',
                data: { targetID: targetID },
                success: function(response) {
                    if(response.success) {
                        showAlert('success', 'Success', response.message);
                        location.reload();
                    } else {
                        showAlert('error', 'Error', response.message);
                    }
                },
                error: function() {
                    showAlert('error', 'Error', 'Failed to delete tasks.');
                }
            });
        }
    );
}

</script>