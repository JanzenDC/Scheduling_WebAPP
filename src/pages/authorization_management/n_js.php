<script>
const BASE_URL = '<?php echo $baseUrl; ?>';

$(document).ready(function() {
    fetchRoles();
    
    $('#rolesDropdown').on('change', function() {
        const roleId = $(this).val();
        if (roleId) {
            fetchPagesAndModules(roleId);
        }
    });
    $('.save-permissions').on('click', function() {
        savePermissions();
    });
});

function collectPermissions() {
    const roleId = $('#rolesDropdown').val();
    if (!roleId) {
        showAlert('error', 'Error', 'Please select a role first');
        return null;
    }

    const modulePermissions = [];
    const pagePermissions = [];

    // Collect module permissions
    $('input[data-type="module"]').each(function() {
        const moduleId = $(this).data('id');
        const permission = $(this).data('permission');
        
        let module = modulePermissions.find(m => m.module_id === moduleId);
        if (!module) {
            module = {
                module_id: moduleId,
                can_view: 0,
                can_add: 0,
                can_edit: 0,
                can_delete: 0
            };
            modulePermissions.push(module);
        }
        module[permission] = $(this).is(':checked') ? 1 : 0;
    });

    // Collect page permissions
    $('input[data-type="page"]').each(function() {
        const pageId = $(this).data('id');
        const permission = $(this).data('permission');
        
        let page = pagePermissions.find(p => p.page_id === pageId);
        if (!page) {
            page = {
                page_id: pageId,
                can_view: 0,
                can_add: 0,
                can_edit: 0,
                can_delete: 0
            };
            pagePermissions.push(page);
        }
        page[permission] = $(this).is(':checked') ? 1 : 0;
    });

    return {
        role_id: roleId,
        module_permissions: modulePermissions,
        page_permissions: pagePermissions
    };
}

function savePermissions() {
    const permissions = collectPermissions();
    if (!permissions) return;

    // Show loading state
    const $saveButton = $('.save-permissions');
    const originalText = $saveButton.html();
    $saveButton.html('<i class="fa-solid fa-spinner fa-spin"></i> Saving...').prop('disabled', true);

    $.ajax({
        url: BASE_URL + 'backend/permission_management.php?action=save_permissions',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(permissions),
        success: function(response) {
            if (response.success) {
                showAlert('success', 'Success', 'Permissions saved successfully');
                // Refresh the permissions display
                fetchPagesAndModules($('#rolesDropdown').val());
            } else {
                showAlert('error', 'Error', response.message || 'Failed to save permissions');
            }
        },
        error: function(xhr, status, error) {
            showAlert('error', 'Error', 'There was an error saving the permissions');
        },
        complete: function() {
            // Restore button state
            $saveButton.html(originalText).prop('disabled', false);
        }
    });
}

$(document).on('change', 'input[data-type="module"]', function() {
    const moduleId = $(this).data('id');
    const permission = $(this).data('permission');
    const isChecked = $(this).is(':checked');
    
    // When unchecking view permission, uncheck all other permissions
    if (permission === 'can_view' && !isChecked) {
        $(`input[data-type="module"][data-id="${moduleId}"]`).prop('checked', false);
        $(`input[data-type="page"][data-module="${moduleId}"]`).prop('checked', false);
    }
    
    // When checking any permission, ensure view is checked
    if (permission !== 'can_view' && isChecked) {
        $(`input[data-type="module"][data-id="${moduleId}"][data-permission="can_view"]`).prop('checked', true);
    }
});

$(document).on('change', 'input[data-type="page"]', function() {
    const pageId = $(this).data('id');
    const permission = $(this).data('permission');
    const isChecked = $(this).is(':checked');
    
    // When unchecking view permission, uncheck all other permissions
    if (permission === 'can_view' && !isChecked) {
        $(`input[data-type="page"][data-id="${pageId}"]`).prop('checked', false);
    }
    
    // When checking any permission, ensure view is checked
    if (permission !== 'can_view' && isChecked) {
        $(`input[data-type="page"][data-id="${pageId}"][data-permission="can_view"]`).prop('checked', true);
    }
});


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
    var $dropdown = $('#rolesDropdown');
    $dropdown.empty().append('<option value="" disabled selected>-- SELECT --</option>');
    $.each(roles, function(index, role) {
        $dropdown.append('<option value="' + role.role_id + '">' + role.role_name + '</option>');
    });

    if (roles.length > 0) {
        $dropdown.val(roles[0].role_id);
        fetchPagesAndModules(roles[0].role_id);
    }
}

