/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
  * Allows moving around inputs/select by Ctrl+arrows
  *
  * @param object   event data
  */
function onKeyDownArrowsHandler(e)
{
    e = e || window.event;
    var o = (e.srcElement || e.target);
    if (!o) {
        return;
    }
    if (o.tagName != "TEXTAREA" && o.tagName != "INPUT" && o.tagName != "SELECT") {
        return;
    }
    console.log(e);
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

    switch (e.keyCode) {
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

    var is_firefox = navigator.userAgent.toLowerCase().indexOf("firefox/") > -1;

    // restore selected index, bug #3799
    if (is_firefox && e.type == "keyup") {
        o.selectedIndex = window["selectedIndex_" + o.id];
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
    if (e.type == "keydown") {
        nO.focus();
        if (is_firefox) {
            window["selectedIndex_" + nO.id] = nO.selectedIndex;
        }
    }
    if (nO.tagName != 'SELECT') {
        nO.select();
    }
    e.returnValue = false;
}

AJAX.registerTeardown('keyhandler.js', function () {
    $('#table_columns').die('keydown keyup');
    $('table.insertRowTable').die('keydown keyup');
});

AJAX.registerOnload('keyhandler.js', function () {
    $('#table_columns').live('keydown keyup', function (event) {
        onKeyDownArrowsHandler(event.originalEvent);
    });
    $('table.insertRowTable').live('keydown keyup', function (event) {
        onKeyDownArrowsHandler(event.originalEvent);
    });
});
