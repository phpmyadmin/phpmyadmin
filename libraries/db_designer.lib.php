<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of functions related to designer
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

require_once 'libraries/relation.lib.php';

/**
 * Function to get html to display a page selector
 *
 * @param array  $cfgRelation information about the configuration storage
 * @param string $db          database name
 *
 * @return string html content
 */
function PMA_getHtmlForPageSelector($cfgRelation, $db)
{
    $html = '<select name="selected_page" id="selected_page">';
    $html .= '<option value="0">-- ' . __('Select page') . ' --</option>';
    if ($cfgRelation['pdfwork']) {
        $pages = PMA_getPageIdsAndNames($db);
        foreach ($pages as $nr => $desc) {
            $html .= '<option value="' . $nr . '">';
            $html .= htmlspecialchars($desc) . '</option>';
        }
    }
    $html .= '</select>';
    return $html;
}

/**
 * Function to get html for displaying the page edit/delete form
 *
 * @param string $db        database name
 * @param string $operation 'edit' or 'delete' depending on the operation
 *
 * @return string html content
 */
function PMA_getHtmlForEditOrDeletePages($db, $operation)
{
    $cfgRelation = PMA_getRelationsParam();
    $html  = '<form action="db_designer.php" method="post"'
        . ' name="edit_delete_pages" id="edit_delete_pages" class="ajax">';
    $html .= PMA_URL_getHiddenInputs($db);
    $html .= '<fieldset id="page_edit_delete_options">';
    $html .= '<input type="hidden" name="operation" value="' . $operation . '" />';
    $html .= '<label for="selected_page">';
    if ($operation == 'editPage') {
        $html .= __("Page to open");
    } else {
        $html .= __("Page to delete");
    }
    $html .= ': </label>';
    $html .= PMA_getHtmlForPageSelector($cfgRelation, $db);
    $html .= '</fieldset>';
    $html .= '</form>';
    return $html;
}

/**
 * Function to get html for displaying the page save as form
 *
 * @param string $db database name
 *
 * @return string html content
 */
function PMA_getHtmlForPageSaveAs($db)
{
    $cfgRelation = PMA_getRelationsParam();
    $choices = array(
        'same' => __('Save to selected page'),
        'new' => __('Create a page and save to it')
    );

    $html  = '<form action="db_designer.php" method="post"'
        . ' name="save_as_pages" id="save_as_pages" class="ajax">';
    $html .= PMA_URL_getHiddenInputs($db);
    $html .= '<fieldset id="page_save_as_options">';
    $html .= '<table><tbody>';

    $html .= '<tr>';
    $html .= '<td>';
    $html .= '<input type="hidden" name="operation" value="savePage" />';
    $html .= PMA_getHtmlForPageSelector($cfgRelation, $db);
    $html .= '</td>';
    $html .= '</tr>';

    $html .= '<tr>';
    $html .= '<td>';
    $html .= PMA_Util::getRadioFields('save_page', $choices, 'same', true);
    $html .= '</td>';
    $html .= '</tr>';

    $html .= '<tr>';
    $html .= '<td>';
    $html .= '<label for="selected_value">' . __('New page name') . '</label>';
    $html .= '<input type="text" name="selected_value" id="selected_value" />';
    $html .= '</td>';
    $html .= '</tr>';

    $html .= '</tbody></table>';
    $html .= '</fieldset>';
    $html .= '</form>';

    return $html;
}

/**
 * Retrieve IDs and names of schema pages
 *
 * @param string $db database name
 *
 * @return array array of schema page id and names
 */
function PMA_getPageIdsAndNames($db)
{
    $cfgRelation = PMA_getRelationsParam();
    $page_query = "SELECT `page_nr`, `page_descr` FROM "
        . PMA_Util::backquote($cfgRelation['db']) . "."
        . PMA_Util::backquote($cfgRelation['pdf_pages'])
        . " WHERE db_name = '" . PMA_Util::sqlAddSlashes($db) . "'"
        . " ORDER BY `page_nr`";
    $page_rs = PMA_queryAsControlUser(
        $page_query, false, PMA_DatabaseInterface::QUERY_STORE
    );

    $result = array();
    while ($curr_page = $GLOBALS['dbi']->fetchAssoc($page_rs)) {
        $result[$curr_page['page_nr']] = $curr_page['page_descr'];
    }
    return $result;
}

/**
 * Function to get html for displaying the schema export
 *
 * @param string $db   database name
 * @param int    $page the page to be exported
 *
 * @return string
 */
function PMA_getHtmlForSchemaExport($db, $page)
{
    /* Scan for schema plugins */
    $export_list = PMA_getPlugins(
        "schema",
        'libraries/plugins/schema/',
        null
    );

    /* Fail if we didn't find any schema plugin */
    if (empty($export_list)) {
        return PMA_Message::error(
            __('Could not load schema plugins, please check your installation!')
        )->getDisplay();
    }

    $htmlString  = '<form method="post" action="schema_export.php"'
        . ' class="disableAjax" id="id_export_pages">';
    $htmlString .= '<fieldset>';
    $htmlString .= PMA_URL_getHiddenInputs($db);
    $htmlString .= '<label>' . __('Select Export Relational Type')
        . '</label><br />';
    $htmlString .= PMA_pluginGetChoice(
        'Schema', 'export_type', $export_list, 'format'
    );
    $htmlString .= '<input type="hidden" name="page_number"'
        . ' value="' . htmlspecialchars($page) . '" />';
    $htmlString .= PMA_pluginGetOptions('Schema', $export_list);
    $htmlString .= '</fieldset>';
    $htmlString .= '</form>';

    return $htmlString;
}

/**
 * Returns HTML for including some variable to be accessed by JavaScript
 *
 * @param array $script_tables        array on foreign key support for each table
 * @param array $script_contr         initialization data array
 * @param array $script_display_field display fields of each table
 * @param int   $display_page         page number of the selected page
 *
 * @return string html
 */
function PMA_getHtmlForJSFields(
    $script_tables, $script_contr, $script_display_field, $display_page
) {
    $cfgRelation = PMA_getRelationsParam();

    $html  = '<div id="script_server" class="hide">';
    $html .= htmlspecialchars($GLOBALS['server']);
    $html .= '</div>';
    $html .= '<div id="script_db" class="hide">';
    $html .= htmlspecialchars($_GET['db']);
    $html .= '</div>';
    $html .= '<div id="script_token" class="hide">';
    $html .= htmlspecialchars($_GET['token']);
    $html .= '</div>';
    $html .= '<div id="script_tables" class="hide">';
    $html .= htmlspecialchars(json_encode($script_tables));
    $html .= '</div>';
    $html .= '<div id="script_contr" class="hide">';
    $html .= htmlspecialchars(json_encode($script_contr));
    $html .= '</div>';
    $html .= '<div id="script_display_field" class="hide">';
    $html .= htmlspecialchars(json_encode($script_display_field));
    $html .= '</div>';
    $html .= '<div id="script_display_page" class="hide">';
    $html .= htmlspecialchars($display_page);
    $html .= '</div>';
    $html .= '<div id="pmd_tables_enabled" class="hide">';
    $html .= htmlspecialchars($cfgRelation['pdfwork']);
    $html .= '</div>';

    return $html;
}

/**
 * Returns HTML for the menu bar of the designer page
 *
 * @param boolean $visualBuilder whether this is visual query builder
 * @param string  $selected_page name of the selected page
 *
 * @return string html
 */
