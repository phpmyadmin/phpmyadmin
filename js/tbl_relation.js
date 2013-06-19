/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * for tbl_relation.php
 *
 */
function show_hide_clauses($thisDropdown)
{
    if ($thisDropdown.val() == '') {
        $thisDropdown.parent().nextAll('span').hide();
    } else {
        if ($thisDropdown.is('select[name^="destination_foreign_column"]')) {
            $thisDropdown.parent().nextAll('span').show();
        }
    }
}

/**
 * Retrieves and populates dropdowns to the left based on the selected value
 *
 * @param $dropdown the dropdown whose value got changed
 */
function getDropdownValues($dropdown) {
    var foreignDb = null, foreignTable = null;
    var $tableDd, $columnDd;
    var foreign = '';
    if ($dropdown.is('select[name^="destination_foreign"]')) {
        $tableDd  = $dropdown.parent().find('select[name^="destination_foreign_table"]');
        $columnDd = $dropdown.parent().find('select[name^="destination_foreign_column"]');
        foreign = '_foreign';
    } else {
        $tableDd  = $dropdown.parent().find('select[name^="destination_table"]');
        $columnDd = $dropdown.parent().find('select[name^="destination_column"]');
    }

    if ($dropdown.is('select[name^="destination' + foreign + '_db"]')) {
        foreignDb = $dropdown.val();
        if (foreignDb == '') {
            setDropdownValues($tableDd, []);
            setDropdownValues($columnDd, []);
            return;
        }
    } else {
        foreignDb = $dropdown.parent().find('select[name^="destination' + foreign + '_db"]').val();
        foreignTable = $dropdown.val();
        if (foreignTable == '') {
            setDropdownValues($columnDd, []);
            return;
        }
    }
    var $msgbox = PMA_ajaxShowMessage();
    var $form = $dropdown.parents('form');
    var url = 'tbl_relation.php?getDropdownValues=true&ajax_request=true'
        + '&token=' + $form.find('input[name="token"]').val()
        + '&db=' + $form.find('input[name="db"]').val()
        + '&table=' + $form.find('input[name="table"]').val()
        + '&foreign=' + (foreign != '')
        + '&foreignDb=' + encodeURIComponent(foreignDb)
        + (foreignTable !== null ? '&foreignTable=' + encodeURIComponent(foreignTable) : '');
    $.ajax({
        url: url,
        datatype: 'json',
        success: function(data) {
            PMA_ajaxRemoveMessage($msgbox);
            if (data.success) {
                if (foreignTable == null) {
                    setDropdownValues($tableDd, data.tables);
                    setDropdownValues($columnDd, []);
                } else {
                    setDropdownValues($columnDd, data.columns);
                }
            } else {
                PMA_ajaxShowMessage(data.error, false);
            }
        }
    });
}

function setDropdownValues($dropdown, values) {
    $dropdown.empty();
    var optionsAsString = '';
    values.unshift('');
    $.each(values, function() {
        optionsAsString += "<option value='" + this + "'>" + this + "</option>";
    })
    $dropdown.append($(optionsAsString));
}

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('tbl_relation.js', function () {
    $('select[name^="destination_foreign"]').unbind('change');
    $('select[name^="destination_db"],'
            + ' select[name^="destination_table"],'
            + ' select[name^="destination_foreign_db"],'
            + ' select[name^="destination_foreign_table"]').unbind('change');
});

AJAX.registerOnload('tbl_relation.js', function () {
    // initial display
    $('select[name^="destination_foreign_column"]').each(function (index, one_dropdown) {
        show_hide_clauses($(one_dropdown));
    });
    // change
    $('select[name^="destination_foreign"]').change(function () {
        show_hide_clauses($(this));
    });

    $('select[name^="destination_db"],'
            + ' select[name^="destination_table"],'
            + ' select[name^="destination_foreign_db"],'
            + ' select[name^="destination_foreign_table"]')
        .change(function() {
            getDropdownValues($(this));
        });
});
