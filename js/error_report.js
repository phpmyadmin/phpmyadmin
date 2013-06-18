/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * general function, usually for data manipulation pages
 *
 */

var last_erro_info = {}

window.onerror = error_handler
function error_handler(message, file, line) {
    last_error_info = {
        "message": message,
        "file": file,
        "line": line,
    }
    showErrorNotification()
}

function showReportDialog (message, file, line) {
    regex = /token=([\da-z]+)/
    token = regex.exec($("#selflink a")[0].href)[1]
    
    var report_data = {
        "ajax_request": true,
        "token": token,
        "message": message,
        "file": file,
        "line": line,
    }

    /*Remove the hidden dialogs if there are*/
    if ($('#error_report_dialog').length !== 0) {
        $('#error_report_dialog').remove();
    }
    var $div = $('<div id="error_report_dialog"></div>');

    var button_options = {};

    button_options["Send Error Report"] = function () {
        $dialog = $(this);
        post_data = $.extend(report_data, {
            send_error_report: true,
            extra_info: $("#report_extra_info").val()
        })
        $.post("error_report.php", post_data, function (data) {
            $dialog.dialog('close')
            if (data.success === false) {
                //in the case of an error, show the error message returned.
                PMA_ajaxShowMessage(data.error, false);
            } else {
                PMA_ajaxShowMessage("the page will reload shortly", false);
                setTimeout("window.location.reload()", 2000);
            }
        })
    }

    button_options[PMA_messages.strCancel] = function () {
        $(this).dialog('close');
    };

    $.get("error_report.php", report_data, function (data) {
        if (data.success === false) {
            //in the case of an error, show the error message returned.
            PMA_ajaxShowMessage(data.error, false);
        } else {
            // Show dialog if the request was successful
            $div
            .append(data.message)
            .dialog({
                title: "Submit error report",
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
    html += 'An error has occured do you want to send an error report'
    $div.html(html)

    $buttons = $('<div style="float:right"></div>')
    button_html = '';
    button_html += '<button onclick="createReportDialog()">Send Report</button>'
    button_html += '<button onclick="removeErrorNotification()">Ignore</button>'
    $buttons.html(button_html)

    $div.append($buttons)
    $div.appendTo(document.body)
}

function removeErrorNotification() {
    $("#error_notification").fadeOut()
}

function createReportDialog() {
    removeErrorNotification()
    showReportDialog(last_error_info.message, last_error_info.file, last_error_info.line)
}

$(function(){
    setTimeout("a()", 3000);
})
