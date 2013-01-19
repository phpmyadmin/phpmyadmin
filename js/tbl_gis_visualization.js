/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @fileoverview    functions used for visualizing GIS data
 *
 * @requires    jquery
 * @requires    jquery/jquery.svg.js
 * @requires    jquery/jquery.mousewheel.js
 * @requires    jquery/jquery.event.drag-2.0.js
 */

// Constants
var zoomFactor = 1.5;
var defaultX = 0;
var defaultY = 0;

// Variables
var x;
var y;
var scale;

var svg;

/**
 * Zooms and pans the visualization.
 */
function zoomAndPan()
{
    var g = svg.getElementById('groupPanel');

    g.setAttribute('transform', 'translate(' + x + ', ' + y + ') scale(' + scale + ')');
    var id;
    var circle;
    $('circle.vector').each(function() {
        id = $(this).attr('id');
        circle = svg.getElementById(id);
        svg.change(circle, {
            r : (3 / scale),
            "stroke-width" : (2 / scale)
        });
    });

    var line;
    $('polyline.vector').each(function() {
        id = $(this).attr('id');
        line = svg.getElementById(id);
        svg.change(line, {
            "stroke-width" : (2 / scale)
        });
    });

    var polygon;
    $('path.vector').each(function() {
        id = $(this).attr('id');
        polygon = svg.getElementById(id);
        svg.change(polygon, {
            "stroke-width" : (0.5 / scale)
        });
    });
}

/**
 * Initially loads either SVG or OSM visualization based on the choice.
 */
function selectVisualization() {
    if ($('#choice').prop('checked') != true) {
        $('#openlayersmap').hide();
    } else {
        $('#placeholder').hide();
    }
}

/**
 * Adds necessary styles to the div that coontains the openStreetMap.
 */
function styleOSM() {
    var $placeholder = $('#placeholder');
    var cssObj = {
        'border' : '1px solid #aaa',
        'width' : $placeholder.width(),
        'height' : $placeholder.height(),
        'float' : 'right'
    };
    $('#openlayersmap').css(cssObj);
}

/**
 * Loads the SVG element and make a reference to it.
 */
function loadSVG() {
    var $placeholder = $('#placeholder');

    $placeholder.svg({
        onLoad: function(svg_ref) {
            svg = svg_ref;
        }
    });

    // Removes the second SVG element unnecessarily added due to the above command
    $placeholder.find('svg:nth-child(2)').remove();
}

/**
 * Adds controllers for zooming and panning.
 */
function addZoomPanControllers() {
    var $placeholder = $('#placeholder');
    if ($("#placeholder svg").length > 0) {
        var pmaThemeImage = $('#pmaThemeImage').val();
        // add panning arrows
        $('<img class="button" id="left_arrow" src="' + pmaThemeImage + 'west-mini.png">').appendTo($placeholder);
        $('<img class="button" id="right_arrow" src="' + pmaThemeImage + 'east-mini.png">').appendTo($placeholder);
        $('<img class="button" id="up_arrow" src="' + pmaThemeImage + 'north-mini.png">').appendTo($placeholder);
        $('<img class="button" id="down_arrow" src="' + pmaThemeImage + 'south-mini.png">').appendTo($placeholder);
        // add zooming controls
        $('<img class="button" id="zoom_in" src="' + pmaThemeImage + 'zoom-plus-mini.png">').appendTo($placeholder);
        $('<img class="button" id="zoom_world" src="' + pmaThemeImage + 'zoom-world-mini.png">').appendTo($placeholder);
        $('<img class="button" id="zoom_out" src="' + pmaThemeImage + 'zoom-minus-mini.png">').appendTo($placeholder);
    }
}

/**
 * Resizes the GIS visualization to fit into the space available.
 */
function resizeGISVisualization() {
    var $placeholder = $('#placeholder');
    var old_width = $placeholder.width();
    var visWidth = $('#div_view_options').width() - 48;

    // Assign new value for width
    $placeholder.width(visWidth);
    $('svg').attr('width', visWidth);

    // Assign the offset created due to resizing to defaultX and center the svg.
    defaultX = (visWidth - old_width) / 2;
    x = defaultX;
    y = 0;
    scale = 1;
}

/**
 * Initialize the GIS visualization.
 */
function initGISVisualization() {
    // Loads either SVG or OSM visualization based on the choice
    selectVisualization();
    // Resizes the GIS visualization to fit into the space available
    resizeGISVisualization();
    // Adds necessary styles to the div that coontains the openStreetMap
    styleOSM();
    // Draws openStreetMap with openLayers
    drawOpenLayers();
    // Loads the SVG element and make a reference to it
    loadSVG();
    // Adds controllers for zooming and panning
    addZoomPanControllers();
    zoomAndPan();
}

function getRelativeCoords(e) {
    var position = $('#placeholder').offset();
    return {
        x : e.pageX - position.left,
        y : e.pageY - position.top
    };
}

/**
 * Ajax handlers for GIS visualization page
 *
 * Actions Ajaxified here:
 *
 * Zooming in and zooming out on mousewheel movement.
 * Panning the visualization on dragging.
 * Zooming in on double clicking.
 * Zooming out on clicking the zoom out button.
 * Panning on clicking the arrow buttons.
 * Displaying tooltips for GIS objects.
 */

