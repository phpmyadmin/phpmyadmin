/**
 * Initializes the data required to run Designer, then fires it up.
 */

/* global DesignerOfflineDB */ // js/designer/database.js
/* global DesignerHistory */ // js/designer/history.js
/* global DesignerMove */ // js/designer/move.js
/* global DesignerPage */ // js/designer/page.js
/* global designerConfig */ // templates/database/designer/main.twig

window.AJAX.registerTeardown('designer/init.js', function () {
    $('.trigger').off('click');
});

window.AJAX.registerOnload('designer/init.js', function () {
    $('.trigger').on('click', function () {
        $('.panel').toggle('fast');
        $(this).toggleClass('active');
        $('#ab').accordion('refresh');
        return false;
    });

    window.jTabs = designerConfig.scriptTables.j_tabs;
    window.hTabs = designerConfig.scriptTables.h_tabs;
    window.contr = designerConfig.scriptContr;
    window.displayField = designerConfig.scriptDisplayField;
    window.server = designerConfig.server;
    window.selectedPage = designerConfig.displayPage;
    window.db = designerConfig.db;
    window.designerTablesEnabled = designerConfig.tablesEnabled;

    DesignerMove.main();

    if (! window.designerTablesEnabled) {
        DesignerOfflineDB.open(function (success) {
            if (success) {
                DesignerPage.showTablesInLandingPage(window.db);
            }
        });
    }

    $('#query_Aggregate_Button').on('click', function () {
        $('#query_Aggregate').css('display', 'none');
    });

    $('#query_having_button').on('click', function () {
        $('#query_having').css('display', 'none');
    });

    $('#query_rename_to_button').on('click', function () {
        $('#query_rename_to').css('display', 'none');
    });

    $('#build_query_button').on('click', function () {
        DesignerHistory.buildQuery('SQL Query on Database', 0);
    });

    $('#query_where_button').on('click', function () {
        $('#query_where').css('display', 'none');
    });
});
