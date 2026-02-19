define(['jquery', 'core/ajax', 'core/notification', 'core/str'], function ($, Ajax, Notification, Str) {
    return {
        init: function (params) {
            console.log('=== AUTOGRADEHELPER AMD MODULE INIT ===');
            console.log('Autograde Helper: Init called with params:', params);

            var assignmentId, courseId, userId, submissionId, isTeacher;

            // Handle parameters
            if (typeof params === 'number') {
                assignmentId = params;
                courseId = null;
                isTeacher = true; // Assume teacher if old style call
            } else if (params && typeof params === 'object') {
                assignmentId = params.assignmentid;
                courseId = params.courseid;
                userId = params.userid;
                submissionId = params.submissionid;
                isTeacher = params.isteacher;
            } else {
                // Fallback
                var urlParams = new URLSearchParams(window.location.search);
                assignmentId = urlParams.get('id') ? parseInt(urlParams.get('id')) : null;
            }

            console.log('AUTOGRADEHELPER Params:', { assignmentId, courseId, userId, submissionId, isTeacher });

            if (!assignmentId) {
                console.error('AUTOGRADEHELPER: No assignment ID available!');
                return;
            }

            $(document).ready(function () {
                // Find container
                var container = $('.submissionstatustable').first();
                if (!container.length) container = $('.gradingtable').first();
                if (!container.length) container = $('.grading-actions-form');
                if (!container.length) container = $('[role="main"]');

                // 1. TEACHER BUTTON
                if (isTeacher) {
                    Str.get_string('grade_with_ai_button', 'local_autogradehelper').done(function (buttonLabel) {
                        var button = $('<button class="btn btn-primary ml-2">' + buttonLabel + '</button>');
                        button.click(function (e) {
                            e.preventDefault();
                            button.prop('disabled', true);

                            Ajax.call([{
                                methodname: 'local_autogradehelper_trigger_grading',
                                args: { assignmentid: assignmentId }
                            }])[0].done(function (response) {
                                if (response.success) {
                                    Str.get_string('trigger_success', 'local_autogradehelper').done(function (s) { Notification.alert('Success', s, 'Ok'); });
                                } else {
                                    Notification.alert('Error', response.message, 'Ok');
                                }
                            }).fail(Notification.exception).always(function () { button.prop('disabled', false); });
                        });

                        // Append logic
                        if ($('.grading-actions-form').length) $('.grading-actions-form').append(button);
                        else if ($('.submissionlinks').length) $('.submissionlinks').append(button);
                        else container.before(button);
                    });
                }

                // 2. STUDENT BUTTON (If submission exists)
                if (submissionId) {
                    // We'll use a hardcoded label "Check AI Feedback" if string missing, or fetch from lang
                    // Using a promise for string consistent with above
                    // NOTE: You should add 'check_ai_feedback' to lang file later
                    var btnLabel = "Check AI Feedback";

                    var studentButton = $('<button class="btn btn-info ml-2">' + btnLabel + '</button>');
                    studentButton.click(function (e) {
                        e.preventDefault();
                        studentButton.prop('disabled', true);
                        console.log('Student AI Button Clicked. Sending:', { submissionId, assignmentId, courseId, userId });

                        // Call new webservice
                        Ajax.call([{
                            methodname: 'local_autogradehelper_check_feedback',
                            args: {
                                submissionid: submissionId,
                                assignmentid: assignmentId,
                                courseid: courseId,
                                userid: userId
                            }
                        }])[0].done(function (response) {
                            console.log('Feedback response:', response);
                            if (response.success) {
                                Notification.alert('Feedback', response.message || 'Feedback request sent!', 'Ok');
                            } else {
                                Notification.alert('Info', response.message || 'No feedback available yet.', 'Ok');
                            }
                        }).fail(function (ex) {
                            console.error(ex);
                            // SIlently fail or show modest error
                            Notification.alert('Error', 'Could not fetch feedback status.', 'Ok');
                        }).always(function () {
                            studentButton.prop('disabled', false);
                        });
                    });

                    // Append logic - attempt to put it near submission status
                    if ($('.submissionstatustable').length) {
                        $('.submissionstatustable').after(studentButton);
                    } else if ($('.submissionlinks').length) {
                        $('.submissionlinks').append(studentButton);
                    } else {
                        container.append(studentButton);
                    }
                }
            });
        }
    };
});