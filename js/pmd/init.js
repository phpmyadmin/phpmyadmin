/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Initialises the data required to run PMD, then fires it up.
 */

var j_tabs, h_tabs, contr, server, db, token, selected_page, pmd_tables_enabled;

AJAX.registerTeardown('pmd/init.js', function () {
    $(".trigger").unbind('click');
});

AJAX.registerOnload('pmd/init.js', function () {
    $(".trigger").click(function () {
        $(".panel").toggle("fast");
        $(this).toggleClass("active");
        return false;
    });
    var tables_data = $.parseJSON($("#script_tables").html());

    j_tabs             = tables_data.j_tabs;
    h_tabs             = tables_data.h_tabs;
    contr              = $.parseJSON($("#script_contr").html());
    display_field      = $.parseJSON($("#script_display_field").html());

    server             = $("#script_server").html();
    db                 = $("#script_db").html();
    token              = $("#script_token").html();
    selected_page      = $("#script_display_page").html() === "" ? "-1" : $("#script_display_page").html();
    pmd_tables_enabled = $("#pmd_tables_enabled").html() === "1";

    Main();

    if (! pmd_tables_enabled) {
        DesignerOfflineDB.open(function(success) {
            if (success) {
                Show_tables_in_landing_page();
            }
        });
    }
});
