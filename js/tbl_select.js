/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @fileoverview JavaScript functions used on tbl_select.php
 *
 * @requires    jQuery
 * @requires    js/functions.js
 */

/**
 * Ajax event handlers for this page
 *
 * Actions ajaxified here:
 * Table search
 */

/**
 * Checks if given data-type is numeric or date.
 *
 * @param string data_type Column data-type
 *
 * @return bool|string
 */
function PMA_checkIfDataTypeNumericOrDate(data_type)
{
    // To test for numeric data-types.
    var numeric_re = new RegExp(
        'TINYINT|SMALLINT|MEDIUMINT|INT|BIGINT|DECIMAL|FLOAT|DOUBLE|REAL',
        'i'
    );

    // To test for date data-types.
    var date_re = new RegExp(
        'DATETIME|DATE|TIMESTAMP|TIME|YEAR',
        'i'
    );

    // Return matched data-type
    if (numeric_re.test(data_type)) {
        return numeric_re.exec(data_type)[0];
    }

    if (date_re.test(data_type)) {
        return date_re.exec(data_type)[0];
    }

    return false;
}

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('tbl_select.js', function () {
    $('#togglesearchformlink').unbind('click');
    $(document).off('submit', "#tbl_search_form.ajax");
    $('select.geom_func').unbind('change');
    $(document).off('click', 'span.open_search_gis_editor');
    $('body').off('click', 'select[name*="criteriaColumnOperators"]');
});

