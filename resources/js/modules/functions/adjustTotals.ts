import $ from 'jquery';

/**
 * Adjust number of rows and total size in the summary
 * when truncating, creating, dropping or inserting into a table
 */
export default function adjustTotals () {
    const byteUnits = [
        window.Messages.strB,
        window.Messages.strKiB,
        window.Messages.strMiB,
        window.Messages.strGiB,
        window.Messages.strTiB,
        window.Messages.strPiB,
        window.Messages.strEiB,
    ];
    /**
     * @var $allTr jQuery object that references all the rows in the list of tables
     */
    const $allTr = $('#tablesForm').find('table.data tbody').first().find('tr');
    // New summary values for the table
    const tableSum = $allTr.length;
    let rowsSum = 0;
    let sizeSum = 0;
    let overheadSum = 0;
    let rowSumApproximated = false;

    $allTr.each(function () {
        const $this = $(this);
        let i;
        let tmpVal;
        // Get the number of rows for this SQL table
        let strRows = $this.find('.tbl_rows').text();
        // If the value is approximated
        if (strRows.indexOf('~') === 0) {
            rowSumApproximated = true;
            // The approximated value contains a preceding ~ (Eg 100 --> ~100)
            strRows = strRows.substring(1, strRows.length);
        }

        strRows = strRows.replace(/[,.\s]/g, '');
        const intRow = parseInt(strRows, 10);
        if (!isNaN(intRow)) {
            rowsSum += intRow;
        }

        // Extract the size and overhead
        let valSize = 0;
        let valOverhead = 0;
        const strSize = $this.find('.tbl_size span:not(.unit)').text().trim();
        const strSizeUnit = $this.find('.tbl_size span.unit').text().trim();
        const strOverhead = $this.find('.tbl_overhead span:not(.unit)').text().trim();
        const strOverheadUnit = $this.find('.tbl_overhead span.unit').text().trim();
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
    let strRowSum = rowsSum + '';
    const regex = /(\d+)(\d{3})/;
    while (regex.test(strRowSum)) {
        strRowSum = strRowSum.replace(regex, '$1' + ',' + '$2');
    }

    // If approximated total value add ~ in front
    if (rowSumApproximated) {
        strRowSum = '~' + strRowSum;
    }

    // Calculate the magnitude for the size and overhead values
    let sizeMagnitude = 0;
    let overheadMagnitude = 0;
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
    const $summary = $('#tbl_summary_row');
    $summary.find('.tbl_num').text(window.sprintf(window.Messages.strNTables, tableSum));
    if (rowSumApproximated) {
        $summary.find('.row_count_sum').text(strRowSum);
    } else {
        $summary.find('.tbl_rows').text(strRowSum);
    }

    $summary.find('.tbl_size').text(sizeSum + ' ' + byteUnits[sizeMagnitude]);
    $summary.find('.tbl_overhead').text(overheadSum + ' ' + byteUnits[overheadMagnitude]);
}
