/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Module import
 */
import { $ } from './utils/JqueryExtended';
import { PMA_Messages as messages } from './variables/export_variables';
import { PMA_sprintf } from './utils/sprintf';
import { escapeHtml } from './utils/Sanitise';
import { PMA_ajaxShowMessage, PMA_ajaxRemoveMessage, PMA_tooltip } from './utils/show_ajax_messages';
import { getForeignKeyCheckboxLoader, loadForeignKeyCheckbox, getJSConfirmCommonParam } from './functions/Sql/ForeignKey';
import { PMA_adjustTotals, PMA_fetchRealRowCount } from './functions/Database/Structure';
import { printPreview } from './functions/Print';
import { PMA_reloadNavigation } from './functions/navigation';
/**
 * @fileoverview    functions used on the database structure page
 * @name            Database Structure
 *
 * @requires    jQuery
 * @requires    jQueryUI
 * @required    js/functions.js
 */

/**
 * AJAX scripts for db_structure.php
 *
 * Actions ajaxified here:
 * Drop Database
 * Truncate Table
 * Drop Table
 *
 */

/**
 * Unbind all event handlers before tearing down a page
 */
export function teardownDbStructure () {
    $(document).off('click', 'a.truncate_table_anchor.ajax');
    $(document).off('click', 'a.drop_table_anchor.ajax');
    $(document).off('click', '#real_end_input');
    $(document).off('click', 'a.favorite_table_anchor.ajax');
    $(document).off('click', '#printView');
    $('a.real_row_count').off('click');
    $('a.row_count_sum').off('click');
    $('select[name=submit_mult]').off('change');
}

