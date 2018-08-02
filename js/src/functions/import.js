/**
 * Functions used in the import tab
 *
 */

/**
 * Toggles the hiding and showing of each plugin's options
 * according to the currently selected plugin from the dropdown list
 */
export function changePluginOpts () {
    $('#format_specific_opts').find('div.format_specific_options').each(function () {
        $(this).hide();
    });
    var selected_plugin_name = $('#plugins').find('option:selected').val();
    $('#' + selected_plugin_name + '_options').fadeIn('slow');
    if (selected_plugin_name === 'csv') {
        $('#import_notification').text(PMA_messages.strImportCSV);
    } else {
        $('#import_notification').text('');
    }
}

/**
 * Toggles the hiding and showing of each plugin's options and sets the selected value
 * in the plugin dropdown list according to the format of the selected file
 */
export function matchFile (fname) {
    var fname_array = fname.toLowerCase().split('.');
    var len = fname_array.length;
    if (len !== 0) {
        var extension = fname_array[len - 1];
        if (extension === 'gz' || extension === 'bz2' || extension === 'zip') {
            len--;
        }
        // Only toggle if the format of the file can be imported
        if ($('select[name=\'format\'] option').filterByValue(fname_array[len - 1]).length === 1) {
            $('select[name=\'format\'] option').filterByValue(fname_array[len - 1]).prop('selected', true);
            changePluginOpts();
        }
    }
}
