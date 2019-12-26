/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Initialises the data required to run Designer, then fires it up.
 */

/* global DesignerOfflineDB */ // js/designer/database.js
/* global DesignerHistory */ // js/designer/history.js
/* global DesignerMove */ // js/designer/move.js
/* global DesignerPage */ // js/designer/page.js
/* global designerConfig */ // templates/database/designer/main.twig

/* eslint-disable no-unused-vars */
var jTabs;
var hTabs;
var contr;
var displayField;
var server;
var selectedPage;
/* eslint-enable no-unused-vars */

var db;
var designerTablesEnabled;

AJAX.registerTeardown('designer/init.js', function () {
    $('.trigger').off('click');
});

AJAX.registerOnload('designer/init.js', function () {
    $('.trigger').on('click', function () {
        $('.panel').toggle('fast');
        $(this).toggleClass('active');
        $('#ab').accordion('refresh');
        return false;
    });

    jTabs = designerConfig.scriptTables.j_tabs;
    hTabs = designerConfig.scriptTables.h_tabs;
    contr = designerConfig.scriptContr;
    displayField = designerConfig.scriptDisplayField;

    server = designerConfig.server;
    db = designerConfig.db;
    selectedPage = designerConfig.displayPage;
    designerTablesEnabled = designerConfig.tablesEnabled;

    DesignerMove.main();

    if (! designerTablesEnabled) {
        DesignerOfflineDB.open(function (success) {
            if (success) {
                DesignerPage.showTablesInLandingPage(db);
            }
        });
    }

    $('#query_Aggregate_Button').on('click', function () {
        document.getElementById('query_Aggregate').style.display = 'none';
    });

    $('#query_having_button').on('click', function () {
        document.getElementById('query_having').style.display = 'none';
    });

    $('#query_rename_to_button').on('click', function () {
        document.getElementById('query_rename_to').style.display = 'none';
    });

    $('#build_query_button').on('click', function () {
        DesignerHistory.buildQuery('SQL Query on Database', 0);
    });

    $('#query_where_button').on('click', function () {
        document.getElementById('query_where').style.display = 'none';
    });
});
