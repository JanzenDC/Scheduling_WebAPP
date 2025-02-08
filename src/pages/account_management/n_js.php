<script>
const BASE_URL = '<?php echo $baseUrl; ?>';
// Add button in the table header
$(document).ready(function() {
    console.log(userPermissions.can_add)
    if (userPermissions.can_add === 1) {
        const addUserButton = `
            <div class="mb-4">
                <button onclick="addUser()" class="bg-[#044389] text-white px-4 py-2 rounded-md hover:bg-[#3cc5dd] focus:outline-none">
                    <i class="fas fa-plus"></i> Add User
                </button>
            </div>
        `;
        $('#usersTable').before(addUserButton);

        const addRoleButton = `
            <div class="mb-4">
                <button onclick="addRole()" class="bg-[#044389] text-white px-4 py-2 rounded-md hover:bg-[#3cc5dd] focus:outline-none">
                    <i class="fas fa-plus"></i> Add Role
                </button>
            </div>
        `;
        $('#rolesTable').before(addRoleButton);
    }

    // Fetch data for tables
    fetchUsers();
    fetchRoles();
    fetchAssignedRoles();
});


function fetchUsers() {
    $.ajax({
        url: BASE_URL + 'backend/user_management.php?action=fetch_all',
        type: 'GET',
        success: function(response) {
            if (response.success) {
                displayUsers(response.data);
            } else {
                showAlert('error', 'Error Fetching Data', response.message);
            }
        },
        error: function(xhr, status, error) {
            showAlert('error', 'Error Fetching Data', 'There was an error fetching the user data.');
        }
    });
}

function displayUsers(data) {
    if ($.fn.DataTable.isDataTable('#usersTable')) {
        $('#usersTable').DataTable().destroy();
    }
    const tableBody = $('#usersTable tbody');
    tableBody.empty();

    data.forEach(user => {
        const firstName = getSafeValue(user.fname);
        const middleName = getSafeValue(user.mname);
        const lastName = getSafeValue(user.lname);
        
        let fullName = `${lastName}, ${firstName}`;
        if (middleName) {
            fullName += ` ${middleName.charAt(0)}.`;
        }

        const userRow = `
            <tr class="border-b">
                <td class="px-2 py-1 text-sm">${getSafeValue(user.user_id)}</td>
                <td class="px-2 py-1 text-sm">${fullName}</td>
                <td class="px-2 py-1 text-sm">${getSafeValue(user.email)}</td>
                <td class="px-2 py-1 text-center flex justify-center items-center">
                    <button class="action-btn bg-blue-500 text-white px-3 py-1 text-xs rounded-md hover:bg-blue-700 focus:outline-none mr-2" onclick="viewUser(${user.user_id})" data-toggle="tooltip" data-placement="top" title="View">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="action-btn bg-yellow-500 text-white px-3 py-1 text-xs rounded-md hover:bg-yellow-700 focus:outline-none mr-2" onclick="editUser(${user.user_id})" data-toggle="tooltip" data-placement="top" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="action-btn bg-red-500 text-white px-3 py-1 text-xs rounded-md hover:bg-red-700 focus:outline-none mr-2" onclick="deleteUser(${user.user_id})" data-toggle="tooltip" data-placement="top" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                    <button class="action-btn bg-green-500 text-white px-3 py-1 text-xs rounded-md hover:bg-[#044389] focus:outline-none" onclick="regeneratePassword(${user.user_id})" data-toggle="tooltip" data-placement="top" title="Regenerate Password">
                        <i class="fas fa-key"></i>
                    </button>
                </td>
            </tr>
        `;
        tableBody.append(userRow);
    });

    // Initialize Popper.js tooltips for each action button
    initializePopperTooltips();

    initializeDataTable('#usersTable');
}

function regeneratePassword(userId) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This will regenerate the user's password and send it via email.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, regenerate it!',
        preConfirm: () => {
            return $.ajax({
                url: BASE_URL + 'backend/user_management.php?action=regeneratePassword',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ user_id: userId }),
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Password Regenerated!',
                            text: 'The new password has been sent to the user\'s email.',
                            icon: 'success',
                            confirmButtonColor: '#3085d6'
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: response.message || 'An error occurred while regenerating the password.',
                            icon: 'error',
                            confirmButtonColor: '#3085d6'
                        });
                    }
                },
                error: function(err) {
                    Swal.fire({
                        title: 'Error',
                        text: 'There was an issue processing your request.',
                        icon: 'error',
                        confirmButtonColor: '#3085d6'
                    });
                }
            });
        }
    });
}