function PMA_getDesignerPageMenu($visualBuilder, $selected_page)
{
    $iconClass = '';
    $textClass = 'hidable hide';

    $html = '';

    if (!$visualBuilder) {
        $html .= '<div id="name-panel" style="overflow:hidden">';
        $html .= '<span id="page_name" style="border:none">';
        $html .= ($selected_page == null
            ? __("Untitled")
            : htmlspecialchars($selected_page));
        $html .= '</span>';
        $html .= '<span id="saved_state" style="border:none;width:5px">';
        $html .= ($selected_page == null ? '*' : '') . '</span>';
        $html .= '</div>';
    }

    $html .= '<div class="pmd_header side-menu" id="side_menu">';

    $html .= '<a class="M_butt" id="key_Show_left_menu" href="#" target="_self">';
    $html .= '<img class="' . $iconClass . '" title="' . __('Show/Hide tables list') . '" alt="v" ';
    $html .= 'src="' . $_SESSION['PMA_Theme']->getImgPath('pmd/downarrow2_m.png') . '" '
        . 'data-down="' . $_SESSION['PMA_Theme']->getImgPath('pmd/downarrow2_m.png') . '" '
        . 'data-up="' . $_SESSION['PMA_Theme']->getImgPath('pmd/uparrow2_m.png') . '" />';
    $html .= '<span class="' . $textClass . '">' . __('Show/Hide tables list') . '</span>';
    $html .= '</a>';

    $html .= '<a href="#" id="toggleFullscreen" class="M_butt" target="_self">';
    $html .= '<img class="' . $iconClass . '" title="' . __('View in fullscreen') . '" alt="" ';
    $html .= 'src="' . $_SESSION['PMA_Theme']->getImgPath('pmd/viewInFullscreen.png') . '" '
        . 'data-enter="' . $_SESSION['PMA_Theme']->getImgPath('pmd/viewInFullscreen.png') . '" '
        . 'data-exit="' . $_SESSION['PMA_Theme']->getImgPath('pmd/exitFullscreen.png') . '"  />';
    $html .= '<span class="' . $textClass . '" data-exit="' . __('Exit fullscreen');
    $html .= '" data-enter="' . __('View in fullscreen') . '">' . __('View in fullscreen') . '</span>';
    $html .= '</a>';

    if (! $visualBuilder) {

        $html .= '<a id="newPage" href="#" ';
        $html .= 'class="M_butt" target="_self">';
        $html .= '<img class="' . $iconClass . '" title="' . __('New page') . '"alt="" ';
        $html .= 'src="' . $_SESSION['PMA_Theme']->getImgPath('pmd/page_add.png')
            . '" />';
        $html .= '<span class="' . $textClass . '">' . __('New page') . '</span>';
        $html .= '</a>';

        $html .= '<a href="#" id="editPage" ';
        $html .= 'class="M_butt ajax" target="_self">';
        $html .= '<img class="' . $iconClass . '" title="' . __('Open page') . '" alt="" ';
        $html .= 'src="' . $_SESSION['PMA_Theme']->getImgPath('pmd/page_edit.png')
            . '" />';
        $html .= '<span class="' . $textClass . '">' . __('Open page') . '</span>';
        $html .= '</a>';

        $html .= '<a href="#" id="savePos" ';
        $html .= 'class="M_butt" target="_self">';
        $html .= '<img class="' . $iconClass . '" title="' . __('Save position') . '" alt="" ';
        $html .= 'src="' . $_SESSION['PMA_Theme']->getImgPath('pmd/save.png')
            . '" />';
        $html .= '<span class="' . $textClass . '">' . __('Save position') . '</span>';
        $html .= '</a>';

        $html .= '<a href="#" id="SaveAs" ';
        $html .= 'class="M_butt ajax" target="_self">';
        $html .= '<img class="' . $iconClass . '" title="' . __('Save positions as') . '" alt="" ';
        $html .= 'src="' . $_SESSION['PMA_Theme']->getImgPath('pmd/save_as.png')
            . '" />';
        $html .= '<span class="' . $textClass . '">' . __('Save positions as') . '</span>';
        $html .= '</a>';

        $html .= '<a href="#" id="delPages" ';
        $html .= 'class="M_butt ajax" target="_self">';
        $html .= '<img class="' . $iconClass . '" title="' . __('Delete pages') . '" alt="" ';
        $html .= 'src="' . $_SESSION['PMA_Theme']->getImgPath('pmd/page_delete.png')
            . '" />';
        $html .= '<span class="' . $textClass . '">' . __('Delete pages') . '</span>';
        $html .= '</a>';
    }

    $html .= '<a href="#" id="StartTableNew" ';
    $html .= 'class="M_butt" target="_self">';
    $html .= '<img class="' . $iconClass . '" title="' . __('Create table') . '" alt="" ';
    $html .= 'src="' . $_SESSION['PMA_Theme']->getImgPath('pmd/table.png') . '" />';
    $html .= '<span class="' . $textClass . '">' . __('Create table') . '</span>';
    $html .= '</a>';

    $html .= '<a href="#" class="M_butt" ';
    $html .= 'id="rel_button" target="_self">';
    $html .= '<img class="' . $iconClass . '" title="' . __('Create relation') . '" alt="" ';
    $html .= 'src="' . $_SESSION['PMA_Theme']->getImgPath('pmd/relation.png')
        . '" />';
    $html .= '<span class="' . $textClass . '">' . __('Create relation') . '</span>';
    $html .= '</a>';

    $html .= '<a href="#" ';
    $html .= 'class="M_butt" id="display_field_button" target="_self">';
    $html .= '<img class="' . $iconClass . '" title="' . __('Choose column to display') . '" alt="" ';
    $html .= 'src="' . $_SESSION['PMA_Theme']->getImgPath('pmd/display_field.png')
        . '" />';
    $html .= '<span class="' . $textClass . '">' . __('Choose column to display') . '</span>';
    $html .= '</a>';

    $html .= '<a href="#" id="reloadPage" class="M_butt" ';
    $html .= 'target="_self">';
    $html .= '<img class="' . $iconClass . '" title="' . __('Reload') . '" alt="" ';
    $html .= 'src="' . $_SESSION['PMA_Theme']->getImgPath('pmd/reload.png') . '" />';
    $html .= '<span class="' . $textClass . '">' . __('Reload') . '</span>';
    $html .= '</a>';

    $html .= '<a href="' . PMA_Util::getDocuLink('faq', 'faq6-31') . '" ';
    $html .= 'target="documentation" class="M_butt" target="_self">';
    $html .= '<img class="' . $iconClass . '" title="' . __('Help') . '" alt="" ';
    $html .= 'src="' . $_SESSION['PMA_Theme']->getImgPath('pmd/help.png') . '" />';
    $html .= '<span class="' . $textClass . '">' . __('Help') . '</span>';
    $html .= '</a>';

    $html .= '<a href="#" ';
    $html .= 'class="M_butt" id="angular_direct_button" target="_self">';
    $html .= '<img class="' . $iconClass . '" title="' . __('Angular links') . ' / ' . __('Direct links') . '" alt="" ';
    $html .= 'src="' . $_SESSION['PMA_Theme']->getImgPath('pmd/ang_direct.png')
        . '" />';
    $html .= '<span class="' . $textClass . '">' . __('Angular links') . ' / ' . __('Direct links') . '</span>';
    $html .= '</a>';

    $html .= '<a href="#" class="M_butt" ';
    $html .= 'id="grid_button" target="_self">';
    $html .= '<img class="' . $iconClass . '" title="' . __('Snap to grid') . '" alt="" ';
    $html .= 'src="' . $_SESSION['PMA_Theme']->getImgPath('pmd/grid.png') . '" />';
    $html .= '<span class="' . $textClass . '">' . __('Snap to grid') . '</span>';
    $html .= '</a>';

    $html .= '<a href="#" class="M_butt" target="_self" id="key_SB_all">';
    $html .= '<img class="' . $iconClass . '" title="' . __('Small/Big All') . '" alt="v" ';
    $html .= 'src="' . $_SESSION['PMA_Theme']->getImgPath('pmd/downarrow1.png') .'" '
        . 'data-right="' . $_SESSION['PMA_Theme']->getImgPath('pmd/rightarrow1.png') .'" '
        . 'data-down="' . $_SESSION['PMA_Theme']->getImgPath('pmd/downarrow1.png') .'"" />';
    $html .= '<span class="' . $textClass . '">' . __('Small/Big All') . '</span>';
    $html .= '</a>';

    $html .= '<a href="#" id="SmallTabInvert" ';
    $html .= 'class="M_butt" target="_self" >';
    $html .= '<img class="' . $iconClass . '" title="' . __('Toggle small/big') . '" alt="key" ';
    $html .= 'src="' . $_SESSION['PMA_Theme']->getImgPath('pmd/bottom.png')
        . '" />';
    $html .= '<span class="' . $textClass . '">' . __('Toggle small/big') . '</span>';
    $html .= '</a>';

    $html .= '<a href="#" id="relLineInvert" ';
    $html .= 'class="M_butt" target="_self" >';
    $html .= '<img class="' . $iconClass . '" title="' . __('Toggle relation lines') . '" alt="key" ';
    $html .= 'src="' . $_SESSION['PMA_Theme']->getImgPath('pmd/toggle_lines.png')
        . '" />';
    $html .= '<span class="' . $textClass . '">' . __('Toggle relation lines') . '</span>';
    $html .= '</a>';

    if (! $visualBuilder) {

        $html .= '<a href="#" id="exportPages" ';
        $html .= 'class="M_butt" target="_self" >';
        $html .= '<img class="' . $iconClass . '" title="' . __('Export schema') . '" alt="key" ';
        $html .= 'src="' . $_SESSION['PMA_Theme']->getImgPath('pmd/export.png')
            . '" />';
        $html .= '<span class="' . $textClass . '">' . __('Export schema') . '</span>';
        $html .= '</a>';

    } else {
        $html .= '<a class="M_butt" href="#" onclick="build_query(\'SQL Query on Database\', 0)" ';
        $html .= 'onmousedown="return false;" class="M_butt" target="_self">';
        $html .= '<img class="' . $iconClass . '" title="' . __('Build Query') . '" alt="key" ';
        $html .= 'width="20" height="20" src="';
        $html .= $_SESSION['PMA_Theme']->getImgPath('pmd/query_builder.png')
            . '" />';
        $html .= '<span class="' . $textClass . '">' . __('Build Query') . '</span>';
        $html .= '</a>';
    }

    $html .= '<a href="#" class="M_butt" target="_self" id="key_Left_Right">';
    $html .= '<img class="' . $iconClass . '" title="' . __('Move Menu') . '" alt=">"';
    $html .= ' data-right="' . $_SESSION['PMA_Theme']->getImgPath('pmd/2leftarrow_m.png');
    $html .= '" src="' . $_SESSION['PMA_Theme']->getImgPath('pmd/2rightarrow_m.png') . '" />';
    $html .= '<span class="' . $textClass . '">' . __('Move Menu') . '</span>';
    $html .= '</a>';

    $html .= '<a href="#" class="M_butt" target="_self" id="pin_Text">';
    $html .= '<img class="' . $iconClass . '" title="' . __('Pin text') . '" alt=">"';
    $html .= ' data-right="' . $_SESSION['PMA_Theme']->getImgPath('pmd/anchor.png');
    $html .= '" src="' . $_SESSION['PMA_Theme']->getImgPath('pmd/anchor.png') . '" />';
    $html .= '<span class="' . $textClass . '">' . __('Pin text') . '</span>';
    $html .= '</a>';

    $html .= '</div>';

    return $html;
}

