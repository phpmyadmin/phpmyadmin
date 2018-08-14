/* vim: set expandtab sw=4 ts=4 sts=4: */
import PMA_commonParams from './variables/common_params';
import { PMA_ajaxShowMessage } from './utils/show_ajax_messages';
import { AJAX } from './ajax';
import { removeColumnFromMultiSort } from './functions/ColumnSorting';

/**
 * @fileoverview    Implements the shiftkey + click remove column
 *                  from order by clause funcationality
 * @name            columndelete
 *
 * @requires    jQuery
 */

export function onloadMultiColumnSort () {
    $(document).on('click', 'th.draggable.column_heading.pointer.marker a', function (event) {
        var url = $(this).parent().find('input').val();
        var argsep = PMA_commonParams.get('arg_separator');
        if (event.ctrlKey || event.altKey) {
            event.preventDefault();
            let params = removeColumnFromMultiSort(url, $(this).parent());
            if (params) {
                AJAX.source = $(this);
                PMA_ajaxShowMessage();
                params += argsep + 'ajax_request=true' + argsep + 'ajax_page_request=true';
                $.post('sql.php', params, AJAX.responseHandler);
            }
        } else if (event.shiftKey) {
            event.preventDefault();
            AJAX.source = $(this);
            PMA_ajaxShowMessage();
            let params = url.substring(url.indexOf('?') + 1);
            params += argsep + 'ajax_request=true' + argsep + 'ajax_page_request=true';
            $.post('sql.php', params, AJAX.responseHandler);
        }
    });
}

export function teardownMultiColumnSort () {
    $(document).off('click', 'th.draggable.column_heading.pointer.marker a');
}
