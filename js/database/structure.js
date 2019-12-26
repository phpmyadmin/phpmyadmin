/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @fileoverview    functions used on the database structure page
 * @name            Database Structure
 *
 * @requires    jQuery
 * @requires    jQueryUI
 * @required    js/functions.js
 */

var DatabaseStructure = {};

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
AJAX.registerTeardown('database/structure.js', function () {
    $(document).off('click', 'a.truncate_table_anchor.ajax');
    $(document).off('click', 'a.drop_table_anchor.ajax');
    $(document).off('click', '#real_end_input');
    $(document).off('click', 'a.favorite_table_anchor.ajax');
    $(document).off('click', '#printView');
    $('a.real_row_count').off('click');
    $('a.row_count_sum').off('click');
    $('select[name=submit_mult]').off('change');
});

/**
 * Adjust number of rows and total size in the summary
 * when truncating, creating, dropping or inserting into a table
 */
DatabaseStructure.adjustTotals = function () {
    var byteUnits = [
        Messages.strB,
        Messages.strKiB,
        Messages.strMiB,
        Messages.strGiB,
        Messages.strTiB,
        Messages.strPiB,
        Messages.strEiB
    ];
    /**
     * @var $allTr jQuery object that references all the rows in the list of tables
     */
    var $allTr = $('#tablesForm').find('table.data tbody:first tr');
    // New summary values for the table
    var tableSum = $allTr.length;
    var rowsSum = 0;
    var sizeSum = 0;
    var overheadSum = 0;
    var rowSumApproximated = false;

    $allTr.each(function () {
        var $this = $(this);
        var i;
        var tmpVal;
        // Get the number of rows for this SQL table
        var strRows = $this.find('.tbl_rows').text();
        // If the value is approximated
        if (strRows.indexOf('~') === 0) {
            rowSumApproximated = true;
            // The approximated value contains a preceding ~ (Eg 100 --> ~100)
            strRows = strRows.substring(1, strRows.length);
        }
        strRows = strRows.replace(/[,.]/g, '');
        var intRow = parseInt(strRows, 10);
        if (! isNaN(intRow)) {
            rowsSum += intRow;
        }
        // Extract the size and overhead
        var valSize         = 0;
        var valOverhead     = 0;
        var strSize         = $.trim($this.find('.tbl_size span:not(.unit)').text());
        var strSizeUnit     = $.trim($this.find('.tbl_size span.unit').text());
        var strOverhead     = $.trim($this.find('.tbl_overhead span:not(.unit)').text());
        var strOverheadUnit = $.trim($this.find('.tbl_overhead span.unit').text());
        // Given a value and a unit, such as 100 and KiB, for the table size
        // and overhead calculate their numeric values in bytes, such as 102400
        for (i = 0; i < byteUnits.length; i++) {
            if (strSizeUnit === byteUnits[i]) {
                tmpVal = parseFloat(strSize);
                valSize = tmpVal * Math.pow(1024, i);
                break;
            }
        }
        for (i = 0; i < byteUnits.length; i++) {
            if (strOverheadUnit === byteUnits[i]) {
                tmpVal = parseFloat(strOverhead);
                valOverhead = tmpVal * Math.pow(1024, i);
                break;
            }
        }
        sizeSum += valSize;
        overheadSum += valOverhead;
    });
    // Add some commas for readability:
    // 1000000 becomes 1,000,000
    var strRowSum = rowsSum + '';
    var regex = /(\d+)(\d{3})/;
    while (regex.test(strRowSum)) {
        strRowSum = strRowSum.replace(regex, '$1' + ',' + '$2');
    }
    // If approximated total value add ~ in front
    if (rowSumApproximated) {
        strRowSum = '~' + strRowSum;
    }
    // Calculate the magnitude for the size and overhead values
    var sizeMagnitude = 0;
    var overheadMagnitude = 0;
    while (sizeSum >= 1024) {
        sizeSum /= 1024;
        sizeMagnitude++;
    }
    while (overheadSum >= 1024) {
        overheadSum /= 1024;
        overheadMagnitude++;
    }

    sizeSum = Math.round(sizeSum * 10) / 10;
    overheadSum = Math.round(overheadSum * 10) / 10;

    // Update summary with new data
    var $summary = $('#tbl_summary_row');
    $summary.find('.tbl_num').text(Functions.sprintf(Messages.strNTables, tableSum));
    if (rowSumApproximated) {
        $summary.find('.row_count_sum').text(strRowSum);
    } else {
        $summary.find('.tbl_rows').text(strRowSum);
    }
    $summary.find('.tbl_size').text(sizeSum + ' ' + byteUnits[sizeMagnitude]);
    $summary.find('.tbl_overhead').text(overheadSum + ' ' + byteUnits[overheadMagnitude]);
};

/**
 * Gets the real row count for a table or DB.
 * @param object $target Target for appending the real count value.
 */
DatabaseStructure.fetchRealRowCount = function ($target) {
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
                    $.each(JSON.parse(response.real_row_count_all),
                        function (index, table) {
                            // Update each table row count.
                            $('table.data td[data-table*="' + table.table + '"]')
                                .text(table.row_count);
                        }
                    );
                }
                // If to update a particular table's row count.
                if (response.real_row_count) {
                    // Append the parent cell with real row count.
                    $target.parent().text(response.real_row_count);
                }
                // Adjust the 'Sum' displayed at the bottom.
                DatabaseStructure.adjustTotals();
            } else {
                Functions.ajaxShowMessage(Messages.strErrorRealRowCount);
            }
        },
        error: function () {
            Functions.ajaxShowMessage(Messages.strErrorRealRowCount);
        }
    });
};