/**
 * Returns HTML for the canvas element
 *
 * @return string html
 */
function PMA_getHTMLCanvas()
{
    $html  = '<div id="osn_tab">';
    $html .= '<canvas class="pmd" id="canvas" width="100" height="100" ';
    $html .= '></canvas>';
    $html .= '</div>';

    return $html;
}

/**
 * Return HTML for the table list
 *
 * @param array $tab_pos      table positions
 * @param int   $display_page page number of the selected page
 *
 * @return string html
 */
function PMA_getHTMLTableList($tab_pos, $display_page)
{
    $html  = '<div id="layer_menu" style="display:none;">';

    $html .= '<div class="center">';

    $html .= '<a href="#" class="M_butt" target="_self" ';
    $html .= '>';
    $html .= '<img title="' . __('Hide/Show all') . '" alt="v" id="key_HS_all" ';
    $html .= 'src="' . $_SESSION['PMA_Theme']->getImgPath('pmd/downarrow1.png') . '" '
        . 'data-down="' . $_SESSION['PMA_Theme']->getImgPath('pmd/downarrow1.png') . '" '
        . 'data-right="' . $_SESSION['PMA_Theme']->getImgPath('pmd/rightarrow1.png') . '" />';
    $html .= '</a>';

    $html .= '<a href="#" class="M_butt" target="_self" ';
    $html .= '>';
    $html .= '<img alt="v" id="key_HS" ';
    $html .= 'title="' . __('Hide/Show Tables with no relation') . '"';
    $html .= 'src="' . $_SESSION['PMA_Theme']->getImgPath('pmd/downarrow2.png') . '" '
        . 'data-down="' . $_SESSION['PMA_Theme']->getImgPath('pmd/downarrow2.png') . '" '
        . 'data-right="' . $_SESSION['PMA_Theme']->getImgPath('pmd/rightarrow2.png') . '" />';
    $html .= '</a>';

    $html .= '</div>';

    $html .= '<div id="id_scroll_tab" class="scroll_tab">';

    $html .= '<table width="100%" style="padding-left: 3px;">';

    $name_cnt = count($GLOBALS['PMD']['TABLE_NAME']);
    for ($i = 0; $i < $name_cnt; $i++) {

        $html .= '<tr>';

        $html .= '<td title="' . __('Structure') . '" width="1px" ';
        $html .= 'onmouseover="this.className=\'L_butt2_2\'" ';
        $html .= 'onmouseout="this.className=\'L_butt2_1\'" class="L_butt2_1">';
        $html .= '<img alt="" ';
        $html .= 'table_name="' . $GLOBALS['PMD_URL']['TABLE_NAME_SMALL'][$i] . '"'
                . ' class="scroll_tab_struct" ';
        $html .= 'src="' . $_SESSION['PMA_Theme']->getImgPath('pmd/exec.png')
            . '"/>';
        $html .= '</td>';

        $html .= '<td width="1px">';
        $html .= '<input class="scroll_tab_checkbox" ';
        $html .= 'title="' . __('Hide') . '" ';
        $html .= 'id="check_vis_' . $GLOBALS['PMD_URL']["TABLE_NAME"][$i] . '" ';
        $html .= 'style="margin:0px;" type="checkbox" ';
        $html .= 'value="' . $GLOBALS['PMD_URL']["TABLE_NAME"][$i] . '"';

        if ((isset($tab_pos[$GLOBALS['PMD']["TABLE_NAME"][$i]])
            && $tab_pos[$GLOBALS['PMD']["TABLE_NAME"][$i]]["H"])
            || $display_page == -1
        ) {
            $html .= 'checked="checked"';
        }
        $html .= '/></td>';

        $html .= '<td class="pmd_Tabs" ';
        $html .= 'onmouseover="this.className=\'pmd_Tabs2\'" ';
        $html .= 'onmouseout="this.className=\'pmd_Tabs\'" ';
        $html .= 'pmd_url_table_name="'
            . $GLOBALS['PMD_URL']['TABLE_NAME'][$i] . '">';
        $html .= $GLOBALS['PMD_OUT']["TABLE_NAME"][$i];
        $html .= '</td>';

        $html .= '</tr>';
    }

    $html .= '</table>';
    $html .= '</div>'; // end id_scroll_tab

    $html .= '<div class="center">' . __('Number of tables:')
        . ' ' . $name_cnt . '</div>';
    $html .= '<div id="layer_menu_sizer" onmousedown="layer_menu_cur_click=1">';
    $html .= '<div class="floatleft">';
    $html .= '<img class="icon" data-right="' . $_SESSION['PMA_Theme']->getImgPath('pmd/resizeright.png');
    $html .= '" src="' . $_SESSION['PMA_Theme']->getImgPath('pmd/resize.png') . '"/></div>';
    $html .= '</div>';

    $html .= '</div>'; // end layer_menu

    return $html;
}

