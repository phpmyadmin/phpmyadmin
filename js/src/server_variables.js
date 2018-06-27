/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Module import
 */
import { editVariable } from './functions/Server/ServerVariables';

/**
 * @package PhpMyAdmin
 *
 * Server Variables
 */

/**
 * Unbind all event handlers before tearing down a page.
 */
function teardownServerVariables () {
    $(document).off('click', 'a.editLink');
    $('#serverVariables').find('.var-name').find('a img').remove();
}

/**
 * Binding event handlers on page load.
 */
function onloadServerVariables () {
    // var $editLink = $('a.editLink');
    var $saveLink = $('a.saveLink');
    var $cancelLink = $('a.cancelLink');

    $('#serverVariables').find('.var-name').find('a').append(
        $('#docImage').clone().css('display', 'inline-block')
    );

    /* Launches the variable editor */
    $(document).on('click', 'a.editLink', function (event) {
        event.preventDefault();
        editVariable(this, $saveLink, $cancelLink);
    });
}

export {
    teardownServerVariables,
    onloadServerVariables
};
