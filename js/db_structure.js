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
    $("span.fkc_switch").unbind('click');
    $('#fkc_checkbox').unbind('change');
    $("a.truncate_table_anchor.ajax").die('click');
    $("a.drop_table_anchor.ajax").die('click');
    $('a.drop_tracking_anchor.ajax').die('click');
    $('#real_end_input').die('click');
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
    var $allTr = $("#tablesForm table.data tbody:first tr");
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
            // The approximated value contains a preceding ~ and a following 2 (Eg 100 --> ~1002)
            strRows = strRows.substring(1, strRows.length - 1);
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
    // Add some commas for readablility:
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
    $summary.find('.tbl_num').text($.sprintf(PMA_messages.strTables, tableSum));
    $summary.find('.tbl_rows').text(strRowSum);
    $summary.find('.tbl_size').text(sizeSum + " " + byteUnits[size_magnitude]);
    $summary.find('.tbl_overhead').text(overheadSum + " " + byteUnits[overhead_magnitude]);
}

AJAX.registerOnload('db_structure.js', function () {
    /**
     * Handler for the print view multisubmit.
     * All other multi submits can be handled via ajax, but this one needs
     * special treatment as the results need to open in another browser window
     */
    $('#tablesForm').submit(function (event) {
        var $form = $(this);
        if ($form.find('select[name=submit_mult]').val() === 'print') {
            event.preventDefault();
            event.stopPropagation();
            $('form#clone').remove();
            var $clone = $form
                .clone()
                .hide()
                .appendTo('body');
            $clone
                .find('select[name=submit_mult]')
                .val('print');
            $clone
                .attr('target', 'printview')
                .attr('id', 'clone')
                .submit();
        }
    });

     /**
     * Event handler for 'Foreign Key Checks' disabling option
     * in the drop table confirmation form
     */
    $("span.fkc_switch").click(function (event) {
        if ($("#fkc_checkbox").prop('checked')) {
            $("#fkc_checkbox").prop('checked', false);
            $("#fkc_status").html(PMA_messages.strForeignKeyCheckDisabled);
            return;
        }
        $("#fkc_checkbox").prop('checked', true);
        $("#fkc_status").html(PMA_messages.strForeignKeyCheckEnabled);
    });

    $('#fkc_checkbox').change(function () {
        if ($(this).prop("checked")) {
            $("#fkc_status").html(PMA_messages.strForeignKeyCheckEnabled);
            return;
        }
        $("#fkc_status").html(PMA_messages.strForeignKeyCheckDisabled);
    }); // End of event handler for 'Foreign Key Check'

    /**
     * Ajax Event handler for 'Truncate Table'
     */
    $("a.truncate_table_anchor.ajax").live('click', function (event) {
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
            $.sprintf(PMA_messages.strDoYouReally, 'TRUNCATE ' + escapeHtml(curr_table_name));

        $this_anchor.PMA_confirm(question, $this_anchor.attr('href'), function (url) {

            PMA_ajaxShowMessage(PMA_messages.strProcessingRequest);

            $.get(url, {'is_js_confirmed' : 1, 'ajax_request' : true}, function (data) {
                if (data.success === true) {
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
        }); //end $.PMA_confirm()
    }); //end of Truncate Table Ajax action

    /**
     * Ajax Event handler for 'Drop Table' or 'Drop View'
     */
    $("a.drop_table_anchor.ajax").live('click', function (event) {
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
                $.sprintf(PMA_messages.strDoYouReally, 'DROP TABLE ' + escapeHtml(curr_table_name));
        } else {
            question =
                $.sprintf(PMA_messages.strDoYouReally, 'DROP VIEW ' + escapeHtml(curr_table_name));
        }

        $this_anchor.PMA_confirm(question, $this_anchor.attr('href'), function (url) {

            var $msg = PMA_ajaxShowMessage(PMA_messages.strProcessingRequest);

            $.get(url, {'is_js_confirmed' : 1, 'ajax_request' : true}, function (data) {
                if (data.success === true) {
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
        }); // end $.PMA_confirm()
    }); //end of Drop Table Ajax action

    /**
     * Ajax Event handler for 'Drop tracking'
     */
    $('a.drop_tracking_anchor.ajax').live('click', function (event) {
        event.preventDefault();

        var $anchor = $(this);

        /**
         * @var curr_tracking_row   Object containing reference to the current tracked table's row
         */
        var $curr_tracking_row = $anchor.parents('tr');
         /**
         * @var question    String containing the question to be asked for confirmation
         */
        var question = PMA_messages.strDeleteTrackingData;

        $anchor.PMA_confirm(question, $anchor.attr('href'), function (url) {

            PMA_ajaxShowMessage(PMA_messages.strDeletingTrackingData);

            $.get(url, {'is_js_confirmed': 1, 'ajax_request': true}, function (data) {
                if (data.success === true) {
                    var $tracked_table = $curr_tracking_row.parents('table');
                    var table_name = $curr_tracking_row.find('td:nth-child(2)').text();

                    // Check how many rows will be left after we remove
                    if ($tracked_table.find('tbody tr').length === 1) {
                        // We are removing the only row it has
                        $('#tracked_tables').hide("slow").remove();
                    } else {
                        // There are more rows left after the deletion
                        toggleRowColors($curr_tracking_row.next());
                        $curr_tracking_row.hide("slow", function () {
                            $(this).remove();
                        });
                    }

                    // Make the removed table visible in the list of 'Untracked tables'.
                    var $untracked_table = $('table#noversions');

                    // This won't work if no untracked tables are there.
                    if ($untracked_table.length > 0) {
                        var $rows = $untracked_table.find('tbody tr');

                        $rows.each(function (index) {
                            var $row = $(this);
                            var tmp_tbl_name = $row.find('td:first-child').text();
                            var is_last_iteration = (index == ($rows.length - 1));

                            if (tmp_tbl_name > table_name || is_last_iteration) {
                                var $cloned = $row.clone();

                                // Change the table name of the cloned row.
                                $cloned.find('td:first-child').text(table_name);

                                // Change the link of the cloned row.
                                var new_url = $cloned
                                    .find('td:nth-child(2) a')
                                    .attr('href')
                                    .replace('table=' + tmp_tbl_name, 'table=' + encodeURIComponent(table_name));
                                $cloned.find('td:nth-child(2) a').attr('href', new_url);

                                // Insert the cloned row in an appropriate location.
                                if (tmp_tbl_name > table_name) {
                                    $cloned.insertBefore($row);
                                    toggleRowColors($row);
                                    return false;
                                } else {
                                    $cloned.insertAfter($row);
                                    toggleRowColors($cloned);
                                }
                            }
                        });
                    }

                    PMA_ajaxShowMessage(data.message);
                } else {
                    PMA_ajaxShowMessage(PMA_messages.strErrorProcessingRequest + " : " + data.error, false);
                }
            }); // end $.get()
        }); // end $.PMA_confirm()
    }); //end Drop Tracking

    //Calculate Real End for InnoDB
    /**
     * Ajax Event handler for calculatig the real end for a InnoDB table
     *
     */
    $('#real_end_input').live('click', function (event) {
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

}); // end $()