/**
 * Get HTML to display tables on designer page
 *
 * @param array $tab_pos                  tables positions
 * @param int   $display_page             page number of the selected page
 * @param array $tab_column               table column info
 * @param array $tables_all_keys          all indices
 * @param array $tables_pk_or_unique_keys unique or primary indices
 *
 * @return string html
 */
function PMA_getDatabaseTables(
    $tab_pos, $display_page, $tab_column, $tables_all_keys, $tables_pk_or_unique_keys
) {
    $html  = '';
    for ($i = 0; $i < count($GLOBALS['PMD']["TABLE_NAME"]); $i++) {
        $t_n = $GLOBALS['PMD']["TABLE_NAME"][$i];
        $t_n_url = $GLOBALS['PMD_URL']["TABLE_NAME"][$i];

        $html .= '<input name="t_x[' . $t_n_url . ']" type="hidden" id="t_x_'
            . $t_n_url . '_" />';
        $html .= '<input name="t_y[' . $t_n_url . ']" type="hidden" id="t_y_'
            . $t_n_url . '_" />';
        $html .= '<input name="t_v[' . $t_n_url . ']" type="hidden" id="t_v_'
            . $t_n_url . '_" />';
        $html .= '<input name="t_h[' . $t_n_url . ']" type="hidden" id="t_h_'
            . $t_n_url . '_" />';

        $html .= '<table id="' . $t_n_url . '" cellpadding="0" cellspacing="0" ';
        $html .= 'class="pmd_tab" style="position:absolute;';
        $html .= 'left:';
        $html .= (isset($tab_pos[$t_n]) ? $tab_pos[$t_n]["X"] : rand(20, 700))
            . 'px;';
        $html .= 'top:';
        $html .= (isset($tab_pos[$t_n]) ? $tab_pos[$t_n]["Y"] : rand(20, 550))
            . 'px;';
        $html .= 'display:';
        $html .= (isset($tab_pos[$t_n]) || $display_page == -1) ? 'block;' : 'none;';
        $html .= 'z-index: 1;">';

        $html .= '<thead>';
        $html .= '<tr class="header">';

        if (isset($_REQUEST['query'])) {
            $html .= '<td class="select_all">';
            $html .= '<input class="select_all_1" type="checkbox" '
                . 'style="margin: 0px;" ';
            $html .= 'value="select_all_' . htmlspecialchars($t_n_url) . '" ';
            $html .= 'id="select_all_' . htmlspecialchars($t_n_url) . '" ';
            $html .= 'title="select all" pmd_url_table_name="'
                . htmlspecialchars($t_n_url) . '" ';
            $html .= 'pmd_out_owner="'
                . htmlspecialchars($GLOBALS['PMD_OUT']['OWNER'][$i]) . '">';
            $html .= '</td>';
        }

        $html .= '<td class="small_tab" ';
        $html .= 'id="id_hide_tbody_' . $t_n_url . '" ';
        $html .= 'table_name="' . htmlspecialchars($t_n_url)
            . '" onmouseover="this.className=\'small_tab2\';" ';
        $html .= 'onmouseout="this.className=\'small_tab\';" ';
        $html .= '>';

        // no space allowed here, between tags and content !!!
        // JavaScript function does require this
        if (! isset($tab_pos[$t_n]) || ! empty($tab_pos[$t_n]["V"])) {
            $html .= 'v';
        } else {
            $html .= '&gt;';
        }

        $html .= '</td>';

        $html .= '<td class="small_tab_pref small_tab_pref_1" ';
        $html .= 'table_name_small="' . $GLOBALS['PMD_URL']["TABLE_NAME_SMALL"][$i]
            . '" onmouseover="this.className='
                . '\'small_tab_pref2 small_tab_pref_1\';" ';
        $html .= 'onmouseout="this.className=\'small_tab_pref small_tab_pref_1\';" ';
        $html .= '>';
        $html .= '<img alt="" ';
        $html .= 'src="' . $_SESSION['PMA_Theme']->getImgPath('pmd/exec_small.png')
            . '" />';
        $html .= '</td>';

        $html .= '<td id="id_zag_' . htmlspecialchars($t_n_url)
            . '" class="tab_zag nowrap tab_zag_noquery" ';
        $html .= 'onmousedown="cur_click=document.getElementById(\''
            . $t_n_url . '\');" ';
        $html .= 'table_name="' . htmlspecialchars($t_n_url) . ''
            . '" query_set="' . (isset($_REQUEST['query']) ? 1 : 0 ) . '" ';
        $html .= '>';
        $html .= '<span class="owner">';
        $html .= $GLOBALS['PMD_OUT']["OWNER"][$i] . '.';
        $html .= '</span>';
        $html .= $GLOBALS['PMD_OUT']['TABLE_NAME_SMALL'][$i];
        $html .= '</td>';

        if (isset($_REQUEST['query'])) {
            $html .= '<td class="tab_zag tab_zag_query" ';
            $html .= 'id="id_zag_' . htmlspecialchars($t_n_url) . '_2" ';
            $html .= 'table_name="' . htmlspecialchars($t_n_url) . '" ';
            $html .= 'onmousedown="cur_click=document.getElementById(\''
                . htmlspecialchars($t_n_url) . '\');" ';
            $html .= '>';
        }

        $html .= '</tr>';
        $html .= '</thead>';

        $html .= '<tbody id="id_tbody_' . $t_n_url . '" ';
        if (isset($tab_pos[$t_n]) && empty($tab_pos[$t_n]["V"])) {
            $html .= 'style="display: none;"';
        }
        $html .= '>';
        $display_field = PMA_getDisplayField(
            $_GET['db'],
            $GLOBALS['PMD']["TABLE_NAME_SMALL"][$i]
        );
        for (
            $j = 0, $id_cnt = count($tab_column[$t_n]["COLUMN_ID"]);
        $j < $id_cnt;
        $j++
        ) {
            $html .= '<tr id="id_tr_'
                . $GLOBALS['PMD_URL']["TABLE_NAME_SMALL"][$i] . '.'
                . urlencode($tab_column[$t_n]["COLUMN_NAME"][$j]) . '" ';

            if ($display_field == $tab_column[$t_n]["COLUMN_NAME"][$j]) {
                $html .= 'class="tab_field_3" ';
            } else {
                $html .= 'class="tab_field" ';
            }

            $html .= 'onmouseover="old_class = this.className; ';
            $html .= 'this.className = \'tab_field_2\';" ';
            $html .= 'onmouseout="this.className = old_class;" ';
            $html .= 'click_field_param="';
            $html .= $GLOBALS['PMD_URL']["TABLE_NAME_SMALL"][$i] . ',';
            $html .= urlencode($tab_column[$t_n]["COLUMN_NAME"][$j]) . ',';

            $tmpColumn = $t_n . "." . $tab_column[$t_n]["COLUMN_NAME"][$j];

            if (!PMA_Util::isForeignKeySupported($GLOBALS['PMD']['TABLE_TYPE'][$i])
            ) {
                $html .= (isset($tables_pk_or_unique_keys[$tmpColumn]) ? 1 : 0);
            } else {
                // if foreign keys are supported, it's not necessary that the
                // index is a primary key
                $html .= (isset($tables_all_keys[$tmpColumn]) ? 1 : 0);
            }
            $html .= '"';
            $html .= '>';

            if (isset($_REQUEST['query'])) {
                $html .= '<td class="select_all">';
                $html .= '<input class="select_all_store_col" value="'
                    . htmlspecialchars($t_n_url)
                    . urlencode($tab_column[$t_n]["COLUMN_NAME"][$j]) . '"';
                $html .= 'type="checkbox" id="select_' . htmlspecialchars($t_n_url)
                    . '._' . urlencode($tab_column[$t_n]["COLUMN_NAME"][$j]) . '" ';
                $html .= 'style="margin: 0px;" title="select_'
                    . urlencode($tab_column[$t_n]["COLUMN_NAME"][$j]) . '" ';
                $html .= 'store_column_param="'
                    . urlencode($GLOBALS['PMD_OUT']["TABLE_NAME_SMALL"][$i]) . ','
                    . htmlspecialchars($GLOBALS['PMD_OUT']["OWNER"][$i]) . ','
                    . urlencode($tab_column[$t_n]["COLUMN_NAME"][$j]) . '"></td>';
            }

            $html .= '<td width="10px" colspan="3"';
            $html .= 'id="' . $t_n_url . '.'
                . urlencode($tab_column[$t_n]["COLUMN_NAME"][$j]) . '">';
            $html .= '<div class="nowrap">';

            if (isset($tables_pk_or_unique_keys[$t_n . "." . $tab_column[$t_n]["COLUMN_NAME"][$j]])) {

                $html .= '<img src="'
                    . $_SESSION['PMA_Theme']->getImgPath('pmd/FieldKey_small.png')
                    . '" alt="*" />';
            } else {

                $image = 'pmd/Field_small';
                if (strstr($tab_column[$t_n]["TYPE"][$j], 'char')
                    || strstr($tab_column[$t_n]["TYPE"][$j], 'text')
                ) {
                    $image .= '_char';
                } elseif (strstr($tab_column[$t_n]["TYPE"][$j], 'int')
                    || strstr($tab_column[$t_n]["TYPE"][$j], 'float')
                    || strstr($tab_column[$t_n]["TYPE"][$j], 'double')
                    || strstr($tab_column[$t_n]["TYPE"][$j], 'decimal')
                ) {
                    $image .= '_int';
                } elseif (strstr($tab_column[$t_n]["TYPE"][$j], 'date')
                    || strstr($tab_column[$t_n]["TYPE"][$j], 'time')
                    || strstr($tab_column[$t_n]["TYPE"][$j], 'year')
                ) {
                    $image .= '_date';
                }
                $image .= '.png';

                $html .= '<img src="'
                    . $_SESSION['PMA_Theme']->getImgPath($image) . '" alt="*" />';
            }

            $html .= htmlspecialchars(
                $tab_column[$t_n]["COLUMN_NAME"][$j] . " : "
                . $tab_column[$t_n]["TYPE"][$j],
                ENT_QUOTES
            );
            $html .= "</div>\n</td>\n";

            if (isset($_REQUEST['query'])) {
                $html .= '<td class="small_tab_pref small_tab_pref_click_opt" ';
                $html .= 'onmouseover="this.className='
                    . '\'small_tab_pref2 small_tab_pref_click_opt\';" ';
                $html .= 'onmouseout="this.className='
                    . '\'small_tab_pref small_tab_pref_click_opt\';" ';
                $html .= 'Click_option_param="pmd_optionse,'
                    . urlencode($tab_column[$t_n]['COLUMN_NAME'][$j]) . ','
                    . $GLOBALS['PMD_OUT']['TABLE_NAME_SMALL'][$i] . '" >';
                $html .=  '<img src="'
                    . $_SESSION['PMA_Theme']->getImgPath('pmd/exec_small.png')
                    . '" title="options" alt="" /></td> ';
            }
            $html .= "</tr>";
        }
        $html .= "</tbody>";
        $html .= "</table>";
    }

    return $html;
}

