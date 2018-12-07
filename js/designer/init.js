/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Initialises the data required to run Designer, then fires it up.
 */

var j_tabs;
var h_tabs;
var contr;
var display_field;
var server;
var db;
var selected_page;
var designer_tables_enabled;

AJAX.registerTeardown('designer/init.js', function () {
    $('.trigger').off('click');
});

AJAX.registerOnload('designer/init.js', function () {
    $('.trigger').click(function () {
        $('.panel').toggle('fast');
        $(this).toggleClass('active');
        $('#ab').accordion('refresh');
        return false;
    });

    j_tabs             = designer_config.scriptTables.j_tabs;
    h_tabs             = designer_config.scriptTables.h_tabs;
    contr              = designer_config.scriptContr;
    display_field      = designer_config.scriptDisplayField;

    server             = designer_config.server;
    db                 = designer_config.db;
    selected_page      = designer_config.displayPage;
    designer_tables_enabled = designer_config.tablesEnabled;

    Main();

    if (! designer_tables_enabled) {
        DesignerOfflineDB.open(function (success) {
            if (success) {
                Show_tables_in_landing_page(db);
            }
        });
    }

    $('#query_Aggregate_Button').click(function () {
        document.getElementById('query_Aggregate').style.display = 'none';
    });

    $('#query_having_button').click(function () {
        document.getElementById('query_having').style.display = 'none';
    });

    $('#query_rename_to_button').click(function () {
        document.getElementById('query_rename_to').style.display = 'none';
    });

    $('#build_query_button').click(function () {
        build_query('SQL Query on Database', 0);
    });

    $('#query_where_button').click(function () {
        document.getElementById('query_where').style.display = 'none';
    });
});
