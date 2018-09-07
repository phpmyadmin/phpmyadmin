/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Module import
 */
import DragDropImport from './classes/DragDropImport';

/* This script handles PMA Drag Drop Import, loaded only when configuration is enabled.*/

export function teardownDragDropImport () {
    $(document).off('dragenter');
    $(document).off('dragover');
    $(document).off('dragleave', '.pma_drop_handler');
    $(document).off('drop', 'body');
    $(document).off('click', '.pma_sql_import_status h2 .minimize');
    $(document).off('click', '.pma_sql_import_status h2 .close');
    $(document).off('click', '.pma_drop_result h2 .close');
}

export function onloadDragDropImport () {
    /**
     * Called when some user drags, dragover, leave
     *       a file to the PMA UI
     * @param object Event data
     * @return void
     */
    $(document).on('dragenter', DragDropImport._dragenter);
    $(document).on('dragover', DragDropImport._dragover);
    $(document).on('dragleave', '.pma_drop_handler', DragDropImport._dragleave);

    // when file is dropped to PMA UI
    $(document).on('drop', 'body', DragDropImport._drop);

    // minimizing-maximising the sql ajax upload status
    $(document).on('click', '.pma_sql_import_status h2 .minimize', function () {
        if ($(this).attr('toggle') === 'off') {
            $('.pma_sql_import_status div').css('height','270px');
            $(this).attr('toggle','on');
            $(this).html('-');  // to minimize
        } else {
            $('.pma_sql_import_status div').css('height','0px');
            $(this).attr('toggle','off');
            $(this).html('+');  // to maximise
        }
    });

    // closing sql ajax upload status
    $(document).on('click', '.pma_sql_import_status h2 .close', function () {
        $('.pma_sql_import_status').fadeOut(function () {
            $('.pma_sql_import_status div').html('');
            DragDropImport.importStatus = [];  // clear the message array
        });
    });

    // Closing the import result box
    $(document).on('click', '.pma_drop_result h2 .close', function () {
        $(this).parent('h2').parent('div').remove();
    });
}