/**
 * Returns HTML for the new relations panel.
 *
 * @return string html
 */
function PMA_getNewRelationPanel()
{
    $html  = '<table id="layer_new_relation" style="display:none;" ';
    $html .= 'width="5%" cellpadding="0" cellspacing="0">';
    $html .= '<tbody>';
    $html .= '<tr>';
    $html .= '<td class="frams1" width="10px"></td>';
    $html .= '<td class="frams5" width="99%" ></td>';
    $html .= '<td class="frams2" width="10px"><div class="bor"></div></td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<td class="frams8"></td>';
    $html .= '<td class="input_tab">';
    $html .= '<table width="168" class="center" cellpadding="2" cellspacing="0">';
    $html .= '<thead>';
    $html .= '<tr>';
    $html .= '<td colspan="2" class="center nowrap">';
    $html .= '<strong>' . __('Create relation') . '</strong>';
    $html .= '</td>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody id="foreign_relation">';
    $html .= '<tr>';
    $html .= '<td colspan="2" class="center nowrap"><strong>FOREIGN KEY</strong>';
    $html .= '</td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<td width="58" class="nowrap">on delete</td>';
    $html .= '<td width="102"><select name="on_delete" id="on_delete">';
    $html .= '<option value="nix" selected="selected">--</option>';
    $html .= '<option value="CASCADE">CASCADE</option>';
    $html .= '<option value="SET NULL">SET NULL</option>';
    $html .= '<option value="NO ACTION">NO ACTION</option>';
    $html .= '<option value="RESTRICT">RESTRICT</option>';
    $html .= '</select>';
    $html .= '</td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<td class="nowrap">on update</td>';
    $html .= '<td><select name="on_update" id="on_update">';
    $html .= '<option value="nix" selected="selected">--</option>';
    $html .= '<option value="CASCADE">CASCADE</option>';
    $html .= '<option value="SET NULL">SET NULL</option>';
    $html .= '<option value="NO ACTION">NO ACTION</option>';
    $html .= '<option value="RESTRICT">RESTRICT</option>';
    $html .= '</select>';
    $html .= '</td>';
    $html .= '</tr>';
    $html .= '</tbody>';
    $html .= '<tbody>';
    $html .= '<tr>';
    $html .= '<td colspan="2" class="center nowrap">';
    $html .= '<input type="button" id="ok_new_rel_panel" class="butt" '
        . 'name="Button" ';
    $html .= 'value="' . __('OK') . '" />';
    $html .= '<input type="button" id="cancel_new_rel_panel" '
        . 'class="butt" name="Button" ';
    $html .= 'value="' . __('Cancel') . '" ';
    $html .= '/>';
    $html .= '</td>';
    $html .= '</tr>';
    $html .= '</tbody>';
    $html .= '</table>';
    $html .= '</td>';
    $html .= '<td class="frams6"></td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<td class="frams4"><div class="bor"></div></td>';
    $html .= '<td class="frams7"></td>';
    $html .= '<td class="frams3"></td>';
    $html .= '</tr>';
    $html .= '</tbody>';
    $html .= '</table>';

    return $html;
}

