/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * for tbl_relation.php
 *
 */
function show_hide_clauses($thisDropdown)
{
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
function setDropdownValues($dropdown, values) {
    $dropdown.empty();
    var optionsAsString = '';
    // add an empty string to the beginning for empty selection
    values.unshift('');
    $.each(values, function () {
        optionsAsString += "<option value='" + this + "'>" + this + "</option>";
    });
    $dropdown.append($(optionsAsString));
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
    // if the changed dropdown is for foreign key constraints
    if ($dropdown.is('select[name^="destination_foreign"]')) {
        $tableDd  = $dropdown.parent().parent().parent().find('select[name^="destination_foreign_table"]');
        $columnDd = $dropdown.parent().parent().parent().find('select[name^="destination_foreign_column"]');
        foreign = '_foreign';
    } else { // internal relations
        $tableDd  = $dropdown.parent().find('select[name^="destination_table"]');
        $columnDd = $dropdown.parent().find('select[name^="destination_column"]');
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
        foreignDb = $dropdown.parent().parent().parent()
            .find('select[name^="destination' + foreign + '_db"]').val();
        foreignTable = $dropdown.val();
         // if no table is selected empty the column dropdown
        if (foreignTable === '') {
            setDropdownValues($columnDd, []);
            return;
        }
    }
    var $msgbox = PMA_ajaxShowMessage();
    var $form = $dropdown.parents('form');
    var url = 'tbl_relation.php?getDropdownValues=true&ajax_request=true' +
        '&token=' + $form.find('input[name="token"]').val() +
        '&db=' + $form.find('input[name="db"]').val() +
        '&table=' + $form.find('input[name="table"]').val() +
        '&foreign=' + (foreign !== '') +
        '&foreignDb=' + encodeURIComponent(foreignDb) +
        (foreignTable !== null ?
            '&foreignTable=' + encodeURIComponent(foreignTable) : ''
        );
    var $server = $form.find('input[name="server"]');
    if ($server.length > 0) {
        url += '&server=' + $form.find('input[name="server"]').val();
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
                    setDropdownValues($columnDd, data.columns);
                }
            } else {
                PMA_ajaxShowMessage(data.error, false);
            }
        }
    });
}

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('tbl_relation.js', function () {
    $('body').off('change',
        'select[name^="destination_db"], ' +
        'select[name^="destination_table"], ' +
        'select[name^="destination_foreign_db"], ' +
        'select[name^="destination_foreign_table"]'
    );
    $('body').off('click', 'a.add_foreign_key_field');
    $('body').off('click', 'a.add_foreign_key');
    $('a.drop_foreign_key_anchor.ajax').off('click');
});

AJAX.registerOnload('tbl_relation.js', function () {

    /**
     * Ajax event handler to fetch table/column dropdown values.
     */
    $('body').on('change',
        'select[name^="destination_db"], ' +
        'select[name^="destination_table"], ' +
        'select[name^="destination_foreign_db"], ' +
        'select[name^="destination_foreign_table"]',
        function () {
            getDropdownValues($(this));
        }
    );

    /**
     * Ajax event handler to add a column to a foreign key constraint.
     */
    $('body').on('click', 'a.add_foreign_key_field', function (event) {
        event.preventDefault();
        event.stopPropagation();

        // Add field.
        $(this)
        .prev('span')
        .clone(true, true)
        .insertBefore($(this))
        .find('select')
        .val('');

        // Add foreign field.
        var $source_elem = $('select[name^="destination_foreign_column[' +
            $(this).attr('data-index') + ']"]:last').parent();
        $source_elem
        .clone(true, true)
        .insertAfter($source_elem)
        .find('select')
        .val('');
    });

    /**
     * Ajax event handler to add a foreign key constraint.
     */
    $('body').on('click', 'a.add_foreign_key', function (event) {
        event.preventDefault();
        event.stopPropagation();

        var $prev_row = $(this).closest('tr').prev('tr');
        var odd_even = ($prev_row.attr('class') == 'odd') ? 'even' : 'odd';
        var $new_row = $prev_row.clone(true, true).attr('class', odd_even);

        // Update serial number.
        var curr_index = $new_row
            .find('a.add_foreign_key_field')
            .attr('data-index');
        var new_index = parseInt(curr_index) + 1;
        $new_row.find('a.add_foreign_key_field').attr('data-index', new_index);

        // Update form parameter names.
        $new_row.find('select[name^="foreign_key_fields_name"]:not(:first), ' +
            'select[name^="destination_foreign_column"]:not(:first)'
        ).each(function () {
            $(this).parent().remove();
        });
        $new_row.find('input, select').each(function () {
            $(this).attr('name',
                $(this).attr('name').replace(/\d/, new_index)
            );
        });

        // Finally add the row.
        $new_row.insertAfter($prev_row);
    });

    /**
     * Ajax Event handler for 'Drop Foreign key'
     */
    $('a.drop_foreign_key_anchor.ajax').on('click', function (event) {
        event.preventDefault();
        var $anchor = $(this);

        // Object containing reference to the current field's row
        var $curr_row = $anchor.parents('tr');

        var drop_query = escapeHtml(
            $curr_row.children('td')
                .children('.drop_foreign_key_msg')
                .val()
        );

        var question = PMA_sprintf(PMA_messages.strDoYouReally, drop_query);

        $anchor.PMA_confirm(question, $anchor.attr('href'), function (url) {
            var $msg = PMA_ajaxShowMessage(PMA_messages.strDroppingForeignKey, false);
            $.get(url, {'is_js_confirmed': 1, 'ajax_request': true}, function (data) {
                if (data.success === true) {
                    PMA_ajaxRemoveMessage($msg);
                    PMA_commonActions.refreshMain(false, function () {
                        // Do nothing
                    });
                } else {
                    PMA_ajaxShowMessage(PMA_messages.strErrorProcessingRequest + " : " + data.error, false);
                }
            }); // end $.get()
        }); // end $.PMA_confirm()
    }); //end Drop Foreign key
});
