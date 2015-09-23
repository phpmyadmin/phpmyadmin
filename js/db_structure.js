/* vim: set expandtab sw=4 ts=4 sts=4: */
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
AJAX.registerTeardown('db_structure.js', function () {
    $(document).off('click', "a.truncate_table_anchor.ajax");
    $(document).off('click', "a.drop_table_anchor.ajax");
    $(document).off('click', '#real_end_input');
    $(document).off('click', "a.favorite_table_anchor.ajax");
    $(document).off('click', '#printView');
    $('a.real_row_count').off('click');
    $('a.row_count_sum').off('click');
    $('select[name=submit_mult]').unbind('change');
});

/**
 * Adjust number of rows and total size in the summary
 * when truncating, creating, dropping or inserting into a table
 */
function PMA_adjustTotals() {
    var byteUnits = new Array(
        PMA_messages.strB,
        PMA_messages.strKiB,
        PMA_messages.strMiB,
        PMA_messages.strGiB,
        PMA_messages.strTiB,
        PMA_messages.strPiB,
        PMA_messages.strEiB
    );
    /**
     * @var $allTr jQuery object that references all the rows in the list of tables
     */
    var $allTr = $("#tablesForm").find("table.data tbody:first tr");
    // New summary values for the table
    var tableSum = $allTr.size();
    var rowsSum = 0;
    var sizeSum = 0;
    var overheadSum = 0;
    var rowSumApproximated = false;

    $allTr.each(function () {
        var $this = $(this);
        var i, tmpVal;
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
            if (strSizeUnit == byteUnits[i]) {
                tmpVal = parseFloat(strSize);
                valSize = tmpVal * Math.pow(1024, i);
                break;
            }
        }
        for (i = 0; i < byteUnits.length; i++) {
            if (strOverheadUnit == byteUnits[i]) {
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
    var strRowSum = rowsSum + "";
    var regex = /(\d+)(\d{3})/;
    while (regex.test(strRowSum)) {
        strRowSum = strRowSum.replace(regex, '$1' + ',' + '$2');
    }
    // If approximated total value add ~ in front
    if (rowSumApproximated) {
        strRowSum = "~" + strRowSum;
    }
    // Calculate the magnitude for the size and overhead values
    var size_magnitude = 0, overhead_magnitude = 0;
    while (sizeSum >= 1024) {
        sizeSum /= 1024;
        size_magnitude++;
    }
    while (overheadSum >= 1024) {
        overheadSum /= 1024;
        overhead_magnitude++;
    }

    sizeSum = Math.round(sizeSum * 10) / 10;
    overheadSum = Math.round(overheadSum * 10) / 10;

    // Update summary with new data
    var $summary = $("#tbl_summary_row");
    $summary.find('.tbl_num').text(PMA_sprintf(PMA_messages.strNTables, tableSum));
    if (rowSumApproximated) {
        $summary.find('.row_count_sum').text(strRowSum);
    } else {
        $summary.find('.tbl_rows').text(strRowSum);
    }
    $summary.find('.tbl_size').text(sizeSum + " " + byteUnits[size_magnitude]);
    $summary.find('.tbl_overhead').text(overheadSum + " " + byteUnits[overhead_magnitude]);
}

/**
 * Gets the real row count for a table or DB.
 * @param object $target Target for appending the real count value.
 */
function PMA_fetchRealRowCount($target)
{
    var $throbber = $('#pma_navigation').find('.throbber')
        .first()
        .clone()
        .css({visibility: 'visible', display: 'inline-block'})
        .click(false);
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
                PMA_adjustTotals();
            } else {
                PMA_ajaxShowMessage(PMA_messages.strErrorRealRowCount);
            }
        },
        error: function () {
            PMA_ajaxShowMessage(PMA_messages.strErrorRealRowCount);
        }
    });
}

