/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functions used in the import tab
 *
 * @version $Id$
 */
 
 /**
  * Toggles the hiding and showing of each plugin's options
  * according to the currently selected plugin from the dropdown list
  */
$(document).ready(function() {
    $("#plugins").change(function() {
        $(".format_specific_options").each(function() { 
            $(this).hide();
        }); 
        var selected_plugin_name = $("#plugins option:selected").attr("value");
        $("#" + selected_plugin_name + "_options").fadeIn('slow');
    });
});