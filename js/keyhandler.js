/**
  * Allows moving around inputs/select by Ctrl+arrows
  *
  * @param   object   event data
  */
function onKeyDownArrowsHandler(e) {
    e = e||window.event;
    var o = (e.srcElement||e.target);
    if (!o) return;
    if (o.tagName != "TEXTAREA" && o.tagName != "INPUT" && o.tagName != "SELECT") return;
    if (navigator.userAgent.toLowerCase().indexOf('applewebkit/') != -1) {
        if (e.ctrlKey || e.shiftKey || !e.altKey) return;
    } else {
        if (!e.ctrlKey || e.shiftKey || e.altKey) return;
    }
    if (!o.id) return;

    var pos = o.id.split("_");
    if (pos[0] != "field" || typeof pos[2] == "undefined") return;

    var x = pos[2], y=pos[1];

    // skip non existent fields
    for (i=0; i<10; i++)
    {
        if (switch_movement) {
            switch(e.keyCode) {
                case 38: x--; break; // up
                case 40: x++; break; // down
                case 37: y--; break; // left
                case 39: y++; break; // right
                default: return;
            }
        } else {
            switch(e.keyCode) {
                case 38: y--; break; // up
                case 40: y++; break; // down
                case 37: x--; break; // left
                case 39: x++; break; // right
                default: return;
            }
        }

        var id = "field_" + y + "_" + x;
        var nO = document.getElementById(id);
        if (!nO) {
            var id = "field_" + y + "_" + x + "_0";
            var nO = document.getElementById(id);
        }
        if (nO) break;
    }

    if (!nO) return;
    nO.focus();
    if (nO.tagName != 'SELECT') {
        nO.select();
    }
    e.returnValue = false;
}