function addUser() {
    const title = "Add New User";
    $("#dialog_emp").remove();
    $('body').append("<div id='dialog_emp'></div>");
    SYS_dialog3('#dialog_emp', '400', '450', title, function() {
        saveUser('create');
    });

    const str = `
        <form id="userForm" class="p-4">
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">First Name*</label>
                <input type="text" name="fname" class="w-full p-2 border rounded" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Middle Name</label>
                <input type="text" name="mname" class="w-full p-2 border rounded">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Last Name*</label>
                <input type="text" name="lname" class="w-full p-2 border rounded" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Email*</label>
                <input type="email" name="email" class="w-full p-2 border rounded" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Phone Number</label>
                <input type="tel" name="phone_number" class="w-full p-2 border rounded">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Address</label>
                <input type="text" name="address" class="w-full p-2 border rounded">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">City</label>
                <input type="text" name="city" class="w-full p-2 border rounded">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">State</label>
                <input type="text" name="state" class="w-full p-2 border rounded">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Country</label>
                <input type="text" name="country" class="w-full p-2 border rounded">
            </div>
        </form>
    `;

    $("#dialog_emp").html(str).dialog("open");
}

function viewUser(userId) {
    $.ajax({
        url: BASE_URL + 'backend/user_management.php?action=fetch_single&user_id=' + userId,
        type: 'GET',
        success: function(response) {
            if (response.success) {
                displayUserDetails(response.data);
            } else {
                showAlert('error', 'Error', response.message);
            }
        },
        error: function() {
            showAlert('error', 'Error', 'Failed to fetch user details.');
        }
    });
}

function displayUserDetails(user) {
    const title = "View User Details";
    $("#dialog_emp").remove();
    $('body').append("<div id='dialog_emp'></div>");
    SYS_dialog3('#dialog_emp', '400', '450', title);

    const str = `
        <div class="p-4">
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Full Name</label>
                <p class="text-gray-700">${getSafeValue(user.fname)} ${getSafeValue(user.mname)} ${getSafeValue(user.lname)}</p>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Email</label>
                <p class="text-gray-700">${getSafeValue(user.email)}</p>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Phone Number</label>
                <p class="text-gray-700">${getSafeValue(user.phone_number)}</p>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Address</label>
                <p class="text-gray-700">${getSafeValue(user.address)}</p>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">City</label>
                <p class="text-gray-700">${getSafeValue(user.city)}</p>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">State</label>
                <p class="text-gray-700">${getSafeValue(user.state)}</p>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Country</label>
                <p class="text-gray-700">${getSafeValue(user.country)}</p>
            </div>
        </div>
    `;

    $("#dialog_emp").html(str).dialog("open");
}

function editUser(userId) {
    $.ajax({
        url: BASE_URL + 'backend/user_management.php?action=fetch_single&user_id=' + userId,
        type: 'GET',
        success: function(response) {
            if (response.success) {
                displayEditForm(response.data);
            } else {
                showAlert('error', 'Error', response.message);
            }
        },
        error: function() {
            showAlert('error', 'Error', 'Failed to fetch user details.');
        }
    });
}

