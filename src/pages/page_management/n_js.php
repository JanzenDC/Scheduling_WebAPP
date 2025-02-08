<script>
const BASE_URL = '<?php echo $baseUrl; ?>';
    function fetchPages() {
        showConfirmationAlert(
            'Fetch Pages',
            'This will fetch and insert all Pages from the log file. Continue?',
            function() {
                $.ajax({
                    url: BASE_URL + 'backend/page_management.php?action=fetch_from_log',
                    type: 'POST',
                    success: function(response) {
                        if(response.success) {
                            showLoadingAlert();
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
function fetchModulesAndPages() {
    $.ajax({
        url: BASE_URL + 'backend/page_management.php?action=fetch_all',
        type: 'GET',
        success: function(response) {
            console.log('Response:', response);
            if(response.success) {
                displayModulesAndPages(response.data);
            } else {
                showAlert('warning', 'No Data Found', response.message || 'No modules and pages available.');
            }
        },
        error: function(xhr, status, error) {
            console.log('Error:', error);
            showAlert('error', 'Error Fetching Data', 'There was an error fetching the modules and pages.');
        }
    });
}

function displayModulesAndPages(data) {
    const tbody = $('tbody');
    tbody.empty();

    data.modules.forEach((module, index) => {
        const moduleRow = `
            <tr class="bg-[#212121] text-white">
                <td  colspan="7" class="p-2 text-sm">
                    <span class="font-semibold">${module.module_name ? module.module_name : 'Untitled Module'}</span>
                </td>
                <td class="p-2 text-center">
                    <button onclick="addPageToModule(${module.id}, '${module.module_name.replace(/'/g, "&apos;")}')" class=" p-2 bg-[#044389] text-white rounded hover:bg-blue-600 transition-colors text-xs">
                        <i class="fas fa-plus hidden sm:inline"></i> <!-- Add icon only on mobile -->
                        <span class="inline sm:hidden">Add</span> <!-- Show "Add" only on small screens -->
                    </button>
                </td>
            </tr>
        `;

        tbody.append(moduleRow);

        const pages = data.pages.filter(page => page.module_id === module.id);
        pages.forEach(page => {
            const pageRow = `
                <tr class="hover:bg-gray-50 text-sm">
                    <td class="p-2">${page.page_id}</td>
                    <td class="p-2 text-center"><i class="${page.icon}"></i></td>
                    <td class="p-2">${page.page_name}</td>
                    <td class="p-2">${page.page_alias}</td>
                    <td class="p-2">${module.module_name}</td>
                    <td class="p-2">${page.sequence_number || 'N/A'}</td>
                    <td class="p-2 text-center">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" class="sr-only peer" 
                                id="page_switch_${page.page_id}" 
                                ${page.is_active == 1 ? 'checked' : ''}
                                onchange="togglePageStatus(${page.page_id}, this.checked)">
                            <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </td>
                    <td class="p-2 text-center">
                        <div class="flex gap-1 justify-center">
                            <button onclick="editPage(${page.page_id})" class="p-1 bg-green-500 text-white rounded hover:bg-blue-600 transition-colors text-xs">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deletePage(${page.page_id})" class="p-1 bg-red-500 text-white rounded hover:bg-red-600 transition-colors text-xs">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            tbody.append(pageRow);
        });
    });
}


function addPageToModule(moduleId, moduleName) {
    const title = `Add Page | ${moduleName}`;
    $("#dialog_emp").remove();
    $('body').append("<div id='dialog_emp'></div>");
    
    SYS_dialog3('#dialog_emp', '400', '530', title, function() {
        savePage();
    });

    const str = `
        <div class="p-4">
            <div class="mb-4">
                <input type='hidden' id="moduleID" value="${moduleId}" >
                <label for="pageName" class="block text-gray-700 font-medium mb-2">Page Name:</label>
                <input type="text" id="pageName" 
                       class="w-full border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-500" 
                       placeholder="Enter Page Name">
            </div>
            <div class="mb-4">
                <label for="pageAlias" class="block text-gray-700 font-medium mb-2">Page Alias:</label>
                <input type="text" id="pageAlias" 
                       class="w-full border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-500" 
                       placeholder="Enter Page Alias">
            </div>
            <div class="mb-4">
                <label for="icon" class="block text-gray-700 font-medium mb-2">Icon (FontAwesome Class):</label>
                <input type="text" id="icon" 
                    class="w-full border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-500" 
                    placeholder="e.g., fas fa-home">
                <small class="text-gray-500">Visit <a href="https://fontawesome.com/icons" target="_blank" class="text-blue-500 underline">FontAwesome</a> for available icons.</small>
            </div>

            <div class="mb-4">
                <label for="sequenceNumber" class="block text-gray-700 font-medium mb-2">
                    Sequence Number:
                </label>
                <input type="number" id="sequenceNumber" 
                       class="w-full border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-500" 
                       placeholder="Enter Sequence Number">
            </div>
        </div>
    `;

    $("#dialog_emp").html(str).dialog("open");
}

function savePage() {
    const moduleId = $("#moduleID").val();
    const pageName = $("#pageName").val();
    const pageAlias = $("#pageAlias").val();
    const icon = $("#icon").val();
    const sequenceNumber = $("#sequenceNumber").val();

    if (!pageName || !pageAlias || !icon) {
        Swal.fire({
            title: 'Validation Error',
            text: 'Please fill in all required fields.',
            icon: 'error',
            confirmButtonColor: '#3085d6'
        });
        return;
    }

    showConfirmationAlert(
        'Save Page',
        'Are you sure you want to save this page?',
        () => {
            $.ajax({
                url: BASE_URL + 'backend/page_management.php?action=add_page',
                method: 'POST',
                data: {
                    moduleId,
                    pageName,
                    pageAlias,
                    icon,
                    sequenceNumber,
                },
                success: function(response) {
                    Swal.close();

                    Swal.fire({
                        title: 'Processing...',
                        text: 'Please wait while we process your request...',
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        onOpen: () => {
                            Swal.showLoading();
                            let timerInterval;
                            Swal.update({
                                html: 'Please wait while we process your request...',
                                timer: 2000,
                                didOpen: () => {
                                    const progressBar = document.createElement('progress');
                                    progressBar.setAttribute('value', 0);
                                    progressBar.setAttribute('max', 100);
                                    progressBar.style.width = '100%';
                                    Swal.getContent().appendChild(progressBar);

                                    timerInterval = setInterval(() => {
                                        const value = progressBar.value + 2;
                                        progressBar.value = value;
                                        if (value === 100) {
                                            clearInterval(timerInterval);
                                        }
                                    }, 50);
                                },
                                willClose: () => {
                                    clearInterval(timerInterval);
                                }
                            });
                        }
                    });

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
    );

}


// Add these functions for the other actions
function togglePageStatus(pageId, status) {
    showConfirmationAlert(
        'Toggle Status',
        'Are you sure you want to change the page status?',
        () => {
            $.ajax({
                url: BASE_URL + 'backend/page_management.php?action=toggle_status',
                method: 'POST',
                data: { pageId, status },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Success',
                            text: 'Status updated successfully.',
                            icon: 'success',
                            confirmButtonColor: '#3085d6'
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: response.message || 'Error updating status.',
                            icon: 'error',
                            confirmButtonColor: '#3085d6'
                        });
                        // Revert the toggle if there was an error
                        $(`#page_switch_${pageId}`).prop('checked', !status);
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        title: 'Error',
                        text: 'There was an error updating the status.',
                        icon: 'error',
                        confirmButtonColor: '#3085d6'
                    });
                    // Revert the toggle on error
                    $(`#page_switch_${pageId}`).prop('checked', !status);
                }
            });
        },
        () => {
            // Revert the toggle if user cancels
            $(`#page_switch_${pageId}`).prop('checked', !status);
        }
    );
}

function deletePage(pageId) {
    showConfirmationAlert(
        'Delete Page',
        'Are you sure you want to delete this page? This action cannot be undone.',
        () => {
            $.ajax({
                url: BASE_URL + 'backend/page_management.php?action=delete_page',
                method: 'POST',
                data: { pageId },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Success',
                            text: 'Page deleted successfully.',
                            icon: 'success',
                            confirmButtonColor: '#3085d6'
                        });
                        fetchModulesAndPages();
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: response.message || 'Error deleting page.',
                            icon: 'error',
                            confirmButtonColor: '#3085d6'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        title: 'Error',
                        text: 'There was an error deleting the page.',
                        icon: 'error',
                        confirmButtonColor: '#3085d6'
                    });
                }
            });
        }
    );
}

