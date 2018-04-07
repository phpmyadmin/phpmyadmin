<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\Database\Designer class
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin\Database;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\Plugins\SchemaPlugin;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

/**
 * Set of functions related to database designer
 *
 * @package PhpMyAdmin
 */
class Designer
{
    /**
     * @var Relation $relation
     */
    private $relation;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->relation = new Relation();
    }

    /**
     * Function to get html for displaying the page edit/delete form
     *
     * @param string $db        database name
     * @param string $operation 'edit' or 'delete' depending on the operation
     *
     * @return string html content
     */
    public function getHtmlForEditOrDeletePages($db, $operation)
    {
        $cfgRelation = $this->relation->getRelationsParam();
        return Template::get('database/designer/edit_delete_pages')->render([
            'db' => $db,
            'operation' => $operation,
            'pdfwork' => $cfgRelation['pdfwork'],
            'pages' => $this->getPageIdsAndNames($db),
        ]);
    }

    /**
     * Function to get html for displaying the page save as form
     *
     * @param string $db database name
     *
     * @return string html content
     */
    public function getHtmlForPageSaveAs($db)
    {
        $cfgRelation = $this->relation->getRelationsParam();
        return Template::get('database/designer/page_save_as')->render([
            'db' => $db,
            'pdfwork' => $cfgRelation['pdfwork'],
            'pages' => $this->getPageIdsAndNames($db),
        ]);
    }

    /**
     * Retrieve IDs and names of schema pages
     *
     * @param string $db database name
     *
     * @return array array of schema page id and names
     */
    private function getPageIdsAndNames($db)
    {
        $cfgRelation = $this->relation->getRelationsParam();
        $page_query = "SELECT `page_nr`, `page_descr` FROM "
            . Util::backquote($cfgRelation['db']) . "."
            . Util::backquote($cfgRelation['pdf_pages'])
            . " WHERE db_name = '" . $GLOBALS['dbi']->escapeString($db) . "'"
            . " ORDER BY `page_descr`";
        $page_rs = $this->relation->queryAsControlUser(
            $page_query,
            false,
            DatabaseInterface::QUERY_STORE
        );

        $result = [];
        while ($curr_page = $GLOBALS['dbi']->fetchAssoc($page_rs)) {
            $result[intval($curr_page['page_nr'])] = $curr_page['page_descr'];
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
    public function getHtmlForSchemaExport($db, $page)
    {
        /* Scan for schema plugins */
        /* @var $export_list SchemaPlugin[] */
        $export_list = Plugins::getPlugins(
            "schema",
            'libraries/classes/Plugins/Schema/',
            null
        );

        /* Fail if we didn't find any schema plugin */
        if (empty($export_list)) {
            return Message::error(
                __('Could not load schema plugins, please check your installation!')
            )->getDisplay();
        }

        return Template::get('database/designer/schema_export')
            ->render(
                [
                    'db' => $db,
                    'page' => $page,
                    'export_list' => $export_list
                ]
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
    public function getHtmlForJsFields(
        array $script_tables,
        array $script_contr,
        array $script_display_field,
        $display_page
    ) {
        $cfgRelation = $this->relation->getRelationsParam();
        return Template::get('database/designer/js_fields')->render([
            'server' => $GLOBALS['server'],
            'db' => $_GET['db'],
            'script_tables' => json_encode($script_tables),
            'script_contr' => json_encode($script_contr),
            'script_display_field' => json_encode($script_display_field),
            'display_page' => $display_page,
            'relation_pdfwork' => $cfgRelation['pdfwork'],
        ]);
    }

    /**
     * Returns HTML for the menu bar of the designer page
     *
     * @param boolean $visualBuilder whether this is visual query builder
     * @param string  $selectedPage  name of the selected page
     * @param array   $paramsArray   array with class name for various buttons
     *                               on side menu
     *
     * @return string html
     */
    public function getPageMenu($visualBuilder, $selectedPage, array $paramsArray)
    {
        return Template::get('database/designer/side_menu')->render([
            'visual_builder' => $visualBuilder,
            'selected_page' => $selectedPage,
            'params_array' => $paramsArray,
            'theme' => $GLOBALS['PMA_Theme'],
        ]);
    }

    /**
     * Returns array of stored values of Designer Settings
     *
     * @return array stored values
     */
    private function getSideMenuParamsArray()
    {
        $params = [];

        $cfgRelation = $this->relation->getRelationsParam();

        if ($GLOBALS['cfgRelation']['designersettingswork']) {
            $query = 'SELECT `settings_data` FROM '
                . Util::backquote($cfgRelation['db']) . '.'
                . Util::backquote($cfgRelation['designer_settings'])
                . ' WHERE ' . Util::backquote('username') . ' = "'
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
    public function returnClassNamesFromMenuButtons()
    {
        $classes_array = [];
        $params_array = $this->getSideMenuParamsArray();

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
    public function getHtmlCanvas()
    {
        return Template::get('database/designer/canvas')->render();
    }

    /**
     * Return HTML for the table list
     *
     * @param array $tab_pos      table positions
     * @param int   $display_page page number of the selected page
     *
     * @return string html
     */
    public function getHtmlTableList(array $tab_pos, $display_page)
    {
        return Template::get('database/designer/table_list')->render([
            'tab_pos' => $tab_pos,
            'display_page' => $display_page,
            'theme' => $GLOBALS['PMA_Theme'],
            'table_names' => $GLOBALS['designer']['TABLE_NAME'],
            'table_names_url' => $GLOBALS['designer_url']['TABLE_NAME'],
            'table_names_small_url' => $GLOBALS['designer_url']['TABLE_NAME_SMALL'],
            'table_names_out' => $GLOBALS['designer_out']['TABLE_NAME'],
        ]);
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
    public function getDatabaseTables(
        array $tab_pos,
        $display_page,
        array $tab_column,
        array $tables_all_keys,
        array $tables_pk_or_unique_keys
    ) {
        return Template::get('database/designer/database_tables')->render([
            'db' => $GLOBALS['db'],
            'get_db' => $_GET['db'],
            'has_query' => isset($_REQUEST['query']),
            'tab_pos' => $tab_pos,
            'display_page' => $display_page,
            'tab_column' => $tab_column,
            'tables_all_keys' => $tables_all_keys,
            'tables_pk_or_unique_keys' => $tables_pk_or_unique_keys,
            'table_names' => $GLOBALS['designer']['TABLE_NAME'],
            'table_names_url' => $GLOBALS['designer_url']['TABLE_NAME'],
            'table_names_small' => $GLOBALS['designer']['TABLE_NAME_SMALL'],
            'table_names_small_url' => $GLOBALS['designer_url']['TABLE_NAME_SMALL'],
            'table_names_small_out' => $GLOBALS['designer_out']['TABLE_NAME_SMALL'],
            'table_types' => $GLOBALS['designer']['TABLE_TYPE'],
            'owner_out' => $GLOBALS['designer_out']['OWNER'],
            'theme' => $GLOBALS['PMA_Theme'],
        ]);
    }

    /**
     * Returns HTML for the new relations panel.
     *
     * @return string html
     */
    public function getNewRelationPanel()
    {
        return Template::get('database/designer/new_relation_panel')
            ->render();
    }

    /**
     * Returns HTML for the relations delete panel
     *
     * @return string html
     */
    public function getDeleteRelationPanel()
    {
        return Template::get('database/designer/delete_relation_panel')
            ->render();
    }

    /**
     * Returns HTML for the options panel
     *
     * @return string html
     */
    public function getOptionsPanel()
    {
        return Template::get('database/designer/options_panel')->render();
    }

    /**
     * Get HTML for the 'rename to' panel
     *
     * @return string html
     */
    public function getRenameToPanel()
    {
        return Template::get('database/designer/rename_to_panel')
            ->render();
    }

    /**
     * Returns HTML for the 'having' panel
     *
     * @return string html
     */
    public function getHavingQueryPanel()
    {
        return Template::get('database/designer/having_query_panel')
            ->render();
    }

    /**
     * Returns HTML for the 'aggregate' panel
     *
     * @return string html
     */
    public function getAggregateQueryPanel()
    {
        return Template::get('database/designer/aggregate_query_panel')
            ->render();
    }

    /**
     * Returns HTML for the 'where' panel
     *
     * @return string html
     */
    public function getWhereQueryPanel()
    {
        return Template::get('database/designer/where_query_panel')
            ->render();
    }

    /**
     * Returns HTML for the query details panel
     *
     * @param string $db Database name
     *
     * @return string html
     */
    public function getQueryDetails($db)
    {
        return Template::get('database/designer/query_details')->render([
            'db' => $db,
        ]);
    }
}