AJAX.registerOnload('db_structure.js', function () {
/**
 * function to open the confirmation dialog for making table consistent with central list
 *
 * @param string   msg     message text to be displayed to user
 * @param function success function to be called on success
 *
 */
    var jqConfirm = function(msg, success) {
        var dialogObj = $("<div style='display:none'>"+msg+"</div>");
        $('body').append(dialogObj);
        var buttonOptions = {};
        buttonOptions[PMA_messages.strContinue] = function () {
            success();
            $( this ).dialog( "close" );
        };
        buttonOptions[PMA_messages.strCancel] = function () {
            $( this ).dialog( "close" );
            $('#tablesForm')[0].reset();
        };
        $(dialogObj).dialog({
            resizable: false,
            modal: true,
            title: PMA_messages.confirmTitle,
            buttons: buttonOptions
        });
    };

/**
 *  Event handler on select of "Make consistent with central list"
 */
    $('select[name=submit_mult]').change(function(event) {
        if($(this).val() === 'make_consistent_with_central_list') {
            event.preventDefault();
            event.stopPropagation();
            jqConfirm(PMA_messages.makeConsistentMessage, function(){
                        $('#tablesForm').submit();
                    });
            return false;
        }
    });

    /**
     * Ajax Event handler for 'Truncate Table'
     */
    $(document).on('click', "a.truncate_table_anchor.ajax", function (event) {
        event.preventDefault();

        /**
         * @var $this_anchor Object  referring to the anchor clicked
         */
        var $this_anchor = $(this);

        //extract current table name and build the question string
        /**
         * @var curr_table_name String containing the name of the table to be truncated
         */
        var curr_table_name = $this_anchor.parents('tr').children('th').children('a').text();
        /**
         * @var question    String containing the question to be asked for confirmation
         */
        var question = PMA_messages.strTruncateTableStrongWarning + ' ' +
            PMA_sprintf(PMA_messages.strDoYouReally, 'TRUNCATE ' + escapeHtml(curr_table_name)) +
            getForeignKeyCheckboxLoader();

        $this_anchor.PMA_confirm(question, $this_anchor.attr('href'), function (url) {

            PMA_ajaxShowMessage(PMA_messages.strProcessingRequest);

            var params = getJSConfirmCommonParam(this);

            $.get(url, params, function (data) {
                if (typeof data !== 'undefined' && data.success === true) {
                    PMA_ajaxShowMessage(data.message);
                    // Adjust table statistics
                    var $tr = $this_anchor.closest('tr');
                    $tr.find('.tbl_rows').text('0');
                    $tr.find('.tbl_size, .tbl_overhead').text('-');
                    //Fetch inner span of this anchor
                    //and replace the icon with its disabled version
                    var span = $this_anchor.html().replace(/b_empty/, 'bd_empty');
                    //To disable further attempts to truncate the table,
                    //replace the a element with its inner span (modified)
                    $this_anchor
                        .replaceWith(span)
                        .removeClass('truncate_table_anchor');
                    PMA_adjustTotals();
                } else {
                    PMA_ajaxShowMessage(PMA_messages.strErrorProcessingRequest + " : " + data.error, false);
                }
            }); // end $.get()
        }, loadForeignKeyCheckbox); //end $.PMA_confirm()
    }); //end of Truncate Table Ajax action

    /**
     * Ajax Event handler for 'Drop Table' or 'Drop View'
     */
    $(document).on('click', "a.drop_table_anchor.ajax", function (event) {
        event.preventDefault();

        var $this_anchor = $(this);

        //extract current table name and build the question string
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
            question = PMA_messages.strDropTableStrongWarning + ' ' +
                PMA_sprintf(PMA_messages.strDoYouReally, 'DROP TABLE ' + escapeHtml(curr_table_name));
        } else {
            question =
                PMA_sprintf(PMA_messages.strDoYouReally, 'DROP VIEW ' + escapeHtml(curr_table_name));
        }
        question += getForeignKeyCheckboxLoader();

        $this_anchor.PMA_confirm(question, $this_anchor.attr('href'), function (url) {

            var $msg = PMA_ajaxShowMessage(PMA_messages.strProcessingRequest);

            var params = getJSConfirmCommonParam(this);

            $.get(url, params, function (data) {
                if (typeof data !== 'undefined' && data.success === true) {
                    PMA_ajaxShowMessage(data.message);
                    toggleRowColors($curr_row.next());
                    $curr_row.hide("medium").remove();
                    PMA_adjustTotals();
                    PMA_reloadNavigation();
                    PMA_ajaxRemoveMessage($msg);
                } else {
                    PMA_ajaxShowMessage(PMA_messages.strErrorProcessingRequest + " : " + data.error, false);
                }
            }); // end $.get()
        }, loadForeignKeyCheckbox); // end $.PMA_confirm()
    }); //end of Drop Table Ajax action

    /**
     * Attach Event Handler for 'Print View'
     */
    $(document).on('click', "#printView", function (event) {
        event.preventDefault();

        // Print the page
        printPage();
    }); //end of Print View action

    //Calculate Real End for InnoDB
    /**
     * Ajax Event handler for calculating the real end for a InnoDB table
     *
     */
    $(document).on('click', '#real_end_input', function (event) {
        event.preventDefault();

        /**
         * @var question    String containing the question to be asked for confirmation
         */
        var question = PMA_messages.strOperationTakesLongTime;

        $(this).PMA_confirm(question, '', function () {
            return true;
        });
        return false;
    }); //end Calculate Real End for InnoDB

    // Add tooltip to favorite icons.
    $(".favorite_table_anchor").each(function () {
        PMA_tooltip(
            $(this),
            'a',
            $(this).attr("title")
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
}); // end $()