function displayEditForm(user) {
    const title = "Edit User";
    $("#dialog_emp").remove();
    $('body').append("<div id='dialog_emp'></div>");
    SYS_dialog3('#dialog_emp', '400', '450', title, function() {
        saveUser('update');
    });

    const str = `
        <form id="userForm" class="p-4">
            <input type="hidden" name="user_id" value="${getSafeValue(user.user_id)}">
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">First Name*</label>
                <input type="text" name="fname" class="w-full p-2 border rounded" value="${getSafeValue(user.fname)}" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Middle Name</label>
                <input type="text" name="mname" class="w-full p-2 border rounded" value="${getSafeValue(user.mname)}">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Last Name*</label>
                <input type="text" name="lname" class="w-full p-2 border rounded" value="${getSafeValue(user.lname)}" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Email*</label>
                <input type="email" name="email" class="w-full p-2 border rounded" value="${getSafeValue(user.email)}" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Phone Number</label>
                <input type="tel" name="phone_number" class="w-full p-2 border rounded" value="${getSafeValue(user.phone_number)}">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Address</label>
                <input type="text" name="address" class="w-full p-2 border rounded" value="${getSafeValue(user.address)}">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">City</label>
                <input type="text" name="city" class="w-full p-2 border rounded" value="${getSafeValue(user.city)}">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">State</label>
                <input type="text" name="state" class="w-full p-2 border rounded" value="${getSafeValue(user.state)}">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Country</label>
                <input type="text" name="country" class="w-full p-2 border rounded" value="${getSafeValue(user.country)}">
            </div>
        </form>
    `;

    $("#dialog_emp").html(str).dialog("open");
}

function saveUser(action) {
    const formData = {};
    const form = document.getElementById('userForm');
    const formElements = form.elements;

    for (let i = 0; i < formElements.length; i++) {
        const element = formElements[i];
        if (element.name) {
            formData[element.name] = element.value;
        }
    }

    $.ajax({
        url: BASE_URL + 'backend/user_management.php?action=' + action,
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(formData),
        beforeSend: function() {
            Swal.fire({
                title: 'Processing...',
                text: 'Please wait while we process your request...',
                showConfirmButton: false,
                allowOutsideClick: false,
                timer: 2000,
                timerProgressBar: true
            });
        },
        success: function(response) {
            Swal.close();

            if (response.success) {
                Swal.fire({
                    title: 'Success',
                    text: 'Page added successfully.',
                    icon: 'success',
                    confirmButtonColor: '#3085d6'
                });
                $("#dialog_emp").dialog("close");
                location.reload();
            } else {
                Swal.fire({
                    title: 'Error',
                    text: response.message || 'Error adding page.',
                    icon: 'error',
                    confirmButtonColor: '#3085d6'
                });
            }
        },
        error: function(err) {
            Swal.close();

            Swal.fire({
                title: 'Error',
                text: 'There was an issue processing your request.',
                icon: 'error',
                confirmButtonColor: '#3085d6'
            });
        }
    });


}

function deleteUser(userId) {
    showConfirmationAlert(
        'Are you sure?',
        'You are about to delete this user. This action cannot be undone.',
        function() {  // onConfirm
            $.ajax({
                url: BASE_URL + 'backend/user_management.php?action=delete',
                type: 'POST',
                data: { user_id: userId },
                success: function(response) {
                    if(response.success) {
                        showAlert('success', 'Success', response.message);
                        location.reload();
                    } else {
                        showAlert('error', 'Error', response.message);
                    }
                },
                error: function() {
                    showAlert('error', 'Error', 'Failed to delete user.');
                }
            });
        }
    );
}

function getSafeValue(value) {
    return value === null || value === undefined ? '' : value;
}
//ANCHOR - 
function fetchRoles() {
    $.ajax({
        url: BASE_URL + 'backend/roles_management.php?action=fetch_all',
        type: 'GET',
        success: function(response) {
            if (response.success) {
                displayRoles(response.data);
            } else {
                showAlert('error', 'Error Fetching Data', response.message);
            }
        },
        error: function(xhr, status, error) {
            showAlert('error', 'Error Fetching Data', 'There was an error fetching the user data.');
        }
    });
}

