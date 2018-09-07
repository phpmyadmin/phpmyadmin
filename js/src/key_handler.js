/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Module import
 */
import { onKeyDownArrowsHandler } from './functions/KeyHandler';

function teardownKeyhandler () {
    $(document).off('keydown keyup', '#table_columns');
    $(document).off('keydown keyup', 'table.insertRowTable');
}

function onloadKeyhandler () {
    $(document).on('keydown keyup', '#table_columns', function (event) {
        onKeyDownArrowsHandler(event.originalEvent);
    });
    $(document).on('keydown keyup', 'table.insertRowTable', function (event) {
        onKeyDownArrowsHandler(event.originalEvent);
    });
}

/**
 * Module export
 */
export {
    teardownKeyhandler,
    onloadKeyhandler
};
