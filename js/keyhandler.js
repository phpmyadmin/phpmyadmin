/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
  * Allows moving around inputs/select by Ctrl+arrows
  *
  * @param object   event data
  */

AJAX.registerTeardown('keyhandler.js', function() {
    $('#table_columns').die('keydown');
    $('table.insertRowTable').die('keydown');
});

AJAX.registerOnload('keyhandler.js', function() {
    $('#table_columns').live('keydown', function(event) {
        onKeyDownArrowsHandler(event.originalEvent);
    });
    $('table.insertRowTable').live('keydown', function(event) {
        onKeyDownArrowsHandler(event.originalEvent);
    });
});

function onKeyDownArrowsHandler(e)
{
    e = e||window.event;
    var o = (e.srcElement||e.target);
    if (!o) {
        return;
    }
    if (o.tagName != "TEXTAREA" && o.tagName != "INPUT" && o.tagName != "SELECT") {
        return;
    }
    if (navigator.userAgent.toLowerCase().indexOf('applewebkit/') != -1) {
        if (e.ctrlKey || e.shiftKey || !e.altKey) {
            return;
        }
    } else {
        if (!e.ctrlKey || e.shiftKey || e.altKey) {
            return;
        }
    }
    if (!o.id) {
        return;
    }

    var pos = o.id.split("_");
    if (pos[0] != "field" || typeof pos[2] == "undefined") {
        return;
    }

    var x = pos[2], y = pos[1];

    var nO = null;

    switch(e.keyCode) {
        case 38:
            // up
            y--;
            break;
        case 40:
            // down
            y++;
            break;
        case 37:
            // left
            x--;
            break;
        case 39:
            // right
            x++;
            break;
        default:
            return;
    }

    var id = "field_" + y + "_" + x;
    nO = document.getElementById(id);
    if (! nO) {
        id = "field_" + y + "_" + x + "_0";
        nO = document.getElementById(id);
    }

    // skip non existent fields
    if (! nO) {
        return;
    }
    nO.focus();
    if (nO.tagName != 'SELECT') {
        nO.select();
    }
    e.returnValue = false;
}
