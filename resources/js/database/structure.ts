import $ from 'jquery';
import * as bootstrap from 'bootstrap';
import { AJAX } from '../modules/ajax.ts';
import { getForeignKeyCheckboxLoader, loadForeignKeyCheckbox } from '../modules/functions.ts';
import { Navigation } from '../modules/navigation.ts';
import { CommonParams } from '../modules/common.ts';
import { ajaxRemoveMessage, ajaxShowMessage } from '../modules/ajax-message.ts';
import getJsConfirmCommonParam from '../modules/functions/getJsConfirmCommonParam.ts';
import { escapeHtml, escapeJsString } from '../modules/functions/escape.ts';
import adjustTotals from '../modules/functions/adjustTotals.ts';

/**
 * @fileoverview    functions used on the database structure page
 * @name            Database Structure
 *
 * @requires    jQueryUI
 */

/**
 * AJAX scripts for /database/structure
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
AJAX.registerTeardown('database/structure.js', function () {
    $(document).off('click', 'a.truncate_table_anchor.ajax');
    $(document).off('click', 'a.drop_table_anchor.ajax');
    $(document).off('click', 'a.favorite_table_anchor.ajax');
    $('a.real_row_count').off('click');
    $('a.row_count_sum').off('click');
    $('select[name=submit_mult]').off('change');
});

/**
 * Gets the real row count for a table or DB.
 * @param {object} $target Target for appending the real count value.
 */
function fetchRealRowCount ($target) {
    var $throbber = $('#pma_navigation').find('.throbber')
        .first()
        .clone()
        .css({ visibility: 'visible', display: 'inline-block' })
        .on('click', false);
    $target.html($throbber);
    $.ajax({
        type: 'GET',
        url: $target.attr('href'),
        cache: false,
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                // If to update all row counts for a DB.
                if (response.real_row_count_all) {
                    $.each(response.real_row_count_all,
                        function (index, table) {
                            // Update each table row count.
                            $('table.data td[data-table*="' + escapeJsString(table.table) + '"]')
                                .text(table.row_count);
                        },
                    );
                }

                // If to update a particular table's row count.
                if (response.real_row_count) {
                    // Append the parent cell with real row count.
                    $target.parent().text(response.real_row_count);
                }

                // Adjust the 'Sum' displayed at the bottom.
                adjustTotals();
            } else {
                ajaxShowMessage(window.Messages.strErrorRealRowCount);
            }
        },
        error: function () {
            ajaxShowMessage(window.Messages.strErrorRealRowCount);
        },
    });
}

function addTooltipToFavoriteIcons (): void {
    document.querySelectorAll('.favorite_table_anchor').forEach((favoriteTableAnchor: HTMLElement): void => {
        new bootstrap.Tooltip(favoriteTableAnchor);
    });
}