function displayRoles(roles) {
    if ($.fn.DataTable.isDataTable('#rolesTable')) {
        $('#rolesTable').DataTable().destroy();
    }
    const rolesTableBody = $('#rolesTable tbody');
    rolesTableBody.empty(); // Clear the existing table content before appending new data

    roles.forEach(role => {
        const roleRow = `
            <tr>
                <td class="px-2 py-1">${role.role_id}</td>
                <td class="px-2 py-1">${role.role_name}</td>
                <td class="px-2 py-1">${role.role_description || 'No Description'}</td>
                <td class="px-2 py-1 text-center">
                    <span class="inline-block ${role.is_active === '1' ? 'bg-green-500' : 'text-black bg-yellow-300'} text-white text-sm font-semibold py-1 px-4 rounded-full">
                        ${role.is_active === '1' ? 'Active' : 'Inactive'}
                    </span>
                </td>
                <td class="flex gap-2 px-2 py-1 text-center">
                    <!-- Edit Button -->
                    <button onclick="editRole(${role.role_id})" class="bg-green-500 text-white hover:bg-green-600 px-2 py-1 rounded-md flex items-center">
                        <i class="fas fa-edit "></i>
                        <span class="hidden md:block">Edit</span>
                    </button>
                    <!-- Delete Button -->
                    <button onclick="deleteRole(${role.role_id})" class="bg-red-600 text-white hover:bg-red-700 px-2 py-1 rounded-md flex items-center">
                        <i class="fas fa-trash-alt "></i>
                        <span class="hidden md:block">Delete</span>
                    </button>
                </td>
            </tr>
        `;
        rolesTableBody.append(roleRow);
    });

    // Initialize DataTable (if applicable)
    initializeDataTable('#rolesTable');
}



function addRole() {
    const title = "Add New Role";
    $("#dialog_emp").remove();
    $('body').append("<div id='dialog_emp'></div>");
    
    SYS_dialog3('#dialog_emp', '400', '450', title, function() {
        saveRole('create');
    });

    const str = `
        <form id="addRoleForm">
            <div class="mb-4">
                <label for="roleName" class="block text-sm font-medium">Role Name</label>
                <input type="text" id="roleName" name="roleName" class="w-full p-2 border border-gray-300 rounded" required>
            </div>
            <div class="mb-4">
                <label for="roleDescription" class="block text-sm font-medium">Role Description</label>
                <textarea id="roleDescription" name="roleDescription" class="w-full p-2 border border-gray-300 rounded"></textarea>
            </div>
            <div class="mb-4">
                <label for="roleActive" class="block text-sm font-medium">Status</label>
                <select id="roleActive" name="roleActive" class="w-full p-2 border border-gray-300 rounded">
                    <option value="1" selected>Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
        </form>
    `;

    $("#dialog_emp").html(str).dialog("open");
}


function editRole(roleId) {
    const title = "Edit Role";
    $("#dialog_emp").remove();
    $('body').append("<div id='dialog_emp'></div>");
    
    SYS_dialog3('#dialog_emp', '400', '450', title, function() {
        saveRole('update', roleId);
    });

    // Fetch the role details from the backend
    $.ajax({
        url: BASE_URL + 'backend/roles_management.php?action=fetch_single&role_id=' + roleId,
        type: 'GET',
        success: function(response) {
            if (response.success) {
                const role = response.data;
                const str = `
                    <form id="editRoleForm">
                        <div class="mb-4">
                            <label for="roleName" class="block text-sm font-medium">Role Name</label>
                            <input type="text" id="roleName" name="roleName" value="${role.role_name}" class="w-full p-2 border border-gray-300 rounded" required>
                        </div>
                        <div class="mb-4">
                            <label for="roleDescription" class="block text-sm font-medium">Role Description</label>
                            <textarea id="roleDescription" name="roleDescription" class="w-full p-2 border border-gray-300 rounded">${role.role_description || ''}</textarea>
                        </div>
                        <div class="mb-4">
                            <label for="roleActive" class="block text-sm font-medium">Status</label>
                            <select id="roleActive" name="roleActive" class="w-full p-2 border border-gray-300 rounded">
                                <option value="1" ${role.is_active === "1" ? 'selected' : ''}>Active</option>
                                <option value="0" ${role.is_active === "0" ? 'selected' : ''}>Inactive</option>
                            </select>
                        </div>
                    </form>
                `;

                $("#dialog_emp").html(str).dialog("open");
            } else {
                showAlert('error', 'Error', response.message);
            }
        },
        error: function() {
            showAlert('error', 'Error', 'There was an error fetching the role data.');
        }
    });
}


