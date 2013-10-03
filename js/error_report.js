/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * general function, usually for data manipulation pages
 *
 */

var ErrorReport = {
    /**
     * @var object stores the last exception info
     */
    _last_exception: null,
    /**
     * handles thrown error exceptions based on user preferences
     *
     * @return void
     */
    error_handler: function (exception) {
        ErrorReport._last_exception = exception;
        $.get("error_report.php", {
            ajax_request: true,
            token: PMA_commonParams.get('token'),
            get_settings: true
        }, function (data) {
            if (!data.success === true) {
                PMA_ajaxShowMessage(data.error, false);
                return;
            }
            if (data.report_setting == "ask") {
                ErrorReport._showErrorNotification();
            } else if (data.report_setting == "always") {
                report_data = ErrorReport._get_report_data(exception);
                post_data = $.extend(report_data, {
                    send_error_report: true,
                    automatic: true
                });
                $.post("error_report.php", post_data, function (data) {
                    if (data.success === false) {
                        //in the case of an error, show the error message returned.
                        PMA_ajaxShowMessage(data.error, false);
                    } else {
                        PMA_ajaxShowMessage(data.message, false);
                    }
                });
            }
        });
    },
    /**
     * Shows the modal dialog previewing the report
     *
     * @param object error report info
     *
     * @return void
     */
    _showReportDialog: function (exception) {
        var report_data = ErrorReport._get_report_data(exception);

        /*Remove the hidden dialogs if there are*/
        if ($('#error_report_dialog').length !== 0) {
            $('#error_report_dialog').remove();
        }
        var $div = $('<div id="error_report_dialog"></div>');

        var button_options = {};

        button_options[PMA_messages.strSendErrorReport] = function () {
            $dialog = $(this);
            post_data = $.extend(report_data, {
                send_error_report: true,
                description: $("#report_description").val(),
                always_send: $("#always_send_checkbox")[0].checked
            });
            $.post("error_report.php", post_data, function (data) {
                $dialog.dialog('close');
                if (data.success === false) {
                    //in the case of an error, show the error message returned.
                    PMA_ajaxShowMessage(data.error, false);
                } else {
                    PMA_ajaxShowMessage(data.message, 3000);
                }
            });
        };

        button_options[PMA_messages.strCancel] = function () {
            $(this).dialog('close');
        };

        $.post("error_report.php", report_data, function (data) {
            if (data.success === false) {
                //in the case of an error, show the error message returned.
                PMA_ajaxShowMessage(data.error, false);
            } else {
                // Show dialog if the request was successful
                $div
                .append(data.message)
                .dialog({
                    title: PMA_messages.strSubmitErrorReport,
                    width: 650,
                    modal: true,
                    buttons: button_options,
                    close: function () {
                        $(this).remove();
                    }
                });
            }
        }); // end $.get()
    },
    /**
     * Shows the small notification that asks for user permission
     *
     * @return void
     */
    _showErrorNotification: function () {
        ErrorReport._removeErrorNotification();

        $div = $(
            '<div style="position:fixed;bottom:0px;left:5px;right:5px;' +
            'z-index:1000" class="error" id="error_notification"></div>'
        );
        html = '';
        html += '<img src="themes/dot.gif" title="" alt="" class="icon ic_s_error">';
        html += PMA_messages.strErrorOccurred;
        $div.html(html);

        $buttons = $('<div style="float:right"></div>');
        button_html = '';
        button_html += '<button id="change_error_settings">' +
                        PMA_messages.strChangeReportSettings + '</button>';
        button_html += '<button id="show_error_report">' +
                        PMA_messages.strShowReportDetails + '</button>';
        button_html += '<button id="ignore_error">' +
                        PMA_messages.strIgnore + '</button>';
        $buttons.html(button_html);

        $div.append($buttons);
        $div.appendTo(document.body);
        $("#change_error_settings").on("click", ErrorReport._redirect_to_settings);
        $("#show_error_report").on("click", ErrorReport._createReportDialog);
        $("#ignore_error").on("click", ErrorReport._removeErrorNotification);
    },
    /**
     * Removes the notification if it was displayed before
     *
     * @return void
     */
    _removeErrorNotification: function () {
        $("#error_notification").fadeOut(function () {
            $(this).remove();
        });
    },
    /**
     * Shows the modal dialog previewing the report
     *
     * @return void
     */
    _createReportDialog: function () {
        ErrorReport._removeErrorNotification();
        ErrorReport._showReportDialog(ErrorReport._last_exception);
    },
    /**
     * Returns the needed info about stored microhistory
     *
     * @return object
     */
    _get_microhistory: function () {
        cached_pages = AJAX.cache.pages.slice(-7);
        remove = ["common_query", "table", "db", "token", "pma_absolute_uri"];
        return {
            pages: cached_pages.map(function (page) {
                simplepage = {
                    hash: page.hash
                };

                if (page.params) {
                    simplepage.params = $.extend({}, page.params);
                    $.each(simplepage.params, function (param) {
                        if ($.inArray(param, remove) != -1) {
                            delete simplepage.params[param];
                        }
                    });
                }

                return simplepage;
            }),
            current_index: AJAX.cache.current -
                (AJAX.cache.pages.length - cached_pages.length)
        };
    },
    /**
     * Redirects to the settings page containing error report
     * preferences
     *
     * @return void
     */
    _redirect_to_settings: function () {
        window.location.href = "prefs_forms.php?token=" + PMA_commonParams.get('token');
    },
    /**
     * Returns the report data to send to the server
     *
     * @param object exception info
     *
     * @return object
     */
    _get_report_data: function (exception) {
        var token = PMA_commonParams.get('token');

        var report_data = {
            "ajax_request": true,
            "token": token,
            "exception": exception,
            "current_url": window.location.href,
            "microhistory": ErrorReport._get_microhistory(),
            "scripts": AJAX.cache.pages[AJAX.cache.current - 1].scripts.map(
                function (script) {
                    return script.name;
                }
            )
        };
        return report_data;
    },
    /**
     * Returns the exception after removing the url of the script file and
     * concatenating the context
     *
     * @param object exception info
     *
     * @return object
     */
    _simplify_exception: function (exception) {
        exception.stack = exception.stack.map(function (level) {
            if (/get_scripts\.js\.php/.test(level.url)) {
                level.url = "get_scripts.js.php";
            }
            //level.context = level.context.join("\n");
            return level;
        });
        return exception;
    },
    /**
     * Wraps all global functions that start with PMA_
     *
     * @return void
     */
    wrap_global_functions: function () {
        for (var key in window) {
            var global = window[key];
            if (typeof(global) === "function" && key.indexOf("PMA_") === 0) {
                window[key] = ErrorReport.wrap_function(global);
            }
        }
    },
    /**
     * Wraps given function in error reporting code and returns wrapped function
     *
     * @param function function to be wrapped
     *
     * @return function
     */
    wrap_function: function (func) {
        if (!func.wrapped) {
            var new_func = function () {
                try {
                    return func.apply(this, arguments);
                } catch (x) {
                    TraceKit.report(x);
                }
            };
            new_func.wrapped = true;
            return new_func;
        } else {
            return func;
        }
    },
    /**
     * Automatically wraps the callback in AJAX.registerOnload
     *
     * @return void
     */
    _wrap_ajax_onload_callback: function () {
        var oldOnload = AJAX.registerOnload;
        AJAX.registerOnload = function (file, func) {
            func = ErrorReport.wrap_function(func);
            oldOnload.call(this, file, func);
        };
    },
    /**
     * Automatically wraps the callback in $.fn.on
     *
     * @return void
     */
    _wrap_$_on_callback: function () {
        var oldOn = $.fn.on;
        $.fn.on = function () {
            for (var i = 1; i <= 3; i++) {
                if (typeof(arguments[i]) === "function") {
                    arguments[i] = ErrorReport.wrap_function(arguments[i]);
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
    set_up_error_reporting: function () {
        ErrorReport.wrap_global_functions();
        ErrorReport._wrap_ajax_onload_callback();
        ErrorReport._wrap_$_on_callback();
    }

};

TraceKit.report.subscribe(ErrorReport.error_handler);
ErrorReport.set_up_error_reporting();
$(function () {
    ErrorReport.wrap_global_functions();
});
