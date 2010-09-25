/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * function used for index manipulation pages
 *
 */

/**
 * Ensures indexes names are valid according to their type and, for a primary
 * key, lock index name to 'PRIMARY'
 *
 * @return  boolean  false if there is no index form, true else
 */
function checkIndexName()
{
    if (typeof(document.forms['index_frm']) == 'undefined') {
        return false;
    }

    // Gets the elements pointers
    var the_idx_name = document.forms['index_frm'].elements['index'];
    var the_idx_type = document.forms['index_frm'].elements['index_type'];

    // Index is a primary key
    if (the_idx_type.options[0].value == 'PRIMARY' && the_idx_type.options[0].selected) {
        document.forms['index_frm'].elements['index'].value = 'PRIMARY';
        if (typeof(the_idx_name.disabled) != 'undefined') {
            document.forms['index_frm'].elements['index'].disabled = true;
        }
    }

    // Other cases
    else {
        if (the_idx_name.value == 'PRIMARY') {
            document.forms['index_frm'].elements['index'].value = '';
        }
        if (typeof(the_idx_name.disabled) != 'undefined') {
            document.forms['index_frm'].elements['index'].disabled = false;
        }
    }

    return true;
} // end of the 'checkIndexName()' function


onload = checkIndexName;
