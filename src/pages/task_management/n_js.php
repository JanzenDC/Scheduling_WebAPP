<script>
const BASE_URL = '<?php echo $baseUrl; ?>';

// Fetch and display users
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

// Display users in the selection area
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

    // Initialize search functionality
    $('#userSearch').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        $('.user-item').each(function() {
            const userName = $(this).find('span').text().toLowerCase();
            $(this).toggle(userName.includes(searchTerm));
        });
    });
}

// Handle user selection
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

// Update selected users display
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

// Check for conflicts
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

// Create the task
function createTask() {
    if (!validateForm()) return;
    
    const taskData = {
        task_name: $('#taskName').val(),
        description: $('#taskDescription').val(), // New field
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

// Form validation
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

// Show notification
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

// Reset form
function resetForm() {
    $('#taskName').val('');
    $('#taskDescription').val(''); // New field
    $('#taskDate').val('');
    $('#startTime').val('');
    $('#endTime').val('');
    selectedUsers = [];
    updateSelectedUsersList();
    $('#conflictsArea').empty();
    $('.select-user-btn').removeClass('selected bg-blue-100').text('Select');
}

// Initialize
$(document).ready(function() {
    fetchUsers();
    
    $('#checkConflicts').click(checkConflicts);
    $('#createTask').click(createTask);
});
</script>