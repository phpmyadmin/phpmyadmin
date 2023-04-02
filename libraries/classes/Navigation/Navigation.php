<?php
/**
 * This class is responsible for instantiating
 * the various components of the navigation panel
 */

declare(strict_types=1);

namespace PhpMyAdmin\Navigation;

use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Dbal\Connection;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Sanitize;
use PhpMyAdmin\Server\Select;
use PhpMyAdmin\Template;
use PhpMyAdmin\Theme\Theme;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function count;
use function defined;
use function file_exists;
use function is_bool;
use function parse_url;
use function strpos;
use function trim;

use const PHP_URL_HOST;

/**
 * The navigation panel - displays server, db and table selection tree
 */
class Navigation
{
    private NavigationTree $tree;

    public function __construct(private Template $template, private Relation $relation, private DatabaseInterface $dbi)
    {
        $this->tree = new NavigationTree($this->template, $this->dbi);
    }

    /**
     * Renders the navigation tree, or part of it
     *
     * @return string The navigation tree
     */
    public function getDisplay(): string
    {
        $logo = [
            'is_displayed' => $GLOBALS['cfg']['NavigationDisplayLogo'],
            'has_link' => false,
            'link' => '#',
            'attributes' => ' target="_blank" rel="noopener noreferrer"',
            'source' => '',
        ];

        $response = ResponseRenderer::getInstance();
        if (! $response->isAjax()) {
            $logo['source'] = $this->getLogoSource();
            $logo['has_link'] = (string) $GLOBALS['cfg']['NavigationLogoLink'] !== '';
            $logo['link'] = trim((string) $GLOBALS['cfg']['NavigationLogoLink']);
            if (! Sanitize::checkLink($logo['link'], true)) {
                $logo['link'] = 'index.php';
            }

            if ($GLOBALS['cfg']['NavigationLogoLinkWindow'] === 'main') {
                if (empty(parse_url($logo['link'], PHP_URL_HOST))) {
                    $hasStartChar = strpos($logo['link'], '?');
                    $logo['link'] .= Url::getCommon(
                        [],
                        is_bool($hasStartChar) ? '?' : Url::getArgSeparator(),
                    );
                    // Internal link detected
                    $logo['attributes'] = '';
                } else {
                    // External links having a domain name should not be considered
                    // to be links that can use our internal ajax loading
                    $logo['attributes'] = ' class="disableAjax"';
                }
            }

            if ($GLOBALS['cfg']['NavigationDisplayServers'] && count($GLOBALS['cfg']['Servers']) > 1) {
                $serverSelect = Select::render(true, true);
            }

            if (! defined('PMA_DISABLE_NAVI_SETTINGS')) {
                $pageSettings = new PageSettings('Navi', 'pma_navigation_settings');
                $response->addHTML($pageSettings->getErrorHTML());
                $navigationSettings = $pageSettings->getHTML();
            }
        }

        if (! $response->isAjax() || ! empty($_POST['full']) || ! empty($_POST['reload'])) {
            if ($GLOBALS['cfg']['ShowDatabasesNavigationAsTree']) {
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
            'config_navigation_width' => $GLOBALS['cfg']['NavigationWidth'],
            'is_synced' => $GLOBALS['cfg']['NavigationLinkWithMainPanel'],
            'is_highlighted' => $GLOBALS['cfg']['NavigationTreePointerEnable'],
            'is_autoexpanded' => $GLOBALS['cfg']['NavigationTreeAutoexpandSingleDb'],
            'server' => $GLOBALS['server'],
            'auth_type' => $GLOBALS['cfg']['Server']['auth_type'],
            'is_servers_displayed' => $GLOBALS['cfg']['NavigationDisplayServers'],
            'servers' => $GLOBALS['cfg']['Servers'],
            'server_select' => $serverSelect ?? '',
            'navigation_tree' => $navRender,
            'is_navigation_settings_enabled' => ! defined('PMA_DISABLE_NAVI_SETTINGS'),
            'navigation_settings' => $navigationSettings ?? '',
            'is_drag_drop_import_enabled' => $GLOBALS['cfg']['enable_drag_drop_import'] === true,
            'is_mariadb' => $this->dbi->isMariaDB(),
        ]);
    }

