/* vim: set expandtab sw=4 ts=4 sts=4: */

// gloabl var holds the value: 0- if ctrl key is not pressed 1- if ctrl key is pressed
var ctrlKeyHistory = 0;

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
    
    if (!o.id) {
        return;
    }

	var is_firefox = navigator.userAgent.toLowerCase().indexOf("firefox/") > -1;

    if (e.type == "keyup"){
		if (e.which==17)
			ctrlKeyHistory = 0;
		return;
	}
	
	else if (e.type == "keydown"){
		if (e.which==17)
			ctrlKeyHistory = 1;
	
		if (ctrlKeyHistory==1){
			e.preventDefault();
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

			var lvalue = o.selectedIndex;

			nO.focus();

			if (nO.tagName != 'SELECT') {
				nO.select();
			}
			if (is_firefox) {
				if (e.which==38 || e.which==37)
				lvalue=lvalue+1;

				else if (e.which==40 || e.which==39)
				lvalue=lvalue-1;

				o.selectedIndex=lvalue;
				}

			e.returnValue = false;
			}
		else return;
		}
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
