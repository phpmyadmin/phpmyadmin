/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Module import
 */
import { filterVariables } from './functions/Server/ServerStatusVariables';

/**
 * @package PhpMyAdmin
 *
 * Server Status Variables
 */

/**
 * Unbind all event handlers before tearing down a page
 */
function teardownServerStatusVariables () {
    $('#filterAlert').off('change');
    $('#filterText').off('keyup');
    $('#filterCategory').off('change');
    $('#dontFormat').off('change');
}

/**
 * Binding event handlers on page load
 */
function onloadServerStatusVariables () {
    // Filters for status variables
    var textFilter = null;
    var alertFilter = $('#filterAlert').prop('checked');
    var categoryFilter = $('#filterCategory').find(':selected').val();
    var text = ''; // Holds filter text

    /* 3 Filtering functions */
    $('#filterAlert').change(function () {
        alertFilter = this.checked;
        filterVariables(textFilter, alertFilter, categoryFilter, text);
    });

    $('#filterCategory').change(function () {
        categoryFilter = $(this).val();
        filterVariables(textFilter, alertFilter, categoryFilter, text);
    });

    $('#dontFormat').change(function () {
        // Hiding the table while changing values speeds up the process a lot
        $('#serverstatusvariables').hide();
        $('#serverstatusvariables').find('td.value span.original').toggle(this.checked);
        $('#serverstatusvariables').find('td.value span.formatted').toggle(! this.checked);
        $('#serverstatusvariables').show();
    }).trigger('change');

    $('#filterText').keyup(function () {
        var word = $(this).val().replace(/_/g, ' ');
        if (word.length === 0) {
            textFilter = null;
        } else {
            try {
                textFilter = new RegExp('(^| )' + word, 'i');
                $(this).removeClass('error');
            } catch (e) {
                if (e instanceof SyntaxError) {
                    $(this).addClass('error');
                    textFilter = null;
                }
            }
        }
        text = word;
        filterVariables(textFilter, alertFilter, categoryFilter, text);
    }).trigger('keyup');
}

/**
 * Module export
 */
export {
    teardownServerStatusVariables,
    onloadServerStatusVariables
};
