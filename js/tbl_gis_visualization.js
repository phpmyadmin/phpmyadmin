/**
 * @fileoverview    functions used for visualizing GIS data
 *
 * @requires    jQuery
 * @requires    flot/jquery.flot.navigate.js
 * @requires    flot/jquery.flot.js
 */

/**
 * Displays tooltips for GIS data points.
 *
 * @param   x          string   the x coordinate
 * @param   y          string   the y coordinate
 * @param   content    string   tooltip message
 */
function showTooltip(x, y, contents) {
    $('<div id="tooltip">' + contents + '</div>').css({
        position : 'absolute',
        display : 'none',
        top : y + 5,
        left : x + 5,
        border : '1px solid #fdd',
        padding : '2px',
        'background-color' : '#fee',
        opacity : 0.80
    }).appendTo("body").fadeIn(200);
}

/*
function manipulateLegend(plot, canvascontext) {
    var limit = 7;
    var count = 0;
    var old_cell = '';
    $('.legend').find('tr').each(function() {
        count++;
        var $tr = $(this);
        var new_cell = $tr.html();
        if (new_cell == old_cell) {
            $tr.hide();
        } else {
            old_cell = new_cell;
            if (count > limit) {
                $tr.addClass('hidden').hide();
            }
            
        }
    });
    if (count > limit) {
        $('.legend').find('table tr:last').after('<tr><td colspan=2><a class="showAll">Show all</a></td></tr>');
    }
    
    $('.legend').children('div').hide();
    $('.legend').find('table').css({
        'background-color' : 'rgb(255, 255, 255)',
        opacity : 0.85
    });
}
*/

/**
 * Ajax handlers for GIS visualization page
 *
 * Actions Ajaxified here:
 * Displaying tooltips for GIS data points.
 */
$(document).ready(function() {
    
    /**
     * Detect the plotover event and show/hide tooltips appropriately.
     */
    var previousPoint = null;
    $("#placeholder").bind("plothover", function (event, pos, item) {
        if (item) {
            if (previousPoint != item.dataIndex) {
                previousPoint = item.dataIndex;

                $("#tooltip").remove();
                var x = item.datapoint[0].toFixed(0);
                var y = item.datapoint[1].toFixed(0);

                showTooltip(item.pageX, item.pageY, item.series.label + " (" + x + ", "+ y + ")");
            }
        } else {
            $("#tooltip").remove();
            previousPoint = null;
        }
    });

/*
    $('.showAll').live('click', function() {
        $(this)
            .removeClass('showAll')
            .addClass('showFew')
            .html('Show few');
        $('.hidden').show();
    });
    
    $('.showFew').live('click', function() {
        $(this)
            .removeClass('showFew')
            .addClass('showAll')
            .html('Show all');
        $('.hidden').hide();
    });
*/
});
