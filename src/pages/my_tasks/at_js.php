<script>
const BASE_URL = '<?php echo $baseUrl; ?>';

$(document).ready(function() {
    fetchTasks();

});
function fetchTasks() {
    $.ajax({
        url: BASE_URL + 'backend/edittask_management.php?action=fetch_all_target',
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
                </td>
            </tr>
        `;
        tableBody.append(taskRow);
    });

    initializeDataTable('#taskManagement');
    initializePopperTooltips(); 
}

function viewTask(targetID) {
    const title = "View Task Details";
    $("#dialog_emp").remove();
    $('body').append("<div id='dialog_emp'></div>");
    SYS_dialog2('#dialog_emp', '650', '500', title, function() {});

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
    `;

    $("#dialog_emp").html(str).dialog("open");
}

function downloadTaskPDF(taskID) {
    window.open(`${BASE_URL}backend/edittask_management.php?action=download_pdf&task_id=${taskID}`, '_blank');
}

</script>