/**
 * Return value of a cell in a table.
 */
export function PMA_getCellValue (td) {
    var $td = $(td);
    if ($td.is('.null')) {
        return '';
    } else if ((! $td.is('.to_be_saved')
        || $td.is('.set'))
        && $td.data('original_data')
    ) {
        return $td.data('original_data');
    } else {
        return $td.text();
    }
}