    /**
     * Add an item of navigation tree to the hidden items list in PMA database.
     *
     * @param string $itemName name of the navigation tree item
     * @param string $itemType type of the navigation tree item
     * @param string $dbName   database name
     */
    public function hideNavigationItem(
        string $itemName,
        string $itemType,
        string $dbName,
    ): void {
        $navigationItemsHidingFeature = $this->relation->getRelationParameters()->navigationItemsHidingFeature;
        if ($navigationItemsHidingFeature === null) {
            return;
        }

        $navTable = Util::backquote($navigationItemsHidingFeature->database)
            . '.' . Util::backquote($navigationItemsHidingFeature->navigationHiding);
        $sqlQuery = 'INSERT INTO ' . $navTable
            . '(`username`, `item_name`, `item_type`, `db_name`, `table_name`)'
            . ' VALUES ('
            . $this->dbi->quoteString($GLOBALS['cfg']['Server']['user'], Connection::TYPE_CONTROL) . ','
            . $this->dbi->quoteString($itemName, Connection::TYPE_CONTROL) . ','
            . $this->dbi->quoteString($itemType, Connection::TYPE_CONTROL) . ','
            . $this->dbi->quoteString($dbName, Connection::TYPE_CONTROL) . ','
            . "'')";
        $this->dbi->tryQueryAsControlUser($sqlQuery);
    }

    /**
     * Remove a hidden item of navigation tree from the
     * list of hidden items in PMA database.
     *
     * @param string $itemName name of the navigation tree item
     * @param string $itemType type of the navigation tree item
     * @param string $dbName   database name
     */
    public function unhideNavigationItem(
        string $itemName,
        string $itemType,
        string $dbName,
    ): void {
        $navigationItemsHidingFeature = $this->relation->getRelationParameters()->navigationItemsHidingFeature;
        if ($navigationItemsHidingFeature === null) {
            return;
        }

        $navTable = Util::backquote($navigationItemsHidingFeature->database)
            . '.' . Util::backquote($navigationItemsHidingFeature->navigationHiding);
        $sqlQuery = 'DELETE FROM ' . $navTable
            . ' WHERE'
            . ' `username`='
            . $this->dbi->quoteString($GLOBALS['cfg']['Server']['user'], Connection::TYPE_CONTROL)
            . ' AND `item_name`=' . $this->dbi->quoteString($itemName, Connection::TYPE_CONTROL)
            . ' AND `item_type`=' . $this->dbi->quoteString($itemType, Connection::TYPE_CONTROL)
            . ' AND `db_name`=' . $this->dbi->quoteString($dbName, Connection::TYPE_CONTROL);
        $this->dbi->tryQueryAsControlUser($sqlQuery);
    }

    /**
     * Returns HTML for the dialog to show hidden navigation items.
     *
     * @param string $database database name
     *
     * @return string HTML for the dialog to show hidden navigation items
     */
    public function getItemUnhideDialog(string $database): string
    {
        $hidden = $this->getHiddenItems($database);

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
            'hidden' => $hidden,
            'types' => $typeMap,
        ]);
    }

    /** @return mixed[] */
    private function getHiddenItems(string $database): array
    {
        $navigationItemsHidingFeature = $this->relation->getRelationParameters()->navigationItemsHidingFeature;
        if ($navigationItemsHidingFeature === null) {
            return [];
        }

        $navTable = Util::backquote($navigationItemsHidingFeature->database)
            . '.' . Util::backquote($navigationItemsHidingFeature->navigationHiding);
        $sqlQuery = 'SELECT `item_name`, `item_type` FROM ' . $navTable
            . ' WHERE `username`='
            . $this->dbi->quoteString($GLOBALS['cfg']['Server']['user'], Connection::TYPE_CONTROL)
            . ' AND `db_name`=' . $this->dbi->quoteString($database, Connection::TYPE_CONTROL)
            . " AND `table_name`=''";
        $result = $this->dbi->tryQueryAsControlUser($sqlQuery);

        $hidden = [];
        if ($result) {
            foreach ($result as $row) {
                $type = $row['item_type'];
                if (! isset($hidden[$type])) {
                    $hidden[$type] = [];
                }

                $hidden[$type][] = $row['item_name'];
            }
        }

        return $hidden;
    }

    /** @return string Logo source */
    private function getLogoSource(): string
    {
        $GLOBALS['theme'] ??= null;

        if ($GLOBALS['theme'] instanceof Theme) {
            if (@file_exists($GLOBALS['theme']->getFsPath() . 'img/logo_left.png')) {
                return $GLOBALS['theme']->getPath() . '/img/logo_left.png';
            }

            if (@file_exists($GLOBALS['theme']->getFsPath() . 'img/pma_logo2.png')) {
                return $GLOBALS['theme']->getPath() . '/img/pma_logo2.png';
            }
        }

        return '';
    }
}
