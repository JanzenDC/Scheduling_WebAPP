
/*
 * Example of using showToast:
 * 
 * 1. Show a success toast when saving data:
 *    showToast("Your data has been saved successfully!", 'success', 'Success');
 * 
 * 2. Show an error toast when an action fails:
 *    showToast("An error occurred while processing your request.", 'error', 'Error');
 * 
 * 3. Show a warning toast for user action:
 *    showToast("Please double-check the information you provided.", 'warning', 'Warning');
 * 
 * 4. Show an informational toast:
 *    showToast("New updates are available. Please refresh.", 'info', 'Information');
 */
function showToast(message, type = 'info', title = '') {
    switch(type) {
        case 'success':
            toastr.success(message, title);
            break;
        case 'error':
            toastr.error(message, title);
            break;
        case 'warning':
            toastr.warning(message, title);
            break;
        case 'info':
        default:
            toastr.info(message, title);
            break;
    }
}

/* 
 * Sample Usage:
 * 
 * 1. To validate required fields in a form:
 * 
 *    - For saving a page:
 *    savePage() function calls validateRequiredFields with the required fields:
 *    validateRequiredFields(["#pageName", "#pageAlias", "#icon"]);
 * 
 *    - For a login form validation:
 *    validateLoginForm() function calls validateRequiredFields with the fields:
 *    validateRequiredFields(["#username", "#password"]);
 * 
 *    - For a contact form validation:
 *    validateContactForm() function calls validateRequiredFields with the fields:
 *    validateRequiredFields(["#name", "#email", "#message"]);
 *
 * 2. In each case, if any required field is empty, it will show an error message and prevent the action (such as form submission) from proceeding.
 */

function validateRequiredFields(selectors) {
    // Loop through all the selectors and check if they have a value
    for (let selector of selectors) {
        // Check if the input is empty
        if (!$(selector).val()) {
            // Show error toast if the field is empty
            showToast(`Please fill in the required field: ${selector}`, 'error', 'Validation Error');
            return false;  // Return false to indicate validation failure
        }
    }
    return true;  // Return true if all fields are filled
}


function initializePopperTooltips() {
    const actionButtons = document.querySelectorAll('.action-btn');
    
    actionButtons.forEach(button => {
        // Create tooltip element
        const tooltip = document.createElement('div');
        tooltip.classList.add('tooltip', 'tooltip-top');
        tooltip.innerHTML = button.getAttribute('title');
        document.body.appendChild(tooltip);
        
        const popperInstance = Popper.createPopper(button, tooltip, {
            placement: 'top',
        });

        button.addEventListener('mouseenter', () => {
            tooltip.style.visibility = 'visible';
            popperInstance.update();
        });

        button.addEventListener('mouseleave', () => {
            tooltip.style.visibility = 'hidden';
        });
    });
}


function getSafeValue(value) {
    return value === null || value === undefined ? '' : value;
}


function initializeDataTable(tableSelector) {

    if ($.fn.DataTable.isDataTable(tableSelector)) {
        // Destroy the existing DataTable
        $(tableSelector).DataTable().destroy();
    }

    // Reinitialize the DataTable
    $(tableSelector).DataTable({
        paging: true,
        searching: true,
        ordering: true,
        info: true,
        autoWidth: false,
        responsive: true,
    });
}

// Global alert function using Swal.fire
function showAlert(type, title, message) {
    Swal.fire({
        title: title || '',         // Optional title
        text: message || '',        // Optional message
        icon: type || 'info',        // 'warning', 'error', 'success', 'info'
        showCancelButton: false,
        confirmButtonText: 'OK',
        confirmButtonColor: '#3085d6',
        background: '#fff',
        timer: 5000  // auto-close after 5 seconds
    });
}

// Custom confirmation alert using SweetAlert2
function showConfirmationAlert(title, message, onConfirm, onCancel) {
    Swal.fire({
        title: title || 'Are you sure?',
        text: message || 'Are you sure you want to proceed?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes',
        cancelButtonText: 'No',
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        background: '#fff',
        allowOutsideClick: false
    }).then((result) => {
        if (result.isConfirmed) {
            if (onConfirm && typeof onConfirm === 'function') {
                onConfirm();
            }
        } else if (result.isDismissed) {
            if (onCancel && typeof onCancel === 'function') {
                onCancel();
            }
        }
    });
}