function editPage(pageId) {
    // First fetch the current page data
    $.ajax({
        url: BASE_URL + 'backend/page_management.php?action=fetch_page',
        type: 'GET',
        data: { pageId },
        success: function(response) {
            if(response.success) {
                showEditDialog(response.data);
            } else {
                Swal.fire({
                    title: 'Error',
                    text: 'Error fetching page data.',
                    icon: 'error',
                    confirmButtonColor: '#3085d6'
                });
            }
        },
        error: function(xhr, status, error) {
            Swal.fire({
                title: 'Error',
                text: 'There was an error fetching the page data.',
                icon: 'error',
                confirmButtonColor: '#3085d6'
            });
        }
    });
}

function showEditDialog(pageData) {
    const title = `Edit Page | ${pageData.page_name}`;
    $("#dialog_emp").remove();
    $('body').append("<div id='dialog_emp'></div>");
    
    SYS_dialog3('#dialog_emp', '400', '530', title, function() {
        updatePage(pageData.page_id);
    });

    // Fetch modules for dropdown
    $.ajax({
        url: BASE_URL + 'backend/page_management.php?action=fetch_modules',
        type: 'GET',
        success: function(response) {
            if(response.success) {
                let moduleOptions = response.data.map(module => 
                    `<option value="${module.id}" ${module.id == pageData.module_id ? 'selected' : ''}>
                        ${module.module_name}
                    </option>`
                ).join('');

                const str = `
                    <div class="p-4">
                        <input type='hidden' id="editPageId" value="${pageData.page_id}">
                        <div class="mb-4">
                            <label for="editPageName" class="block text-gray-700 font-medium mb-2">Page Name:</label>
                            <input type="text" id="editPageName" 
                                class="w-full border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                value="${pageData.page_name}">
                        </div>
                        <div class="mb-4">
                            <label for="editModule" class="block text-gray-700 font-medium mb-2">Module:</label>
                            <select id="editModule" 
                                class="w-full border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                ${moduleOptions}
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="editIcon" class="block text-gray-700 font-medium mb-2">Icon (FontAwesome Class):</label>
                            <input type="text" id="editIcon" 
                                class="w-full border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                value="${pageData.icon}">
                            <small class="text-gray-500">Visit <a href="https://fontawesome.com/icons" target="_blank" class="text-blue-500 underline">FontAwesome</a> for available icons.</small>
                        </div>
                        <div class="mb-4">
                            <label for="editSequenceNumber" class="block text-gray-700 font-medium mb-2">Sequence Number:</label>
                            <input type="number" id="editSequenceNumber" 
                                class="w-full border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                value="${pageData.sequence_number || ''}">
                        </div>
                    </div>
                `;

                $("#dialog_emp").html(str).dialog("open");
            }
        }
    });
}