/**
 * Returns HTML for the relations delete panel
 *
 * @return string html
 */
function PMA_getDeleteRelationPanel()
{
    $html  = '<table id="layer_upd_relation" style="display:none;" ';
    $html .= 'width="5%" cellpadding="0" cellspacing="0">';
    $html .= '<tbody>';
    $html .= '<tr>';
    $html .= '<td class="frams1" width="10px"></td>';
    $html .= '<td class="frams5" width="99%"></td>';
    $html .= '<td class="frams2" width="10px"><div class="bor"></div></td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<td class="frams8"></td>';
    $html .= '<td class="input_tab">';
    $html .= '<table width="100%" class="center" cellpadding="2" cellspacing="0">';
    $html .= '<tr>';
    $html .= '<td colspan="3" class="center nowrap">';
    $html .= '<strong>' . __('Delete relation') . '</strong>';
    $html .= '</td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<td colspan="3" class="center nowrap">';
    $html .= '<input id="del_button" name="Button" type="button" class="butt" ';
    $html .= 'value="' . __('Delete') . '" />';
    $html .= '<input id="cancel_button" type="button" class="butt" name="Button" ';
    $html .= 'value="' . __('Cancel') . '" ';
    $html .= '/>';
    $html .= '</td>';
    $html .= '</tr>';
    $html .= '</table></td>';
    $html .= '<td class="frams6"></td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<td class="frams4"><div class="bor"></div></td>';
    $html .= '<td class="frams7"></td>';
    $html .= '<td class="frams3"></td>';
    $html .= '</tr>';
    $html .= '</tbody>';
    $html .= '</table>';

    return $html;
}

/**
 * Returns HTML for the options panel
 *
 * @return string html
 */
function PMA_getOptionsPanel()
{
    $html  = '<table id="pmd_optionse" style="display:none;" ';
    $html .= 'width="5%" cellpadding="0" cellspacing="0">';
    $html .= '<tbody>';

    $html .= '<tr>';
    $html .= '<td class="frams1" width="10px"></td>';
    $html .= '<td class="frams5" width="99%" ></td>';
    $html .= '<td class="frams2" width="10px"><div class="bor"></div></td>';
    $html .= '</tr>';

    $html .= '<tr>';
    $html .= '<td class="frams8"></td>';
    $html .= '<td class="input_tab">';
    $html .= '<table width="168" class="center" cellpadding="2" cellspacing="0">';
    $html .= '<thead>';
    $html .= '<tr>';
    $html .= '<td colspan="2" rowspan="2" id="option_col_name" ';
    $html .= 'class="center nowrap">';
    $html .= '</td>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody id="where">';
    $html .= '<tr><td class="center nowrap"><b>WHERE</b></td></tr>';
    $html .= '<tr>';
    $html .= '<td width="58" class="nowrap">' . __('Relation operator') . '</td>';
    $html .= '<td width="102"><select name="rel_opt" id="rel_opt">';
    $html .= '<option value="--" selected="selected"> -- </option>';
    $html .= '<option value="="> = </option>';
    $html .= '<option value="&gt;"> &gt; </option>';
    $html .= '<option value="&lt;"> &lt; </option>';
    $html .= '<option value="&gt;="> &gt;= </option>';
    $html .= '<option value="&lt;="> &lt;= </option>';
    $html .= '<option value="NOT"> NOT </option>';
    $html .= '<option value="IN"> IN </option>';
    $html .= '<option value="EXCEPT">' . __('Except') . '</option>';
    $html .= '<option value="NOT IN"> NOT IN </option>';
    $html .= '</select>';
    $html .= '</td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<td class="nowrap">' . __('Value') . '<br />' . __('subquery');
    $html .= '</td>';
    $html .= '<td><textarea id="Query" value="" cols="18"></textarea>';
    $html .= '</td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<td class="center nowrap"><b>' . __('Rename to') . '</b></td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<td width="58" class="nowrap">' . __('New name') . '</td>';
    $html .= '<td width="102"><input type="text" value="" id="new_name"/></td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<td class="center nowrap"><b>' . __('Aggregate') . '</b></td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<td width="58" class="nowrap">' . __('Operator') . '</td>';
    $html .= '<td width="102"><select name="operator" id="operator">';
    $html .= '<option value="---" selected="selected">---</option>';
    $html .= '<option value="sum" > SUM </option>';
    $html .= '<option value="min"> MIN </option>';
    $html .= '<option value="max"> MAX </option>';
    $html .= '<option value="avg"> AVG </option>';
    $html .= '<option value="count"> COUNT </option>';
    $html .= '</select>';
    $html .= '</td></tr>';
    $html .= '<tr>';
    $html .= '<td width="58" class="center nowrap"><b>GROUP BY</b></td>';
    $html .= '<td><input type="checkbox" value="groupby" id="groupby"/></td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<td width="58" class="center nowrap"><b>ORDER BY</b></td>';
    $html .= '<td><input type="checkbox" value="orderby" id="orderby"/></td>';
    $html .= '</tr>';
    $html .= '<tr><td class="center nowrap"><b>HAVING</b></td></tr>';
    $html .= '<tr>';
    $html .= '<td width="58" class="nowrap">' . __('Operator') . '</td>';
    $html .= '<td width="102"><select name="h_operator" id="h_operator">';
    $html .= '<option value="---" selected="selected">---</option>';
    $html .= '<option value="None" >' . __('None') . '</option>';
    $html .= '<option value="sum" > SUM </option>';
    $html .= '<option value="min"> MIN </option>';
    $html .= '<option value="max"> MAX </option>';
    $html .= '<option value="avg"> AVG </option>';
    $html .= '<option value="count"> COUNT </option>';
    $html .= '</select>';
    $html .= '</td></tr>';
    $html .= '<tr>';
    $html .= '<td width="58" class="nowrap">' . __('Relation operator') . '</td>';
    $html .= '<td width="102"><select name="h_rel_opt" id="h_rel_opt">';
    $html .= '<option value="--" selected="selected"> -- </option>';
    $html .= '<option value="="> = </option>';
    $html .= '<option value="&gt;"> &gt; </option>';
    $html .= '<option value="&lt;"> &lt; </option>';
    $html .= '<option value="&gt;="> &gt;= </option>';
    $html .= '<option value="&lt;="> &lt;= </option>';
    $html .= '<option value="NOT"> NOT </option>';
    $html .= '<option value="IN"> IN </option>';
    $html .= '<option value="EXCEPT">' . __('Except') . '</option>';
    $html .= '<option value="NOT IN"> NOT IN </option>';
    $html .= '</select>';
    $html .= '</td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<td width="58" class="nowrap">';
    $html .=  __('Value') . '<br/>';
    $html .=  __('subquery');
    $html .= '</td>';
    $html .= '<td width="102">';
    $html .= '<textarea id="having" value="" cols="18"></textarea>';
    $html .= '</td>';
    $html .= '</tr>';
    $html .= '</tbody>';
    $html .= '<tbody>';
    $html .= '<tr>';
    $html .= '<td colspan="2" class="center nowrap">';
    $html .= '<input type="button" id="ok_add_object" class="butt" name="Button" ';
    $html .= 'value="' . __('OK') . '" />';
    $html .= '<input type="button" id="cancel_close_option" class="butt" ';
    $html .= 'name="Button" value="' . __('Cancel') . '" ';
    $html .= '/>';
    $html .= '</td>';
    $html .= '</tr>';
    $html .= '</tbody>';
    $html .= '</table>';
    $html .= '</td>';
    $html .= '<td class="frams6"></td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<td class="frams4"><div class="bor"></div></td>';
    $html .= '<td class="frams7"></td>';
    $html .= '<td class="frams3"></td>';
    $html .= '</tr>';
    $html .= '</tbody>';
    $html .= '</table>';

    return $html;
}

