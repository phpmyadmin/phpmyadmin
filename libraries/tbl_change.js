/* $Id$ */


/**
 * Modify from controls when the "NULL" checkbox is selected
 *
 * @param   string   the MySQL field type
 * @param   string   the urlencoded field name
 * @param   string   the md5 hashed field name
 *
 * @return  boolean  always true
 */
function nullify(theType, urlField, md5Field, multi_edit)
{
    var rowForm = document.forms['insertForm'];

    if (typeof(rowForm.elements['funcs' + multi_edit + '[' + urlField + ']']) != 'undefined') {
        rowForm.elements['funcs' + multi_edit + '[' + urlField + ']'].selectedIndex = -1;
    }

    // "SET" field , "ENUM" field with more than 20 characters
    // or foreign key field
    if (theType == 1 || theType == 3 || theType == 4) {
        rowForm.elements['field_' + md5Field + multi_edit + '[]'].selectedIndex = -1;
    }
    // Other "ENUM" field
    else if (theType == 2) {
        var elts     = rowForm.elements['field_' + md5Field + multi_edit + '[]'];
        // when there is just one option in ENUM:
        if (elts.checked) {
            elts.checked = false;
        } else {
            var elts_cnt = elts.length;
            for (var i = 0; i < elts_cnt; i++ ) {
                elts[i].checked = false;
            } // end for

        } // end if
    }
    // Other field types
    else /*if (theType == 5)*/ {
        rowForm.elements['fields' + multi_edit + '[' + urlField + ']'].value = '';
    } // end if... else if... else

    return true;
} // end of the 'nullify()' function


/**
 * Unchecks the "NULL" control when a function has been selected or a value
 * entered
 *
 * @param   string   the urlencoded field name
 *
 * @return  boolean  always true
 */
function unNullify(urlField, multi_edit)
{
    var rowForm = document.forms['insertForm'];

    if (typeof(rowForm.elements['fields_null' + multi_edit + '[' + urlField + ']']) != 'undefined') {
        rowForm.elements['fields_null' + multi_edit + '[' + urlField + ']'].checked = false
    } // end if

    return true;
} // end of the 'unNullify()' function

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
    if (!e.ctrlKey) return;
    if (!o.id) return;

    var pos = o.id.split("_");
    if (pos[0] != "field" || typeof pos[2] == "undefined") return;

    var x = pos[2], y=pos[1];
    
    // skip non existent fields
    for (i=0; i<10; i++)
    {
        switch(e.keyCode) {
            case 38: y--; break; // up
            case 40: y++; break; // down
            case 37: x--; break; // left
            case 39: x++; break; // right
            default: return;
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
