import $ from 'jquery';
import { AJAX } from './modules/ajax.ts';
import { CommonParams } from './modules/common.ts';
import { ajaxShowMessage } from './modules/ajax-message.ts';
import getImageTag from './modules/functions/getImageTag.ts';
import type * as TraceKit from 'tracekit/tracekit.d.ts';

declare global {
    interface Window {
        TraceKit: typeof TraceKit;
    }
}

/**
 * general function, usually for data manipulation pages
 *
 */
var ErrorReport = {
    /**
     * @var {object}, stores the last exception info
     */
    lastException: null,
    /**
     * @var object stores the Error Report Data to prevent unnecessary data fetching
     */
    errorReportData: null,
    /**
     * @var object maintains unique keys already used
     */
    keyDict: {},
    /**
     * handles thrown error exceptions based on user preferences
     *
     * @param {object} data
     * @param {any} exception
     */
    errorDataHandler: function (data, exception): void {
        if (data.success !== true) {
            ajaxShowMessage(data.error, false);

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
            $.post('index.php?route=/error-report', postData, function (data) {
                if (data.success === false) {
                    // in the case of an error, show the error message returned.
                    ajaxShowMessage(data.error, false);
                } else {
                    ajaxShowMessage(data.message, false);
                }
            });
        }
    },
    errorHandler: function (exception) {
        // issue: 14359
        if (JSON.stringify(ErrorReport.lastException) === JSON.stringify(exception)) {
            return;
        }

        if (exception.name === null || typeof (exception.name) === 'undefined') {
            exception.name = ErrorReport.extractExceptionName(exception);
        }

        ErrorReport.lastException = exception;
        if (ErrorReport.errorReportData === null) {
            $.post('index.php?route=/error-report', {
                'ajax_request': true,
                'server': CommonParams.get('server'),
                'get_settings': true,
                'exception_type': 'js'
            }, function (data) {
                ErrorReport.errorReportData = data;
                ErrorReport.errorDataHandler(data, exception);
            });
        } else {
            ErrorReport.errorDataHandler(ErrorReport.errorReportData, exception);
        }
    },
    /**
     * Shows the modal dialog previewing the report
     *
     * @param exception object error report info
     */
    showReportDialog: function (exception): void {
        const reportData = ErrorReport.getReportData(exception);

        const sendErrorReport = function () {
            const postData = $.extend(reportData, {
                'send_error_report': true,
                'description': $('#errorReportDescription').val(),
                'always_send': ($('#errorReportAlwaysSendCheckbox') as JQuery<HTMLInputElement>)[0].checked
            });
            $.post('index.php?route=/error-report', postData, function (data) {
                if (data.success === false) {
                    ajaxShowMessage(data.error, false);
                } else {
                    ajaxShowMessage(data.message, 3000);
                }
            });

            $('#errorReportModal').modal('hide');
        };

        $.post('index.php?route=/error-report', reportData).done(function (data) {
            // Delete the modal to refresh it in case the user changed SendErrorReports value
            if (document.getElementById('errorReportModal') !== null) {
                $('#errorReportModal').remove();
            }

            $('body').append($(data.report_modal));
            const $errorReportModal = $('#errorReportModal');
            $errorReportModal.on('show.bs.modal', function () {
                // Prevents multiple onClick events
                $('#errorReportModalConfirm').off('click', sendErrorReport);
                $('#errorReportModalConfirm').on('click', sendErrorReport);
                $('#errorReportModal .modal-body').html(data.message);
            });

            $errorReportModal.modal('show');
        });
    },
    /**
     * Shows the small notification that asks for user permission
     */
    showErrorNotification: function (): void {
        var key = Math.random().toString(36).substring(2, 12);
        while (key in ErrorReport.keyDict) {
            key = Math.random().toString(36).substring(2, 12);
        }

        ErrorReport.keyDict[key] = 1;

        var $div = $(
            '<div class="alert alert-danger" role="alert" id="error_notification_' + key + '"></div>'
        ).append(
            getImageTag('s_error') + window.Messages.strErrorOccurred
        );

        var $buttons = $('<div class="float-end"></div>');
        var buttonHtml = '<button class="btn btn-primary" id="show_error_report_' + key + '">';
        buttonHtml += window.Messages.strShowReportDetails;
        buttonHtml += '</button>';

        var settingsUrl = 'index.php?route=/preferences/features&server=' + CommonParams.get('server');
        buttonHtml += '<a class="ajax" href="' + settingsUrl + '">';
        buttonHtml += getImageTag('s_cog', window.Messages.strChangeReportSettings);
        buttonHtml += '</a>';

        buttonHtml += '<a href="#" id="ignore_error_' + key + '" data-notification-id="' + key + '">';
        buttonHtml += getImageTag('b_close', window.Messages.strIgnore);
        buttonHtml += '</a>';

        $buttons.html(buttonHtml);

        $div.append($buttons);
        // eslint-disable-next-line compat/compat
        $div.appendTo(document.body);
        $(document).on('click', '#show_error_report_' + key, ErrorReport.createReportDialog);
        $(document).on('click', '#ignore_error_' + key, ErrorReport.removeErrorNotification);
    },
    /**
     * Removes the notification if it was displayed before
     *
     * @param {Event} e
     */
    removeErrorNotification: function (e = undefined): void {
        if (e) {
            // don't remove the hash fragment by navigating to #
            e.preventDefault();
        }

        $('#error_notification_' + $(this).data('notification-id')).fadeOut(function () {
            $(this).remove();
        });
    },
    /**
     * Extracts Exception name from message if it exists
     *
     * @param exception
     * @return {string}
     */
    extractExceptionName: function (exception) {
        if (exception.message === null || typeof (exception.message) === 'undefined') {
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
     */
    createReportDialog: function (): void {
        ErrorReport.removeErrorNotification();
        ErrorReport.showReportDialog(ErrorReport.lastException);
    },
    /**
     * Returns the report data to send to the server
     *
     * @param exception object exception info
     *
     * @return {object}
     */
    getReportData: function (exception) {
        if (exception && exception.stack && exception.stack.length) {
            for (var i = 0; i < exception.stack.length; i++) {
                var stack = exception.stack[i];
                if (stack.context && stack.context.length) {
                    for (var j = 0; j < stack.context.length; j++) {
                        if (stack.context[j].length > 80) {
                            stack.context[j] = stack.context[j].substring(-1, 75) + '//...';
                        }
                    }
                }
            }
        }

        var reportData: { exception: any; server: any; ajax_request: boolean; exception_type: string; url: string, scripts?: any[] } = {
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
     * Wraps given function in error reporting code and returns wrapped function
     *
     * @param {Function} func function to be wrapped
     *
     * @return {Function}
     */
    wrapFunction: function (func) {
        if (! func.wrapped) {
            var newFunc = function () {
                try {
                    return func.apply(this, arguments);
                } catch (x) {
                    window.TraceKit.report(x);
                }
            };

            // @ts-ignore
            newFunc.wrapped = true;
            // Set guid of wrapped function same as original function, so it can be removed
            // See bug#4146 (problem with jquery draggable and sortable)
            // @ts-ignore
            newFunc.guid = func.guid = func.guid || newFunc.guid || $.guid++;

            return newFunc;
        } else {
            return func;
        }
    },
    /**
     * Automatically wraps the callback in AJAX.registerOnload
     */
    wrapAjaxOnloadCallback: function (): void {
        var oldOnload = AJAX.registerOnload;
        AJAX.registerOnload = function (file, func) {
            var wrappedFunction = ErrorReport.wrapFunction(func);
            oldOnload.call(this, file, wrappedFunction);
        };
    },
    /**
     * Automatically wraps the callback in $.fn.on
     */
    wrapJqueryOnCallback: function (): void {
        var oldOn = $.fn.on;
        $.fn.on = function () {
            for (var i = 1; i <= 3; i++) {
                if (typeof (arguments[i]) === 'function') {
                    arguments[i] = ErrorReport.wrapFunction(arguments[i]);
                    break;
                }
            }

            return oldOn.apply(this, arguments);
        };
    },
    /**
     * Wraps the callback in AJAX.registerOnload automatically
     */
    setUpErrorReporting: function (): void {
        ErrorReport.wrapAjaxOnloadCallback();
        ErrorReport.wrapJqueryOnCallback();
    }
};

AJAX.registerOnload('error_report.js', function () {
    window.TraceKit.report.subscribe(ErrorReport.errorHandler);
    ErrorReport.setUpErrorReporting();
});