/**
 * Get HTML for the 'rename to' panel
 *
 * @return string html
 */
function PMA_getRenameToPanel()
{
    $html  = '<table id="query_rename_to" style="display:none;" ';
    $html .= 'width="5%" cellpadding="0" cellspacing="0">';
    $html .= '<tbody>';

    $html .= '<tr>';
    $html .= '<td class="frams1" width="10px"></td>';
    $html .= '<td class="frams5" width="99%" ></td>';
    $html .= '<td class="frams2" width="10px"><div class="bor"></div></td>';
    $html .= '</tr>';

    $html .= '<tr>';
    $html .= '<td class="frams8"></td>';
    $html .= '<td class="input_tab">';
    $html .= '<table width="168" class="center" cellpadding="2" cellspacing="0">';
    $html .= '<thead>';
    $html .= '<tr>';
    $html .= '<td colspan="2" class="center nowrap">';
    $html .= '<strong>' . __('Rename to') . '</strong>';
    $html .= '</td>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody id="rename_to">';
    $html .= '<tr>';
    $html .= '<td width="58" class="nowrap">' . __('New name') . '</td>';
    $html .= '<td width="102">';
    $html .= '<input type="text" value="" id="e_rename"/>';
    $html .= '</td>';
    $html .= '</tr>';
    $html .= '</tbody>';
    $html .= '<tbody>';
    $html .= '<tr>';
    $html .= '<td colspan="2" class="center nowrap">';
    $html .= '<input type="button" id="ok_edit_rename" class="butt" name="Button" ';
    $html .= 'value="' . __('OK') . '" />';
    $html .= '<input type="button" class="butt" name="Button" ';
    $html .= 'value="' . __('Cancel') . '" ';
    $html .= 'onclick="document.getElementById(\'query_rename_to\').style.display'
        . ' = \'none\';" />';
    $html .= '</td>';
    $html .= '</tr>';
    $html .= '</tbody>';
    $html .= '</table>';
    $html .= '</td>';
    $html .= '<td class="frams6"></td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<td class="frams4"><div class="bor"></div></td>';
    $html .= '<td class="frams7"></td>';
    $html .= '<td class="frams3"></td>';
    $html .= '</tr>';
    $html .= '</tbody>';
    $html .= '</table>';

    return $html;
}

/**
 * Returns HTML for the 'having' panel
 *
 * @return string html
 */
function PMA_getHavingQueryPanel()
{
    $html  = '<table id="query_having" style="display:none;" ';
    $html .= 'width="5%" cellpadding="0" cellspacing="0">';
    $html .= '<tbody>';
    $html .= '<tr>';
    $html .= '<td class="frams1" width="10px"></td>';
    $html .= '<td class="frams5" width="99%" ></td>';
    $html .= '<td class="frams2" width="10px"><div class="bor"></div></td>';
    $html .= '</tr>';

    $html .= '<tr>';
    $html .= '<td class="frams8"></td>';
    $html .= '<td class="input_tab">';
    $html .= '<table width="168" class="center" cellpadding="2" cellspacing="0">';
    $html .= '<thead>';
    $html .= '<tr>';
    $html .= '<td colspan="2" class="center nowrap"><strong>HAVING</strong></td>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody id="rename_to">';
    $html .= '<tr>';
    $html .= '<td width="58" class="nowrap">' . __('Operator') . '</td>';
    $html .= '<td width="102"><select name="hoperator" id="hoperator">';
    $html .= '<option value="---" selected="selected">---</option>';
    $html .= '<option value="None" > None </option>';
    $html .= '<option value="sum" > SUM </option>';
    $html .= '<option value="min"> MIN </option>';
    $html .= '<option value="max"> MAX </option>';
    $html .= '<option value="avg"> AVG </option>';
    $html .= '<option value="count"> COUNT </option>';
    $html .= '</select>';
    $html .= '</td></tr>';
    $html .= '<tr>';
    $html .= '<tr>';
    $html .= '<td width="58" class="nowrap">' . __('Operator') . '</td>';
    $html .= '<td width="102"><select name="hrel_opt" id="hrel_opt">';
    $html .= '<option value="--" selected="selected"> -- </option>';
    $html .= '<option value="="> = </option>';
    $html .= '<option value="&gt;"> &gt; </option>';
    $html .= '<option value="&lt;"> &lt; </option>';
    $html .= '<option value="&gt;="> &gt;= </option>';
    $html .= '<option value="&lt;="> &lt;= </option>';
    $html .= '<option value="NOT"> NOT </option>';
    $html .= '<option value="IN"> IN </option>';
    $html .= '<option value="EXCEPT">' . __('Except') . '</option>';
    $html .= '<option value="NOT IN"> NOT IN </option>';
    $html .= '</select>';
    $html .= '</td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<td class="nowrap">' . __('Value') . ' <br />' . __('subquery');
    $html .= '</td>';
    $html .= '<td><textarea id="hQuery" value="" cols="18"></textarea>';
    $html .= '</td>';
    $html .= '</tr>';
    $html .= '</tbody>';
    $html .= '<tbody>';
    $html .= '<tr>';
    $html .= '<td colspan="2" class="center nowrap">';
    $html .= '<input type="button" id="ok_edit_having" class="butt" name="Button" ';
    $html .= 'value="' . __('OK') . '" />';
    $html .= '<input type="button" class="butt" name="Button" ';
    $html .= 'value="' . __('Cancel') . '" ';
    $html .= 'onclick="document.getElementById(\'query_having\').style.display'
        . ' = \'none\';" />';
    $html .= '</td>';
    $html .= '</tr>';
    $html .= '</tbody>';
    $html .= '</table>';
    $html .= '</td>';
    $html .= '<td class="frams6"></td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<td class="frams4"><div class="bor"></div></td>';
    $html .= '<td class="frams7"></td>';
    $html .= '<td class="frams3"></td>';
    $html .= '</tr>';
    $html .= '</tbody>';
    $html .= '</table>';

    return $html;
}

/**
 * Returns HTML for the 'aggregate' panel
 *
 * @return string html
 */
