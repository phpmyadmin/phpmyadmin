<?php
/**
 * This class is responsible for instantiating
 * the various components of the navigation panel
 */

declare(strict_types=1);

namespace PhpMyAdmin\Navigation;

use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;
use PhpMyAdmin\Sanitize;
use PhpMyAdmin\Server\Select;
use PhpMyAdmin\Template;
use PhpMyAdmin\Theme;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use const PHP_URL_HOST;
use function count;
use function defined;
use function file_exists;
use function is_bool;
use function parse_url;
use function strpos;
use function trim;

/**
 * The navigation panel - displays server, db and table selection tree
 */
class Navigation
{
    /** @var Template */
    private $template;

    /** @var Relation */
    private $relation;

    /** @var DatabaseInterface */
    private $dbi;

    /** @var NavigationTree */
    private $tree;

    /**
     * @param Template          $template Template instance
     * @param Relation          $relation Relation instance
     * @param DatabaseInterface $dbi      DatabaseInterface instance
     */
    public function __construct($template, $relation, $dbi)
    {
        $this->template = $template;
        $this->relation = $relation;
        $this->dbi = $dbi;
        $this->tree = new NavigationTree($this->template, $this->dbi);
    }

    /**
     * Renders the navigation tree, or part of it
     *
     * @return string The navigation tree
     */
    public function getDisplay(): string
    {
        global $cfg;

        $logo = [
            'is_displayed' => $cfg['NavigationDisplayLogo'],
            'has_link' => false,
            'link' => '#',
            'attributes' => ' target="_blank" rel="noopener noreferrer"',
            'source' => '',
        ];

        $response = Response::getInstance();
        if (! $response->isAjax()) {
            $logo['source'] = $this->getLogoSource();
            $logo['has_link'] = (string) $cfg['NavigationLogoLink'] !== '';
            $logo['link'] = trim((string) $cfg['NavigationLogoLink']);
            if (! Sanitize::checkLink($logo['link'], true)) {
                $logo['link'] = 'index.php';
            }
            if ($cfg['NavigationLogoLinkWindow'] === 'main') {
                if (empty(parse_url($logo['link'], PHP_URL_HOST))) {
                    $hasStartChar = strpos($logo['link'], '?');
                    $logo['link'] .= Url::getCommon(
                        [],
                        is_bool($hasStartChar) ? '?' : Url::getArgSeparator()
                    );
                    // Internal link detected
                    $logo['attributes'] = '';
                } else {
                    // External links having a domain name should not be considered
                    // to be links that can use our internal ajax loading
                    $logo['attributes'] = ' class="disableAjax"';
                }
            }

            if ($cfg['NavigationDisplayServers'] && count($cfg['Servers']) > 1) {
                $serverSelect = Select::render(true, true);
            }

            if (! defined('PMA_DISABLE_NAVI_SETTINGS')) {
                $pageSettings = new PageSettings('Navi', 'pma_navigation_settings');
                $response->addHTML($pageSettings->getErrorHTML());
                $navigationSettings = $pageSettings->getHTML();
            }
        }
        if (! $response->isAjax()
            || ! empty($_POST['full'])
            || ! empty($_POST['reload'])
        ) {
            if ($cfg['ShowDatabasesNavigationAsTree']) {
                // provide database tree in navigation
                $navRender = $this->tree->renderState();
            } else {
                // provide legacy pre-4.0 navigation
                $navRender = $this->tree->renderDbSelect();
            }
        } else {
            $navRender = $this->tree->renderPath();
        }

        return $this->template->render('navigation/main', [
            'is_ajax' => $response->isAjax(),
            'logo' => $logo,
            'config_navigation_width' => $cfg['NavigationWidth'],
            'is_synced' => $cfg['NavigationLinkWithMainPanel'],
            'is_highlighted' => $cfg['NavigationTreePointerEnable'],
            'is_autoexpanded' => $cfg['NavigationTreeAutoexpandSingleDb'],
            'server' => $GLOBALS['server'],
            'auth_type' => $cfg['Server']['auth_type'],
            'is_servers_displayed' => $cfg['NavigationDisplayServers'],
            'servers' => $cfg['Servers'],
            'server_select' => $serverSelect ?? '',
            'navigation_tree' => $navRender,
            'is_navigation_settings_enabled' => ! defined('PMA_DISABLE_NAVI_SETTINGS'),
            'navigation_settings' => $navigationSettings ?? '',
            'is_drag_drop_import_enabled' => $cfg['enable_drag_drop_import'] === true,
            'is_mariadb' => $this->dbi->isMariaDB(),
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
            . '.' . Util::backquote($GLOBALS['cfgRelation']['navigationhiding']);
        $sqlQuery = 'INSERT INTO ' . $navTable
            . '(`username`, `item_name`, `item_type`, `db_name`, `table_name`)'
            . ' VALUES ('
            . "'" . $this->dbi->escapeString($GLOBALS['cfg']['Server']['user']) . "',"
            . "'" . $this->dbi->escapeString($itemName) . "',"
            . "'" . $this->dbi->escapeString($itemType) . "',"
            . "'" . $this->dbi->escapeString($dbName) . "',"
            . "'" . (! empty($tableName) ? $this->dbi->escapeString($tableName) : '' )
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
            . '.' . Util::backquote($GLOBALS['cfgRelation']['navigationhiding']);
        $sqlQuery = 'DELETE FROM ' . $navTable
            . ' WHERE'
            . " `username`='"
            . $this->dbi->escapeString($GLOBALS['cfg']['Server']['user']) . "'"
            . " AND `item_name`='" . $this->dbi->escapeString($itemName) . "'"
            . " AND `item_type`='" . $this->dbi->escapeString($itemType) . "'"
            . " AND `db_name`='" . $this->dbi->escapeString($dbName) . "'"
            . (! empty($tableName)
                ? " AND `table_name`='" . $this->dbi->escapeString($tableName) . "'"
                : ''
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
     *
     * @return array
     */
    private function getHiddenItems(string $database, ?string $table): array
    {
        $navTable = Util::backquote($GLOBALS['cfgRelation']['db'])
            . '.' . Util::backquote($GLOBALS['cfgRelation']['navigationhiding']);
        $sqlQuery = 'SELECT `item_name`, `item_type` FROM ' . $navTable
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

    /**
     * @return string Logo source
     */
    private function getLogoSource(): string
    {
        /** @var Theme|null $PMA_Theme */
        global $PMA_Theme;
        if ($PMA_Theme !== null) {
            if (@file_exists($PMA_Theme->getFsPath() . 'img/logo_left.png')) {
                return $PMA_Theme->getPath() . '/img/logo_left.png';
            }

            if (@file_exists($PMA_Theme->getFsPath() . 'img/pma_logo2.png')) {
                return $PMA_Theme->getPath() . '/img/pma_logo2.png';
            }
        }

        return '';
    }
}
