/* vim: set expandtab sw=4 ts=4 sts=4: */

// global var that holds: 0- if ctrl key is not pressed 1- if ctrl key is pressed
var ctrlKeyHistory = 0;

/**
  * Allows moving around inputs/select by Ctrl+arrows
  *
  * @param object   event data
  */
function onKeyDownArrowsHandler (event) {
    var e = event || window.event;

    var o = (e.srcElement || e.target);
    if (!o) {
        return;
    }
    if (o.tagName !== 'TEXTAREA' && o.tagName !== 'INPUT' && o.tagName !== 'SELECT') {
        return;
    }
    if ((e.which !== 17) && (e.which !== 37) && (e.which !== 38) && (e.which !== 39) && (e.which !== 40)) {
        return;
    }
    if (!o.id) {
        return;
    }

    if (e.type === 'keyup') {
        if (e.which === 17) {
            ctrlKeyHistory = 0;
        }
        return;
    } else if (e.type === 'keydown') {
        if (e.which === 17) {
            ctrlKeyHistory = 1;
        }
    }

    if (ctrlKeyHistory !== 1) {
        return;
    }

    e.preventDefault();

    var pos = o.id.split('_');
    if (pos[0] !== 'field' || typeof pos[2] === 'undefined') {
        return;
    }

    var x = pos[2];
    var y = pos[1];

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

    var isFirefox = navigator.userAgent.toLowerCase().indexOf('firefox/') > -1;

    var id = 'field_' + y + '_' + x;

    var nO = document.getElementById(id);
    if (! nO) {
        id = 'field_' + y + '_' + x + '_0';
        nO = document.getElementById(id);
    }

    // skip non existent fields
    if (! nO) {
        return;
    }

    // for firefox select tag
    var lvalue = o.selectedIndex;
    var nOvalue = nO.selectedIndex;

    nO.focus();

    if (isFirefox) {
        var ffcheck = 0;
        var ffversion;
        for (ffversion = 3 ; ffversion < 25 ; ffversion++) {
            var isFirefoxV24 = navigator.userAgent.toLowerCase().indexOf('firefox/' + ffversion) > -1;
            if (isFirefoxV24) {
                ffcheck = 1;
                break;
            }
        }
        if (ffcheck === 1) {
            if (e.which === 38 || e.which === 37) {
                nOvalue++;
            } else if (e.which === 40 || e.which === 39) {
                nOvalue--;
            }
            nO.selectedIndex = nOvalue;
        } else {
            if (e.which === 38 || e.which === 37) {
                lvalue++;
            } else if (e.which === 40 || e.which === 39) {
                lvalue--;
            }
            o.selectedIndex = lvalue;
        }
    }

    if (nO.tagName !== 'SELECT') {
        nO.select();
    }
    e.returnValue = false;
}

AJAX.registerTeardown('keyhandler.js', function () {
    $(document).off('keydown keyup', '#table_columns');
    $(document).off('keydown keyup', 'table.insertRowTable');
});

AJAX.registerOnload('keyhandler.js', function () {
    $(document).on('keydown keyup', '#table_columns', function (event) {
        onKeyDownArrowsHandler(event.originalEvent);
    });
    $(document).on('keydown keyup', 'table.insertRowTable', function (event) {
        onKeyDownArrowsHandler(event.originalEvent);
    });
});