/**
 * Unbind all event handlers before tearing down a page
 */
AJAX.registerTeardown('tbl_gis_visualization.js', function() {
    $('#choice').die('click');
    $('#placeholder').die('mousewheel');
    $('svg').die('dragstart');
    $('svg').die('mouseup');
    $('svg').die('drag');
    $('#placeholder').die('dblclick');
    $('#zoom_in').die('click');
    $('#zoom_world').die('click');
    $('#zoom_out').die('click');
    $('#left_arrow').die('click');
    $('#right_arrow').die('click');
    $('#up_arrow').die('click');
    $('#down_arrow').die('click');
    $('.vector').unbind('mousemove').unbind('mouseout');
});

AJAX.registerOnload('tbl_gis_visualization.js', function() {

    // If we are in GIS visualization, initialize it
    if ($('table.gis_table').length > 0) {
        initGISVisualization();
    }

    $('#choice').live('click', function() {
        if ($(this).prop('checked') == false) {
            $('#placeholder').show();
            $('#openlayersmap').hide();
        } else {
            $('#placeholder').hide();
            $('#openlayersmap').show();
        }
    });

    $('#placeholder').live('mousewheel', function(event, delta) {
        var relCoords = getRelativeCoords(event);
        if (delta > 0) {
            //zoom in
            scale *= zoomFactor;
            // zooming in keeping the position under mouse pointer unmoved.            
            x = relCoords.x - (relCoords.x - x) * zoomFactor;
            y = relCoords.y - (relCoords.y - y) * zoomFactor;
            zoomAndPan();
        } else {
            //zoom out
            scale /= zoomFactor;
            // zooming out keeping the position under mouse pointer unmoved.
            x = relCoords.x - (relCoords.x - x) / zoomFactor;
            y = relCoords.y - (relCoords.y - y) / zoomFactor;
            zoomAndPan();
        }
        return true;
    });

    var dragX = 0; var dragY = 0;
    $('svg').live('dragstart', function(event, dd) {
        $('#placeholder').addClass('placeholderDrag');
        dragX = Math.round(dd.offsetX);
        dragY = Math.round(dd.offsetY);
    });

    $('svg').live('mouseup', function(event) {
        $('#placeholder').removeClass('placeholderDrag');
    });

    $('svg').live('drag', function(event, dd) {
        newX = Math.round(dd.offsetX);
        x +=  newX - dragX;
        dragX = newX;
        newY = Math.round(dd.offsetY);
        y +=  newY - dragY;
        dragY = newY;
        zoomAndPan();
    });

    $('#placeholder').live('dblclick', function(event) {
        scale *= zoomFactor;
        // zooming in keeping the position under mouse pointer unmoved.
        var relCoords = getRelativeCoords(event);
        x = relCoords.x - (relCoords.x - x) * zoomFactor;
        y = relCoords.y - (relCoords.y - y) * zoomFactor;
        zoomAndPan();
    });

    $('#zoom_in').live('click', function(e) {
        e.preventDefault();
        //zoom in
        scale *= zoomFactor;

        width = $('#placeholder svg').attr('width');
        height = $('#placeholder svg').attr('height');
        // zooming in keeping the center unmoved.
        x = width / 2 - (width / 2 - x) * zoomFactor;
        y = height / 2 - (height / 2 - y) * zoomFactor;
        zoomAndPan();
    });

    $('#zoom_world').live('click', function(e) {
        e.preventDefault();
        scale = 1;
        x = defaultX;
        y = defaultY;
        zoomAndPan();
    });

    $('#zoom_out').live('click', function(e) {
        e.preventDefault();
        //zoom out
        scale /= zoomFactor;

        width = $('#placeholder svg').attr('width');
        height = $('#placeholder svg').attr('height');
        // zooming out keeping the center unmoved.
        x = width / 2 - (width / 2 - x) / zoomFactor;
        y = height / 2 - (height / 2 - y) / zoomFactor;
        zoomAndPan();
    });

    $('#left_arrow').live('click', function(e) {
        e.preventDefault();
        x += 100;
        zoomAndPan();
    });

    $('#right_arrow').live('click', function(e) {
        e.preventDefault();
        x -= 100;
        zoomAndPan();
    });

    $('#up_arrow').live('click', function(e) {
        e.preventDefault();
        y += 100;
        zoomAndPan();
    });

    $('#down_arrow').live('click', function(e) {
        e.preventDefault();
        y -= 100;
        zoomAndPan();
    });
    
    /**
     * Detect the mousemove event and show tooltips.
     */
    $('.vector').bind('mousemove', function(event) {
        var contents = $.trim(escapeHtml($(this).attr('name')));
        $("#tooltip").remove();
        if (contents != '') {
            $('<div id="tooltip">' + contents + '</div>').css({
                position : 'absolute',
                top : event.pageY + 10,
                left : event.pageX + 10,
                border : '1px solid #fdd',
                padding : '2px',
                'background-color' : '#fee',
                opacity : 0.90
            }).appendTo("body").fadeIn(200);
        }
    });

    /**
     * Detect the mouseout event and hide tooltips.
     */
    $('.vector').bind('mouseout', function(event) {
        $("#tooltip").remove();
    });
});
