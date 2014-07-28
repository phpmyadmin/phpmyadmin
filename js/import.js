/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functions used in the import tab
 *
 */


/**
 * Toggles the hiding and showing of each plugin's options
 * according to the currently selected plugin from the dropdown list
 */
function changePluginOpts()
{
    $("#format_specific_opts div.format_specific_options").each(function () {
        $(this).hide();
    });
    var selected_plugin_name = $("#plugins option:selected").val();
    $("#" + selected_plugin_name + "_options").fadeIn('slow');
    if (selected_plugin_name == "csv") {
        $("#import_notification").text(PMA_messages.strImportCSV);
    } else {
        $("#import_notification").text("");
    }
}

/**
 * Toggles the hiding and showing of each plugin's options and sets the selected value
 * in the plugin dropdown list according to the format of the selected file
 */
function matchFile(fname)
{
    var fname_array = fname.toLowerCase().split(".");
    var len = fname_array.length;
    if (len !== 0) {
        var extension = fname_array[len - 1];
        if (extension == "gz" || extension == "bz2" || extension == "zip") {
            len--;
        }
        // Only toggle if the format of the file can be imported
        if ($("select[name='format'] option").filterByValue(fname_array[len - 1]).length == 1) {
            $("select[name='format'] option").filterByValue(fname_array[len - 1]).prop('selected', true);
            changePluginOpts();
        }
    }
}

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('import.js', function () {
    $("#plugins").unbind('change');
    $("#input_import_file").unbind('change');
    $("#select_local_import_file").unbind('change');
    $("#input_import_file").unbind('change').unbind('focus');
    $("#select_local_import_file").unbind('focus');
    $("#text_csv_enclosed").add("#text_csv_escaped").unbind('keyup');
});

AJAX.registerOnload('import.js', function () {
    // import_file_form validation.
    $('#import_file_form').live('submit', function (event) {
        var radioLocalImport = $("#radio_local_import_file");
        var radioImport = $("#radio_import_file");
        var fileMsg = '<div class="error"><img src="themes/dot.gif" title="" alt="" class="icon ic_s_error" /> ' + PMA_messages.strImportDialogMessage + '</div>';

        if (radioLocalImport.length !== 0) {
            // remote upload.
            // TODO Remove this section when all browsers support HTML5 "required" property
            if (! radioLocalImport.is(":checked") && ! radioImport.is(":checked")) {
                radioImport.focus();
                var msg = '<div class="error"><img src="themes/dot.gif" title="" alt="" class="icon ic_s_error" /> ';
                msg += PMA_messages.strRadioUnchecked;
                msg += '</div>';
                PMA_ajaxShowMessage(msg, false);
                return false;
            }

            if (radioImport.is(":checked") && $("#input_import_file").val() === '') {
                $("#input_import_file").focus();
                PMA_ajaxShowMessage(fileMsg, false);
                return false;
            }

            if (radioLocalImport.is(":checked")) {
                if ($("#select_local_import_file").length === 0) {
                    PMA_ajaxShowMessage('<div class="error"><img src="themes/dot.gif" title="" alt="" class="icon ic_s_error" /> ' + PMA_messages.strNoImportFile + ' </div>', false);
                    return false;
            }

                if ($("#select_local_import_file").val() === '') {
                    $("#select_local_import_file").focus();
                    PMA_ajaxShowMessage(fileMsg, false);
                    return false;
                }
            }
        } else {
            // local upload.
            if ($("#input_import_file").val() === '') {
                $("#input_import_file").focus();
                PMA_ajaxShowMessage(fileMsg, false);
                return false;
            }
        }

        // show progress bar.
        $("#upload_form_status").css("display", "inline");
        $("#upload_form_status_info").css("display", "inline");
        return;
    });

    // Initially display the options for the selected plugin
    changePluginOpts();

   // Whenever the selected plugin changes, change the options displayed
    $("#plugins").change(function () {
        changePluginOpts();
    });

    $("#input_import_file").change(function () {
        matchFile($(this).val());
    });

    $("#select_local_import_file").change(function () {
        matchFile($(this).val());
    });

    /*
     * When the "Browse the server" form is clicked or the "Select from the web server upload directory"
     * form is clicked, the radio button beside it becomes selected and the other form becomes disabled.
     */
    $("#input_import_file").bind("focus change", function () {
        $("#radio_import_file").prop('checked', true);
        $("#radio_local_import_file").prop('checked', false);
    });
    $("#select_local_import_file").focus(function () {
        $("#radio_local_import_file").prop('checked', true);
        $("#radio_import_file").prop('checked', false);
    });

    /**
     * Set up the interface for Javascript-enabled browsers since the default is for
     *  Javascript-disabled browsers
     */
    $("#scroll_to_options_msg").hide();
    $("#format_specific_opts div.format_specific_options")
    .css({
        "border": 0,
        "margin": 0,
        "padding": 0
    })
    .find("h3")
    .remove();
    //$("form[name=import] *").unwrap();

    /**
     * for input element text_csv_enclosed and text_csv_escaped allow just one character to enter.
     * as mysql allows just one character for these fields,
     * if first character is escape then allow two including escape character.
     */
    $("#text_csv_enclosed").add("#text_csv_escaped").bind('keyup', function() {
        if($(this).val().length === 2 && $(this).val().charAt(0) !== "\\") {
            $(this).val($(this).val().substring(0, 1));
            return false;
        }
        return true;
    });

});