function logoutUser() {
    Swal.fire({
        title: 'Do you want to logout?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, logout',
        cancelButtonText: 'No, stay',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.assign('logout.php');
        }
    });
}

function SYS_dialog2(selector, width, height, title) {
    $(selector).dialog({
        autoOpen: false,
        modal: true,
        width: parseInt(width, 10),
        height: parseInt(height, 10),
        title: title,
        buttons: {
            "Close": function() {
                $(this).dialog("close");
            }
        },
        close: function() {
            $(this).dialog('destroy').remove(); 
        }
    });

    $(selector).dialog("open");
}


function SYS_dialog3(selector, height, width, title, savefunct) {
    $(selector).dialog({
        autoOpen: false,
        modal: true,
        width: parseInt(width, 10),
        height: parseInt(height, 10),
        title: title,
        buttons: [
            {
                text: "Save",
                click: function() {
                    if (typeof savefunct === 'function') {
                        savefunct();
                    }
                }
            },
            {
                text: "Cancel",
                click: function() {
                    $(this).dialog("close");
                }
            }
        ],
        close: function() {
            $(this).dialog('destroy').remove();
        },
        open: function() {
            fluidDialog(); // Call fluidDialog when the dialog opens
        }
    });

    $(selector).dialog("open");
}

function fluidDialog() {
    var $visible = $(".ui-dialog:visible");
    // each open dialog
    $visible.each(function () {
        var $this = $(this);
        var dialog = $this.find(".ui-dialog-content").data("ui-dialog");
        // if fluid option == true
        if (dialog.options.fluid) {
            var wWidth = $(window).width();
            // check window width against dialog width
            if (wWidth < (parseInt(dialog.options.maxWidth) + 50)) {
                // keep dialog from filling entire screen
                $this.css("max-width", "90%");
            } else {
                // fix maxWidth bug
                $this.css("max-width", dialog.options.maxWidth + "px");
            }
            // reposition dialog
            dialog.option("position", dialog.options.position);
        }
    });
}

$(document).ready(function() {
    function setCont(tabIndex) {
        // Hide all content divs
        $("[id^=setCont]").hide();

        // Show the selected content
        $("#setCont" + tabIndex).show();

        // Remove active class from all tabs
        $(".tab-link").removeClass("active-tab");

        // Set active class for the clicked tab
        $(".tab-link").eq(tabIndex - 1).addClass("active-tab");
    }

    // Set default active tab on page load
    setCont(1);

    // Make the function global
    window.setCont = setCont;
});

$(document).ready(function() {
    $('.treeview-parent').next('ul').addClass('sidebar-submenu');

    $('.treeview-parent').click(function(e) {
        e.stopPropagation();
        $(this).find('.fa-caret-right').toggleClass('rotate-90');
        $(this).next('ul').toggleClass('active');

        let $parentUl = $(this).closest('ul');
        if ($parentUl.hasClass('sidebar-submenu')) {
            $parentUl.css('max-height', '500px');
        }
    });

    const currentPage = window.location.search;
    $('a').each(function() {
        if ($(this).attr('href') && $(this).attr('href').includes(currentPage)) {
            $(this).closest('li').addClass('bg-[#fefeff] rounded-md text-[#044389]');

             // Change the text of the link when selected
             if ($(this).text().includes('Add Module')) {
                // Change the text
                $(this).addClass('rounded-md text-[#044389]'); // Change text color to red (you can change the color code)
            }
            if ($(this).text().includes('Add Pages')) {
                // Change the text
                $(this).addClass(' rounded-md text-[#044389] hover:bg-[#3cc5dd]'); // Change text color to red (you can change the color code)
            }
           
            
            $(this).parents('ul.sidebar-submenu').each(function() {
                $(this).addClass('active');
                $(this).prev('.treeview-parent')
                       .find('.fa-caret-right')
                       .addClass('rotate-90');
            });
            
        }
    });
    

    $('#calendar').fullCalendar({
        header: {
            left: 'prev,next today',
            center: 'title',
            right: 'month,agendaWeek,agendaDay'
        },
        editable: true,
        height: 700, // Adjust the height of the calendar
        contentHeight: 'auto'
    });
});

