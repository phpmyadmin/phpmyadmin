/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @fileoverview    Handle shortcuts in various pages
 * @name            Shortcuts handler
 *
 * @requires    jQuery
 * @requires    jQueryUI
 */

/**
 * Register key events on load
 */
$(document).ready(function() {
    var databaseOp = false;
    var tableOp = false;
    var keyD = 68;
    var keyT = 84;
    var keyK = 75;
    var keyS = 83;
    var keyF = 70;
    var keyE = 69;
    var keyH = 72;
    var keyC = 67;
    var keyBackSpace = 8;
    $(document).keyup(function(e) {
        if( e.target.nodeName === 'INPUT' || e.target.nodeName === 'TEXTAREA' || e.target.nodeName === 'SELECT' ) {
            return;
        }

        if(e.keyCode === keyD) {
            setTimeout(function() {
                databaseOp = false;
            }, 2000);
        }
        else if(e.keyCode === keyT) {
            setTimeout(function() {
                tableOp = false;
            }, 2000);
        }
    });
    $(document).keydown(function(e) {
        if ( e.ctrlKey && e.altKey && e.keyCode === keyC ) {
            PMA_console.toggle();
        }

        if( e.ctrlKey && e.keyCode == keyK ) {
            e.preventDefault();
            PMA_console.toggle();
        }

        if( e.target.nodeName === 'INPUT' || e.target.nodeName === 'TEXTAREA' || e.target.nodeName === 'SELECT' ) {
            return;
        }

        var isTable;
        var isDb;
        if(e.keyCode === keyD) {
            databaseOp = true;
        }
        else if(e.keyCode === keyK) {
            e.preventDefault();
            PMA_console.toggle();
        }
        else if(e.keyCode === keyS) {
            if(databaseOp === true) {
                isTable = PMA_commonParams.get('table');
                isDb = PMA_commonParams.get('db');
                if(isDb && ! isTable) {
                    $('.tab .ic_b_props').first().trigger('click');
                }
            }
            else if(tableOp === true) {
                isTable = PMA_commonParams.get('table');
                isDb = PMA_commonParams.get('db');
                if(isDb && isTable) {
                    $('.tab .ic_b_props').first().trigger('click');
                }
            }
            else{
                $('#pma_navigation_settings_icon').trigger('click');
            }
        }
        else if(e.keyCode === keyF) {
            if(databaseOp === true) {
                isTable = PMA_commonParams.get('table');
                isDb = PMA_commonParams.get('db');
                if(isDb && ! isTable) {
                    $('.tab .ic_b_search').first().trigger('click');
                }
            }
            else if(tableOp === true) {
                isTable = PMA_commonParams.get('table');
                isDb = PMA_commonParams.get('db');
                if(isDb && isTable) {
                    $('.tab .ic_b_search').first().trigger('click');
                }
            }
        }
        else if(e.keyCode === keyT) {
            tableOp = true;
        }
        else if(e.keyCode === keyE) {
            $('.ic_b_export').first().trigger('click');
        }
        else if(e.keyCode === keyBackSpace) {
            window.history.back();
        }
        else if(e.keyCode === keyH) {
            $('.ic_b_home').first().trigger('click');
        }
    });
});
