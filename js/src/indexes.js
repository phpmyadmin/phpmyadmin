/* vim: set expandtab sw=4 ts=4 sts=4: */
import { PMA_Messages as PMA_messages } from './variables/export_variables';
import IndexEnum from './utils/IndexEnum';
import * as Indexes from './functions/Indexes';
import { escapeHtml } from './utils/Sanitise';
import { PMA_ajaxShowMessage, PMA_ajaxRemoveMessage } from './utils/show_ajax_messages';
import { PMA_hideShowConnection } from './functions/Table/TableColumns';
import { PMA_previewSQL } from './functions/Sql/PreviewSql';
import PMA_commonParams from './variables/common_params';
import { AJAX } from './ajax';
import { getJSConfirmCommonParam } from './functions/Common';
import { PMA_commonActions } from './classes/CommonActions';
import { PMA_highlightSQL } from './utils/sql';
/**
 * @fileoverview    function used for index manipulation pages
 * @name            Table Structure
 *
 * @requires    jQuery
 * @requires    jQueryUI
 * @required    js/functions.js
 */

/**
 * Unbind all event handlers before tearing down a page
 */
export function teardownIndexes () {
    $(document).off('click', '#save_index_frm');
    $(document).off('click', '#preview_index_frm');
    $(document).off('change', '#select_index_choice');
    $(document).off('click', 'a.drop_primary_key_index_anchor.ajax');
    $(document).off('click', '#table_index tbody tr td.edit_index.ajax, #index_div .add_index.ajax');
    $(document).off('click', '#index_frm input[type=submit]');
    $('body').off('change', 'select[name*="field_key"]');
    $(document).off('click', '.show_index_dialog');
}

/**
 * @description <p>Ajax scripts for table index page</p>
 *
 * Actions ajaxified here:
 * <ul>
 * <li>Showing/hiding inputs depending on the index type chosen</li>
 * <li>create/edit/drop indexes</li>
 * </ul>
 */
