import { PMA_Messages as PMA_messages } from '../../variables/export_variables';
import { PMA_sprintf } from '../../utils/sprintf';
import { PMA_ajaxShowMessage } from '../../utils/show_ajax_messages';
/**
 * Adjust number of rows and total size in the summary
 * when truncating, creating, dropping or inserting into a table
 */
export function PMA_adjustTotals () {
    var byteUnits = [
        PMA_messages.strB,
        PMA_messages.strKiB,
        PMA_messages.strMiB,
        PMA_messages.strGiB,
        PMA_messages.strTiB,
        PMA_messages.strPiB,
        PMA_messages.strEiB
    ];
    /**
     * @var $allTr jQuery object that references all the rows in the list of tables
     */
    var $allTr = $('#tablesForm').find('table.data tbody:first tr');
    // New summary values for the table
    var tableSum = $allTr.size();
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
    var size_magnitude = 0;
    var overhead_magnitude = 0;
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
    var $summary = $('#tbl_summary_row');
    $summary.find('.tbl_num').text(PMA_sprintf(PMA_messages.strNTables, tableSum));
    if (rowSumApproximated) {
        $summary.find('.row_count_sum').text(strRowSum);
    } else {
        $summary.find('.tbl_rows').text(strRowSum);
    }
    $summary.find('.tbl_size').text(sizeSum + ' ' + byteUnits[size_magnitude]);
    $summary.find('.tbl_overhead').text(overheadSum + ' ' + byteUnits[overhead_magnitude]);
}

/**
 * Gets the real row count for a table or DB.
 * @param object $target Target for appending the real count value.
 */
export function PMA_fetchRealRowCount ($target) {
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