AJAX.registerOnload('database/structure.js', function () {
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
        buttonOptions[Messages.strContinue] = function () {
            success();
            $(this).dialog('close');
        };
        buttonOptions[Messages.strCancel] = function () {
            $(this).dialog('close');
            $('#tablesForm')[0].reset();
        };
        $(dialogObj).dialog({
            resizable: false,
            modal: true,
            title: Messages.confirmTitle,
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
                Messages.makeConsistentMessage, function () {
                    $('#tablesForm').trigger('submit');
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
                modalTitle = Messages.strCopyTablesTo;
            } else if ($(this).val() === 'add_prefix_tbl') {
                modalTitle = Messages.strAddPrefix;
            } else if ($(this).val() === 'replace_prefix_tbl') {
                modalTitle = Messages.strReplacePrefix;
            } else if ($(this).val() === 'copy_tbl_change_prefix') {
                modalTitle = Messages.strCopyPrefix;
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
                buttonOptions[Messages.strContinue] = function () {
                    $('#ajax_form').trigger('submit');
                    $(this).dialog('close');
                };
                buttonOptions[Messages.strCancel] = function () {
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
            $('#tablesForm').trigger('submit');
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
        var $thisAnchor = $(this);

        // extract current table name and build the question string
        /**
         * @var curr_table_name String containing the name of the table to be truncated
         */
        var currTableName = $thisAnchor.parents('tr').children('th').children('a').text();
        /**
         * @var question    String containing the question to be asked for confirmation
         */
        var question = Messages.strTruncateTableStrongWarning + ' ' +
            Functions.sprintf(Messages.strDoYouReally, 'TRUNCATE `' + Functions.escapeHtml(currTableName) + '`') +
            Functions.getForeignKeyCheckboxLoader();

        $thisAnchor.confirm(question, $thisAnchor.attr('href'), function (url) {
            Functions.ajaxShowMessage(Messages.strProcessingRequest);

            var params = Functions.getJsConfirmCommonParam(this, $thisAnchor.getPostData());

            $.post(url, params, function (data) {
                if (typeof data !== 'undefined' && data.success === true) {
                    Functions.ajaxShowMessage(data.message);
                    // Adjust table statistics
                    var $tr = $thisAnchor.closest('tr');
                    $tr.find('.tbl_rows').text('0');
                    $tr.find('.tbl_size, .tbl_overhead').text('-');
                    // Fetch inner span of this anchor
                    // and replace the icon with its disabled version
                    var span = $thisAnchor.html().replace(/b_empty/, 'bd_empty');
                    // To disable further attempts to truncate the table,
                    // replace the a element with its inner span (modified)
                    $thisAnchor
                        .replaceWith(span)
                        .removeClass('truncate_table_anchor');
                    DatabaseStructure.adjustTotals();
                } else {
                    Functions.ajaxShowMessage(Messages.strErrorProcessingRequest + ' : ' + data.error, false);
                }
            }); // end $.post()
        }, Functions.loadForeignKeyCheckbox);
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
            question = Messages.strDropTableStrongWarning + ' ' +
                Functions.sprintf(Messages.strDoYouReally, 'DROP TABLE `' + Functions.escapeHtml(currTableName) + '`');
        } else {
            question =
                Functions.sprintf(Messages.strDoYouReally, 'DROP VIEW `' + Functions.escapeHtml(currTableName) + '`');
        }
        question += Functions.getForeignKeyCheckboxLoader();

        $thisAnchor.confirm(question, $thisAnchor.attr('href'), function (url) {
            var $msg = Functions.ajaxShowMessage(Messages.strProcessingRequest);

            var params = Functions.getJsConfirmCommonParam(this, $thisAnchor.getPostData());

            $.post(url, params, function (data) {
                if (typeof data !== 'undefined' && data.success === true) {
                    Functions.ajaxShowMessage(data.message);
                    $currRow.hide('medium').remove();
                    DatabaseStructure.adjustTotals();
                    Navigation.reload();
                    Functions.ajaxRemoveMessage($msg);
                } else {
                    Functions.ajaxShowMessage(Messages.strErrorProcessingRequest + ' : ' + data.error, false);
                }
            }); // end $.post()
        }, Functions.loadForeignKeyCheckbox);
    }); // end of Drop Table Ajax action

    /**
     * Attach Event Handler for 'Print' link
     */
    $(document).on('click', '#printView', function (event) {
        event.preventDefault();

        // Take to preview mode
        Functions.printPreview();
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
        var question = Messages.strOperationTakesLongTime;

        $(this).confirm(question, '', function () {
            return true;
        });
        return false;
    }); // end Calculate Real End for InnoDB

    // Add tooltip to favorite icons.
    $('.favorite_table_anchor').each(function () {
        Functions.tooltip(
            $(this),
            'a',
            $(this).attr('title')
        );
    });

    // Get real row count via Ajax.
    $('a.real_row_count').on('click', function (event) {
        event.preventDefault();
        DatabaseStructure.fetchRealRowCount($(this));
    });
    // Get all real row count.
    $('a.row_count_sum').on('click', function (event) {
        event.preventDefault();
        DatabaseStructure.fetchRealRowCount($(this));
    });
});