export function onloadIndexes () {
    // Re-initialize variables.
    IndexEnum.primary_indexes = [];
    IndexEnum.unique_indexes = [];
    IndexEnum.indexes = [];
    IndexEnum.fulltext_indexes = [];
    IndexEnum.spatial_indexes = [];

    // for table creation form
    var $engine_selector = $('.create_table_form select[name=tbl_storage_engine]');
    if ($engine_selector.length) {
        PMA_hideShowConnection($engine_selector);
    }

    var $form = $('#index_frm');
    if ($form.length > 0) {
        Indexes.showIndexEditDialog($form);
    }

    $(document).on('click', '#save_index_frm', function (event) {
        event.preventDefault();
        var $form = $('#index_frm');
        var argsep = PMA_commonParams.get('arg_separator');
        var submitData = $form.serialize() + argsep + 'do_save_data=1' + argsep + 'ajax_request=true' + argsep + 'ajax_page_request=true';
        var $msgbox = PMA_ajaxShowMessage(PMA_messages.strProcessingRequest);
        AJAX.source = $form;
        $.post($form.attr('action'), submitData, AJAX.responseHandler);
    });

    $(document).on('click', '#preview_index_frm', function (event) {
        event.preventDefault();
        PMA_previewSQL($('#index_frm'));
    });

    $(document).on('change', '#select_index_choice', function (event) {
        event.preventDefault();
        Indexes.checkIndexType();
        Indexes.checkIndexName('index_frm');
    });

    /**
     * Ajax Event handler for 'Drop Index'
     */
    $(document).on('click', 'a.drop_primary_key_index_anchor.ajax', function (event) {
        event.preventDefault();

        var $anchor = $(this);
        /**
         * @var $curr_row    Object containing reference to the current field's row
         */
        var $curr_row = $anchor.parents('tr');
        /** @var    Number of columns in the key */
        var rows = $anchor.parents('td').attr('rowspan') || 1;
        /** @var    Rows that should be hidden */
        var $rows_to_hide = $curr_row;
        for (var i = 1, $last_row = $curr_row.next(); i < rows; i++, $last_row = $last_row.next()) {
            $rows_to_hide = $rows_to_hide.add($last_row);
        }

        var question = escapeHtml(
            $curr_row.children('td')
                .children('.drop_primary_key_index_msg')
                .val()
        );

        $anchor.PMA_confirm(question, $anchor.attr('href'), function (url) {
            var $msg = PMA_ajaxShowMessage(PMA_messages.strDroppingPrimaryKeyIndex, false);
            var params = getJSConfirmCommonParam(this, $anchor.getPostData());
            $.post(url, params, function (data) {
                if (typeof data !== 'undefined' && data.success === true) {
                    PMA_ajaxRemoveMessage($msg);
                    var $table_ref = $rows_to_hide.closest('table');
                    if ($rows_to_hide.length === $table_ref.find('tbody > tr').length) {
                        // We are about to remove all rows from the table
                        $table_ref.hide('medium', function () {
                            $('div.no_indexes_defined').show('medium');
                            $rows_to_hide.remove();
                        });
                        $table_ref.siblings('div.notice').hide('medium');
                    } else {
                        // We are removing some of the rows only
                        $rows_to_hide.hide('medium', function () {
                            $(this).remove();
                        });
                    }
                    if ($('.result_query').length) {
                        $('.result_query').remove();
                    }
                    if (data.sql_query) {
                        $('<div class="result_query"></div>')
                            .html(data.sql_query)
                            .prependTo('#structure_content');
                        PMA_highlightSQL($('#page_content'));
                    }
                    PMA_commonActions.refreshMain(false, function () {
                        $('a.ajax[href^=#indexes]').trigger('click');
                    });
                    PMA_reloadNavigation();
                } else {
                    PMA_ajaxShowMessage(PMA_messages.strErrorProcessingRequest + ' : ' + data.error, false);
                }
            }); // end $.post()
        }); // end $.PMA_confirm()
    }); // end Drop Primary Key/Index

    /**
     *Ajax event handler for index edit
    **/
    $(document).on('click', '#table_index tbody tr td.edit_index.ajax, #index_div .add_index.ajax', function (event) {
        event.preventDefault();
        var url;
        var title;
        if ($(this).find('a').length === 0) {
            // Add index
            var valid = checkFormElementInRange(
                $(this).closest('form')[0],
                'added_fields',
                'Column count has to be larger than zero.'
            );
            if (! valid) {
                return;
            }
            url = $(this).closest('form').serialize();
            title = PMA_messages.strAddIndex;
        } else {
            // Edit index
            url = $(this).find('a').attr('href');
            if (url.substring(0, 16) === 'tbl_indexes.php?') {
                url = url.substring(16, url.length);
            }
            title = PMA_messages.strEditIndex;
        }
        url += PMA_commonParams.get('arg_separator') + 'ajax_request=true';
        Indexes.indexEditorDialog(url, title, function () {
            // refresh the page using ajax
            PMA_commonActions.refreshMain(false, function () {
                $('a.ajax[href^=#indexes]').trigger('click');
            });
        });
    });

    /**
     * Ajax event handler for advanced index creation during table creation
     * and column addition.
     */
    $('body').on('change', 'select[name*="field_key"]', function () {
        // Index of column on Table edit and create page.
        var col_index = /\d+/.exec($(this).attr('name'));
        col_index = col_index[0];
        // Choice of selected index.
        var index_choice = /[a-z]+/.exec($(this).val());
        index_choice = index_choice[0];
        // Array containing corresponding indexes.
        var source_array = null;

        if (index_choice === 'none') {
            Indexes.PMA_removeColumnFromIndex(col_index);
            return false;
        }

        // Select a source array.
        source_array = Indexes.PMA_getIndexArray(index_choice);
        if (source_array === null) {
            return;
        }

        if (source_array.length === 0) {
            var index = {
                'Key_name': (index_choice === 'primary' ? 'PRIMARY' : ''),
                'Index_choice': index_choice.toUpperCase()
            };
            Indexes.PMA_showAddIndexDialog(source_array, 0, [col_index], col_index, index);
        } else {
            if (index_choice === 'primary') {
                var array_index = 0;
                var source_length = source_array[array_index].columns.length;
                var target_columns = [];
                for (var i = 0; i < source_length; i++) {
                    target_columns.push(source_array[array_index].columns[i].col_index);
                }
                target_columns.push(col_index);

                Indexes.PMA_showAddIndexDialog(source_array, array_index, target_columns, col_index,
                    source_array[array_index]);
            } else {
                // If there are multiple columns selected for an index, show advanced dialog.
                Indexes.PMA_indexTypeSelectionDialog(source_array, index_choice, col_index);
            }
        }
    });

    $(document).on('click', '.show_index_dialog', function (e) {
        e.preventDefault();

        // Get index details.
        var previous_index = $(this).prev('select')
            .attr('data-index')
            .split(',');

        var index_choice = previous_index[0];
        var array_index  = previous_index[1];

        var source_array = Indexes.PMA_getIndexArray(index_choice);
        var source_length = source_array[array_index].columns.length;

        var target_columns = [];
        for (var i = 0; i < source_length; i++) {
            target_columns.push(source_array[array_index].columns[i].col_index);
        }

        Indexes.PMA_showAddIndexDialog(source_array, array_index, target_columns, -1, source_array[array_index]);
    });

    $('#index_frm').on('submit', function () {
        if (typeof(this.elements['index[Key_name]'].disabled) !== 'undefined') {
            this.elements['index[Key_name]'].disabled = false;
        }
    });
}
