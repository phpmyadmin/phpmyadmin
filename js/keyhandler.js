/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
  * Allows moving around inputs/select by Ctrl+arrows
  *
  * @param object   event data
  */
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

    var x = pos[2], y=pos[1];

    // skip non existent fields
    for (i=0; i<10; i++)
    {
        if (switch_movement) {
            switch(e.keyCode) {
                case 38:
                    // up
                    x--;
                    break;
                case 40:
                    // down
                    x++;
                    break;
                case 37:
                    // left
                    y--;
                    break;
                case 39:
                    // right
                    y++;
                    break;
                default:
                    return;
            }
        } else {
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
        }

        var id = "field_" + y + "_" + x;
        var nO = document.getElementById(id);
        if (!nO) {
            var id = "field_" + y + "_" + x + "_0";
            var nO = document.getElementById(id);
        }
        if (nO) {
            break;
        }
    }

    if (!nO) {
        return;
    }
    nO.focus();
    if (nO.tagName != 'SELECT') {
        nO.select();
    }
    e.returnValue = false;
}
