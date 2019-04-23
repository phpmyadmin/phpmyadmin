<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * This class is responsible for instantiating
 * the various components of the navigation panel
 *
 * @package PhpMyAdmin-navigation
 */
declare(strict_types=1);

namespace PhpMyAdmin\Navigation;

use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

/**
 * The navigation panel - displays server, db and table selection tree
 *
 * @package PhpMyAdmin-Navigation
 */
class Navigation
{
    /**
     * @var Template
     */
    private $template;

    /**
     * @var Relation
     */
    private $relation;

    /**
     * @var DatabaseInterface
     */
    private $dbi;

    /**
     * Navigation constructor.
     * @param Template          $template Template instance
     * @param Relation          $relation Relation instance
     * @param DatabaseInterface $dbi      DatabaseInterface instance
     */
    public function __construct($template, $relation, $dbi)
    {
        $this->template = $template;
        $this->relation = $relation;
        $this->dbi = $dbi;
    }

    /**
     * Renders the navigation tree, or part of it
     *
     * @return string The navigation tree
     */
    public function getDisplay(): string
    {
        global $cfg;

        $response = Response::getInstance();
        if (! $response->isAjax()) {
            $navHeader = new NavigationHeader();
            $header = $navHeader->getDisplay();

            if (! defined('PMA_DISABLE_NAVI_SETTINGS')) {
                $navigationSettings = PageSettings::getNaviSettings();
            }
        }
        $tree = new NavigationTree();
        if (! $response->isAjax()
            || ! empty($_POST['full'])
            || ! empty($_POST['reload'])
        ) {
            if ($cfg['ShowDatabasesNavigationAsTree']) {
                // provide database tree in navigation
                $navRender = $tree->renderState();
            } else {
                // provide legacy pre-4.0 navigation
                $navRender = $tree->renderDbSelect();
            }
        } else {
            $navRender = $tree->renderPath();
        }

        return $this->template->render('navigation/main', [
            'is_ajax' => $response->isAjax(),
            'header' => $header ?? '',
            'navigation_tree' => $navRender,
            'navigation_settings' => $navigationSettings ?? '',
            'is_drag_drop_import_enabled' => $cfg['enable_drag_drop_import'] === true,
        ]);
    }

    /**
     * Add an item of navigation tree to the hidden items list in PMA database.
     *
     * @param string $itemName  name of the navigation tree item
     * @param string $itemType  type of the navigation tree item
     * @param string $dbName    database name
     * @param string $tableName table name if applicable
     *
     * @return void
     */
    public function hideNavigationItem(
        $itemName,
        $itemType,
        $dbName,
        $tableName = null
    ) {
        $navTable = Util::backquote($GLOBALS['cfgRelation']['db'])
            . "." . Util::backquote($GLOBALS['cfgRelation']['navigationhiding']);
        $sqlQuery = "INSERT INTO " . $navTable
            . "(`username`, `item_name`, `item_type`, `db_name`, `table_name`)"
            . " VALUES ("
            . "'" . $this->dbi->escapeString($GLOBALS['cfg']['Server']['user']) . "',"
            . "'" . $this->dbi->escapeString($itemName) . "',"
            . "'" . $this->dbi->escapeString($itemType) . "',"
            . "'" . $this->dbi->escapeString($dbName) . "',"
            . "'" . (! empty($tableName) ? $this->dbi->escapeString($tableName) : "" )
            . "')";
        $this->relation->queryAsControlUser($sqlQuery, false);
    }

    /**
     * Remove a hidden item of navigation tree from the
     * list of hidden items in PMA database.
     *
     * @param string $itemName  name of the navigation tree item
     * @param string $itemType  type of the navigation tree item
     * @param string $dbName    database name
     * @param string $tableName table name if applicable
     *
     * @return void
     */
    public function unhideNavigationItem(
        $itemName,
        $itemType,
        $dbName,
        $tableName = null
    ) {
        $navTable = Util::backquote($GLOBALS['cfgRelation']['db'])
            . "." . Util::backquote($GLOBALS['cfgRelation']['navigationhiding']);
        $sqlQuery = "DELETE FROM " . $navTable
            . " WHERE"
            . " `username`='"
            . $this->dbi->escapeString($GLOBALS['cfg']['Server']['user']) . "'"
            . " AND `item_name`='" . $this->dbi->escapeString($itemName) . "'"
            . " AND `item_type`='" . $this->dbi->escapeString($itemType) . "'"
            . " AND `db_name`='" . $this->dbi->escapeString($dbName) . "'"
            . (! empty($tableName)
                ? " AND `table_name`='" . $this->dbi->escapeString($tableName) . "'"
                : ""
            );
        $this->relation->queryAsControlUser($sqlQuery, false);
    }

    /**
     * Returns HTML for the dialog to show hidden navigation items.
     *
     * @param string $database database name
     * @param string $itemType type of the items to include
     * @param string $table    table name
     *
     * @return string HTML for the dialog to show hidden navigation items
     */
    public function getItemUnhideDialog($database, $itemType = null, $table = null)
    {
        $hidden = $this->getHiddenItems($database, $table);

        $typeMap = [
            'group' => __('Groups:'),
            'event' => __('Events:'),
            'function' => __('Functions:'),
            'procedure' => __('Procedures:'),
            'table' => __('Tables:'),
            'view' => __('Views:'),
        ];

        return $this->template->render('navigation/item_unhide_dialog', [
            'database' => $database,
            'table' => $table,
            'hidden' => $hidden,
            'types' => $typeMap,
            'item_type' => $itemType,
        ]);
    }

    /**
     * @param string      $database Database name
     * @param string|null $table    Table name
     * @return array
     */
    private function getHiddenItems(string $database, ?string $table): array
    {
        $navTable = Util::backquote($GLOBALS['cfgRelation']['db'])
            . "." . Util::backquote($GLOBALS['cfgRelation']['navigationhiding']);
        $sqlQuery = "SELECT `item_name`, `item_type` FROM " . $navTable
            . " WHERE `username`='"
            . $this->dbi->escapeString($GLOBALS['cfg']['Server']['user']) . "'"
            . " AND `db_name`='" . $this->dbi->escapeString($database) . "'"
            . " AND `table_name`='"
            . (! empty($table) ? $this->dbi->escapeString($table) : '') . "'";
        $result = $this->relation->queryAsControlUser($sqlQuery, false);

        $hidden = [];
        if ($result) {
            while ($row = $this->dbi->fetchArray($result)) {
                $type = $row['item_type'];
                if (! isset($hidden[$type])) {
                    $hidden[$type] = [];
                }
                $hidden[$type][] = $row['item_name'];
            }
        }
        $this->dbi->freeResult($result);
        return $hidden;
    }
}