function fetchPagesAndModules(roleId) {
    $.ajax({
        url: BASE_URL + 'backend/permission_management.php?action=fetch_modules_pages&role_id=' + roleId,
        type: 'GET',
        success: function(response) {
            if (response.success) {
                displayModulesAndPages(response.data);
                console.log(response.data)
            } else {
                showAlert('error', 'Error Fetching Data', response.message);
            }
        },
        error: function(xhr, status, error) {
            showAlert('error', 'Error Fetching Data', 'There was an error fetching the modules and pages.');
        }
    });
}

function displayModulesAndPages(data) {
    if ($.fn.DataTable.isDataTable('#permissionsTable')) {
        $('#permissionsTable').DataTable().destroy();
    }
    const container = $('#permissionsTable');
    container.empty();

    const table = $(`
        <table class="min-w-full bg-white border border-gray-300">
            <thead>
                <tr class="bg-gray-100">
                    <th class="px-3 py-2 border-b text-left text-sm">Module/Page</th>
                    <th class="px-3 py-2 border-b text-center text-sm">Add</th>
                    <th class="px-3 py-2 border-b text-center text-sm">Edit</th>
                    <th class="px-3 py-2 border-b text-center text-sm">Delete</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    `);

    const tbody = table.find('tbody');

    data.forEach(module => {
        tbody.append(`
            <tr class="bg-gray-50">
                <td class="px-3 py-2 border-b text-sm font-semibold">${module.module_name}</td>
                <td class="px-3 py-2 border-b text-center">
                    <input type="checkbox" ${module.permissions.can_add ? 'checked' : ''} 
                           data-type="module" data-id="${module.id}" data-permission="can_add"
                           class="w-3 h-3 text-blue-600 rounded focus:ring-blue-500">
                </td>
                <td class="px-3 py-2 border-b text-center">
                    <input type="checkbox" ${module.permissions.can_edit ? 'checked' : ''} 
                           data-type="module" data-id="${module.id}" data-permission="can_edit"
                           class="w-3 h-3 text-blue-600 rounded focus:ring-blue-500">
                </td>
                <td class="px-3 py-2 border-b text-center">
                    <input type="checkbox" ${module.permissions.can_delete ? 'checked' : ''} 
                           data-type="module" data-id="${module.id}" data-permission="can_delete"
                           class="w-3 h-3 text-blue-600 rounded focus:ring-blue-500">
                </td>
            </tr>
        `);

        module.pages.forEach(page => {
            tbody.append(`
                <tr>
                    <td class="px-3 py-2 border-b text-sm pl-8">
                        <input type="checkbox" ${page.permissions.can_view ? 'checked' : ''} 
                               data-type="page" data-id="${page.page_id}" data-permission="can_view"
                               class="w-3 h-3 text-blue-600 rounded focus:ring-blue-500">
                        ${page.page_name}
                    </td>
                    <td class="px-3 py-2 border-b text-center">
                        <input type="checkbox" ${page.permissions.can_add ? 'checked' : ''} 
                               data-type="page" data-id="${page.page_id}" data-permission="can_add"
                               class="w-3 h-3 text-blue-600 rounded focus:ring-blue-500">
                    </td>
                    <td class="px-3 py-2 border-b text-center">
                        <input type="checkbox" ${page.permissions.can_edit ? 'checked' : ''} 
                               data-type="page" data-id="${page.page_id}" data-permission="can_edit"
                               class="w-3 h-3 text-blue-600 rounded focus:ring-blue-500">
                    </td>
                    <td class="px-3 py-2 border-b text-center">
                        <input type="checkbox" ${page.permissions.can_delete ? 'checked' : ''} 
                               data-type="page" data-id="${page.page_id}" data-permission="can_delete"
                               class="w-3 h-3 text-blue-600 rounded focus:ring-blue-500">
                    </td>
                </tr>
            `);
        });
    });

    container.append(table);
}

</script>