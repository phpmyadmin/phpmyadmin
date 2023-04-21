import $ from 'jquery';
import { AJAX } from '../modules/ajax.ts';
import { Functions } from '../modules/functions.ts';
import { CommonParams } from '../modules/common.ts';
import highlightSql from '../modules/sql-highlight.ts';
import { ajaxRemoveMessage, ajaxShowMessage } from '../modules/ajax-message.ts';

/**
 * @fileoverview JavaScript functions used on /table/search
 */

/**
 * Checks if given data-type is numeric or date.
 *
 * @param {string} dataType Column data-type
 *
 * @return {boolean | string}
 */
const checkIfDataTypeNumericOrDate = function (dataType) {
    // To test for numeric data-types.
    var numericRegExp = new RegExp(
        'TINYINT|SMALLINT|MEDIUMINT|INT|BIGINT|DECIMAL|FLOAT|DOUBLE|REAL',
        'i'
    );

    // To test for date data-types.
    var dateRegExp = new RegExp(
        'DATETIME|DATE|TIMESTAMP|TIME|YEAR',
        'i'
    );

    // Return matched data-type
    if (numericRegExp.test(dataType)) {
        return numericRegExp.exec(dataType)[0];
    }

    if (dateRegExp.test(dataType)) {
        return dateRegExp.exec(dataType)[0];
    }

    return false;
};

const TableSelect = {
    checkIfDataTypeNumericOrDate: checkIfDataTypeNumericOrDate,
};

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('table/select.js', function () {
    $('#togglesearchformlink').off('click');
    $(document).off('submit', '#tbl_search_form.ajax');
    $('select.geom_func').off('change');
    $(document).off('click', 'span.open_search_gis_editor');
    $('body').off('change', 'select[name*="criteriaColumnOperators"]'); // Fix for bug #13778, changed 'click' to 'change'
});

