/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * general function, usually for data manipulation pages
 *
 */

var last_error_info = {}

window.onerror = error_handler
function error_handler(message, file, line) {
    last_error_info = {
        "message": message,
        "file": file,
        "line": line,
    }

    $.get("error_report.php",{
        ajax_request: true,
        token: get_token(),
        get_settings: true,
    }, function(data) {
        if(data.report_setting == "ask") {
            showErrorNotification();
        } else if(data.report_setting == "always") {
            report_data = get_report_data(message, file, line);
            post_data = $.extend(report_data, {
                send_error_report: true,
                automatic: true,
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
}

function showReportDialog (message, file, line) {

    var report_data = get_report_data(message, file, line);

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
            always_send: $("#always_send_checkbox")[0].checked,
        })
        $.post("error_report.php", post_data, function (data) {
            $dialog.dialog('close')
            if (data.success === false) {
                //in the case of an error, show the error message returned.
                PMA_ajaxShowMessage(data.error, false);
            } else {
                PMA_ajaxShowMessage(data.message, 3000);
            }
        })
    }

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
}

function showErrorNotification() {
    removeErrorNotification()

    $div = $('<div style="position:fixed;bottom:0px;left:5px;right:5px;'
            +'z-index:1000" class="error" id="error_notification"></div>')
    html = ""
    html += '<img src="themes/dot.gif" title="" alt="" class="icon ic_s_error">'
    html += PMA_messages.strErrorOccured
    $div.html(html)

    $buttons = $('<div style="float:right"></div>')
    button_html = '';
    button_html += '<button id="change_error_settings">'+
                    PMA_messages.strChangeReportSettings + '</button>'
    button_html += '<button id="show_error_report">'+
                    PMA_messages.strShowReportDetails + '</button>'
    button_html += '<button id="ignore_error">'+
                    PMA_messages.strIgnore + '</button>'
    $buttons.html(button_html)

    $div.append($buttons)
    $div.appendTo(document.body)
    $("#change_error_settings").on("click", go_to_settings)
    $("#show_error_report").on("click", createReportDialog)
    $("#ignore_error").on("click", removeErrorNotification)
}

function removeErrorNotification() {
    $("#error_notification").fadeOut()
}

function createReportDialog() {
    removeErrorNotification()
    showReportDialog(last_error_info.message, last_error_info.file, last_error_info.line)
}

function get_microhistory() {
    cached_pages = AJAX.cache.pages.slice(-7);
    return {
        pages: cached_pages.map(function(page) {
            return {
                hash: page.hash,
                params: page.params,
            }
        }),
        current_index: AJAX.cache.current -
            (AJAX.cache.pages.length - cached_pages.length)
    };
}

function go_to_settings() {
   window.location.href = "prefs_forms.php?token=" + get_token();
}

function get_token() {
    regex = /token=([\da-z]+)/
    token = regex.exec($("#selflink a")[0].href)[1]
    return token;
}

function get_report_data(message, file, line) {
    var token = get_token();

    var report_data = {
        "ajax_request": true,
        "token": token,
        "message": message,
        "file": file,
        "line": line,
        "current_url": window.location.href,
        "microhistory": get_microhistory(),
    }
    return report_data;
}

$(function(){
    a();
})
