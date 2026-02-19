// Simple vanilla JS button injection (no AMD compilation needed)
(function () {
    console.log('Smart Grade AI: Plain JS Loaded');

    document.addEventListener('DOMContentLoaded', function () {
        console.log('Smart Grade AI: DOM Ready');

        // Get params from window (set by PHP)
        var assignmentId = window.smartgradeai_assignmentid;
        var courseId = window.smartgradeai_courseid;

        console.log('Assignment ID:', assignmentId, 'Course ID:', courseId);

        // Create button
        var button = document.createElement('button');
        button.className = 'btn btn-primary ml-2';
        button.textContent = 'Grade with AI';
        button.style.margin = '10px';

        button.addEventListener('click', function (e) {
            e.preventDefault();
            button.disabled = true;
            console.log('Button clicked');

            // Call Moodle AJAX
            require(['core/ajax', 'core/notification'], function (Ajax, Notification) {
                Ajax.call([{
                    methodname: 'local_smartgradeai_trigger_grading',
                    args: { assignmentid: assignmentId }
                }])[0].done(function (response) {
                    console.log('Response:', response);
                    if (response.success) {
                        alert('Grading triggered successfully!');
                    } else {
                        alert('Error: ' + response.message);
                    }
                }).fail(function (ex) {
                    console.error('Failed:', ex);
                    alert('Error triggering grading');
                }).always(function () {
                    button.disabled = false;
                });
            });
        });

        // Find container and append button
        var container = document.querySelector('.submissionstatustable') ||
            document.querySelector('.gradingtable') ||
            document.querySelector('[role="main"]');

        if (container) {
            console.log('Container found, appending button');
            if (container.parentNode) {
                container.parentNode.insertBefore(button, container);
            }
        } else {
            console.log('No container found!');
        }
    });
})();
