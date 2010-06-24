/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functions used in the import tab
 *
 * @version $Id$
 */

$(document).ready(function() {

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
            $("#import_notification").text("Note: If the file contains multiple tables, they will be combined into one");
        } else {
            $("#import_notification").text("");
        }
    }

    /**
     * Toggles the hiding and showing of each plugin's options and sets the selected value
     * in the plugin dropdown list according to the format of the selected file
     */
    function matchFile(fname) {
        fname_array = fname.toLowerCase().split(".");
        len = fname_array.length;
        if(len != 0) {
            extension = fname_array[len - 1];
            if (extension == "gz" || extension == "bz2" || extension == "zip") {
                len--;
            }
            $("#plugins option:selected").removeAttr("selected");
            switch (fname_array[len - 1]) {
                case "csv" : $("select[name='format'] option[value='csv']").attr('selected', 'selected'); break;
                case "docsql" : $("select[name='format'] option[value='docsql']").attr('selected', 'selected'); break;
                case "ldi" : $("select[name='format'] option[value='ldi']").attr('selected', 'selected'); break;
                case "ods" : $("select[name='format'] option[value='ods']").attr('selected', 'selected'); break;
                case "sql" : $("select[name='format'] option[value='sql']").attr('selected', 'selected'); break;
                case "xls" : $("select[name='format'] option[value='xls']").attr('selected', 'selected'); break;
                case "xlsx" : $("select[name='format'] option[value='xlsx']").attr('selected', 'selected'); break;
                case "xml" : $("select[name='format'] option[value='xml']").attr('selected', 'selected'); break;
            }
            changePluginOpts();
        }
    }

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
 });