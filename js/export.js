/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functions used in the export tab
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
        $("#" + selected_plugin_name + "_options").show();
     });
});

/**
 * Toggles the enabling and disabling of the SQL plugin's comment options that apply only when exporting structure 
 */
$(document).ready(function() {
    $("input[type='radio'][name$='sql_structure_or_data']").change(function() {
        var show = $("input[type='radio'][name$='sql_structure_or_data']:checked").attr("value");
        if(show == 'data') {
            // disable the SQL comment options
            $("#checkbox_sql_dates").parent().fadeTo('fast', 0.4);
            $("#checkbox_sql_dates").attr('disabled', 'disabled');
            $("#checkbox_sql_relation").parent().fadeTo('fast', 0.4);
            $("#checkbox_sql_relation").attr('disabled', 'disabled');
            $("#checkbox_sql_mime").parent().fadeTo('fast', 0.4);
            $("#checkbox_sql_mime").attr('disabled', 'disabled');
        } else {
            // enable the SQL comment options
            $("#checkbox_sql_dates").parent().fadeTo('fast', 1);
            $("#checkbox_sql_dates").removeAttr('disabled');
            $("#checkbox_sql_relation").parent().fadeTo('fast', 1);
            $("#checkbox_sql_relation").removeAttr('disabled');
            $("#checkbox_sql_mime").parent().fadeTo('fast', 1);
            $("#checkbox_sql_mime").removeAttr('disabled');
        }
     });
});


/**
 * Toggles the hiding and showing of plugin structure-specific and data-specific
 * options
 */
$(document).ready(function() {
    function toggleStructureAndDataOpts(pluginName) {
        var radioFormName = pluginName + "_structure_or_data";
        var dataDiv = "#" + pluginName + "_data";
        var structureDiv = "#" + pluginName + "_structure";
        var show = $("input[type='radio'][name='" + radioFormName + "']:checked").attr("value");
        if(show == 'data') {
            $(dataDiv).slideDown('slow');
            $(structureDiv).slideUp('slow');
        } else {
            $(structureDiv).slideDown('slow');
            if(show == 'structure') {
                $(dataDiv).slideUp('slow');
            } else {
                $(dataDiv).slideDown('slow');
            }
        }
    }
    $("input[type='radio'][name='latex_structure_or_data']").change(function() {
        toggleStructureAndDataOpts("latex");
    });
    $("input[type='radio'][name='odt_structure_or_data']").change(function() {
        toggleStructureAndDataOpts("odt");
    });
    $("input[type='radio'][name='texytext_structure_or_data']").change(function() {
        toggleStructureAndDataOpts("texytext");
    });
    $("input[type='radio'][name='htmlword_structure_or_data']").change(function() {
        toggleStructureAndDataOpts("htmlword");
    });
    $("input[type='radio'][name='sql_structure_or_data']").change(function() {
        toggleStructureAndDataOpts("sql");
    });
});

/**
 * Toggles the disabling of the "save to file" options
 */
$(document).ready(function() {
    $("input[type='radio'][name='output_format']").change(function() {
        if($("#radio_dump_asfile:checked").length == 0) {
            $("#ul_save_asfile > li").fadeTo('fast', 0.4);
            $("#ul_save_asfile > li > input").attr('disabled', 'disabled');
            $("#ul_save_asfile > li> select").attr('disabled', 'disabled');
        } else {
            $("#ul_save_asfile > li").fadeTo('fast', 1);
            $("#ul_save_asfile > li > input").removeAttr('disabled');
            $("#ul_save_asfile > li> select").removeAttr('disabled');
        }
     });
});

/**
 * For SQL plugin, toggles the disabling of the "display comments" options
 */
$(document).ready(function() {
    $("#checkbox_sql_include_comments").change(function() {
        if($("#checkbox_sql_include_comments:checked").length == 0) {
            $("#ul_include_comments > li").fadeTo('fast', 0.4);
            $("#ul_include_comments > li > input").attr('disabled', 'disabled');
         } else {
            $("#ul_include_comments > li").fadeTo('fast', 1);
            $("#ul_include_comments > li > input").removeAttr('disabled');
         }
     });
});

/**
 * For SQL plugin, if "CREATE TABLE options" is checked/unchecked, check/uncheck each of its sub-options 
 */ 
$(document).ready(function() {
     $("#checkbox_sql_create_table_statements").change(function() {
         if($("#checkbox_sql_create_table_statements:checked").length == 0) {
            $("#checkbox_sql_if_not_exists").removeAttr('checked');
            $("#checkbox_sql_auto_increment").removeAttr('checked');
        } else {
            $("#checkbox_sql_if_not_exists").attr('checked', 'checked');
            $("#checkbox_sql_auto_increment").attr('checked', 'checked');
        }
    });
});

/** 
 * Disables the view output as text option if the output must be saved as a file
 */
$(document).ready(function() {
    $("#plugins").change(function() {
        var active_plugin = $("#plugins option:selected").attr("value");
         var force_file = $("#force_file_" + active_plugin).attr("value");
        if(force_file == "true") {
            $("#radio_view_as_text").attr('disabled', 'disabled');
            $("#radio_view_as_text").parent().fadeTo('fast', 0.4);
        } else {
            $("#radio_view_as_text").removeAttr('disabled');
            $("#radio_view_as_text").parent().fadeTo('fast', 1);
        }
    });
});

/**
 * Toggles display of options when quick and custom export are selected
 */
$(document).ready(function() {
    $("input[type='radio'][name='quick_or_custom']").change(function() {
        if($("$(this):checked").attr("value") == "custom") {
            $("#databases_and_tables").show();
            $("#rows").show();
            $("#output").show();
            $("#format_specific_opts").show();
            $("#output_quick_export").hide();
            var selected_plugin_name = $("#plugins option:selected").attr("value");
            $("#" + selected_plugin_name + "_options").show();
        } else {
            $("#databases_and_tables").hide();
            $("#rows").hide();
            $("#output").hide();
            $("#format_specific_opts").hide();
            $("#output_quick_export").show();
        }
    });
});

/**
 * Sets up the interface for Javascript-enabled browsers since the default is for
 *  Javascript-disabled browsers
 */
 $(document).ready(function() {
    $("#quick_or_custom").show();
    $("#databases_and_tables").hide();
    $("#rows").hide();
    $("#output_quick_export").show();
    $("#output").hide();
    $("#format_specific_opts").hide();
    $("#scroll_to_options_msg").hide();
    $(".format_specific_options").hide();
    $(".format_specific_options").css({ "border": 0, "margin": 0, "padding": 0});
    $(".format_specific_options h3").remove();
});

/**
 * Disables the "Dump some row(s)" sub-options when it is not selected
 */
 $(document).ready(function() {
     $("input[type='radio'][name='allrows']").change(function() {
//         alert(("$(this):checked").attr("name"));
         if($("input[type='radio'][name='allrows']:checked").attr("value") == "1") {
            $("label[for='limit_to']").fadeTo('fast', 0.4);
             $("label[for='limit_from']").fadeTo('fast', 0.4);
             $("input[type='text'][name='limit_to']").attr('disabled', 'disabled');
             $("input[type='text'][name='limit_from']").attr('disabled', 'disabled');
         } else {
            $("label[for='limit_to']").fadeTo('fast', 1);
            $("label[for='limit_from']").fadeTo('fast', 1);
            $("input[type='text'][name='limit_to']").removeAttr('disabled');
            $("input[type='text'][name='limit_from']").removeAttr('disabled');
         }
     });
});