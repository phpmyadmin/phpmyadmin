import $ from 'jquery';

/**
 * Ensures indexes names are valid according to their type and, for a primary
 * key, lock index name to 'PRIMARY'
 * @param {string} formId Variable which parses the form name as
 *                        the input
 * @return {boolean} false if there is no index form, true else
 */
export default function checkIndexName (formId) {
    if ($('#' + formId).length === 0) {
        return false;
    }

    // Gets the elements pointers
    var $theIdxName = $('#input_index_name');
    var $theIdxChoice = $('#select_index_choice');

    // Index is a primary key
    if ($theIdxChoice.find('option:selected').val() === 'PRIMARY') {
        $theIdxName.val('PRIMARY');
        $theIdxName.prop('disabled', true);
    } else {
        if ($theIdxName.val() === 'PRIMARY') {
            $theIdxName.val('');
        }

        $theIdxName.prop('disabled', false);
    }

    return true;
}
