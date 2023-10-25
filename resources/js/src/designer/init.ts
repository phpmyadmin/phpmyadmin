import $ from 'jquery';
import { AJAX } from '../modules/ajax.ts';
import { DesignerOfflineDB } from './database.ts';
import { DesignerHistory } from './history.ts';
import { DesignerMove } from './move.ts';
import { DesignerPage } from './page.ts';
import { DesignerConfig } from './config.ts';

/**
 * Initializes the data required to run Designer, then fires it up.
 */

AJAX.registerTeardown('designer/init.js', function () {
    DesignerHistory.vqbEditor = null;
    DesignerHistory.historyArray = [];
    DesignerHistory.selectField = [];
    $('#ok_edit_rename').off('click');
    $('#ok_edit_having').off('click');
    $('#ok_edit_Aggr').off('click');
    $('#ok_edit_where').off('click');
});

AJAX.registerOnload('designer/init.js', function () {
    $('#ok_edit_rename').on('click', function () {
        DesignerHistory.edit('Rename');
    });

    $('#ok_edit_having').on('click', function () {
        DesignerHistory.edit('Having');
    });

    $('#ok_edit_Aggr').on('click', function () {
        DesignerHistory.edit('Aggregate');
    });

    $('#ok_edit_where').on('click', function () {
        DesignerHistory.edit('Where');
    });

    $('#ab').accordion({ collapsible: true, active: 'none' });
});

AJAX.registerTeardown('designer/init.js', function () {
    $(document).off('fullscreenchange');
    $('#selflink').show();
});

AJAX.registerOnload('designer/init.js', function () {
    var $content = $('#page_content');
    var $img = $('#toggleFullscreen').find('img');
    var $span = $img.siblings('span');

    $content.css({ 'margin-left': '3px' });
    $(document).on('fullscreenchange', function () {
        if (! document.fullscreenElement) {
            $content.removeClass('content_fullscreen')
                .css({ 'width': 'auto', 'height': 'auto' });

            $('#osn_tab').css({ 'width': 'auto', 'height': 'auto' });
            $img.attr('src', $img.data('enter'))
                .attr('title', $span.data('enter'));

            $span.text($span.data('enter'));

            // Saving the fullscreen state in config when
            // designer exists fullscreen mode via ESC key

            var valueSent = 'off';
            DesignerMove.saveValueInConfig('full_screen', valueSent);
        }
    });

    $('#selflink').hide();
});

AJAX.registerTeardown('designer/init.js', function () {
    $('#side_menu').off('mouseenter mouseleave');
    $('#key_Show_left_menu').off('click');
    $('#toggleFullscreen').off('click');
    $('#newPage').off('click');
    $('#editPage').off('click');
    $('#savePos').off('click');
    $('#SaveAs').off('click');
    $('#delPages').off('click');
    $('#StartTableNew').off('click');
    $('#rel_button').off('click');
    $('#StartTableNew').off('click');
    $('#display_field_button').off('click');
    $('#reloadPage').off('click');
    $('#angular_direct_button').off('click');
    $('#grid_button').off('click');
    $('#key_SB_all').off('click');
    $('#SmallTabInvert').off('click');
    $('#relLineInvert').off('click');
    $('#exportPages').off('click');
    $('#query_builder').off('click');
    $('#key_Left_Right').off('click');
    $('#pin_Text').off('click');
    $('#canvas').off('click');
    $('#key_HS_all').off('click');
    $('#key_HS').off('click');
    $('.scroll_tab_struct').off('click');
    $('.scroll_tab_checkbox').off('click');
    $('#id_scroll_tab').find('tr').off('click', '.designer_Tabs2,.designer_Tabs');
    $('.designer_tab').off('click', '.select_all_1');
    $('.designer_tab').off('click', '.small_tab,.small_tab2');
    $('.designer_tab').off('click', '.small_tab_pref_1');
    $('.tab_zag_noquery').off('mouseover');
    $('.tab_zag_noquery').off('mouseout');
    $('.tab_zag_query').off('mouseover');
    $('.tab_zag_query').off('mouseout');
    $('.designer_tab').off('click', '.tab_field_2,.tab_field_3,.tab_field');
    $('.designer_tab').off('click', '.select_all_store_col');
    $('.designer_tab').off('click', '.small_tab_pref_click_opt');
    $('#del_button').off('click');
    $('#cancel_button').off('click');
    $('#ok_add_object').off('click');
    $('#cancel_close_option').off('click');
    $('#ok_new_rel_panel').off('click');
    $('#cancel_new_rel_panel').off('click');
    $(document).off('mouseup');
    $(document).off('mousedown');
    $(document).off('mousemove');
});