AJAX.registerOnload('tbl_select.js', function () {
    /**
     * Prepare a div containing a link, otherwise it's incorrectly displayed
     * after a couple of clicks
     */
    $('<div id="togglesearchformdiv"><a id="togglesearchformlink"></a></div>')
        .insertAfter('#tbl_search_form')
        // don't show it until we have results on-screen
        .hide();

    $('#togglesearchformlink')
        .html(PMA_messages.strShowSearchCriteria)
        .bind('click', function () {
            var $link = $(this);
            $('#tbl_search_form').slideToggle();
            if ($link.text() == PMA_messages.strHideSearchCriteria) {
                $link.text(PMA_messages.strShowSearchCriteria);
            } else {
                $link.text(PMA_messages.strHideSearchCriteria);
            }
            // avoid default click action
            return false;
        });

    /**
     * Ajax event handler for Table search
     */
    $(document).on('submit', "#tbl_search_form.ajax", function (event) {
        var unaryFunctions = [
            'IS NULL',
            'IS NOT NULL',
            "= ''",
            "!= ''"
        ];

        var geomUnaryFunctions = [
            'IsEmpty',
            'IsSimple',
            'IsRing',
            'IsClosed',
        ];

        // jQuery object to reuse
        var $search_form = $(this);
        event.preventDefault();

        // empty previous search results while we are waiting for new results
        $("#sqlqueryresultsouter").empty();
        var $msgbox = PMA_ajaxShowMessage(PMA_messages.strSearching, false);

        PMA_prepareForAjaxRequest($search_form);

        var values = {};
        $search_form.find(':input').each(function () {
            var $input = $(this);
            if ($input.attr('type') == 'checkbox' || $input.attr('type') == 'radio') {
                if ($input.is(':checked')) {
                    values[this.name] = $input.val();
                }
            } else {
                values[this.name] = $input.val();
            }
        });
        var columnCount = $('select[name="columnsToDisplay[]"] option').length;
        // Submit values only for the columns that have unary column operator or a search criteria
        for (var a = 0; a < columnCount; a++) {
            if ($.inArray(values['criteriaColumnOperators[' + a + ']'], unaryFunctions) >= 0) {
                continue;
            }

            if (values['geom_func[' + a + ']'] &&
                $.isArray(values['geom_func[' + a + ']'], geomUnaryFunctions) >= 0) {
            	continue;
            }

            if (values['criteriaValues[' + a + ']'] === '' || values['criteriaValues[' + a + ']'] === null) {
                delete values['criteriaValues[' + a + ']'];
                delete values['criteriaColumnOperators[' + a + ']'];
                delete values['criteriaColumnNames[' + a + ']'];
                delete values['criteriaColumnTypes[' + a + ']'];
                delete values['criteriaColumnCollations[' + a + ']'];
            }
        }
        // If all columns are selected, use a single parameter to indicate that
        if (values['columnsToDisplay[]'] !== null) {
            if (values['columnsToDisplay[]'].length == columnCount) {
                delete values['columnsToDisplay[]'];
                values.displayAllColumns = true;
            }
        } else {
            values.displayAllColumns = true;
        }

        $.post($search_form.attr('action'), values, function (data) {
            PMA_ajaxRemoveMessage($msgbox);
            if (typeof data !== 'undefined' && data.success === true) {
                if (typeof data.sql_query !== 'undefined') { // zero rows
                    $("#sqlqueryresultsouter").html(data.sql_query);
                } else { // results found
                    $("#sqlqueryresultsouter").html(data.message);
                    $(".sqlqueryresults").trigger('makegrid').trigger('stickycolumns');
                }
                $('#tbl_search_form')
                    // workaround for bug #3168569 - Issue on toggling the "Hide search criteria" in chrome.
                    .slideToggle()
                    .hide();
                $('#togglesearchformlink')
                    // always start with the Show message
                    .text(PMA_messages.strShowSearchCriteria);
                $('#togglesearchformdiv')
                    // now it's time to show the div containing the link
                    .show();
                // needed for the display options slider in the results
                PMA_init_slider();
                $('html, body').animate({scrollTop: 0}, 'fast');
            } else {
                $("#sqlqueryresultsouter").html(data.error);
            }
            PMA_highlightSQL($('#sqlqueryresultsouter'));
        }); // end $.post()
    });

    // Following section is related to the 'function based search' for geometry data types.
    // Initialy hide all the open_gis_editor spans
    $('span.open_search_gis_editor').hide();

    $('select.geom_func').bind('change', function () {
        var $geomFuncSelector = $(this);

        var binaryFunctions = [
            'Contains',
            'Crosses',
            'Disjoint',
            'Equals',
            'Intersects',
            'Overlaps',
            'Touches',
            'Within',
            'MBRContains',
            'MBRDisjoint',
            'MBREquals',
            'MBRIntersects',
            'MBROverlaps',
            'MBRTouches',
            'MBRWithin',
            'ST_Contains',
            'ST_Crosses',
            'ST_Disjoint',
            'ST_Equals',
            'ST_Intersects',
            'ST_Overlaps',
            'ST_Touches',
            'ST_Within'
        ];

        var tempArray = [
            'Envelope',
            'EndPoint',
            'StartPoint',
            'ExteriorRing',
            'Centroid',
            'PointOnSurface'
        ];
        var outputGeomFunctions = binaryFunctions.concat(tempArray);

        // If the chosen function takes two geometry objects as parameters
        var $operator = $geomFuncSelector.parents('tr').find('td:nth-child(5)').find('select');
        if ($.inArray($geomFuncSelector.val(), binaryFunctions) >= 0) {
            $operator.prop('readonly', true);
        } else {
            $operator.prop('readonly', false);
        }

        // if the chosen function's output is a geometry, enable GIS editor
        var $editorSpan = $geomFuncSelector.parents('tr').find('span.open_search_gis_editor');
        if ($.inArray($geomFuncSelector.val(), outputGeomFunctions) >= 0) {
            $editorSpan.show();
        } else {
            $editorSpan.hide();
        }

    });

    $(document).on('click', 'span.open_search_gis_editor', function (event) {
        event.preventDefault();

        var $span = $(this);
        // Current value
        var value = $span.parent('td').children("input[type='text']").val();
        // Field name
        var field = 'Parameter';
        // Column type
        var geom_func = $span.parents('tr').find('.geom_func').val();
        var type;
        if (geom_func == 'Envelope') {
            type = 'polygon';
        } else if (geom_func == 'ExteriorRing') {
            type = 'linestring';
        } else {
            type = 'point';
        }
        // Names of input field and null checkbox
        var input_name = $span.parent('td').children("input[type='text']").attr('name');
        //Token
        var token = $("input[name='token']").val();

        openGISEditor();
        if (!gisEditorLoaded) {
            loadJSAndGISEditor(value, field, type, input_name, token);
        } else {
            loadGISEditor(value, field, type, input_name, token);
        }
    });

    /**
     * Ajax event handler for Range-Search.
     */
    $('body').on('click', 'select[name*="criteriaColumnOperators"]', function () {
        $source_select = $(this);
        // Get the column name.
        var column_name = $(this)
            .closest('tr')
            .find('th:first')
            .text();

        // Get the data-type of column excluding size.
        var data_type = $(this)
            .closest('tr')
            .find('td[data-type]')
            .attr('data-type');
        data_type = PMA_checkIfDataTypeNumericOrDate(data_type);

        // Get the operator.
        var operator = $(this).val();

        if ((operator == 'BETWEEN' || operator == 'NOT BETWEEN')
            && data_type
        ) {
            var $msgbox = PMA_ajaxShowMessage();
            $.ajax({
                url: 'tbl_select.php',
                type: 'POST',
                data: {
                    token: $('input[name="token"]').val(),
                    ajax_request: 1,
                    db: $('input[name="db"]').val(),
                    table: $('input[name="table"]').val(),
                    column: column_name,
                    range_search: 1
                },
                success: function (response) {
                    PMA_ajaxRemoveMessage($msgbox);
                    if (response.success) {
                        // Get the column min value.
                        var min = response.column_data.min
                            ? '(' + PMA_messages.strColumnMin +
                                ' ' + response.column_data.min + ')'
                            : '';
                        // Get the column max value.
                        var max = response.column_data.max
                            ? '(' + PMA_messages.strColumnMax +
                                ' ' + response.column_data.max + ')'
                            : '';
                        var button_options = {};
                        button_options[PMA_messages.strGo] = function () {
                            var min_value = $('#min_value').val();
                            var max_value = $('#max_value').val();
                            var final_value = '';
                            if (min_value.length && max_value.length) {
                                final_value = min_value + ', ' +
                                    max_value;
                            }
                            var $target_field = $source_select.closest('tr')
                                .find('[name*="criteriaValues"]');

                            // If target field is a select list.
                            if ($target_field.is('select')) {
                                $target_field.val(final_value);
                                var $options = $target_field.find('option');
                                var $closest_min = null;
                                var $closest_max = null;
                                // Find closest min and max value.
                                $options.each(function () {
                                    if (
                                        $closest_min === null
                                        || Math.abs($(this).val() - min_value) < Math.abs($closest_min.val() - min_value)
                                    ) {
                                        $closest_min = $(this);
                                    }

                                    if (
                                        $closest_max === null
                                        || Math.abs($(this).val() - max_value) < Math.abs($closest_max.val() - max_value)
                                    ) {
                                        $closest_max = $(this);
                                    }
                                });

                                $closest_min.attr('selected', 'selected');
                                $closest_max.attr('selected', 'selected');
                            } else {
                                $target_field.val(final_value);
                            }
                            $(this).dialog("close");
                        };
                        button_options[PMA_messages.strCancel] = function () {
                            $(this).dialog("close");
                        };

                        // Display dialog box.
                        $('<div/>').append(
                            '<fieldset>' +
                            '<legend>' + operator + '</legend>' +
                            '<label for="min_value">' + PMA_messages.strMinValue +
                            '</label>' +
                            '<input type="text" id="min_value" />' + '<br>' +
                            '<span class="small_font">' + min + '</span>' + '<br>' +
                            '<label for="max_value">' + PMA_messages.strMaxValue +
                            '</label>' +
                            '<input type="text" id="max_value" />' + '<br>' +
                            '<span class="small_font">' + max + '</span>' +
                            '</fieldset>'
                        ).dialog({
                            minWidth: 500,
                            maxHeight: 400,
                            modal: true,
                            buttons: button_options,
                            title: PMA_messages.strRangeSearch,
                            open: function () {
                                // Add datepicker wherever required.
                                PMA_addDatepicker($('#min_value'), data_type);
                                PMA_addDatepicker($('#max_value'), data_type);
                            },
                            close: function () {
                                $(this).remove();
                            }
                        });
                    } else {
                        PMA_ajaxShowMessage(response.error);
                    }
                },
                error: function (response) {
                    PMA_ajaxShowMessage(PMA_messages.strErrorProcessingRequest);
                }
            });
        }
    });
});