AJAX.registerOnload('database/structure.js', function () {
    /**
     * Event handler on select of "Make consistent with central list"
     */
    $('select[name=submit_mult]').on('change', function (event) {
        var url = 'index.php?route=/database/structure';
        var action = $(this).val();

        if (action === 'make_consistent_with_central_list') {
            event.preventDefault();
            event.stopPropagation();

            $('#makeConsistentWithCentralListModal').modal('show').on('shown.bs.modal', function () {
                $('#makeConsistentWithCentralListContinue').on('click', function () {
                    const $form = $('#tablesForm');
                    const argSep = CommonParams.get('arg_separator');
                    const data = $form.serialize() + argSep + 'ajax_request=true' + argSep + 'ajax_page_request=true';

                    ajaxShowMessage();
                    AJAX.source = $form;

                    $.post(
                        'index.php?route=/database/structure/central-columns/make-consistent',
                        data,
                        AJAX.responseHandler
                    );

                    $('#makeConsistentWithCentralListModal').modal('hide');
                });
            });

            return;
        }

        if (action === 'copy_tbl' ||
            action === 'add_prefix_tbl' ||
            action === 'replace_prefix_tbl' ||
            action === 'copy_tbl_change_prefix'
        ) {
            event.preventDefault();
            event.stopPropagation();
            if ($('input[name="selected_tbl[]"]:checked').length === 0) {
                return false;
            }

            var formData = $('#tablesForm').serialize();
            var modalTitle = '';
            if (action === 'copy_tbl') {
                url = 'index.php?route=/database/structure/copy-form';
                modalTitle = window.Messages.strCopyTablesTo;
            } else if (action === 'add_prefix_tbl') {
                url = 'index.php?route=/database/structure/add-prefix';
                modalTitle = window.Messages.strAddPrefix;
            } else if (action === 'replace_prefix_tbl') {
                url = 'index.php?route=/database/structure/change-prefix-form';
                modalTitle = window.Messages.strReplacePrefix;
            } else if (action === 'copy_tbl_change_prefix') {
                url = 'index.php?route=/database/structure/change-prefix-form';
                modalTitle = window.Messages.strCopyPrefix;
            }

            $.ajax({
                type: 'POST',
                url: url,
                dataType: 'html',
                data: formData
            }).done(function (modalBody) {
                const bulkActionModal = $('#bulkActionModal');
                bulkActionModal.on('show.bs.modal', function () {
                    (this.querySelector('.modal-title') as HTMLHeadingElement).innerText = modalTitle;
                    this.querySelector('.modal-body').innerHTML = modalBody;
                });

                bulkActionModal.modal('show').on('shown.bs.modal', function () {
                    $('#bulkActionContinue').on('click', function () {
                        $('#ajax_form').trigger('submit');
                        $('#bulkActionModal').modal('hide');
                    });
                });
            });

            return;
        }

        if (action === 'analyze_tbl') {
            url = 'index.php?route=/table/maintenance/analyze';
        } else if (action === 'sync_unique_columns_central_list') {
            url = 'index.php?route=/database/structure/central-columns/add';
        } else if (action === 'delete_unique_columns_central_list') {
            url = 'index.php?route=/database/structure/central-columns/remove';
        } else if (action === 'check_tbl') {
            url = 'index.php?route=/table/maintenance/check';
        } else if (action === 'checksum_tbl') {
            url = 'index.php?route=/table/maintenance/checksum';
        } else if (action === 'drop_tbl') {
            url = 'index.php?route=/database/structure/drop-form';
        } else if (action === 'empty_tbl') {
            url = 'index.php?route=/database/structure/empty-form';
        } else if (action === 'export') {
            url = 'index.php?route=/export/tables';
        } else if (action === 'optimize_tbl') {
            url = 'index.php?route=/table/maintenance/optimize';
        } else if (action === 'repair_tbl') {
            url = 'index.php?route=/table/maintenance/repair';
        } else if (action === 'show_create') {
            url = 'index.php?route=/database/structure/show-create';
        } else {
            $('#tablesForm').trigger('submit');

            return;
        }

        var $form = $(this).parents('form');
        var argsep = CommonParams.get('arg_separator');
        var data = $form.serialize() + argsep + 'ajax_request=true' + argsep + 'ajax_page_request=true';

        ajaxShowMessage();
        AJAX.source = $form;

        $.post(url, data, AJAX.responseHandler);
    });

    /**
     * Ajax Event handler for 'Truncate Table'
     */
    $(document).on('click', 'a.truncate_table_anchor.ajax', function (event) {
        event.preventDefault();

        /**
         * @var $this_anchor Object  referring to the anchor clicked
         */
        var $thisAnchor = $(this);

        // extract current table name and build the question string
        /**
         * @var curr_table_name String containing the name of the table to be truncated
         */
        var currTableName = $thisAnchor.parents('tr').children('th').children('a').text();
        /**
         * @var question    String containing the question to be asked for confirmation
         */
        var question = window.Messages.strTruncateTableStrongWarning + ' ' +
            window.sprintf(window.Messages.strDoYouReally, 'TRUNCATE `' + escapeHtml(currTableName) + '`') +
            getForeignKeyCheckboxLoader();

        $thisAnchor.confirm(question, $thisAnchor.attr('href'), function (url) {
            ajaxShowMessage(window.Messages.strProcessingRequest);

            var params = getJsConfirmCommonParam(this, $thisAnchor.getPostData());

            $.post(url, params, function (data) {
                if (typeof data !== 'undefined' && data.success === true) {
                    ajaxShowMessage(data.message);
                    // Adjust table statistics
                    var $tr = $thisAnchor.closest('tr');
                    $tr.find('.tbl_rows').text('0');
                    $tr.find('.tbl_size, .tbl_overhead').text('-');
                    adjustTotals();
                } else {
                    ajaxShowMessage(window.Messages.strErrorProcessingRequest + ' : ' + data.error, false);
                }
            }); // end $.post()
        }, loadForeignKeyCheckbox);
    }); // end of Truncate Table Ajax action

    /**
     * Ajax Event handler for 'Drop Table' or 'Drop View'
     */
    $(document).on('click', 'a.drop_table_anchor.ajax', function (event) {
        event.preventDefault();

        var $thisAnchor = $(this);

        // extract current table name and build the question string
        /**
         * @var $curr_row    Object containing reference to the current row
         */
        var $currRow = $thisAnchor.parents('tr');
        /**
         * @var curr_table_name String containing the name of the table to be truncated
         */
        var currTableName = $currRow.children('th').children('a').text();
        /**
         * @var is_view Boolean telling if we have a view
         */
        var isView = $currRow.hasClass('is_view') || $thisAnchor.hasClass('view');
        /**
         * @var question    String containing the question to be asked for confirmation
         */
        var question;
        if (! isView) {
            question = window.Messages.strDropTableStrongWarning + ' ' +
                window.sprintf(window.Messages.strDoYouReally, 'DROP TABLE `' + escapeHtml(currTableName) + '`');
        } else {
            question =
                window.sprintf(window.Messages.strDoYouReally, 'DROP VIEW `' + escapeHtml(currTableName) + '`');
        }

        question += getForeignKeyCheckboxLoader();

        $thisAnchor.confirm(question, $thisAnchor.attr('href'), function (url) {
            var $msg = ajaxShowMessage(window.Messages.strProcessingRequest);

            var params = getJsConfirmCommonParam(this, $thisAnchor.getPostData());

            $.post(url, params, function (data) {
                if (typeof data !== 'undefined' && data.success === true) {
                    ajaxShowMessage(data.message);
                    $currRow.hide('medium').remove();
                    adjustTotals();
                    Navigation.reload();
                    ajaxRemoveMessage($msg);
                } else {
                    ajaxShowMessage(window.Messages.strErrorProcessingRequest + ' : ' + data.error, false);
                }
            }); // end $.post()
        }, loadForeignKeyCheckbox);
    }); // end of Drop Table Ajax action

    addTooltipToFavoriteIcons();

    // Get real row count via Ajax.
    $('a.real_row_count').on('click', function (event) {
        event.preventDefault();
        fetchRealRowCount($(this));
    });

    // Get all real row count.
    $('a.row_count_sum').on('click', function (event) {
        event.preventDefault();
        fetchRealRowCount($(this));
    });
});
