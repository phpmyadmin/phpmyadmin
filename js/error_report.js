/* vim: set expandtab sw=4 ts=4 sts=4: */

/* global TraceKit */ // js/vendor/tracekit.js

/**
 * general function, usually for data manipulation pages
 *
 */
var ErrorReport = {
    /**
     * @var object stores the last exception info
     */
    lastException: null,
    /**
     * handles thrown error exceptions based on user preferences
     *
     * @return void
     */
    errorHandler: function (exception) {
        // issue: 14359
        if (JSON.stringify(ErrorReport.lastException) === JSON.stringify(exception)) {
            return;
        }
        if (exception.name === null || typeof(exception.name) === 'undefined') {
            exception.name = ErrorReport.extractExceptionName(exception);
        }
        ErrorReport.lastException = exception;
        $.post('error_report.php', {
            'ajax_request': true,
            'server': CommonParams.get('server'),
            'get_settings': true,
            'exception_type': 'js'
        }, function (data) {
            if (data.success !== true) {
                Functions.ajaxShowMessage(data.error, false);
                return;
            }
            if (data.report_setting === 'ask') {
                ErrorReport.showErrorNotification();
            } else if (data.report_setting === 'always') {
                var reportData = ErrorReport.getReportData(exception);
                var postData = $.extend(reportData, {
                    'send_error_report': true,
                    'automatic': true
                });
                $.post('error_report.php', postData, function (data) {
                    if (data.success === false) {
                        // in the case of an error, show the error message returned.
                        Functions.ajaxShowMessage(data.error, false);
                    } else {
                        Functions.ajaxShowMessage(data.message, false);
                    }
                });
            }
        });
    },
    /**
     * Shows the modal dialog previewing the report
     *
     * @param exception object error report info
     *
     * @return void
     */
    showReportDialog: function (exception) {
        var reportData = ErrorReport.getReportData(exception);

        /* Remove the hidden dialogs if there are*/
        if ($('#error_report_dialog').length !== 0) {
            $('#error_report_dialog').remove();
        }
        var $div = $('<div id="error_report_dialog"></div>');
        $div.css('z-index', '1000');

        var buttonOptions = {};

        buttonOptions[Messages.strSendErrorReport] = function () {
            var $dialog = $(this);
            var postData = $.extend(reportData, {
                'send_error_report': true,
                'description': $('#report_description').val(),
                'always_send': $('#always_send_checkbox')[0].checked
            });
            $.post('error_report.php', postData, function (data) {
                $dialog.dialog('close');
                if (data.success === false) {
                    // in the case of an error, show the error message returned.
                    Functions.ajaxShowMessage(data.error, false);
                } else {
                    Functions.ajaxShowMessage(data.message, 3000);
                }
            });
        };

        buttonOptions[Messages.strCancel] = function () {
            $(this).dialog('close');
        };

        $.post('error_report.php', reportData, function (data) {
            if (data.success === false) {
                // in the case of an error, show the error message returned.
                Functions.ajaxShowMessage(data.error, false);
            } else {
                // Show dialog if the request was successful
                $div
                    .append(data.message)
                    .dialog({
                        title: Messages.strSubmitErrorReport,
                        width: 650,
                        modal: true,
                        buttons: buttonOptions,
                        close: function () {
                            $(this).remove();
                        }
                    });
            }
        });
    },
    /**
     * Shows the small notification that asks for user permission
     *
     * @return void
     */
    showErrorNotification: function () {
        ErrorReport.removeErrorNotification();

        var $div = $(
            '<div style="position:fixed;bottom:0;left:0;right:0;margin:0;' +
            'z-index:1000" class="error" id="error_notification"></div>'
        ).append(
            Functions.getImage('s_error') + Messages.strErrorOccurred
        );

        var $buttons = $('<div class="floatright"></div>');

        var buttonHtml  = '<button class="btn btn-primary" id="show_error_report">';
        buttonHtml += Messages.strShowReportDetails;
        buttonHtml += '</button>';

        buttonHtml += '<a id="change_error_settings">';
        buttonHtml += Functions.getImage('s_cog', Messages.strChangeReportSettings);
        buttonHtml += '</a>';

        buttonHtml += '<a href="#" id="ignore_error">';
        buttonHtml += Functions.getImage('b_close', Messages.strIgnore);
        buttonHtml += '</a>';

        $buttons.html(buttonHtml);

        $div.append($buttons);
        $div.appendTo(document.body);
        $(document).on('click', '#change_error_settings', ErrorReport.redirectToSettings);
        $(document).on('click', '#show_error_report', ErrorReport.createReportDialog);
        $(document).on('click', '#ignore_error', ErrorReport.removeErrorNotification);
    },
    /**
     * Removes the notification if it was displayed before
     *
     * @return void
     */
    removeErrorNotification: function (e) {
        if (e) {
            // don't remove the hash fragment by navigating to #
            e.preventDefault();
        }
        $('#error_notification').fadeOut(function () {
            $(this).remove();
        });
    },
    /**
     * Extracts Exception name from message if it exists
     *
     * @return String
     */
    extractExceptionName: function (exception) {
        if (exception.message === null || typeof(exception.message) === 'undefined') {
            return '';
        }

        var reg = /([a-zA-Z]+):/;
        var regexResult = reg.exec(exception.message);
        if (regexResult && regexResult.length === 2) {
            return regexResult[1];
        }

        return '';
    },
    /**
     * Shows the modal dialog previewing the report
     *
     * @return void
     */
    createReportDialog: function () {
        ErrorReport.removeErrorNotification();
        ErrorReport.showReportDialog(ErrorReport.lastException);
    },
    /**
     * Redirects to the settings page containing error report
     * preferences
     *
     * @return void
     */
    redirectToSettings: function () {
        window.location.href = 'prefs_forms.php';
    },
    /**
     * Returns the report data to send to the server
     *
     * @param exception object exception info
     *
     * @return object
     */
    getReportData: function (exception) {
        if (exception && exception.stack && exception.stack.length) {
            for (var i = 0; i < exception.stack.length; i++) {
                var stack = exception.stack[i];
                if (stack.context && stack.context.length) {
                    for (var j = 0; j < stack.context.length; j++) {
                        if (stack.context[j].length >  80) {
                            stack.context[j] = stack.context[j].substring(-1, 75) + '//...';
                        }
                    }
                }
            }
        }
        var reportData = {
            'server': CommonParams.get('server'),
            'ajax_request': true,
            'exception': exception,
            'url': window.location.href,
            'exception_type': 'js'
        };
        if (AJAX.scriptHandler.scripts.length > 0) {
            reportData.scripts = AJAX.scriptHandler.scripts.map(
                function (script) {
                    return script;
                }
            );
        }
        return reportData;
    },
    /**
     * Wraps all global functions that start with PMA_
     *
     * @return void
     */
    wrapGlobalFunctions: function () {
        for (var key in window) {
            if (key.indexOf('PMA_') === 0) {
                var global = window[key];
                if (typeof(global) === 'function') {
                    window[key] = ErrorReport.wrapFunction(global);
                }
            }
        }
    },
    /**
     * Wraps given function in error reporting code and returns wrapped function
     *
     * @param func function to be wrapped
     *
     * @return function
     */
    wrapFunction: function (func) {
        if (!func.wrapped) {
            var newFunc = function () {
                try {
                    return func.apply(this, arguments);
                } catch (x) {
                    TraceKit.report(x);
                }
            };
            newFunc.wrapped = true;
            // Set guid of wrapped function same as original function, so it can be removed
            // See bug#4146 (problem with jquery draggable and sortable)
            newFunc.guid = func.guid = func.guid || newFunc.guid || jQuery.guid++;
            return newFunc;
        } else {
            return func;
        }
    },
    /**
     * Automatically wraps the callback in AJAX.registerOnload
     *
     * @return void
     */
    wrapAjaxOnloadCallback: function () {
        var oldOnload = AJAX.registerOnload;
        AJAX.registerOnload = function (file, func) {
            var wrappedFunction = ErrorReport.wrapFunction(func);
            oldOnload.call(this, file, wrappedFunction);
        };
    },
    /**
     * Automatically wraps the callback in $.fn.on
     *
     * @return void
     */
    wrapJqueryOnCallback: function () {
        var oldOn = $.fn.on;
        $.fn.on = function () {
            for (var i = 1; i <= 3; i++) {
                if (typeof(arguments[i]) === 'function') {
                    arguments[i] = ErrorReport.wrapFunction(arguments[i]);
                    break;
                }
            }
            return oldOn.apply(this, arguments);
        };
    },
    /**
     * Wraps all global functions that start with PMA_
     * also automatically wraps the callback in AJAX.registerOnload
     *
     * @return void
     */
    setUpErrorReporting: function () {
        ErrorReport.wrapGlobalFunctions();
        ErrorReport.wrapAjaxOnloadCallback();
        ErrorReport.wrapJqueryOnCallback();
    }
};

AJAX.registerOnload('error_report.js', function () {
    TraceKit.report.subscribe(ErrorReport.errorHandler);
    ErrorReport.setUpErrorReporting();
    ErrorReport.wrapGlobalFunctions();
});
