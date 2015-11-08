<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of functions related to designer
 *
 * @package PhpMyAdmin
 */
use PMA\libraries\Message;
use PMA\libraries\plugins\SchemaPlugin;

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
    return PMA\libraries\Template::get('database/designer/page_selector')
        ->render(
            array(
                'db' => $db,
                'cfgRelation' => $cfgRelation
            )
        );
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
    return PMA\libraries\Template::get('database/designer/edit_delete_pages')
        ->render(
            array(
                'db' => $db,
                'operation' => $operation
            )
        );
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
    return PMA\libraries\Template::get('database/designer/page_save_as')
        ->render(
            array(
                'db' => $db
            )
        );
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
        . PMA\libraries\Util::backquote($cfgRelation['db']) . "."
        . PMA\libraries\Util::backquote($cfgRelation['pdf_pages'])
        . " WHERE db_name = '" . PMA\libraries\Util::sqlAddSlashes($db) . "'"
        . " ORDER BY `page_descr`";
    $page_rs = PMA_queryAsControlUser(
        $page_query, false, PMA\libraries\DatabaseInterface::QUERY_STORE
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
    /* @var $export_list SchemaPlugin[] */
    $export_list = PMA_getPlugins(
        "schema",
        'libraries/plugins/schema/',
        null
    );

    /* Fail if we didn't find any schema plugin */
    if (empty($export_list)) {
        return Message::error(
            __('Could not load schema plugins, please check your installation!')
        )->getDisplay();
    }

    return PMA\libraries\Template::get('database/designer/schema_export')
        ->render(
            array(
                'db' => $db,
                'page' => $page,
                'export_list' => $export_list
            )
        );
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
    return PMA\libraries\Template::get('database/designer/js_fields')
        ->render(
            array(
                'script_tables' => $script_tables,
                'script_contr' => $script_contr,
                'script_display_field' => $script_display_field,
                'display_page' => $display_page
            )
        );
}

/**
 * Returns HTML for the menu bar of the designer page
 *
 * @param boolean $visualBuilder whether this is visual query builder
 * @param string  $selected_page name of the selected page
 * @param array   $params_array  array with class name for various buttons on side
 *                               menu
 *
 * @return string html
 */
function PMA_getDesignerPageMenu($visualBuilder, $selected_page, $params_array)
{
    return PMA\libraries\Template::get('database/designer/side_menu')
        ->render(
            array(
                'visualBuilder' => $visualBuilder,
                'selected_page' => $selected_page,
                'params_array' => $params_array
            )
        );
}

/**
 * Returns array of stored values of Designer Settings
 *
 * @return array stored values
 */
function PMA_getSideMenuParamsArray()
{
    $params = array();

    $cfgRelation = PMA_getRelationsParam();

    if ($GLOBALS['cfgRelation']['designersettingswork']) {

        $query = 'SELECT `settings_data` FROM '
            . PMA\libraries\Util::backquote($cfgRelation['db']) . '.'
            . PMA\libraries\Util::backquote($cfgRelation['designer_settings'])
            . ' WHERE ' . PMA\libraries\Util::backquote('username') . ' = "'
            . $GLOBALS['cfg']['Server']['user'] . '";';

        $result = $GLOBALS['dbi']->fetchSingleRow($query);

        $params = json_decode($result['settings_data'], true);
    }

    return $params;
}

/**
 * Returns class names for various buttons on Designer Side Menu
 *
 * @return array class names of various buttons
 */
function PMA_returnClassNamesFromMenuButtons()
{
    $classes_array = array();
    $params_array = PMA_getSideMenuParamsArray();

    if (isset($params_array['angular_direct'])
        && $params_array['angular_direct'] == 'angular'
    ) {
        $classes_array['angular_direct'] = 'M_butt_Selected_down';
    } else {
        $classes_array['angular_direct'] = 'M_butt';
    }

    if (isset($params_array['snap_to_grid'])
        && $params_array['snap_to_grid'] == 'on'
    ) {
        $classes_array['snap_to_grid'] = 'M_butt_Selected_down';
    } else {
        $classes_array['snap_to_grid'] = 'M_butt';
    }

    if (isset($params_array['pin_text'])
        && $params_array['pin_text'] == 'true'
    ) {
        $classes_array['pin_text'] = 'M_butt_Selected_down';
    } else {
        $classes_array['pin_text'] = 'M_butt';
    }

    if (isset($params_array['relation_lines'])
        && $params_array['relation_lines'] == 'false'
    ) {
        $classes_array['relation_lines'] = 'M_butt_Selected_down';
    } else {
        $classes_array['relation_lines'] = 'M_butt';
    }

    if (isset($params_array['small_big_all'])
        && $params_array['small_big_all'] == 'v'
    ) {
        $classes_array['small_big_all'] = 'M_butt_Selected_down';
    } else {
        $classes_array['small_big_all'] = 'M_butt';
    }

    if (isset($params_array['side_menu'])
        && $params_array['side_menu'] == 'true'
    ) {
        $classes_array['side_menu'] = 'M_butt_Selected_down';
    } else {
        $classes_array['side_menu'] = 'M_butt';
    }

    return $classes_array;
}

/**
 * Returns HTML for the canvas element
 *
 * @return string html
 */
function PMA_getHTMLCanvas()
{
    return PMA\libraries\Template::get('database/designer/canvas')->render();
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
    return PMA\libraries\Template::get('database/designer/table_list')
        ->render(
            array(
                'tab_pos' => $tab_pos,
                'display_page' => $display_page
            )
        );
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
    return PMA\libraries\Template::get('database/designer/database_tables')
        ->render(
            array(
                'tab_pos' => $tab_pos,
                'display_page' => $display_page,
                'tab_column' => $tab_column,
                'tables_all_keys' => $tables_all_keys,
                'tables_pk_or_unique_keys' => $tables_pk_or_unique_keys
            )
        );
}

/**
 * Returns HTML for the new relations panel.
 *
 * @return string html
 */
function PMA_getNewRelationPanel()
{
    return PMA\libraries\Template::get('database/designer/new_relation_panel')
        ->render();
}

/**
 * Returns HTML for the relations delete panel
 *
 * @return string html
 */
function PMA_getDeleteRelationPanel()
{
    return PMA\libraries\Template::get('database/designer/delete_relation_panel')
        ->render();
}

/**
 * Returns HTML for the options panel
 *
 * @return string html
 */
function PMA_getOptionsPanel()
{
    return PMA\libraries\Template::get('database/designer/options_panel')->render();
}

/**
 * Get HTML for the 'rename to' panel
 *
 * @return string html
 */
function PMA_getRenameToPanel()
{
    return PMA\libraries\Template::get('database/designer/rename_to_panel')
        ->render();
}

/**
 * Returns HTML for the 'having' panel
 *
 * @return string html
 */
function PMA_getHavingQueryPanel()
{
    return PMA\libraries\Template::get('database/designer/having_query_panel')
        ->render();
}

/**
 * Returns HTML for the 'aggregate' panel
 *
 * @return string html
 */
function PMA_getAggregateQueryPanel()
{
    return PMA\libraries\Template::get('database/designer/aggregate_query_panel')
        ->render();
}

/**
 * Returns HTML for the 'where' panel
 *
 * @return string html
 */
function PMA_getWhereQueryPanel()
{
    return PMA\libraries\Template::get('database/designer/where_query_panel')
        ->render();
}

/**
 * Returns HTML for the query details panel
 *
 * @return string html
 */
function PMA_getQueryDetails()
{
    return PMA\libraries\Template::get('database/designer/query_details')->render();
}
