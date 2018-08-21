import { PMA_Messages as PMA_messages } from '../variables/export_variables';

/**
 * Produce print preview
 */
export function printPreview () {
    $('#printcss').attr('media','all');
    createPrintAndBackButtons();
}

/**
 * Create print and back buttons in preview page
 */
function createPrintAndBackButtons () {
    var back_button = $('<input/>',{
        type: 'button',
        value: PMA_messages.back,
        id: 'back_button_print_view'
    });
    back_button.on('click', removePrintAndBackButton);
    back_button.appendTo('#page_content');
    var print_button = $('<input/>',{
        type: 'button',
        value: PMA_messages.print,
        id: 'print_button_print_view'
    });
    print_button.on('click', printPage);
    print_button.appendTo('#page_content');
}

/**
 * Remove print and back buttons and revert to normal view
 */
function removePrintAndBackButton () {
    $('#printcss').attr('media','print');
    $('#back_button_print_view').remove();
    $('#print_button_print_view').remove();
}

/**
 * Print page
 */
function printPage () {
    if (typeof(window.print) !== 'undefined') {
        window.print();
    }
}