AJAX.registerOnload('table/select.js', function () {
    /**
     * Prepare a div containing a link, otherwise it's incorrectly displayed
     * after a couple of clicks
     */
    $('<div id="togglesearchformdiv"><a id="togglesearchformlink"></a></div>')
        .insertAfter('#tbl_search_form')
        // don't show it until we have results on-screen
        .hide();

    $('#togglesearchformlink')
        .html(window.Messages.strShowSearchCriteria)
        .on('click', function () {
            var $link = $(this);
            $('#tbl_search_form').slideToggle();
            if ($link.text() === window.Messages.strHideSearchCriteria) {
                $link.text(window.Messages.strShowSearchCriteria);
            } else {
                $link.text(window.Messages.strHideSearchCriteria);
            }

            // avoid default click action
            return false;
        });

    var tableRows = $('#fieldset_table_qbe select.column-operator');
    $.each(tableRows, function (index, item) {
        $(item).on('change', function () {
            window.changeValueFieldType(this, index);
            window.verifyAfterSearchFieldChange(index, '#tbl_search_form');
        });
    });

    /**
     * Ajax event handler for Table search
     */
    $(document).on('submit', '#tbl_search_form.ajax', function (event) {
        var unaryFunctions = [
            'IS NULL',
            'IS NOT NULL',
            '= \'\'',
            '!= \'\''
        ];

        var geomUnaryFunctions = [
            'IsEmpty',
            'IsSimple',
            'IsRing',
            'IsClosed',
        ];

        // jQuery object to reuse
        var $searchForm = $(this);
        event.preventDefault();

        // empty previous search results while we are waiting for new results
        $('#sqlqueryresultsouter').empty();
        var $msgbox = ajaxShowMessage(window.Messages.strSearching, false);

        Functions.prepareForAjaxRequest($searchForm);

        var values: { [k: string]: any } = {};
        ($searchForm.find(':input') as JQuery<HTMLInputElement>).each(function () {
            var $input = $(this);
            if ($input.attr('type') === 'checkbox' || $input.attr('type') === 'radio') {
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
                $.inArray(values['geom_func[' + a + ']'], geomUnaryFunctions) >= 0) {
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
            if (values['columnsToDisplay[]'].length === columnCount) {
                delete values['columnsToDisplay[]'];
                values.displayAllColumns = true;
            }
        } else {
            values.displayAllColumns = true;
        }

        $.post($searchForm.attr('action'), values, function (data) {
            ajaxRemoveMessage($msgbox);
            if (typeof data !== 'undefined' && data.success === true) {
                if (typeof data.sql_query !== 'undefined') { // zero rows
                    $('#sqlqueryresultsouter').html(data.sql_query);
                } else { // results found
                    $('#sqlqueryresultsouter').html(data.message);
                    $('.sqlqueryresults').trigger('makegrid');
                }

                $('#tbl_search_form')
                    // workaround for bug #3168569 - Issue on toggling the "Hide search criteria" in chrome.
                    .slideToggle()
                    .hide();

                $('#togglesearchformlink')
                    // always start with the Show message
                    .text(window.Messages.strShowSearchCriteria);

                $('#togglesearchformdiv')
                    // now it's time to show the div containing the link
                    .show();

                $('html, body').animate({ scrollTop: 0 }, 'fast');
            } else {
                $('#sqlqueryresultsouter').html(data.error);
            }

            highlightSql($('#sqlqueryresultsouter'));
        }); // end $.post()
    });

    // Following section is related to the 'function based search' for geometry data types.
    // Initially hide all the open_gis_editor spans
    $('span.open_search_gis_editor').hide();

    $('select.geom_func').on('change', function () {
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
        var $operator = $geomFuncSelector.parents('tr').find('td').eq(4).find('select');
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
        var value = $span.parent('td').children('input[type=\'text\']').val();
        // Field name
        var field = 'Parameter';
        // Column type
        var geomFunc = $span.parents('tr').find('.geom_func').val();
        var type;
        if (geomFunc === 'Envelope') {
            type = 'polygon';
        } else if (geomFunc === 'ExteriorRing') {
            type = 'linestring';
        } else {
            type = 'point';
        }

        // Names of input field and null checkbox
        var inputName = $span.parent('td').children('input[type=\'text\']').attr('name');
        // Token

        window.openGISEditor();
        if (! window.gisEditorLoaded) {
            window.loadJSAndGISEditor(value, field, type, inputName);
        } else {
            window.loadGISEditor(value, field, type, inputName);
        }
    });

    /**
     * Ajax event handler for Range-Search.
     */
    $('body').on('change', 'select[name*="criteriaColumnOperators"]', function () { // Fix for bug #13778, changed 'click' to 'change'
        var $sourceSelect = $(this);
        // Get the column name.
        var columnName = $(this)
            .closest('tr')
            .find('th')
            .first()
            .text();

        // Get the data-type of column excluding size.
        var dataType: string | false = $(this)
            .closest('tr')
            .find('td[data-type]')
            .attr('data-type');
        dataType = TableSelect.checkIfDataTypeNumericOrDate(dataType);

        // Get the operator.
        var operator = ($(this).val() as string);

        if ((operator === 'BETWEEN' || operator === 'NOT BETWEEN') && dataType) {
            var $msgbox = ajaxShowMessage();
            $.ajax({
                url: 'index.php?route=/table/search',
                type: 'POST',
                data: {
                    'server': CommonParams.get('server'),
                    'ajax_request': 1,
                    'db': $('input[name="db"]').val(),
                    'table': $('input[name="table"]').val(),
                    'column': columnName,
                    'range_search': 1
                },
                success: function (response) {
                    ajaxRemoveMessage($msgbox);
                    if (response.success) {
                        // Get the column min value.
                        var min = response.column_data.min
                            ? '(' + window.Messages.strColumnMin +
                            ' ' + response.column_data.min + ')'
                            : '';
                        // Get the column max value.
                        var max = response.column_data.max
                            ? '(' + window.Messages.strColumnMax +
                            ' ' + response.column_data.max + ')'
                            : '';
                        $('#rangeSearchModal').modal('show');
                        $('#rangeSearchLegend').first().html(operator);
                        $('#rangeSearchMin').first().text(min);
                        $('#rangeSearchMax').first().text(max);
                        // Reset input values on reuse
                        $('#min_value').first().val('');
                        $('#max_value').first().val('');
                        // Add datepicker wherever required.
                        Functions.addDatepicker($('#min_value'), dataType);
                        Functions.addDatepicker($('#max_value'), dataType);
                        $('#rangeSearchModalGo').on('click', function () {
                            var minValue = ($('#min_value').val() as string);
                            var maxValue = ($('#max_value').val() as string);
                            var finalValue = '';
                            if (minValue.length && maxValue.length) {
                                finalValue = minValue + ', ' +
                                    maxValue;
                            }

                            var $targetField = $sourceSelect.closest('tr')
                                .find('[name*="criteriaValues"]');

                            // If target field is a select list.
                            if ($targetField.is('select')) {
                                $targetField.val(finalValue);
                                var $options = $targetField.find('option');
                                var $closestMin: JQuery<HTMLOptionElement> | null = null;
                                var $closestMax: JQuery<HTMLOptionElement> | null = null;
                                // Find closest min and max value.
                                $options.each(function () {
                                    if (
                                        $closestMin === null
                                        || Math.abs(Number($(this).val()) - Number(minValue)) < Math.abs(Number($closestMin.val()) - Number(minValue))
                                    ) {
                                        $closestMin = $(this);
                                    }

                                    if (
                                        $closestMax === null
                                        || Math.abs(Number($(this).val()) - Number(maxValue)) < Math.abs(Number($closestMax.val()) - Number(maxValue))
                                    ) {
                                        $closestMax = $(this);
                                    }
                                });

                                $closestMin.attr('selected', 'selected');
                                $closestMax.attr('selected', 'selected');
                            } else {
                                $targetField.val(finalValue);
                            }

                            $('#rangeSearchModal').modal('hide');
                        });
                    } else {
                        ajaxShowMessage(response.error);
                    }
                },
                error: function () {
                    ajaxShowMessage(window.Messages.strErrorProcessingRequest);
                }
            });
        }
    });

    var windowWidth = $(window).width();
    $('.jsresponsive').css('max-width', (windowWidth - 69) + 'px');
});
