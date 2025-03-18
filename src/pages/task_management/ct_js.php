<script>
const BASE_URL = '<?php echo $baseUrl; ?>';
$(document).ready(function () {
    $("#save-task-button").click(function () {
        let taskData = {
            "priority": $("#priority").val(),
            "task-name": $("#task-name").val(),
            "description": $("#description").val(),
            "task-date": $("#task-date").val(),
            "start-time": $("#start-time").val(),
            "end-time": $("#end-time").val(),
            "user_ids": JSON.stringify($("#user-select").val())
        };

        $.ajax({
            url: BASE_URL + 'backend/createtask_management.php?action=create_task',
            type: 'POST',
            data: taskData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showToast("Task saved successfully!", "success");
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast(response.message, "error");
                }
            },
            error: function(xhr, status, error) {
                let errorMessage = xhr.responseText || "Failed to save task.";
                showToast(`Error: ${status} - ${errorMessage}`, "error");
                console.error("XHR Error:", error);
            }
        });
    });

    function validateForm() {
        let isValid = true;
        let data = {};

        // Validate form fields
        $("#task-form input, #task-form textarea").each(function () {
            let fieldId = $(this).attr("id");
            let fieldValue = $(this).val().trim();

            if (fieldValue === "") {
                isValid = false;
                return false; 
            }

            data[fieldId] = $("#" + fieldId).val();
        });

        if (!isValid) {
            return false; // Stop execution if any form field is empty
        }

        // AJAX call to fetch users
        $.ajax({
            url: BASE_URL + 'backend/edittask_managementv2.php?action=fetch_users',
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $("#user-select").empty();

                    // Filter out roles named: "admin", "superadmin", or "super admin" (in any case)
                    response.data.forEach(user => {
                        let roleName = user.role_name ? user.role_name.toUpperCase() : '';

                        // Only display user if roleName is not admin / super admin
                        if (roleName !== 'ADMIN' && 
                            roleName !== 'SUPERADMIN' && 
                            roleName !== 'SUPER ADMIN') 
                        {
                            let roleDisplay = user.role_name ?  (`(${user.role_name})`) : '';
                            $("#user-select").append(
                                `<option value="${user.user_id}">${user.full_name}${roleDisplay}</option>`
                            );
                        }
                    });

                    // Re-initialize Select2 after appending
                    $("#user-select").prop("disabled", false).select2({
                        placeholder: "Search and select users",
                        allowClear: true
                    });
                } else {
                    $("#user-select")
                    .empty()
                    .append('<option value="">No available users</option>')
                    .prop("disabled", true);
                }
            },
            error: function() {
                console.error("AJAX request failed");
            }
        });

        return isValid;
    }

    $("#task-form input, #task-form textarea").on("input", function () {
        if (validateForm()) {
            $("#next-button").removeClass("opacity-50 cursor-not-allowed").prop("disabled", false);
        } else {
            $("#next-button").addClass("opacity-50 cursor-not-allowed").prop("disabled", true);
        }
    });

    // Initially hide the task assignment section and disable the next button
    $("#assign-task-section").hide();
    $("#next-button").addClass("opacity-50 cursor-not-allowed").prop("disabled", true);

    $("#next-button").click(function () {
        if (validateForm()) {
            $("#assign-task-section").slideDown();
            $(this).hide(); // Hide the button after it's clicked

            // Disable and clear the fields
            $("#task-date, #start-time, #end-time")
                .prop("disabled", true); // Disable inputs
        }
    });

    // Reset Fields Button Logic
    $("#reset-fields-button").click(function () {
        // Clear all fields
        $("#task-form input, #task-form textarea").val("");

        // Re-enable disabled fields
        $("#task-date, #start-time, #end-time").prop("disabled", false);

        // Show Next button again
        $("#next-button").show();

        // Hide the assign task section again
        $("#assign-task-section").slideUp();

        // Reset user selection
        $("#user-select").val(null).trigger("change");
    });
});
</script>