function PMA_getAggregateQueryPanel()
{
    $html  = '<table id="query_Aggregate" style="display:none;"';
    $html .= 'width="5%" cellpadding="0" cellspacing="0">';
    $html .= '<tbody>';

    $html .= '<tr>';
    $html .= '<td class="frams1" width="10px"></td>';
    $html .= '<td class="frams5" width="99%" ></td>';
    $html .= '<td class="frams2" width="10px"><div class="bor"></div></td>';
    $html .= '</tr>';

    $html .= '<tr>';
    $html .= '<td class="frams8"></td>';
    $html .= '<td class="input_tab">';
    $html .= '<table width="168" class="center" cellpadding="2" cellspacing="0">';
    $html .= '<thead>';
    $html .= '<tr>';
    $html .= '<td colspan="2" class="center nowrap">';
    $html .= '<strong>' . __('Aggregate') . '</strong>';
    $html .= '</td>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';
    $html .= '<tr>';
    $html .= '<td width="58" class="nowrap">' . __('Operator') . '</td>';
    $html .= '<td width="102">';
    $html .= '<select name="operator" id="e_operator">';
    $html .= '<option value="---" selected="selected">---</option>';
    $html .= '<option value="sum" > SUM </option>';
    $html .= '<option value="min"> MIN </option>';
    $html .= '<option value="max"> MAX </option>';
    $html .= '<option value="avg"> AVG </option>';
    $html .= '<option value="avg"> COUNT </option>';
    $html .= '</select>';
    $html .= '</td></tr>';
    $html .= '</tbody>';
    $html .= '<tbody>';
    $html .= '<tr>';
    $html .= '<td colspan="2" class="center nowrap">';
    $html .= '<input type="button" id="ok_edit_Aggr" class="butt" name="Button" ';
    $html .= 'value="' . __('OK') . '" />';
    $html .= '<input type="button" class="butt" name="Button"';
    $html .= 'value="' . __('Cancel') . '" ';
    $html .= 'onclick="document.getElementById(\'query_Aggregate\').style.display'
        . ' = \'none\';" />';
    $html .= '</td>';
    $html .= '</tr>';
    $html .= '</tbody>';
    $html .= '</table>';
    $html .= '</td>';
    $html .= '<td class="frams6"></td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<td class="frams4"><div class="bor"></div></td>';
    $html .= '<td class="frams7"></td>';
    $html .= '<td class="frams3"></td>';
    $html .= '</tr>';
    $html .= '</tbody>';
    $html .= '</table>';

    return $html;
}

/**
 * Returns HTML for the 'where' panel
 *
 * @return string html
 */
function PMA_getWhereQueryPanel()
{
    $html  = '<table id="query_where" style="display:none;"';
    $html .= 'width="5%" cellpadding="0" cellspacing="0">';
    $html .= '<tbody>';

    $html .= '<tr>';
    $html .= '<td class="frams1" width="10px"></td>';
    $html .= '<td class="frams5" width="99%" ></td>';
    $html .= '<td class="frams2" width="10px"><div class="bor"></div></td>';
    $html .= '</tr>';

    $html .= '<tr>';
    $html .= '<td class="frams8"></td>';
    $html .= '<td class="input_tab">';
    $html .= '<table width="168" class="center" cellpadding="2" cellspacing="0">';
    $html .= '<thead>';
    $html .= '<tr>';
    $html .= '<td colspan="2" class="center nowrap"><strong>WHERE</strong></td>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody id="rename_to">';
    $html .= '<tr>';
    $html .= '<td width="58" class="nowrap">' . __('Operator') . '</td>';
    $html .= '<td width="102"><select name="erel_opt" id="erel_opt">';
    $html .= '<option value="--" selected="selected"> -- </option>';
    $html .= '<option value="=" > = </option>';
    $html .= '<option value="&gt;"> &gt; </option>';
    $html .= '<option value="&lt;"> &lt; </option>';
    $html .= '<option value="&gt;="> &gt;= </option>';
    $html .= '<option value="&lt;="> &lt;= </option>';
    $html .= '<option value="NOT"> NOT </option>';
    $html .= '<option value="IN"> IN </option>';
    $html .= '<option value="EXCEPT">' . __('Except') . '</option>';
    $html .= '<option value="NOT IN"> NOT IN </option>';
    $html .= '</select>';
    $html .= '</td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<td class="nowrap">' . __('Value') . '<br />' . __('subquery');
    $html .= '</td>';
    $html .= '<td><textarea id="eQuery" value="" cols="18"></textarea>';
    $html .= '</td>';
    $html .= '</tr>';
    $html .= '</tbody>';
    $html .= '<tbody>';
    $html .= '<tr>';
    $html .= '<td colspan="2" class="center nowrap">';
    $html .= '<input type="button" id="ok_edit_where" class="butt" name="Button" ';
    $html .= 'value="' . __('OK') . '" />';
    $html .= '<input type="button" class="butt" name="Button" ';
    $html .= 'value="' . __('Cancel') . '" ';
    $html .= 'onclick="document.getElementById(\'query_where\').style.display'
        . ' = \'none\'" />';
    $html .= '</td>';
    $html .= '</tr>';

    $html .= '</tbody>';
    $html .= '</table>';
    $html .= '</td>';
    $html .= '<td class="frams6"></td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<td class="frams4"><div class="bor"></div></td>';
    $html .= '<td class="frams7"></td>';
    $html .= '<td class="frams3"></td>';
    $html .= '</tr>';
    $html .= '</tbody>';
    $html .= '</table>';

    return $html;
}

/**
 * Returns HTML for the query details panel
 *
 * @return string html
 */
function PMA_getQueryDetails()
{
    $html  = '<div class="panel">';
    $html .= '<div style="clear:both;"></div>';
    $html .= '<div id="ab"></div>';
    $html .= '<div style="clear:both;"></div>';
    $html .= '</div>';
    $html .= '<a class="trigger" href="#">' . __('Active options') . '</a>';
    $html .= '<div id="filter"></div>';
    $html .= '<div id="box">';
    $html .= '<span id="boxtitle"></span>';
    $html .= '<form method="post" action="db_qbe.php">';
    $html .= '<textarea cols="80" name="sql_query" id="textSqlquery"'
        . ' rows="15"></textarea><div id="tblfooter">';
    $html .= '  <input type="submit" name="submit_sql" class="btn" />';
    $html .= '  <input type="button" name="cancel" value="'
        . __('Cancel') . '" onclick="closebox()" class="btn" />';
    $html .= PMA_URL_getHiddenInputs($_GET['db']);
    $html .= '</div></p>';
    $html .= '</form></div>';

    return $html;
}

/**
 * Return HTML to fetch some images eagerly.
 *
 * @return string html
 */
function PMA_getCacheImages()
{
    $html  = '<img src="';
    $html .= $_SESSION['PMA_Theme']->getImgPath('pmd/2leftarrow_m.png');
    $html .= '" width="0" height="0" alt="" />';
    $html .= '<img src="';
    $html .= $_SESSION['PMA_Theme']->getImgPath('pmd/rightarrow1.png');
    $html .= '" width="0" height="0" alt="" />';
    $html .= '<img src="';
    $html .= $_SESSION['PMA_Theme']->getImgPath('pmd/rightarrow2.png');
    $html .= '" width="0" height="0" alt="" />';
    $html .= '<img src="';
    $html .= $_SESSION['PMA_Theme']->getImgPath('pmd/uparrow2_m.png');
    $html .= '" width="0" height="0" alt="" />';
    $html .= '<div id="PMA_disable_floating_menubar"></div>';

    return $html;
}
?>
