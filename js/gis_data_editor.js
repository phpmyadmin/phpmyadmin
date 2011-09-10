/**
 * @fileoverview    functions used in GIS data editor
 *
 * @requires    jQuery
 *
 */

/**
 * Closes the GIS data editor and perform necessary clean up work.
 */
function closeGISEditor(){
    $("#popup_background").fadeOut("fast");
    $("#gis_editor").fadeOut("fast");
    $("#gis_editor").html('');
}

/**
 * Prepares the HTML recieved via AJAX.
 */
function prepareJSVersion() {
    // Hide 'Go' buttons associated with the dropdowns
    $('.go').hide();

    // Change the text on the submit button
    $("input[name='gis_data[save]']")
        .attr('value', PMA_messages['strCopy'])
        .insertAfter($('#gis_data_textarea'))
        .before('<br><br>');

    // Add close and cancel links
    $('#gis_data_editor').prepend('<a class="close_gis_editor">' + PMA_messages['strClose'] + '</a>');
    $('<a class="cancel_gis_editor"> ' + PMA_messages['strCancel'] + '</a>')
        .insertAfter($("input[name='gis_data[save]']"));

    // Remove the unnecessary text
    $('div#gis_data_output p').remove();

    // Remove 'add' buttons and add links
    $('.add').each(function(e) {
        var $button = $(this);
        $button.addClass('addJs').removeClass('add');
        var classes = $button.attr('class');
        $button
            .after('<a class="' + classes + '" name="' + $button.attr('name')
                + '">+ ' + $button.attr('value') + '</a>')
            .remove();
    });
}

/**
 * Returns the HTML for a data point.
 *
 * @param pointNumber point number
 * @param prefix      prefix of the name
 * @returns the HTML for a data point
 */
function addDataPoint(pointNumber, prefix) {
    return '<br>' + $.sprintf(PMA_messages['strPointN'], (pointNumber + 1)) + ':'
        + '<label for="x"> ' + PMA_messages['strX'] + ' </label>'
        + '<input type="text" name="' + prefix + '[' + pointNumber + '][x]" value="">'
        + '<label for="y"> ' + PMA_messages['strY'] + ' </label>'
        + '<input type="text" name="' + prefix + '[' + pointNumber + '][y]" value="">';
}

/**
 * Initialize the visualization in the GIS data editor.
 */
function initGISEditorVisualization() {
    // Loads either SVG or OSM visualization based on the choice
    selectVisualization();
    // Adds necessary styles to the div that coontains the openStreetMap
    styleOSM();
    // Loads the SVG element and make a reference to it
    loadSVG();
    // Adds controllers for zooming and panning
    addZoomPanControllers();
    zoomAndPan();
}

/**
 * Opens up the GIS data editor.
 *
 * @param value      current value of the geometry field
 * @param field      field name
 * @param type       geometry type
 * @param input_name name of the input field
 * @param token      token
 */
function openGISEditor(value, field, type, input_name, token) {
    // Center the popup
    var windowWidth = document.documentElement.clientWidth;
    var windowHeight = document.documentElement.clientHeight;
    var popupWidth = windowWidth * 0.9;
    var popupHeight = windowHeight * 0.9;
    var popupOffsetTop = windowHeight / 2 - popupHeight / 2;
    var popupOffsetLeft = windowWidth / 2 - popupWidth / 2;
    var $gis_editor = $("#gis_editor");
    $gis_editor.css({"top": popupOffsetTop, "left": popupOffsetLeft, "width": popupWidth, "height": popupHeight});

    $.post('gis_data_editor.php', {
        'field' : field,
        'value' : value,
        'type' : type,
        'input_name' : input_name,
        'get_gis_editor' : true,
        'token' : token
    }, function(data) {
        if(data.success == true) {
            $gis_editor.html(data.gis_editor);
            initGISEditorVisualization();
            prepareJSVersion();
        } else {
            PMA_ajaxShowMessage(data.error);
        }
    }, 'json');

    // Make it appear
    $("#popup_background").css({"opacity":"0.7"});
    $("#popup_background").fadeIn("fast");
    $gis_editor.fadeIn("fast");
}

