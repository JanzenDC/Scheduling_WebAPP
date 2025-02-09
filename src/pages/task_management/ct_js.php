<script>
const BASE_URL = '<?php echo $baseUrl; ?>';
$(document).ready(function () {
    $("#save-task-button").click(function () {
        let taskData = {
            "task-name": $("#task-name").val(),
            "description": $("#description").val(),
            "task-date": $("#task-date").val(),
            "start-time": $("#start-time").val(),
            "end-time": $("#end-time").val(),
            "user_ids": JSON.stringify($("#user-select").val()) // Collect multiple user IDs
        };

        $.ajax({
            url: BASE_URL + 'backend/edittask_managementv2.php?action=save_task',
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
            error: function() {
                showToast("Failed to save task.", "error");
            }
        });
    });

    function validateForm() {
        let isValid = true;
        let data = {};

        $("#task-form input, #task-form textarea").each(function () {
            let fieldId = $(this).attr("id");
            let fieldValue = $(this).val().trim();

            if (fieldValue === "") {
                isValid = false;
                return false; // Break loop if empty field is found
            }

            data[fieldId] = $("#" + fieldId).val();
        });

        if (!isValid) {
            return false; // Stop execution if the form is not valid
        }

        $.ajax({
            url: BASE_URL + 'backend/edittask_managementv2.php?action=fetch_users',
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $("#user-select").empty();
                    response.data.forEach(user => {
                        let roleDisplay = user.role_name ? ` (${user.role_name})` : ''; // Show role if available
                        $("#user-select").append(`<option value="${user.user_id}">${user.full_name}${roleDisplay}</option>`);
                    });

                    // Re-initialize Select2 to enable search after loading options
                    $("#user-select").prop("disabled", false).select2({
                        placeholder: "Search and select users",
                        allowClear: true
                    });
                } else {
                    $("#user-select").empty().append('<option value="">No available users</option>').prop("disabled", true);
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
        }
    });
});

</script>