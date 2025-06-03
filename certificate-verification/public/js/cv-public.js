jQuery(document).ready(function($) {
    $('.cv-verification-form').on('submit', function(e) {
        e.preventDefault(); // Prevent default form submission

        var form = $(this);
        var resultsDiv = form.siblings('.cv-verification-results-ajax-container'); // Changed selector
        if (resultsDiv.length === 0) { // Create if not exists
            form.after('<div class="cv-verification-results-ajax-container"></div>');
            resultsDiv = form.siblings('.cv-verification-results-ajax-container');
        }
        resultsDiv.html('<p class="cv-loading">' + cv_public_vars.loading_message + '</p>'); // Show loading message

        var formData = form.serialize();

        $.ajax({
            url: cv_public_vars.ajax_url,
            type: 'POST',
            data: formData + '&action=cv_verify_certificate', // Add WordPress AJAX action
            dataType: 'json', // Expect JSON response
            success: function(response) {
                resultsDiv.empty(); // Clear loading message
                if (response.success) {
                    if (response.data.certificate_data) {
                        var data = response.data.certificate_data;
                        var html = '<h2>' + cv_public_vars.details_header + '</h2>';
                        html += '<table class="cv-results-table">';
                        html += '<tr><th>' + cv_public_vars.student_name_label + '</th><td>' + escapeHtml(data.student_name) + '</td></tr>';
                        if (data.father_mother_name) {
                            html += '<tr><th>' + cv_public_vars.father_mother_name_label + '</th><td>' + escapeHtml(data.father_mother_name) + '</td></tr>';
                        }
                        html += '<tr><th>' + cv_public_vars.course_name_label + '</th><td>' + escapeHtml(data.course_name) + '</td></tr>';
                        html += '<tr><th>' + cv_public_vars.course_status_label + '</th><td>' + escapeHtml(data.course_status) + '</td></tr>';
                        if (data.date_of_birth && data.date_of_birth !== '0000-00-00') {
                            html += '<tr><th>' + cv_public_vars.dob_label + '</th><td>' + escapeHtml(data.date_of_birth_formatted) + '</td></tr>';
                        }
                        if (data.issue_date && data.issue_date !== '0000-00-00') {
                            html += '<tr><th>' + cv_public_vars.issue_date_label + '</th><td>' + escapeHtml(data.issue_date_formatted) + '</td></tr>';
                        }
                        html += '</table>';
                        resultsDiv.html(html);
                    } else if (response.data.message) { // Should be handled by error block too if success:false
                         resultsDiv.html('<div class="cv-verification-message cv-warning"><p>' + escapeHtml(response.data.message) + '</p></div>');
                    }
                } else { // response.success === false
                    var message = response.data && response.data.message ? response.data.message : cv_public_vars.error_message;
                    resultsDiv.html('<div class="cv-verification-message cv-error"><p>' + escapeHtml(message) + '</p></div>');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                resultsDiv.empty(); // Clear loading message
                resultsDiv.html('<div class="cv-verification-message cv-error"><p>' + cv_public_vars.error_message + ' (' + escapeHtml(textStatus) + ')</p></div>');
                console.error("AJAX Error:", textStatus, errorThrown, jqXHR.responseText);
            }
        });
    });

    // Basic HTML escaping function
    function escapeHtml(text) {
        if (typeof text !== 'string') return '';
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
});