/*
Global function to show a loading alert with title, text, and message duration

This function uses SweetAlert2 to show a loading alert with a customizable title, text, and message duration. 
The page will reload after the specified `messageDuration`.

Example with custom title, custom text, and custom message duration (5 seconds):
showLoadingAlert(
    'Processing...',                         // Custom title
    'Please wait while we process your request...', // Custom text
    5000                                     // Message duration (5 seconds)
);

Example with default settings (loading for 2 seconds):
showLoadingAlert();
*/
const showLoadingAlert = (title = 'Processing...', text = 'Please wait while we process your request...', messageDuration = 2000) => {
    Swal.fire({
        title: title,         // Custom title (default: 'Processing...')
        text: text,           // Custom text (default: 'Please wait while we process your request...')
        allowOutsideClick: false,   // Prevents closing the alert by clicking outside
        didOpen: () => {
            Swal.showLoading();    // Show the loading spinner
        }
    });

    // Close the alert after the specified message duration and show success alert
    setTimeout(() => {
        Swal.close();  // Close the loading alert

        // Show success Swal
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: 'Your request has been processed successfully.',
            showConfirmButton: true
        });

        // Optionally reload the page after a short delay
        setTimeout(() => {
            location.reload();  // Reload the page
        }, 1500);  // Delay before reload
    }, messageDuration);
};






// Global Shepherd Tutorial System
const ShepherdTour = {
    tour: null,
    
    // Initialize with custom steps and options
    init: function(steps = [], options = {}) {
        const defaultOptions = {
            useModalOverlay: true,
            defaultStepOptions: {
                cancelIcon: {
                    enabled: true
                },
                classes: 'shadow-md bg-purple-100',
                scrollTo: { behavior: 'smooth', block: 'center' }
            }
        };

        // Merge default options with custom options
        const tourOptions = { ...defaultOptions, ...options };
        
        // Initialize Shepherd tour
        this.tour = new Shepherd.Tour(tourOptions);
        
        // Add steps if provided
        if (steps.length > 0) {
            steps.forEach(step => this.tour.addStep(step));
        }
        
        // Add styles
        this.addStyles();
        
        // Setup toggle if exists
        this.setupToggle();
        
        return this;
    },

    // Add custom styles for Shepherd
    addStyles: function() {
        const styleSheet = document.createElement('style');
        styleSheet.textContent = `
            .shepherd-button {
                background: #202c34 !important;
                color: white !important;
                border: none !important;
                padding: 0.5rem 1rem !important;
                margin: 0.25rem !important;
                border-radius: 0.25rem !important;
                font-size: 0.875rem !important;
            }

            .shepherd-button:hover {
                background: #2d3f4a !important;
            }

            .shepherd-cancel-icon {
                color: #4a5568 !important;
            }

            .shepherd-title {
                color: #202c34 !important;
                font-size: 1.125rem !important;
                font-weight: bold !important;
            }

            .shepherd-text {
                color: #4a5568 !important;
            }

            .shepherd-modal-overlay-container {
                opacity: 0.5;
            }
        `;
        document.head.appendChild(styleSheet);
    },

    // Setup toggle button functionality
    setupToggle: function() {
        const tutorialToggle = document.getElementById('tutorialToggle');
        if (tutorialToggle) {
            tutorialToggle.checked = this.isEnabled();
            
            tutorialToggle.addEventListener('change', () => {
                this.toggle(tutorialToggle.checked);
            });

            if (this.isEnabled()) {
                this.start();
            }
        }
    },

    // Start the tour
    start: function() {
        if (this.tour) {
            this.tour.start();
        }
    },

    // Stop the tour
    stop: function() {
        if (this.tour) {
            this.tour.cancel();
        }
    },

    // Toggle the tour
    toggle: function(enabled) {
        localStorage.setItem('shepherd_tutorial_enabled', enabled);
        if (enabled) {
            this.start();
        } else {
            this.stop();
        }
    },

    // Check if tutorial is enabled
    isEnabled: function() {
        return localStorage.getItem('shepherd_tutorial_enabled') === 'true';
    },

    // Add a new step
    addStep: function(stepConfig) {
        if (this.tour) {
            this.tour.addStep(stepConfig);
        }
    },

    // Remove a step
    removeStep: function(stepId) {
        if (this.tour) {
            this.tour.removeStep(stepId);
        }
    },

    // Get the current tour instance
    getTour: function() {
        return this.tour;
    }
};

// Make it globally available
window.ShepherdTour = ShepherdTour;