export function onloadDbStructure () {
/**
 * function to open the confirmation dialog for making table consistent with central list
 *
 * @param string   msg     message text to be displayed to user
 * @param function success function to be called on success
 *
 */
    var jqConfirm = function (msg, success) {
        var dialogObj = $('<div class=\'hide\'>' + msg + '</div>');
        $('body').append(dialogObj);
        var buttonOptions = {};
        buttonOptions[messages.strContinue] = function () {
            success();
            $(this).dialog('close');
        };
        buttonOptions[messages.strCancel] = function () {
            $(this).dialog('close');
            $('#tablesForm')[0].reset();
        };
        $(dialogObj).dialog({
            resizable: false,
            modal: true,
            title: messages.confirmTitle,
            buttons: buttonOptions
        });
    };

    /**
 *  Event handler on select of "Make consistent with central list"
 */
    $('select[name=submit_mult]').on('change', function (event) {
        if ($(this).val() === 'make_consistent_with_central_list') {
            event.preventDefault();
            event.stopPropagation();
            jqConfirm(
                messages.makeConsistentMessage, function () {
                    $('#tablesForm').submit();
                }
            );
            return false;
        } else if ($(this).val() === 'copy_tbl' || $(this).val() === 'add_prefix_tbl' || $(this).val() === 'replace_prefix_tbl' || $(this).val() === 'copy_tbl_change_prefix') {
            event.preventDefault();
            event.stopPropagation();
            if ($('input[name="selected_tbl[]"]:checked').length === 0) {
                return false;
            }
            var formData = $('#tablesForm').serialize();
            var modalTitle = '';
            if ($(this).val() === 'copy_tbl') {
                modalTitle = messages.strCopyTablesTo;
            } else if ($(this).val() === 'add_prefix_tbl') {
                modalTitle = messages.strAddPrefix;
            } else if ($(this).val() === 'replace_prefix_tbl') {
                modalTitle = messages.strReplacePrefix;
            } else if ($(this).val() === 'copy_tbl_change_prefix') {
                modalTitle = messages.strCopyPrefix;
            }
            $.ajax({
                type: 'POST',
                url: 'db_structure.php',
                dataType: 'html',
                data: formData

            }).done(function (data) {
                var dialogObj = $('<div class=\'hide\'>' + data + '</div>');
                $('body').append(dialogObj);
                var buttonOptions = {};
                buttonOptions[messages.strContinue] = function () {
                    $('#ajax_form').submit();
                    $(this).dialog('close');
                };
                buttonOptions[messages.strCancel] = function () {
                    $(this).dialog('close');
                    $('#tablesForm')[0].reset();
                };
                $(dialogObj).dialog({
                    minWidth: 500,
                    resizable: false,
                    modal: true,
                    title: modalTitle,
                    buttons: buttonOptions
                });
            });
        } else {
            $('#tablesForm').submit();
        }
    });

    /**
     * Ajax Event handler for 'Truncate Table'
     */
    $(document).on('click', 'a.truncate_table_anchor.ajax', function (event) {
        event.preventDefault();

        /**
         * @var $this_anchor Object  referring to the anchor clicked
         */
        var $this_anchor = $(this);

        // extract current table name and build the question string
        /**
         * @var curr_table_name String containing the name of the table to be truncated
         */
        var curr_table_name = $this_anchor.parents('tr').children('th').children('a').text();
        /**
         * @var question    String containing the question to be asked for confirmation
         */
        var question = messages.strTruncateTableStrongWarning + ' ' +
            PMA_sprintf(messages.strDoYouReally, 'TRUNCATE `' + escapeHtml(curr_table_name) + '`') +
            getForeignKeyCheckboxLoader();

        $this_anchor.PMA_confirm(question, $this_anchor.attr('href'), function (url) {
            PMA_ajaxShowMessage(messages.strProcessingRequest);

            var params = getJSConfirmCommonParam(this, $this_anchor.getPostData());

            $.post(url, params, function (data) {
                if (typeof data !== 'undefined' && data.success === true) {
                    PMA_ajaxShowMessage(data.message);
                    // Adjust table statistics
                    var $tr = $this_anchor.closest('tr');
                    $tr.find('.tbl_rows').text('0');
                    $tr.find('.tbl_size, .tbl_overhead').text('-');
                    // Fetch inner span of this anchor
                    // and replace the icon with its disabled version
                    var span = $this_anchor.html().replace(/b_empty/, 'bd_empty');
                    // To disable further attempts to truncate the table,
                    // replace the a element with its inner span (modified)
                    $this_anchor
                        .replaceWith(span)
                        .removeClass('truncate_table_anchor');
                    PMA_adjustTotals();
                } else {
                    PMA_ajaxShowMessage(messages.strErrorProcessingRequest + ' : ' + data.error, false);
                }
            }); // end $.post()
        }, loadForeignKeyCheckbox); // end $.PMA_confirm()
    }); // end of Truncate Table Ajax action

    /**
     * Ajax Event handler for 'Drop Table' or 'Drop View'
     */
    $(document).on('click', 'a.drop_table_anchor.ajax', function (event) {
        event.preventDefault();

        var $this_anchor = $(this);

        // extract current table name and build the question string
        /**
         * @var $curr_row    Object containing reference to the current row
         */
        var $curr_row = $this_anchor.parents('tr');
        /**
         * @var curr_table_name String containing the name of the table to be truncated
         */
        var curr_table_name = $curr_row.children('th').children('a').text();
        /**
         * @var is_view Boolean telling if we have a view
         */
        var is_view = $curr_row.hasClass('is_view') || $this_anchor.hasClass('view');
        /**
         * @var question    String containing the question to be asked for confirmation
         */
        var question;
        if (! is_view) {
            question = messages.strDropTableStrongWarning + ' ' +
                PMA_sprintf(messages.strDoYouReally, 'DROP TABLE `' + escapeHtml(curr_table_name) + '`');
        } else {
            question =
                PMA_sprintf(messages.strDoYouReally, 'DROP VIEW `' + escapeHtml(curr_table_name) + '`');
        }
        question += getForeignKeyCheckboxLoader();

        $this_anchor.PMA_confirm(question, $this_anchor.attr('href'), function (url) {
            var $msg = PMA_ajaxShowMessage(messages.strProcessingRequest);

            var params = getJSConfirmCommonParam(this, $this_anchor.getPostData());

            $.post(url, params, function (data) {
                if (typeof data !== 'undefined' && data.success === true) {
                    PMA_ajaxShowMessage(data.message);
                    $curr_row.hide('medium').remove();
                    PMA_adjustTotals();
                    PMA_reloadNavigation();
                    PMA_ajaxRemoveMessage($msg);
                } else {
                    PMA_ajaxShowMessage(messages.strErrorProcessingRequest + ' : ' + data.error, false);
                }
            }); // end $.post()
        }, loadForeignKeyCheckbox); // end $.PMA_confirm()
    }); // end of Drop Table Ajax action

    /**
     * Attach Event Handler for 'Print' link
     */
    $(document).on('click', '#printView', function (event) {
        event.preventDefault();

        // Take to preview mode
        printPreview();
    }); // end of Print View action

    // Calculate Real End for InnoDB
    /**
     * Ajax Event handler for calculating the real end for a InnoDB table
     *
     */
    $(document).on('click', '#real_end_input', function (event) {
        event.preventDefault();

        /**
         * @var question    String containing the question to be asked for confirmation
         */
        var question = messages.strOperationTakesLongTime;

        $(this).PMA_confirm(question, '', function () {
            return true;
        });
        return false;
    }); // end Calculate Real End for InnoDB

    // Add tooltip to favorite icons.
    $('.favorite_table_anchor').each(function () {
        PMA_tooltip(
            $(this),
            'a',
            $(this).attr('title')
        );
    });

    // Get real row count via Ajax.
    $('a.real_row_count').on('click', function (event) {
        event.preventDefault();
        PMA_fetchRealRowCount($(this));
    });
    // Get all real row count.
    $('a.row_count_sum').on('click', function (event) {
        event.preventDefault();
        PMA_fetchRealRowCount($(this));
    });
} // end $()