function saveRole(action, roleId = null) {
    const roleName = $('#roleName').val();
    const roleDescription = $('#roleDescription').val();
    
    // Pass '0' or '1' as string
    const isActive = $('#roleActive').prop('checked') ? '1' : '0';

    const requestData = {
        role_name: roleName,
        role_description: roleDescription,
        is_active: isActive // Pass as string '0' or '1'
    };
    console.log(requestData);

    if (action === 'update' && roleId) {
        requestData.role_id = roleId; // Add the role_id for update
    }

    // Determine the appropriate API endpoint and action
    const url = BASE_URL + 'backend/roles_management.php?action=' + (action === 'create' ? 'create' : 'update');

    // Send data to the backend
    $.ajax({
        url: url,
        type: 'POST',
        data: JSON.stringify(requestData),
        success: function(response) {
            if (response.success) {
                showAlert('success', action === 'create' ? 'Role Added' : 'Role Updated', 'The role has been successfully ' + (action === 'create' ? 'added' : 'updated') + '.');
                location.reload();  // Reload the page to refresh the roles table
                $('#dialog_emp').dialog('close');
            } else {
                showAlert('error', 'Error', response.message);
            }
        },
        error: function() {
            showAlert('error', 'Error', 'There was an error processing your request.');
        }
    });
}



function deleteRole(roleId) {
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
            // Send a request to delete the role
            $.ajax({
                url: BASE_URL + 'backend/roles_management.php?action=delete',
                type: 'POST',
                data: JSON.stringify({ role_id: roleId }),
                success: function(response) {
                    if (response.success) {
                        Swal.fire(
                            'Deleted!',
                            'The role has been successfully deleted.',
                            'success'
                        );
                        fetchRoles();  // Refresh the roles table
                    } else {
                        Swal.fire(
                            'Error!',
                            response.message,
                            'error'
                        );
                    }
                },
                error: function() {
                    Swal.fire(
                        'Error!',
                        'There was an error deleting the role.',
                        'error'
                    );
                }
            });
        } else if (result.dismiss === Swal.DismissReason.cancel) {
            Swal.fire(
                'Cancelled',
                'The role was not deleted.',
                'info'
            );
        }
    });
}



function fetchAssignedRoles() {
    $.ajax({
        url: BASE_URL + 'backend/user_management.php?action=fetch_all',
        type: 'GET',
        success: function(response) {
            if (response.success) {
                displayUsersRoles(response.data);
            } else {
                showAlert('error', 'Error Fetching Data', response.message);
            }
        },
        error: function(xhr, status, error) {
            showAlert('error', 'Error Fetching Data', 'There was an error fetching the user data.');
        }
    });
}

function displayUsersRoles(data) {
    if ($.fn.DataTable.isDataTable('#assignRoles')) {
        $('#assignRoles').DataTable().destroy();
    }
    const tableBody = $('#assignRoles tbody');
    tableBody.empty();

    data.forEach(user => {
        const firstName = getSafeValue(user.fname);
        const middleName = getSafeValue(user.mname);
        const lastName = getSafeValue(user.lname);
        
        let fullName = `${lastName}, ${firstName}`;
        if (middleName) {
            fullName += ` ${middleName.charAt(0)}.`;
        }

        const roleName = getSafeValue(user.role_name) || 'No Role';

        const userRow = `
            <tr class="border-b">
                <td class="px-2 py-1 text-sm">${getSafeValue(user.user_id)}</td>
                <td class="px-2 py-1 text-sm">${fullName}</td>
                <td class="px-2 py-1 text-sm">${getSafeValue(user.email)}</td>
                <td class="px-2 py-1 text-sm">${roleName}</td>
                <td class="px-2 py-1 text-center flex justify-center items-center space-x-2">
                    <!-- Edit Button: Green Background -->
                    <button class="bg-green-500 text-white px-3 py-1 text-xs rounded-md hover:bg-green-600 focus:outline-none" onclick="editRoleUser(${user.user_id})">
                        <i class="fas fa-edit"></i>
                        <span class="hidden sm:inline-block">Edit</span>
                    </button>
                    <!-- Reset Button: Red Background -->
                    <button class="bg-red-500 text-white px-3 py-1 text-xs rounded-md hover:bg-red-600 focus:outline-none" onclick="resetUserRole(${user.user_id})">
                        <i class="fas fa-times-circle"></i>
                        <span class="hidden sm:inline-block">Reset</span>
                    </button>
                </td>
            </tr>
        `;
        tableBody.append(userRow);
    });

    // Initialize DataTable (if applicable)
    initializeDataTable('#assignRoles');
}



