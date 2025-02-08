<script>
const BASE_URL = '<?php echo $baseUrl; ?>';

function fetchUsers() {
    $.ajax({
        url: BASE_URL + 'backend/createtask_management.php?action=fetch_users',
        type: 'GET',
        success: function(response) {
            if (response.success) {
                displayUsers(response.data);
            } else {
                showNotification('error', response.message);
            }
        },
        error: function() {
            showNotification('error', 'Failed to fetch users');
        }
    });
}

function displayUsers(users) {
    const userContainer = $('#peopleInvolved');
    userContainer.empty();
    
    users.forEach(user => {
        const userElement = `
            <div class="user-item p-3 border rounded-lg hover:bg-gray-50" data-user-id="${user.id}">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-900">${user.name}</span>
                    <button class="select-user-btn px-3 py-1 text-sm text-blue-600 hover:bg-blue-50 rounded-md">
                        Select
                    </button>
                </div>
            </div>
        `;
        userContainer.append(userElement);
    });

    $('#userSearch').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        $('.user-item').each(function() {
            const userName = $(this).find('span').text().toLowerCase();
            $(this).toggle(userName.includes(searchTerm));
        });
    });
}

let selectedUsers = [];

$(document).on('click', '.select-user-btn', function() {
    const userItem = $(this).closest('.user-item');
    const userId = userItem.data('user-id');
    const userName = userItem.find('span').text();
    
    if ($(this).hasClass('selected')) {
        $(this).removeClass('selected bg-blue-100').text('Select');
        selectedUsers = selectedUsers.filter(user => user.id !== userId);
    } else {
        $(this).addClass('selected bg-blue-100').text('Selected');
        selectedUsers.push({ id: userId, name: userName });
    }
    
    updateSelectedUsersList();
});

function updateSelectedUsersList() {
    const selectedList = $('#selectedUsers');
    selectedList.empty();
    
    selectedUsers.forEach(user => {
        selectedList.append(`
            <div class="inline-flex items-center px-2.5 py-1.5 rounded-md bg-blue-100 text-blue-700 mr-2 mb-2">
                <span class="text-sm">${user.name}</span>
                <button class="ml-1.5 text-blue-500 hover:text-blue-700" onclick="removeUser(${user.id})">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        `);
    });
}

function removeUser(userId) {
    selectedUsers = selectedUsers.filter(user => user.id !== userId);
    $(`.user-item[data-user-id="${userId}"] .select-user-btn`)
        .removeClass('selected bg-blue-100')
        .text('Select');
    updateSelectedUsersList();
}

function displayConflicts(conflicts) {
    const conflictContainer = $('#conflictContainer');
    conflictContainer.removeClass('hidden');

    const conflictTable = $('#conflictTable');
    conflictTable.empty();

    if (conflicts.length === 0) {
        conflictTable.append('<p class="text-gray-700">No conflicts found.</p>');
        return;
    }

    let table = `
        <table class="min-w-full bg-white border border-gray-200 rounded-lg">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Task Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start Time</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">End Time</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
    `;

    conflicts.forEach(conflict => {
        table += `
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${conflict.user_name}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${conflict.task_name}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${conflict.start_time}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${conflict.end_time}</td>
            </tr>
        `;
    });

    table += `</tbody></table>`;
    conflictTable.append(table);
}

function checkConflicts() {
    if (!validateForm()) return;

    const taskData = {
        task_date: $('#taskDate').val(),
        start_time: $('#startTime').val(),
        end_time: $('#endTime').val(),
        user_ids: selectedUsers.map(user => user.id)
    };
    
    $.ajax({
        url: BASE_URL + 'backend/createtask_management.php?action=check_conflicts',
        type: 'POST',
        data: JSON.stringify(taskData),
        contentType: 'application/json',
        success: function(response) {
            if (response.success) {
                displayConflicts(response.data);
            } else {
                showNotification('error', response.message);
            }
        },
        error: function() {
            showNotification('error', 'Failed to check conflicts');
        }
    });
}

function createTask() {
    if (!validateForm()) return;
    
    const taskData = {
        task_name: $('#taskName').val(),
        description: $('#taskDescription').val(),
        task_date: $('#taskDate').val(),
        start_time: $('#startTime').val(),
        end_time: $('#endTime').val(),
        assigned_users: selectedUsers.map(user => user.id)
    };
    
    $.ajax({
        url: BASE_URL + 'backend/createtask_management.php?action=create_task',
        type: 'POST',
        data: JSON.stringify(taskData),
        contentType: 'application/json',
        success: function(response) {
            if (response.success) {
                showNotification('success', 'Task created successfully');
                addTaskToCalendar(response.data);
                resetForm();
            } else {
                showNotification('error', response.message);
            }
        },
        error: function() {
            showNotification('error', 'Failed to create task');
        }
    });
}

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

function showNotification(type, message) {
    const notificationArea = $('#notificationArea');
    const bgColor = type === 'error' ? 'bg-red-100' : 'bg-green-100';
    const textColor = type === 'error' ? 'text-red-800' : 'text-green-800';
    
    notificationArea.html(`
        <div class="rounded-md ${bgColor} p-4 mb-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    ${type === 'error' 
                        ? '<svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>' 
                        : '<svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>'}
                </div>
                <div class="ml-3">
                    <p class="text-sm ${textColor}">${message}</p>
                </div>
            </div>
        </div>
    `).fadeIn();
    
    setTimeout(() => {
        notificationArea.fadeOut();
    }, 3000);
}

function resetForm() {
    $('#taskName').val('');
    $('#taskDescription').val('');
    $('#taskDate').val('');
    $('#startTime').val('');
    $('#endTime').val('');
    selectedUsers = [];
    updateSelectedUsersList();
    $('#conflictsArea').empty();
    $('.select-user-btn').removeClass('selected bg-blue-100').text('Select');
}

$(document).ready(function() {
    fetchUsers();
    
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

    $('#checkConflicts').click(checkConflicts);
    $('#createTask').click(createTask);
});

// Function to update task date/time when dragged or resized
function updateTaskDate(taskId, newStart, newEnd, revertFunc) {
    $.ajax({
        url: BASE_URL + 'backend/edittask_management.php?action=update_task_datetime',
        type: 'POST',
        data: {
            task_id: taskId,
            task_date: newStart.format('YYYY-MM-DD'),
            start_time: newStart.format('HH:mm:ss'),
            end_time: newEnd.format('HH:mm:ss')
        },
        success: function(response) {
            if (!response.success) {
                revertFunc();
                showAlert('error', 'Error', response.message);
            }
        },
        error: function() {
            revertFunc();
            showAlert('error', 'Error', 'Failed to update task date/time.');
        }
    });
}

// Download PDF function
function downloadTaskPDF(taskId) {
    window.open(`${BASE_URL}backend/edittask_management.php?action=download_pdf&task_id=${taskId}`, '_blank');
}
</script>