AJAX.registerOnload('designer/init.js', function () {
    $('#key_Show_left_menu').on('click', function () {
        DesignerMove.showLeftMenu(this);

        return false;
    });

    $('#toggleFullscreen').on('click', function () {
        DesignerMove.toggleFullscreen();

        return false;
    });

    $('#addOtherDbTables').on('click', function () {
        DesignerMove.addOtherDbTables();

        return false;
    });

    $('#newPage').on('click', function () {
        DesignerMove.new();

        return false;
    });

    $('#editPage').on('click', function () {
        DesignerMove.editPages();

        return false;
    });

    $('#savePos').on('click', function () {
        DesignerMove.save3();

        return false;
    });

    $('#SaveAs').on('click', function () {
        DesignerMove.saveAs();
        $(document).on('ajaxStop', function () {
            $('#selected_value').on('click', function () {
                $('#savePageNewRadio').prop('checked', true);
            });
        });

        return false;
    });

    $('#delPages').on('click', function () {
        DesignerMove.deletePages();

        return false;
    });

    $('#StartTableNew').on('click', function () {
        DesignerMove.startTableNew();

        return false;
    });

    $('#rel_button').on('click', function () {
        DesignerMove.startRelation();

        return false;
    });

    $('#display_field_button').on('click', function () {
        DesignerMove.startDisplayField();

        return false;
    });

    $('#reloadPage').on('click', function () {
        DesignerMove.loadPage(DesignerConfig.selectedPage);
    });

    $('#angular_direct_button').on('click', function () {
        DesignerMove.angularDirect();

        return false;
    });

    $('#grid_button').on('click', function () {
        DesignerMove.grid();

        return false;
    });

    $('#key_SB_all').on('click', function () {
        DesignerMove.smallTabAll(this);

        return false;
    });

    $('#SmallTabInvert').on('click', function () {
        DesignerMove.smallTabInvert();

        return false;
    });

    $('#relLineInvert').on('click', function () {
        DesignerMove.relationLinesInvert();

        return false;
    });

    $('#exportPages').on('click', function () {
        DesignerMove.exportPages();

        return false;
    });

    $('#query_builder').on('click', function () {
        DesignerHistory.buildQuery();
    });

    $('#key_Left_Right').on('click', function () {
        DesignerMove.sideMenuRight(this);

        return false;
    });

    $('#side_menu').on('mouseenter', function () {
        DesignerMove.showText();

        return false;
    });

    $('#side_menu').on('mouseleave', function () {
        DesignerMove.hideText();

        return false;
    });

    $('#pin_Text').on('click', function () {
        DesignerMove.pinText();

        return false;
    });

    $('#canvas').on('click', function (event) {
        DesignerMove.canvasClick(this, event);
    });

    $('#key_HS_all').on('click', function () {
        DesignerMove.hideTabAll(this);

        return false;
    });

    $('#key_HS').on('click', function () {
        DesignerMove.noHaveConstr(this);

        return false;
    });

    $('.designer_tab').each(DesignerMove.enableTableEvents);
    $('.designer_tab').each(DesignerMove.addTableToTablesList);

    $('input#del_button').on('click', function () {
        DesignerMove.updRelation();
    });

    $('input#cancel_button').on('click', function () {
        document.getElementById('layer_upd_relation').style.display = 'none';
        DesignerMove.reload();
    });

    $('input#ok_add_object').on('click', function () {
        DesignerMove.addObject(
            $('#ok_add_object_db_name').val(),
            $('#ok_add_object_table_name').val(),
            $('#ok_add_object_col_name').val(),
            $('#ok_add_object_db_and_table_name_url').val()
        );
    });

    $('input#cancel_close_option').on('click', function () {
        DesignerMove.closeOption();
    });

    $('input#ok_new_rel_panel').on('click', function () {
        DesignerMove.newRelation();
    });

    $('input#cancel_new_rel_panel').on('click', function () {
        document.getElementById('layer_new_relation').style.display = 'none';
    });

    DesignerMove.enablePageContentEvents();
});

AJAX.registerTeardown('designer/init.js', function () {
    $('.trigger').off('click');
});

declare global {
    interface Window {
        designerConfig: {
            db: string;
            scriptTables: { j_tabs: any[], h_tabs: any[] };
            scriptContr: any[];
            server: number;
            scriptDisplayField: any[];
            displayPage: number;
            tablesEnabled: boolean;
        };
    }
}

AJAX.registerOnload('designer/init.js', function () {
    $('.trigger').on('click', function () {
        $('.panel').toggle('fast');
        $(this).toggleClass('active');
        $('#ab').accordion('refresh');

        return false;
    });

    const configValues = window.designerConfig;

    DesignerConfig.jTabs = configValues.scriptTables.j_tabs;
    DesignerConfig.hTabs = configValues.scriptTables.h_tabs;
    DesignerConfig.contr = configValues.scriptContr;
    DesignerConfig.displayField = configValues.scriptDisplayField;
    DesignerConfig.server = configValues.server;
    DesignerConfig.selectedPage = configValues.displayPage;
    DesignerConfig.db = configValues.db;
    DesignerConfig.designerTablesEnabled = configValues.tablesEnabled;

    DesignerMove.main();

    if (! DesignerConfig.designerTablesEnabled) {
        DesignerOfflineDB.open(function (success) {
            if (success) {
                DesignerPage.showTablesInLandingPage(DesignerConfig.db);
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
        DesignerHistory.buildQuery();
    });

    $('#query_where_button').on('click', function () {
        $('#query_where').css('display', 'none');
    });
});
