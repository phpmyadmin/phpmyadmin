/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Module import
 */
import { PMA_Messages as messages } from '../variables/export_variables';

/**
 * Functions used in the import tab
 */

/**
 * Toggles the hiding and showing of each plugin's options
 * according to the currently selected plugin from the dropdown list
 *
 * @access public
 * 
 * @return {void}
 */
function changePluginOpts () {
    $('#format_specific_opts').find('div.format_specific_options').each(function () {
        $(this).hide();
    });
    var selectedPluginName = $('#plugins').find('option:selected').val();
    $('#' + selectedPluginName + '_options').fadeIn('slow');
    if (selectedPluginName === 'csv') {
        $('#import_notification').text(messages.strImportCSV);
    } else {
        $('#import_notification').text('');
    }
}

/**
 * Toggles the hiding and showing of each plugin's options and sets the selected value
 * in the plugin dropdown list according to the format of the selected file
 *
 * @access public
 * 
 * @param {string} fname    Name of the file
 *
 * @return {void}
 */
function matchFile (fname) {
    var fnameArray = fname.toLowerCase().split('.');
    var len = fnameArray.length;
    if (len !== 0) {
        var extension = fnameArray[len - 1];
        if (extension === 'gz' || extension === 'bz2' || extension === 'zip') {
            len--;
        }
        // Only toggle if the format of the file can be imported
        if ($('select[name=\'format\'] option').filterByValue(fnameArray[len - 1]).length === 1) {
            $('select[name=\'format\'] option').filterByValue(fnameArray[len - 1]).prop('selected', true);
            changePluginOpts();
        }
    }
}

/**
 * Module export
 */
export {
    changePluginOpts,
    matchFile
};
