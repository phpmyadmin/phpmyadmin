/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functions used in the import tab
 *
 */


/**
 * Toggles the hiding and showing of each plugin's options
 * according to the currently selected plugin from the dropdown list
 */
function changePluginOpts() {
    $(".format_specific_options").each(function() { 
        $(this).hide();
    }); 
    var selected_plugin_name = $("#plugins option:selected").attr("value");
    $("#" + selected_plugin_name + "_options").fadeIn('slow');
    if(selected_plugin_name == "csv") {
        $("#import_notification").text(PMA_messages['strImportCSV']);
    } else {
        $("#import_notification").text("");
    }
}

/**
 * Toggles the hiding and showing of each plugin's options and sets the selected value
 * in the plugin dropdown list according to the format of the selected file
 */
function matchFile(fname) {
    var fname_array = fname.toLowerCase().split(".");
    var len = fname_array.length;
    if(len != 0) {
        var extension = fname_array[len - 1];
        if (extension == "gz" || extension == "bz2" || extension == "zip") {
            len--;
        }
        // Only toggle if the format of the file can be imported
        if($("select[name='format'] option[value='" + fname_array[len - 1] + "']").length == 1) {
            $("#plugins option:selected").removeAttr("selected");
            $("select[name='format'] option[value='" + fname_array[len - 1] + "']").attr('selected', 'selected');
            changePluginOpts();
        }
    }
}
$(document).ready(function() {
    // Initially display the options for the selected plugin 
    changePluginOpts();

   // Whenever the selected plugin changes, change the options displayed
   $("#plugins").change(function() {
        changePluginOpts();
    });

    $("#input_import_file").change(function() {
        matchFile($(this).attr("value"));
    });

    $("#select_local_import_file").change(function() {
        matchFile($(this).attr("value"));
    });

    /*
     * When the "Browse the server" form is clicked or the "Select from the web server upload directory"
     * form is clicked, the radio button beside it becomes selected and the other form becomes disabled.
     */
    $("#input_import_file").focus(function() {
         $("#radio_import_file").attr('checked', 'checked');
         $("#radio_local_import_file").removeAttr('checked');
    });
    $("#select_local_import_file").focus(function() {
         $("#radio_local_import_file").attr('checked', 'checked');
         $("#radio_import_file").removeAttr('checked');
    });

    /**
     * Set up the interface for Javascript-enabled browsers since the default is for
     *  Javascript-disabled browsers
     */
    $("#scroll_to_options_msg").hide();
    $(".format_specific_options").css({ "border": 0, "margin": 0, "padding": 0 });
    $(".format_specific_options h3").remove();
});