function updatePage(pageId) {
    const pageName = $("#editPageName").val();
    const moduleId = $("#editModule").val();
    const icon = $("#editIcon").val();
    const sequenceNumber = $("#editSequenceNumber").val();

    if (!pageName || !icon) {
        Swal.fire({
            title: 'Validation Error',
            text: 'Please fill in all required fields.',
            icon: 'error',
            confirmButtonColor: '#3085d6'
        });
        return;
    }

    showConfirmationAlert(
        'Update Page',
        'Are you sure you want to update this page?',
        () => {
            $.ajax({
                url: BASE_URL + 'backend/page_management.php?action=update_page',
                method: 'POST',
                data: {
                    pageId,
                    pageName,
                    moduleId,
                    icon,
                    sequenceNumber
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Success',
                            text: 'Page updated successfully.',
                            icon: 'success',
                            confirmButtonColor: '#3085d6'
                        });
                        $("#dialog_emp").dialog("close");
                        fetchModulesAndPages();
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: response.message || 'Error updating page.',
                            icon: 'error',
                            confirmButtonColor: '#3085d6'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        title: 'Error',
                        text: 'There was an error updating the page.',
                        icon: 'error',
                        confirmButtonColor: '#3085d6'
                    });
                }
            });
        }
    );
}

$(document).ready(function() {
    fetchModulesAndPages();
});
</script>
