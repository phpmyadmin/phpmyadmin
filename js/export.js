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
 * Toggles the hiding and showing of the SQL plugin's structure-specific and data-specific
 * options (TODO: expand to include other plugins).
 */
$(document).ready(function() { 
	$("input[type='radio'][name$='structure_or_data']").change(function() {
		var show = $("input[type='radio'][name$='structure_or_data']:checked").attr("value");
		if(show == 'data') {
			$('#data').slideDown('slow');
			$('#structure').slideUp('slow');
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
  			if(show == 'structure') {
				$('#structure').slideDown('slow');
				$('#data').slideUp('slow');
			} else {
				$('#structure').slideDown('slow');
				$('#data').slideDown('slow');
			}
		}	
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
 * For SQL plugin, if "CREATE TABLE options" is checked/uncheck, check/uncheck each of its sub-options 
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