function editRoleUser(userId) {
    // First fetch available roles
    $.ajax({
        url: BASE_URL + 'backend/roles_management.php?action=fetch_all',
        type: 'GET',
        success: function(rolesResponse) {
            if (rolesResponse.success) {
                // Then fetch user's current role
                $.ajax({
                    url: BASE_URL + 'backend/user_management.php?action=fetch_single&user_id=' + userId,
                    type: 'GET',
                    success: function(userResponse) {
                        if (userResponse.success) {
                            displayRoleAssignmentForm(userResponse.data, rolesResponse.data);
                        } else {
                            showAlert('error', 'Error', userResponse.message);
                        }
                    }
                });
            } else {
                showAlert('error', 'Error', rolesResponse.message);
            }
        }
    });
}

function displayRoleAssignmentForm(user, roles) {
    const title = "Assign Role";
    $("#dialog_emp").remove();
    $('body').append("<div id='dialog_emp'></div>");
    
    SYS_dialog3('#dialog_emp', '400', '300', title, function() {
        saveRoleAssignment(user.user_id);
    });

    const fullName = `${getSafeValue(user.fname)} ${getSafeValue(user.mname)} ${getSafeValue(user.lname)}`.trim();
    console.log(user);
    const str = `
        <form id="roleAssignmentForm" class="p-4">
            <input type="hidden" name="user_id" value="${user.user_id}">
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">User</label>
                <p class="text-gray-700">${fullName}</p>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Email</label>
                <p class="text-gray-700">${getSafeValue(user.email)}</p>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1">Assign Role</label>
                <select name="role_id" class="w-full p-2 border rounded">
                    <option value="">No Role</option>
                    ${roles.map(role => `
                        <option value="${role.role_id}" ${user.role_id == role.role_id ? 'selected' : ''}>
                            ${role.role_name}
                        </option>
                    `).join('')}
                </select>
            </div>
        </form>
    `;

    $("#dialog_emp").html(str).dialog("open");
}

function saveRoleAssignment(userId) {
    // Validate role selection
    const roleId = $('#roleAssignmentForm select[name="role_id"]').val();
    
    if (!roleId) {
        Swal.fire({
            icon: 'warning',
            title: 'Select a Role',
            text: 'Please choose a role before saving',
            confirmButtonColor: '#3085d6'
        });
        return;
    }

    // Confirm role assignment
    Swal.fire({
        title: 'Assign Role',
        text: 'Are you sure you want to assign this role?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, assign role!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading sweet alert
            Swal.fire({
                title: 'Assigning Role...',
                html: 'Please wait while we assign the role.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const formData = {
                user_id: userId,
                role_id: roleId
            };

            $.ajax({
                url: BASE_URL + 'backend/roles_management.php?action=assign_role',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(formData),
                success: function(response) {
                    if (response.success) {
                        // Close loading alert and show success
                        Swal.fire({
                            icon: 'success',
                            title: 'Role Assigned Successfully',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            // Close dialog and reload page
                            $("#dialog_emp").dialog("close");
                            location.reload();
                        });
                    } else {
                        // Show error alert
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'Failed to assign role'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    // Detailed error handling
                    let errorMessage = 'Failed to assign role';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (error) {
                        errorMessage = error;
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: errorMessage
                    });
                }
            });
        }
    });
}

function resetUserRole(userId) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This will remove the user's current role assignment",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, reset it!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading sweet alert
            Swal.fire({
                title: 'Resetting Role...',
                html: 'Please wait while we reset the user role.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: BASE_URL + 'backend/roles_management.php?action=reset_role',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ user_id: userId }),
                success: function(response) {
                    if (response.success) {
                        // Close loading alert and show success
                        Swal.fire({
                            icon: 'success',
                            title: 'Role Reset Successfully',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            // Reload the page
                            location.reload();
                        });
                    } else {
                        // Show error alert
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'Failed to reset role'
                        });
                    }
                },
                error: function() {
                    // Show error alert
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to reset role'
                    });
                }
            });
        }
    });
}







</script>