<script>
const BASE_URL = '<?php echo $baseUrl; ?>';

function addTaskToCalendar(task) {
    const event = {
        title: task.task_name,
        start: task.start_time,
        end: task.end_time,
        description: task.description,
        assigned_users: task.assigned_users,
        id: task.id
    };

    $('#calendarTask').fullCalendar('renderEvent', event);
}

function validateForm() {
    const taskName = $('#taskName').val();
    const taskDate = $('#taskDate').val();
    const startTime = $('#startTime').val();
    const endTime = $('#endTime').val();
    
    if (!taskName || !taskDate || !startTime || !endTime) {
        showNotification('error', 'Please fill in all required fields');
        return false;
    }
    
    if (selectedUsers.length === 0) {
        showNotification('error', 'Please select at least one user');
        return false;
    }
    
    return true;
}


$(document).ready(function() {    
    $('#calendarTask').fullCalendar({
        header: {
            left: 'prev,next today',
            center: 'title',
            right: 'month,agendaWeek,agendaDay'
        },
        editable: true,
        height: 700,
        contentHeight: 'auto',
        
        // Load events from the server
        events: function(start, end, timezone, callback) {
            $.ajax({
                url: BASE_URL + 'backend/edittask_management.php?action=fetch_calendar_events',
                type: 'GET',
                data: {
                    start: start.format('YYYY-MM-DD'),
                    end: end.format('YYYY-MM-DD')
                },
                success: function(response) {
                    if (response.success) {
                        const events = response.data.map(task => ({
                            id: task.task_id,
                            title: task.task_name,
                            start: task.task_date + 'T' + task.start_time,
                            end: task.task_date + 'T' + task.end_time,
                            allDay: false
                        }));
                        callback(events);
                    }
                }
            });
        },
        
        // Handle event click
        eventClick: function(calEvent, jsEvent, view) {
            const title = "Task Details";
            $("#taskViewDialog").remove();
            $('body').append("<div id='taskViewDialog'></div>");
            
            // Create dialog with iframe
            $("#taskViewDialog").dialog({
                title: title,
                width: 800,
                height: 600,
                modal: true,
                open: function() {
                    $(this).html(`
                        <div class="p-4">
                            <iframe 
                                id="taskPdfFrame"
                                src="${BASE_URL}backend/edittask_management.php?action=view_pdf&task_id=${calEvent.id}"
                                width="100%"
                                height="500px"
                                frameborder="0"
                                style="border: 1px solid #ddd; border-radius: 4px;"
                            ></iframe>
                        </div>
                        <div class="text-center mt-4">
                            <button 
                                onclick="downloadTaskPDF(${calEvent.id})" 
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                            >
                                Download PDF
                            </button>
                        </div>
                    `);
                },
                buttons: {
                    Close: function() {
                        $(this).dialog("close");
                    }
                }
            });
        },
        
        // Optional: Handle event drag and drop
        eventDrop: function(event, delta, revertFunc) {
            updateTaskDate(event.id, event.start, event.end, revertFunc);
        },
        
        // Optional: Handle event resize
        eventResize: function(event, delta, revertFunc) {
            updateTaskDate(event.id, event.start, event.end, revertFunc);
        }
    });

});

// Function to update task date/time when dragged or resized
function updateTaskDate(taskId, newStart, newEnd, revertFunc) {
    $.ajax({
        url: BASE_URL + 'backend/edittask_management.php?action=update_task_datetime',
        type: 'POST',
        data: {
            task_id: taskId,
            task_date: newStart.format('YYYY-MM-DD')
        },
        success: function(response) {
            if (!response.success) {
                revertFunc();
                showAlert('error', 'Error', response.message);
            }
        },
        error: function() {
            revertFunc();
            showAlert('error', 'Error', 'Failed to update task date.');
        }
    });
}

// Download PDF function
function downloadTaskPDF(taskId) {
    window.open(`${BASE_URL}backend/edittask_management.php?action=download_pdf&task_id=${taskId}`, '_blank');
}
</script>
