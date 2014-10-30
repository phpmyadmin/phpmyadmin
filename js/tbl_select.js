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
 * Table Search
 */

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('tbl_select.js', function () {
    $('#togglesearchformlink').unbind('click');
    $("#tbl_search_form.ajax").die('submit');
    $('select.geom_func').unbind('change');
    $('span.open_search_gis_editor').die('click');
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
     * Ajax event handler for Table Search
     */
    $("#tbl_search_form.ajax").live('submit', function (event) {
        var unaryFunctions = [
            'IS NULL',
            'IS NOT NULL',
            "= ''",
            "!= ''"
        ];

        // jQuery object to reuse
        $search_form = $(this);
        event.preventDefault();

        // empty previous search results while we are waiting for new results
        $("#sqlqueryresults").empty();
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
                values['displayAllColumns'] = true;
            }
        } else {
            values['displayAllColumns'] = true;
        }

        $.post($search_form.attr('action'), values, function (data) {
            PMA_ajaxRemoveMessage($msgbox);
            if (typeof data !== 'undefined' && data.success === true) {
                if (typeof data.sql_query !== 'undefined') { // zero rows
                    $("#sqlqueryresults").html(data.sql_query);
                } else { // results found
                    $("#sqlqueryresults").html(data.message);
                    $("#sqlqueryresults").trigger('makegrid').trigger('stickycolumns');
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
            } else {
                $("#sqlqueryresults").html(data.error);
            }
            PMA_highlightSQL($('#sqlqueryresults'));
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

    $('span.open_search_gis_editor').live('click', function (event) {
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

});
