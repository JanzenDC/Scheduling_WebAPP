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

function editTask(targetID){
    const title = "Edit Tasks";
    $("#dialog_emp").remove();
    $('body').append("<div id='dialog_emp'></div>");
    SYS_dialog3('#dialog_emp', '400', '450', title, function() {
        saveTaskEdit();
    });

    const str = `

    `;

    $("#dialog_emp").html(str).dialog("open");    
}

function viewTask(targetID){
    const title = "Edit Tasks";
    $("#dialog_emp").remove();
    $('body').append("<div id='dialog_emp'></div>");
    SYS_dialog2('#dialog_emp', '400', '450', title, function() {
    });

    const str = `

    `;

    $("#dialog_emp").html(str).dialog("open");    
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