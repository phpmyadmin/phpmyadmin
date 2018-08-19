import { $ } from '../../utils/JqueryExtended';
import { escapeHtml } from '../../utils/Sanitise';
import PMA_commonParams from '../../variables/common_params';
import { PMA_ajaxShowMessage, PMA_ajaxRemoveMessage } from '../../utils/show_ajax_messages';
/**
 * for tbl_relation.php
 *
 */
function show_hide_clauses ($thisDropdown) {
    if ($thisDropdown.val() === '') {
        $thisDropdown.parent().nextAll('span').hide();
    } else {
        if ($thisDropdown.is('select[name^="destination_foreign_column"]')) {
            $thisDropdown.parent().nextAll('span').show();
        }
    }
}

/**
 * Sets dropdown options to values
 */
function setDropdownValues ($dropdown, values, selectedValue) {
    $dropdown.empty();
    var optionsAsString = '';
    // add an empty string to the beginning for empty selection
    values.unshift('');
    $.each(values, function () {
        optionsAsString += '<option value=\'' + escapeHtml(this) + '\'' + (selectedValue === escapeHtml(this) ? ' selected=\'selected\'' : '') + '>' + escapeHtml(this) + '</option>';
    });
    $dropdown.append($(optionsAsString));
}

/**
 * Retrieves and populates dropdowns to the left based on the selected value
 *
 * @param $dropdown the dropdown whose value got changed
 */
export function getDropdownValues ($dropdown) {
    var foreignDb = null;
    var foreignTable = null;
    var $databaseDd;
    var $tableDd;
    var $columnDd;
    var foreign = '';
    // if the changed dropdown is for foreign key constraints
    if ($dropdown.is('select[name^="destination_foreign"]')) {
        $databaseDd = $dropdown.parent().parent().parent().find('select[name^="destination_foreign_db"]');
        $tableDd    = $dropdown.parent().parent().parent().find('select[name^="destination_foreign_table"]');
        $columnDd   = $dropdown.parent().parent().parent().find('select[name^="destination_foreign_column"]');
        foreign = '_foreign';
    } else { // internal relations
        $databaseDd = $dropdown.parent().find('select[name^="destination_db"]');
        $tableDd    = $dropdown.parent().find('select[name^="destination_table"]');
        $columnDd   = $dropdown.parent().find('select[name^="destination_column"]');
    }

    // if the changed dropdown is a database selector
    if ($dropdown.is('select[name^="destination' + foreign + '_db"]')) {
        foreignDb = $dropdown.val();
        // if no database is selected empty table and column dropdowns
        if (foreignDb === '') {
            setDropdownValues($tableDd, []);
            setDropdownValues($columnDd, []);
            return;
        }
    } else { // if a table selector
        foreignDb = $databaseDd.val();
        foreignTable = $dropdown.val();
        // if no table is selected empty the column dropdown
        if (foreignTable === '') {
            setDropdownValues($columnDd, []);
            return;
        }
    }
    var $msgbox = PMA_ajaxShowMessage();
    var $form = $dropdown.parents('form');
    var argsep = PMA_commonParams.get('arg_separator');
    var url = 'tbl_relation.php?getDropdownValues=true' + argsep + 'ajax_request=true' +
        argsep + 'db=' + $form.find('input[name="db"]').val() +
        argsep + 'table=' + $form.find('input[name="table"]').val() +
        argsep + 'foreign=' + (foreign !== '') +
        argsep + 'foreignDb=' + encodeURIComponent(foreignDb) +
        (foreignTable !== null ?
            argsep + 'foreignTable=' + encodeURIComponent(foreignTable) : ''
        );
    var $server = $form.find('input[name="server"]');
    if ($server.length > 0) {
        url += argsep + 'server=' + $form.find('input[name="server"]').val();
    }
    $.ajax({
        url: url,
        datatype: 'json',
        success: function (data) {
            PMA_ajaxRemoveMessage($msgbox);
            if (typeof data !== 'undefined' && data.success) {
                // if the changed dropdown is a database selector
                if (foreignTable === null) {
                    // set values for table and column dropdowns
                    setDropdownValues($tableDd, data.tables);
                    setDropdownValues($columnDd, []);
                } else { // if a table selector
                    // set values for the column dropdown
                    var primary = null;
                    if (typeof data.primary !== 'undefined'
                        && 1 === data.primary.length
                    ) {
                        primary = data.primary[0];
                    }
                    setDropdownValues($columnDd.first(), data.columns, primary);
                    setDropdownValues($columnDd.slice(1), data.columns);
                }
            } else {
                PMA_ajaxShowMessage(data.error, false);
            }
        }
    });
}