/**
 * Prepare and insert the GIS data in Well Known Text format
 * to the input field.
 */
function insertDataAndClose() {
    var $form = $('form#gis_data_editor_form');
    var input_name = $form.find("input[name='input_name']").val();

    $.post('gis_data_editor.php', $form.serialize() + "&generate=true", function(data) {
        if(data.success == true) {
            $("input[name='" + input_name + "']").val(data.result);
        } else {
            PMA_ajaxShowMessage(data.error);
        }
    }, 'json');
    closeGISEditor();
}

$(document).ready(function() {

    // Remove the class that is added due to the URL being too long.
    $('.open_gis_editor a').removeClass('formLinkSubmit');

    /**
     * Prepares and insert the GIS data to the input field on clicking 'copy'.
     */
    $("input[name='gis_data[save]']").live('click', function(event) {
        event.preventDefault();
        insertDataAndClose();
    });

    /**
     * Prepares and insert the GIS data to the input field on pressing 'enter'.
     */
    $('#gis_editor').live('submit', function(event) {
        event.preventDefault();
        insertDataAndClose();
    });

    /**
     * Trigger asynchronous calls on data change and update the output.
     */
    $('#gis_editor').find("input[type='text']").live('change', function() {
        var $form = $('form#gis_data_editor_form');
        $.post('gis_data_editor.php', $form.serialize() + "&generate=true", function(data) {
            if(data.success == true) {
                $('#gis_data_textarea').val(data.result);
                $('#placeholder').empty().removeClass('hasSVG').html(data.visualization);
                $('#openlayersmap').empty();
                eval(data.openLayers);
                initGISEditorVisualization();
            } else {
                PMA_ajaxShowMessage(data.error);
            }
        }, 'json');
    });

    /**
     * Update the form on change of the GIS type.
     */
    $(".gis_type").live('change', function(event) {
        var $gis_editor = $("#gis_editor");
        var $form = $('form#gis_data_editor_form');

        $.post('gis_data_editor.php', $form.serialize() + "&get_gis_editor=true", function(data) {
            if(data.success == true) {
                $gis_editor.html(data.gis_editor);
                initGISEditorVisualization();
                prepareJSVersion();
            } else {
                PMA_ajaxShowMessage(data.error);
            }
        }, 'json');
    });

    /**
     * Handles closing of the GIS data editor.
     */
    $('.close_gis_editor, .cancel_gis_editor').live('click', function() {
        closeGISEditor();
    });

    /**
     * Handles adding data points
     */
    $('.addJs.addPoint').live('click', function() {
        var $a = $(this);
        var name = $a.attr('name');
        // Eg. name = gis_data[0][MULTIPOINT][add_point] => prefix = gis_data[0][MULTIPOINT]
        var prefix = name.substr(0, name.length - 11);
        // Find the number of points
        var $noOfPointsInput = $("input[name='" + prefix + "[no_of_points]" + "']");
        var noOfPoints = parseInt($noOfPointsInput.attr('value'));
        // Add the new data point
        var html = addDataPoint(noOfPoints, prefix);
        $a.before(html);
        $noOfPointsInput.attr('value', noOfPoints + 1);
    });

    /**
     * Handles adding linestrings and inner rings
     */
    $('.addLine.addJs').live('click', function() {
        var $a = $(this);
        var name = $a.attr('name');

        // Eg. name = gis_data[0][MULTILINESTRING][add_line] => prefix = gis_data[0][MULTILINESTRING]
        var prefix = name.substr(0, name.length - 10);
        var type = prefix.slice(prefix.lastIndexOf('[') + 1, prefix.lastIndexOf(']'));

        // Find the number of lines
        var $noOfLinesInput = $("input[name='" + prefix + "[no_of_lines]" + "']");
        var noOfLines = parseInt($noOfLinesInput.attr('value'));

        // Add the new linesting of inner ring based on the type
        var html = '<br>';
        if (type == 'MULTILINESTRING') {
            html += PMA_messages['strLineString'] + (noOfLines + 1) + ':';
            var noOfPoints = 2;
        } else {
            html += PMA_messages['strInnerRing'] + noOfLines + ':';
            var noOfPoints = 4;
        }
        html += '<input type="hidden" name="' + prefix + '[' + noOfLines + '][no_of_points]" value="' + noOfPoints + '">';
        for (i = 0; i < noOfPoints; i++) {
            html += addDataPoint(i, (prefix + '[' + noOfLines + ']'));
        }
        html += '<a class="addPoint addJs" name="' + prefix + '[' + noOfLines + '][add_point]">+ '
            + PMA_messages['strAddPoint'] + '</a><br>';

        $a.before(html);
        $noOfLinesInput.attr('value', noOfLines + 1);
    });

    /**
     * Handles adding polygons
     */
    $('.addJs.addPolygon').live('click', function() {
        var $a = $(this);
        var name = $a.attr('name');
        // Eg. name = gis_data[0][MULTIPOLYGON][add_polygon] => prefix = gis_data[0][MULTIPOLYGON]
        var prefix = name.substr(0, name.length - 13);
        // Find the number of polygons
        var $noOfPolygonsInput = $("input[name='" + prefix + "[no_of_polygons]" + "']");
        var noOfPolygons = parseInt($noOfPolygonsInput.attr('value'));

        // Add the new polygon
        var html = PMA_messages['strPolygon'] + (noOfPolygons + 1) + ':<br>';
        html += '<input type="hidden" name="' + prefix + '[' + noOfPolygons + '][no_of_lines]" value="1">';
            + '<br>' + PMA_messages['strOuterRing'] + ':';
            + '<input type="hidden" name="' + prefix + '[' + noOfPolygons + '][0][no_of_points]" value="4">';
        for (i = 0; i < 4; i++) {
            html += addDataPoint(i, (prefix + '[' + noOfPolygons + '][0]'));
        }
        html += '<a class="addPoint addJs" name="' + prefix + '[' + noOfPolygons + '][0][add_point]">+ '
            + PMA_messages['strAddPoint'] + '</a><br>'
            + '<a class="addLine addJs" name="' + prefix + '[' + noOfPolygons + '][add_line]">+ '
            + PMA_messages['strAddInnerRing'] + '</a><br><br>';

        $a.before(html);
        $noOfPolygonsInput.attr('value', noOfPolygons + 1);
    });

    /**
     * Handles adding geoms
     */
    $('.addJs.addGeom').live('click', function() {
        var $a = $(this);
        var prefix = 'gis_data[GEOMETRYCOLLECTION]';
        // Find the number of geoms
        var $noOfGeomsInput = $("input[name='" + prefix + "[geom_count]" + "']");
        var noOfGeoms = parseInt($noOfGeomsInput.attr('value'));

        var html1 = PMA_messages['strGeometry'] + (noOfGeoms + 1) + ':<br>';
        var $geomType = $("select[name='gis_data[" + (noOfGeoms - 1) + "][gis_type]']").clone();
        $geomType.attr('name', 'gis_data[' + noOfGeoms + '][gis_type]').val('POINT');
        var html2 = '<br>' + PMA_messages['strPoint'] + ' :'
            + '<label for="x"> ' + PMA_messages['strX'] + ' </label>'
            + '<input type="text" name="gis_data[' + noOfGeoms + '][POINT][x]" value="">'
            + '<label for="y"> ' + PMA_messages['strY'] + ' </label>'
            + '<input type="text" name="gis_data[' + noOfGeoms + '][POINT][y]" value="">'
            + '<br><br>';

        $a.before(html1); $geomType.insertBefore($a); $a.before(html2);
        $noOfGeomsInput.attr('value', noOfGeoms + 1);
    });
});
